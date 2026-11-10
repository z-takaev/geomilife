<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Cdek\CdekContactDto;
use App\DTO\Cdek\CdekCreateOrderRequestDto;
use App\DTO\Cdek\CdekOrderItemDto;
use App\DTO\Cdek\CdekOrderPackageDto;
use App\DTO\Cdek\CdekOrderResponseDto;
use App\DTO\Cdek\CdekPaymentDto;
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

final class OrderShipmentService
{
    public function __construct(private readonly CdekService $cdek) {}

    /**
     * Создаёт отправление СДЭК для оплаченного заказа, восстанавливая незавершённую заявку.
     */
    public function create(Order $order): void
    {
        $order->refresh();

        if (! $order->status->isReadyToShip() || ! $order->payment_status->isPaid()
            || ! $order->delivery_method->isCdek() || $order->cancellation_state !== null) {
            throw ValidationException::withMessages(['status' => __('Заявка СДЭК доступна только для оплаченного заказа, готового к отправке.')]);
        }

        $request = $this->buildRequest($order);
        $shipment = $this->claimCreation($order, $request);

        if ($shipment->uuid !== null) {
            return;
        }

        if (isset($shipment->payload['request_sent_at'])) {
            if (! $this->recoverShipment($order, $shipment) && $shipment->fresh()->operation_state === 'ABSENT') {
                $this->create($order);
            }

            return;
        }

        $claimedRows = OrderShipment::query()->whereKey($shipment->id)
            ->where('operation_state', 'CREATING')->whereNull('last_checked_at')
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
            if (! $this->recoverShipment($order, $shipment) && $shipment->fresh()->operation_state === 'ABSENT'
                && $order->cancellation_state === null && $order->status->isReadyToShip()) {
                CreateCdekShipmentJob::dispatch($order->id)->afterCommit();
            }

            return;
        }

