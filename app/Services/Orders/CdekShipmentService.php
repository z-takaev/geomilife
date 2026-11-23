<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Actions\Orders\UpdateOrderStatusFromShipmentAction;
use App\DTO\Cdek\CdekContactDto;
use App\DTO\Cdek\CdekCreateOrderRequestDto;
use App\DTO\Cdek\CdekOrderItemDto;
use App\DTO\Cdek\CdekOrderPackageDto;
use App\DTO\Cdek\CdekOrderResponseDto;
use App\DTO\Cdek\CdekPaymentDto;
use App\DTO\Orders\PackedItemDto;
use App\DTO\Orders\PackedPackageDto;
use App\Enums\Cdek\CdekOrderStatusEnum;
use App\Enums\Cdek\CdekPrintStateEnum;
use App\Enums\Cdek\CdekShipmentOperationStateEnum;
use App\Enums\Orders\OrderDocumentTypeEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Jobs\Orders\PrepareCdekDocumentsJob;
use App\Jobs\Orders\SyncCdekShipmentJob;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\Setting;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Support\Values\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CdekShipmentService
{
    public function __construct(
        private readonly CdekService $cdek,
        private readonly UpdateOrderStatusFromShipmentAction $updateStatusFromShipment,
        private readonly OrderNotificationService $notifications,
    ) {}

    /**
     * Создаёт отправление СДЭК для оплаченного заказа, восстанавливая незавершённую заявку.
     */
    public function create(Order $order): void
    {
        $order->refresh();

        if (
            ! $order->status->isReadyToShip()
            || ! $order->payment_status->isPaid()
            || ! $order->delivery_method->isCdek()
            || $order->cancellation_state !== null
        ) {
            throw ValidationException::withMessages(['status' => __('Заявка СДЭК доступна только для оплаченного заказа, готового к отправке.')]);
        }

        $request = $this->buildRequest($order);
        $shipment = $this->claimCreation($order, $request);

        if ($shipment->uuid !== null) {
            return;
        }

        if (isset($shipment->payload['request_sent_at'])) {
            if (! $this->recoverShipment($order, $shipment)
                && $shipment->fresh()->operation_state?->is(CdekShipmentOperationStateEnum::ABSENT)) {
                $this->create($order);
            }

            return;
        }

        $claimedRows = OrderShipment::query()
            ->whereKey($shipment->id)
            ->where('operation_state', CdekShipmentOperationStateEnum::CREATING->value)
            ->whereNull('last_checked_at')
            ->update(['last_checked_at' => now()]);

        if (! $claimedRows) {
            $this->recoverShipment($order, $shipment);

            return;
        }

        $this->sendCreation($order, $shipment, $request);
    }

    /**
     * Сверяет отправление со СДЭК и обновляет его состояние и статус заказа.
     */
    public function sync(Order $order): void
    {
        $shipment = $order->shipment()->first();

        if ($shipment === null) {
            return;
        }

        if ($shipment->uuid === null) {
            $this->recoverMissingShipment($order, $shipment);

            return;
        }

        $cdekOrder = $this->cdek->getOrderByUuid($shipment->uuid);

        $this->applyShipmentState($order, $shipment, $cdekOrder);
    }

    /**
     * Запрашивает отмену отправления СДЭК и возвращает признак её подтверждения.
     */
    public function cancel(Order $order): bool
    {
        $shipment = $order->shipment()->first();

        if ($shipment === null || ! $this->requiresCancellation($shipment)) {
            return true;
        }

        if ($shipment->operation_state?->is(CdekShipmentOperationStateEnum::CANCELLED)) {
            return true;
        }

        if ($shipment->uuid === null) {
            if (! $this->recoverShipment($order, $shipment)) {
                return $shipment->fresh()->operation_state?->is(CdekShipmentOperationStateEnum::ABSENT) ?? false;
            }

            $shipment->refresh();
        }

        try {
            $cdekOrder = $this->cdek->getOrderByUuid($shipment->uuid);
        } catch (CdekApiException $exception) {
            if ($this->isMissingAfterCancellationRequest($shipment, $exception)) {
                $this->markCancelled($shipment);

                return true;
            }

            throw $exception;
        }

        if ($this->isCancellationConfirmed($cdekOrder)) {
            $this->markCancelled($shipment);

            return true;
        }

        if ($cdekOrder->statusCode === null) {
            return false;
        }

        if (! CdekOrderStatusEnum::tryFrom($cdekOrder->statusCode)?->is(CdekOrderStatusEnum::CREATED)) {
            throw ValidationException::withMessages(['status' => __('В СДЭК началось движение груза. Отмена заказа недоступна.')]);
        }

        if ($this->hasRecentCancellationRequest($shipment)) {
            return false;
        }

        return $this->requestCancellation($shipment);
    }

    private function requestCancellation(OrderShipment $shipment): bool
    {
        $shipment->update([
            'operation_state' => CdekShipmentOperationStateEnum::CANCELLING,
            'last_checked_at' => now(),
            'payload' => [...($shipment->payload ?? []), 'cancel_request_sent_at' => now()->toIso8601String()],
        ]);

        $result = $this->cdek->cancelOrder($shipment->uuid);

        if ($result->errors !== []) {
            $shipment->update(['operation_state' => CdekShipmentOperationStateEnum::CANCEL_FAILED, 'error' => $result->errors[0]['message']]);

            throw new CdekApiException($result->errors[0]['message'], errors: $result->errors);
        }

        if ($result->state?->isSuccessful() ?? false) {
            $this->markCancelled($shipment);

            return true;
        }

        return false;
    }

    private function buildRequest(Order $order): CdekCreateOrderRequestDto
    {
        $packages = $this->buildPackages($order);

        if ($packages === [] || empty($order->delivery_address['code'])) {
            throw ValidationException::withMessages(['delivery' => __('В заказе отсутствует упаковка или выбранный пункт СДЭК.')]);
        }

        return new CdekCreateOrderRequestDto(
            tariffCode: (int) config('orders.cdek.tariff_code'),
            number: $order->externalNumber(),
            recipient: new CdekContactDto(name: $order->customer_name, phone: $order->customer_phone, email: $order->customer_email),
            packages: $packages,
            shipmentPoint: (string) config('orders.cdek.shipment_point'),
            deliveryPoint: $order->delivery_address['code'],
            sender: new CdekContactDto(
                name: (string) config('orders.cdek.sender_name'),
                phone: $this->senderPhone(),
                company: (string) config('orders.cdek.sender_company'),
            ),
        );
    }

    private function buildPackages(Order $order): array
    {
        $items = $order->items()->get()->keyBy('product_variant_id');
        $packages = [];

        foreach ($order->packages ?? [] as $packageData) {
            $package = PackedPackageDto::fromArray($packageData);
            $contents = [];

            foreach (collect($package->items)->groupBy(static fn (PackedItemDto $item): int => $item->productVariantId) as $variantId => $packedItems) {
                $item = $packedItems->first();
                $snapshot = $items->get($variantId);

                if ($snapshot === null) {
                    throw ValidationException::withMessages([
                        'delivery' => __('Не найден товар в снимке упаковки заказа.'),
                    ]);
                }

                $quantity = (int) $packedItems->sum('quantity');

                while ($quantity > 0) {
                    $amount = min(999, $quantity);
                    $contents[] = new CdekOrderItemDto(
                        name: $snapshot->product_name,
                        wareKey: $snapshot->sku,
                        payment: new CdekPaymentDto(value: Money::fromMinor(0)),
                        weight: $item->weight,
                        amount: $amount,
                        cost: $snapshot->price,
                    );
                    $quantity -= $amount;
                }
            }

            $packages[] = new CdekOrderPackageDto(
                number: $package->number,
                weight: $package->weight,
                length: (int) ceil($package->length / 10),
                width: (int) ceil($package->width / 10),
                height: (int) ceil($package->height / 10),
                items: $contents,
            );
        }

        return $packages;
    }

    private function senderPhone(): string
    {
        $phone = preg_replace('/\D/', '', (string) Setting::cached()?->phone);

        if ($phone === null || strlen($phone) !== 11 || ! in_array($phone[0], ['7', '8'], true)) {
            throw ValidationException::withMessages([
                'delivery' => __('Заполните контактный телефон магазина для отправителя СДЭК.'),
            ]);
        }

        return '+7'.substr($phone, 1);
    }

    private function recoverMissingShipment(Order $order, OrderShipment $shipment): void
    {
        if (! $this->recoverShipment($order, $shipment)
            && $shipment->fresh()->operation_state?->is(CdekShipmentOperationStateEnum::ABSENT)
            && $order->cancellation_state === null
            && $order->status->isReadyToShip()) {
            CreateCdekShipmentJob::dispatch($order->id)->afterCommit();
        }
    }

    private function requiresCancellation(OrderShipment $shipment): bool
    {
        return $shipment->uuid !== null || in_array($shipment->operation_state, [
            CdekShipmentOperationStateEnum::CREATING,
            CdekShipmentOperationStateEnum::UNKNOWN,
            CdekShipmentOperationStateEnum::REGISTERING,
        ], true);
    }

    private function isMissingAfterCancellationRequest(
        OrderShipment $shipment,
        CdekApiException $exception,
    ): bool {
        return $shipment->operation_state?->is(CdekShipmentOperationStateEnum::CANCELLING)
            && ($exception->statusCode === 404
                || ($exception->statusCode === 400 && $exception->hasErrorCode('v2_entity_not_found')));
    }

    private function isCancellationConfirmed(CdekOrderResponseDto $order): bool
    {
        foreach ($order->operation->requests as $request) {
            if ($request->type === 'DELETE' && $request->state->isSuccessful()) {
                return true;
            }
        }

        return in_array(CdekOrderStatusEnum::tryFrom($order->statusCode ?? ''), [
            CdekOrderStatusEnum::REMOVED,
            CdekOrderStatusEnum::DELETED,
            CdekOrderStatusEnum::CANCELLED,
            CdekOrderStatusEnum::CANCELED,
        ], true);
    }

    private function hasRecentCancellationRequest(OrderShipment $shipment): bool
    {
        $sentAt = $shipment->payload['cancel_request_sent_at'] ?? null;

        return $shipment->operation_state?->is(CdekShipmentOperationStateEnum::CANCELLING)
            && $sentAt !== null
            && CarbonImmutable::parse($sentAt)->gt(
                now()->subMinutes((int) config('orders.reconciliation_stale_minutes')),
            );
    }

    private function markCancelled(OrderShipment $shipment): void
    {
        $shipment->update([
            'operation_state' => CdekShipmentOperationStateEnum::CANCELLED,
            'error' => null,
            'last_checked_at' => now(),
        ]);
    }

    private function recoverShipment(Order $order, OrderShipment $shipment): bool
    {
        try {
            $cdekOrder = $this->cdek->findOrderByNumber($this->shipmentOrderNumber($order, $shipment));
        } catch (CdekApiException $exception) {
            if ($exception->statusCode === 404) {
                $sentAt = $shipment->payload['request_sent_at'] ?? $shipment->last_checked_at?->toIso8601String() ?? $shipment->created_at?->toIso8601String();

                if ($sentAt !== null && CarbonImmutable::parse($sentAt)->lte(now()->subMinutes((int) config('orders.reconciliation_stale_minutes')))) {
                    $shipment->update(['operation_state' => CdekShipmentOperationStateEnum::ABSENT, 'last_checked_at' => now(), 'error' => null]);
                }

                return false;
            }
            throw $exception;
        }

        if ($cdekOrder->uuid === null) {
            return false;
        }

        $shipment->update(['uuid' => $cdekOrder->uuid]);
        $this->applyShipmentState($order, $shipment, $cdekOrder);

        return true;
    }

    private function applyShipmentState(Order $order, OrderShipment $shipment, CdekOrderResponseDto $cdekOrder): void
    {
        DB::transaction(function () use ($order, $shipment, $cdekOrder): void {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $shipment = OrderShipment::query()
                ->lockForUpdate()
                ->findOrFail($shipment->id);

            if ($cdekOrder->statusDateTime !== null && $shipment->status_at !== null && $cdekOrder->statusDateTime < $shipment->status_at) {
                return;
            }

            $shipment->update($this->shipmentChanges($shipment, $cdekOrder));

            $carrierStatus = CdekOrderStatusEnum::tryFrom($cdekOrder->statusCode ?? '');
            $status = match (true) {
                $carrierStatus?->indicatesShipped() => OrderStatusEnum::SHIPPED,
                $carrierStatus?->isDelivered() => OrderStatusEnum::DELIVERED,
                default => null,
            };

            if ($status !== null && filled($shipment->cdek_number)) {
                $this->updateStatusFromShipment->run($order, $status);
            }

            $order->refresh();

            if ($this->canPrepareDocuments($order, $shipment, $cdekOrder)) {
                PrepareCdekDocumentsJob::dispatch($order->id)->afterCommit();
            }
        });
    }

    private function notifyFailure(Order $order, string $message): void
    {
        $this->notifications->queue($order, 'SHIPPING_FAILED', 'shipping-failed:'.hash('sha256', $message));
    }

    private function claimCreation(Order $order, CdekCreateOrderRequestDto $request): OrderShipment
    {
        return DB::transaction(function () use ($order, $request): OrderShipment {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->cancellation_state !== null || ! $lockedOrder->status->isReadyToShip()) {
                throw ValidationException::withMessages(['status' => __('Операция недоступна для текущего состояния заказа.')]);
            }

            $shipment = $lockedOrder->shipment()->firstOrCreate([]);

            if ($shipment->uuid !== null || in_array($shipment->operation_state, [
                CdekShipmentOperationStateEnum::CREATING,
                CdekShipmentOperationStateEnum::UNKNOWN,
                CdekShipmentOperationStateEnum::REGISTERING,
            ], true)) {
                return $shipment;
            }

            $shipment->update(['operation_state' => CdekShipmentOperationStateEnum::CREATING, 'error' => null, 'last_checked_at' => null, 'payload' => ['request' => $request->toArray()]]);

            return $shipment;
        });
    }

    private function sendCreation(Order $order, OrderShipment $shipment, CdekCreateOrderRequestDto $request): void
    {
        $shipment->refresh();
        $shipment->update(['payload' => [...($shipment->payload ?? []), 'request_sent_at' => now()->toIso8601String()]]);

        try {
            $result = $this->cdek->createOrder($request);

            $shipment->update([
                'uuid' => $result->uuid,
                'request_state' => $result->state,
                'operation_state' => $result->errors === [] ? CdekShipmentOperationStateEnum::REGISTERING : CdekShipmentOperationStateEnum::FAILED,
                'error' => $result->errors === [] ? null : $result->errors[0]['message'],
            ]);

            if ($result->errors !== []) {
                throw ValidationException::withMessages(['delivery' => $result->errors[0]['message']]);
            }

            SyncCdekShipmentJob::dispatch($order->id)
                ->delay(now()->addSeconds(5))
                ->afterCommit();
        } catch (Throwable $exception) {
            if ($shipment->uuid === null) {
                $state = $exception instanceof CdekApiException && $exception->statusCode !== null && $exception->statusCode < 500
                    ? CdekShipmentOperationStateEnum::FAILED
                    : CdekShipmentOperationStateEnum::UNKNOWN;

                $shipment->update(['operation_state' => $state, 'error' => $exception->getMessage()]);
            }

            $this->notifyFailure($order, $exception->getMessage());
            throw $exception;
        }
    }

    private function shipmentChanges(OrderShipment $shipment, CdekOrderResponseDto $cdekOrder): array
    {
        return [
            'uuid' => $cdekOrder->uuid ?? $shipment->uuid,
            'cdek_number' => $cdekOrder->cdekNumber ?? $shipment->cdek_number,
            'status' => $cdekOrder->statusCode ?? $shipment->status,
            'status_at' => $cdekOrder->statusDateTime ?? $shipment->status_at,
            'request_state' => $cdekOrder->operation->state,
            'operation_state' => in_array($shipment->operation_state, [
                CdekShipmentOperationStateEnum::CANCELLING,
                CdekShipmentOperationStateEnum::CANCELLED,
                CdekShipmentOperationStateEnum::CANCEL_FAILED,
            ], true)
            ? $shipment->operation_state
            : ($cdekOrder->operation->errors !== [] ? CdekShipmentOperationStateEnum::FAILED
                : (($cdekOrder->operation->state?->isSuccessful() ?? false)
                    ? CdekShipmentOperationStateEnum::REGISTERED
                    : CdekShipmentOperationStateEnum::REGISTERING)),
            'error' => $cdekOrder->operation->errors[0]['message'] ?? null,
            'last_checked_at' => now(),
        ];
    }

    private function canPrepareDocuments(Order $order, OrderShipment $shipment, CdekOrderResponseDto $cdekOrder): bool
    {
        if (
            ! ($cdekOrder->operation->state?->isSuccessful() ?? false)
            || $cdekOrder->operation->errors !== []
            || $order->status->isCancelled()
            || $order->cancellation_state !== null
            || in_array($shipment->operation_state, [
                CdekShipmentOperationStateEnum::CANCELLING,
                CdekShipmentOperationStateEnum::CANCELLED,
                CdekShipmentOperationStateEnum::CANCEL_FAILED], true)
        ) {
            return false;
        }

        $states = $order->documents()
            ->whereIn('type', array_map(static fn (OrderDocumentTypeEnum $type): string => $type->value, OrderDocumentTypeEnum::cases()))
            ->pluck('state', 'type');

        $pendingStates = [
            CdekPrintStateEnum::PENDING->value,
            CdekPrintStateEnum::CREATING->value,
            CdekPrintStateEnum::ACCEPTED->value,
            CdekPrintStateEnum::PROCESSING->value,
            CdekPrintStateEnum::REMOVED->value,
        ];

        foreach (OrderDocumentTypeEnum::cases() as $type) {
            $state = $states->get($type->value);

            if ($state === null || in_array($state, $pendingStates, true)) {
                return true;
            }
        }

        return false;
    }

    private function shipmentOrderNumber(Order $order, OrderShipment $shipment): string
    {
        $storedNumber = $shipment->payload['request']['number'] ?? null;

        if (is_string($storedNumber) && $storedNumber !== '') {
            return $storedNumber;
        }

        if (is_int($storedNumber)) {
            return (string) $storedNumber;
        }

        return $order->externalNumber();
    }
}