        $remote = $this->cdek->getOrder($shipment->uuid);
        $this->applyShipmentState($order, $shipment, $remote);
    }

    /**
     * Запрашивает отмену отправления СДЭК и возвращает признак её подтверждения.
     */
    public function cancel(Order $order): bool
    {
        $shipment = $order->shipment()->first();

        if ($shipment === null || ($shipment->uuid === null && ! in_array($shipment->operation_state, ['CREATING', 'UNKNOWN', 'REGISTERING'], true))) {
            return true;
        }

        if ($shipment->operation_state === 'CANCELLED') {
            return true;
        }

        if ($shipment->uuid === null) {
            if (! $this->recoverShipment($order, $shipment)) {
                return $shipment->fresh()->operation_state === 'ABSENT';
            }

            $shipment->refresh();
        }

        try {
            $remote = $this->cdek->getOrder($shipment->uuid);
        } catch (CdekApiException $exception) {
            if ($exception->statusCode === 404 && $shipment->operation_state === 'CANCELLING') {
                $shipment->update(['operation_state' => 'CANCELLED', 'error' => null, 'last_checked_at' => now()]);

                return true;
            }
            throw $exception;
        }

        foreach ($remote->operation->requests as $request) {
            if ($request['type'] === 'DELETE' && $request['state']->isSuccessful()) {
                $shipment->update(['operation_state' => 'CANCELLED', 'error' => null, 'last_checked_at' => now()]);

                return true;
            }
        }

        if (in_array($remote->statusCode, ['REMOVED', 'DELETED', 'CANCELLED', 'CANCELED'], true)) {
            $shipment->update(['operation_state' => 'CANCELLED', 'error' => null, 'last_checked_at' => now()]);

            return true;
        }

        if ($remote->statusCode === null) {
            return false;
        }

        if ($remote->statusCode !== 'CREATED') {
            throw ValidationException::withMessages(['status' => __('В СДЭК началось движение груза. Отмена заказа недоступна.')]);
        }

        $sentAt = $shipment->payload['cancel_request_sent_at'] ?? null;

        if ($shipment->operation_state === 'CANCELLING' && $sentAt !== null
            && CarbonImmutable::parse($sentAt)->gt(now()->subMinutes((int) config('orders.reconciliation_stale_minutes')))) {
            return false;
        }

        $shipment->update([
            'operation_state' => 'CANCELLING',
            'last_checked_at' => now(),
            'payload' => [...($shipment->payload ?? []), 'cancel_request_sent_at' => now()->toIso8601String()],
        ]);
        $result = $this->cdek->cancelOrder($shipment->uuid);

        if ($result->errors !== []) {
            $shipment->update(['operation_state' => 'CANCEL_FAILED', 'error' => $result->errors[0]['message']]);
            throw new CdekApiException($result->errors[0]['message'], errors: $result->errors);
        }

        if ($result->state?->isSuccessful() ?? false) {
            $shipment->update(['operation_state' => 'CANCELLED', 'error' => null]);

            return true;
        }

        return false;
    }

    private function buildRequest(Order $order): CdekCreateOrderRequestDto
    {
        $phone = preg_replace('/\D/', '', (string) Setting::cached()?->phone);

        if ($phone !== null && strlen($phone) === 11 && in_array($phone[0], ['7', '8'], true)) {
            $phone = '+7'.substr($phone, 1);
        } else {
            throw ValidationException::withMessages(['delivery' => __('Заполните контактный телефон магазина для отправителя СДЭК.')]);
        }

        $items = $order->items()->get()->keyBy('product_variant_id');
        $packages = [];

        foreach ($order->packages ?? [] as $package) {
            $contents = [];

            foreach (collect($package['items'])->groupBy('product_variant_id') as $variantId => $packedItems) {
                $item = $packedItems->first();
                $snapshot = $items->get($variantId);

                if ($snapshot === null) {
                    throw ValidationException::withMessages(['delivery' => __('Не найден товар в снимке упаковки заказа.')]);
                }

                $quantity = (int) $packedItems->sum('quantity');

                while ($quantity > 0) {
                    $amount = min(999, $quantity);
                    $contents[] = new CdekOrderItemDto(
                        name: $snapshot->product_name,
                        wareKey: $snapshot->sku,
                        payment: new CdekPaymentDto(value: Money::fromMinor(0)),
                        weight: (int) $item['weight'],
                        amount: $amount,
                        cost: $snapshot->price,
                    );
                    $quantity -= $amount;
                }
            }

            $packages[] = new CdekOrderPackageDto(
                number: (string) $package['number'],
                weight: (int) $package['weight'],
                length: (int) ceil($package['length'] / 10),
                width: (int) ceil($package['width'] / 10),
                height: (int) ceil($package['height'] / 10),
                items: $contents,
            );
        }

        if ($packages === [] || empty($order->delivery_address['code'])) {
            throw ValidationException::withMessages(['delivery' => __('В заказе отсутствует упаковка или выбранный пункт СДЭК.')]);
        }

        return new CdekCreateOrderRequestDto(
            tariffCode: 136, number: $order->number,
            recipient: new CdekContactDto(name: $order->customer_name, phone: $order->customer_phone, email: $order->customer_email),
            packages: $packages, shipmentPoint: (string) config('orders.cdek.shipment_point'),
            deliveryPoint: $order->delivery_address['code'],
            sender: new CdekContactDto(name: (string) config('orders.cdek.sender_name'), phone: $phone, company: (string) config('orders.cdek.sender_company')),
            comment: $order->comment,
        );
    }

    private function recoverShipment(Order $order, OrderShipment $shipment): bool
    {
        try {
            $remote = $this->cdek->findOrder($order->number);
        } catch (CdekApiException $exception) {
            if ($exception->statusCode === 404) {
                $sentAt = $shipment->payload['request_sent_at'] ?? $shipment->last_checked_at?->toIso8601String() ?? $shipment->created_at?->toIso8601String();

                if ($sentAt !== null && CarbonImmutable::parse($sentAt)->lte(now()->subMinutes((int) config('orders.reconciliation_stale_minutes')))) {
                    $shipment->update(['operation_state' => 'ABSENT', 'last_checked_at' => now(), 'error' => null]);
                }

                return false;
            }
            throw $exception;
        }

        if ($remote->uuid === null) {
            return false;
        }

        $shipment->update(['uuid' => $remote->uuid]);
        $this->applyShipmentState($order, $shipment, $remote);

        return true;
    }

    private function applyShipmentState(Order $order, OrderShipment $shipment, CdekOrderResponseDto $remote): void
    {
        DB::transaction(function () use ($order, $shipment, $remote): void {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $shipment = OrderShipment::query()
                ->lockForUpdate()
                ->findOrFail($shipment->id);

            if ($remote->statusDateTime !== null && $shipment->status_at !== null && $remote->statusDateTime < $shipment->status_at) {
                return;
            }

            $shipment->update($this->shipmentChanges($shipment, $remote));

            $status = match ($remote->statusCode) {
                'RECEIVED_AT_SHIPMENT_WAREHOUSE', 'ACCEPTED_AT_PICK_UP_POINT', 'READY_FOR_DELIVERY', 'TAKEN_BY_COURIER',
                'ACCEPTED_AT_TRANSIT_WAREHOUSE', 'ACCEPTED_AT_DELIVERY_WAREHOUSE', 'SENT_TO_TRANSIT_CITY', 'SENT_TO_DELIVERY_CITY' => OrderStatusEnum::SHIPPED,
                'DELIVERED' => OrderStatusEnum::DELIVERED,
                default => null,
            };

            if ($status !== null) {
                app(OrderLifecycleService::class)->applyShipmentStatus($order, $status);
            }

            $order->refresh();

            if ($this->canPrepareDocuments($order, $shipment, $remote)) {
                PrepareCdekDocumentsJob::dispatch($order->id)->afterCommit();
            }
        });
    }

    private function notifyFailure(Order $order, string $message): void
    {
        app(OrderNotificationService::class)->send($order, 'SHIPPING_FAILED', 'shipping-failed:'.hash('sha256', $message));
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

            if ($shipment->uuid !== null || in_array($shipment->operation_state, ['CREATING', 'UNKNOWN', 'REGISTERING'], true)) {
                return $shipment;
            }

            $shipment->update(['operation_state' => 'CREATING', 'error' => null, 'last_checked_at' => null, 'payload' => ['request' => $request->toArray()]]);

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
                'request_state' => $result->state?->value,
                'operation_state' => $result->errors === [] ? 'REGISTERING' : 'FAILED',
                'error' => $result->errors === [] ? null : $result->errors[0]['message'],
            ]);

            if ($result->errors !== []) {
                throw ValidationException::withMessages(['delivery' => $result->errors[0]['message']]);
            }
            SyncCdekShipmentJob::dispatch($order->id)->delay(now()->addSeconds(5))->afterCommit();
        } catch (Throwable $exception) {
            if ($shipment->uuid === null) {
                $state = $exception instanceof CdekApiException && $exception->statusCode !== null && $exception->statusCode < 500 ? 'FAILED' : 'UNKNOWN';
                $shipment->update(['operation_state' => $state, 'error' => $exception->getMessage()]);
            }

            $this->notifyFailure($order, $exception->getMessage());
            throw $exception;
        }
    }

    private function shipmentChanges(OrderShipment $shipment, CdekOrderResponseDto $remote): array
    {
        return [
            'uuid' => $remote->uuid ?? $shipment->uuid,
            'cdek_number' => $remote->cdekNumber ?? $shipment->cdek_number,
            'status' => $remote->statusCode ?? $shipment->status,
            'status_at' => $remote->statusDateTime ?? $shipment->status_at,
            'request_state' => $remote->operation->state?->value,
            'operation_state' => in_array($shipment->operation_state, ['CANCELLING', 'CANCELLED', 'CANCEL_FAILED'], true)
                ? $shipment->operation_state
                : ($remote->operation->errors !== [] ? 'FAILED'
                    : (($remote->operation->state?->isSuccessful() ?? false) ? 'REGISTERED' : 'REGISTERING')),
            'error' => $remote->operation->errors[0]['message'] ?? null,
            'last_checked_at' => now(),
        ];
    }

    private function canPrepareDocuments(Order $order, OrderShipment $shipment, CdekOrderResponseDto $remote): bool
    {
        return ($remote->operation->state?->isSuccessful() ?? false) && $remote->operation->errors === []
                        && ! $order->status->isCancelled() && $order->cancellation_state === null
                        && ! in_array($shipment->operation_state, ['CANCELLING', 'CANCELLED', 'CANCEL_FAILED'], true);
    }
}
