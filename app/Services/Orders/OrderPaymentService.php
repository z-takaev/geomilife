<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Tbank\TbankCancelPaymentRequestDto;
use App\DTO\Tbank\TbankInitPaymentRequestDto;
use App\DTO\Tbank\TbankPaymentNotificationDto;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Tbank\Exceptions\TbankApiException;
use App\Services\Tbank\TbankService;
use App\Support\Traits\Orders\ValidatesOrderManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderPaymentService
{
    use ValidatesOrderManagement;

    public function __construct(private readonly TbankService $bank) {}

    public function start(Order $order): Payment
    {
        $payment = $this->claimPaymentAttempt($order);

        $this->initializePayment($order, $payment);

        if ($order->fresh()->status->isCancelled()) {
            $this->refund($order);
            $payment->update(['payment_url' => null]);
        }

        return $payment->fresh();
    }

    /**
     * Сверяет платёж с Т-Банком и при необходимости возвращает оплату отменённого заказа.
     */
    public function sync(Payment $payment, bool $refundIfCancelled = true): void
    {
        $payment->refresh();

        if ($payment->transaction_id === null) {
            $this->recoverInitialization($payment);
        } else {
            $result = $this->bank->getPaymentState($payment->transaction_id);

            if ($result->orderId !== $payment->bank_order_id || ! $result->amount->equals($payment->amount)) {
                throw TbankApiException::invalidResponse();
            }

            $this->applyBankState($payment, $result->status, true);
        }

        $order = $payment->order()->useWritePdo()->firstOrFail();

        if ($refundIfCancelled && $order->status->isCancelled()
            && in_array($payment->fresh()->status, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORIZED], true)) {
            $this->refund($order);
        }
    }

    /**
     * Проверяет соответствие уведомления платежу и обновляет состояние оплаты заказа.
     */
    public function handleBankNotification(TbankPaymentNotificationDto $notification): void
    {
        $payment = Payment::query()->where('provider', 'TBANK')
            ->where('transaction_id', $notification->paymentId)->first();

        if ($payment === null) {
            $payment = Payment::query()->where('provider', 'TBANK')
                ->where('bank_order_id', $notification->orderId)->first();
        }

        if ($payment === null || $payment->bank_order_id !== $notification->orderId
            || ($payment->transaction_id !== null && $payment->transaction_id !== $notification->paymentId)
            || ! $payment->amount->equals($notification->amount)) {
            throw ValidationException::withMessages(['notification' => 'Платёж не соответствует заказу.']);
        }

        if (in_array($notification->status, [TbankPaymentStatusEnum::CONFIRMED, TbankPaymentStatusEnum::AUTHORIZED,
            TbankPaymentStatusEnum::REFUNDED, TbankPaymentStatusEnum::REVERSED], true)
            && (! $notification->success || $notification->errorCode !== '0')) {
            throw ValidationException::withMessages(['notification' => 'Не подтверждена успешная оплата.']);
        }

        if ($payment->transaction_id === null) {
            $payment->update(['transaction_id' => $notification->paymentId]);
        }

        $this->applyBankState($payment, $notification->status);
        $order = $payment->order()->useWritePdo()->firstOrFail();

        if ($order->status->isCancelled()
            && in_array($payment->fresh()->status, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORIZED], true)) {
            $this->refund($order);
        }
    }

    /**
     * Запускает отмену платежей или возврат оплаты отменённого онлайн-заказа.
     */
    public function refund(Order $order): void
    {
        $order->refresh();

        if (! $order->status->isCancelled() || ! $order->payment_method->isOnline()) {
            return;
        }

        foreach ($order->payments()->where('provider', 'TBANK')->orderBy('id')->get() as $payment) {
            $this->refundPayment($order, $payment);
        }
    }

    /**
     * Фиксирует оплату при самовывозе с проверкой прав сотрудника.
     */
    public function markPaidOnReceipt(Order $order, FilamentUser $actor): void
    {
        $this->assertStaff($actor);
        DB::transaction(function () use ($order, $actor): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $this->assertNotCancelling($lockedOrder);

            if (! $lockedOrder->delivery_method->isPickup() || ! $lockedOrder->payment_method->isPickup()
                || in_array($lockedOrder->status, [OrderStatusEnum::CANCELLED, OrderStatusEnum::DELIVERED], true)) {
                throw ValidationException::withMessages(['payment' => 'Ручная отметка оплаты недоступна.']);
            }

            if ($lockedOrder->payment_status->isPaid()) {
                return;
            }

            $lockedOrder->update(['payment_status' => PaymentStatusEnum::PAID]);

            $lockedOrder->history()->firstOrCreate(['event_key' => 'payment-on-receipt'], [
                'from_status' => $lockedOrder->status->value,
                'to_status' => $lockedOrder->status->value,
                'source' => 'STAFF',
                'actor_type' => $actor::class,
                'actor_id' => $actor->id,
                'metadata' => ['payment_status' => PaymentStatusEnum::PAID->value],
                'created_at' => now(),
            ]);
            $this->notify($lockedOrder, 'PAYMENT_PAID', 'payment-paid:'.$lockedOrder->id);
        });
    }

    private function recoverInitialization(Payment $payment): void
    {
        $result = $this->bank->checkOrder($payment->bank_order_id);

        if (count($result->payments) > 1) {
            throw TbankApiException::invalidResponse();
        }

        if ($result->payments === []) {
            $payment->update([
                'status' => PaymentStatusEnum::FAILED,
                'operation_state' => null,
                'last_checked_at' => now(),
                'error' => null,
            ]);

            return;
        }

        $bankPayment = $result->payments[0];

        if (! $bankPayment->amount->equals($payment->amount)) {
            throw TbankApiException::invalidResponse();
        }

        $payment->update(['transaction_id' => $bankPayment->paymentId, 'operation_state' => null, 'error' => null]);
        $this->applyBankState($payment, $bankPayment->status, true);
    }

    private function applyBankState(Payment $payment, TbankPaymentStatusEnum $providerStatus, bool $fromBankCheck = false): void
    {
        DB::transaction(function () use ($payment, $providerStatus, $fromBankCheck): void {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $nextStatus = $this->mapPaymentStatus($providerStatus);
            $currentStatus = $lockedPayment->status;

            if ($this->isRefundUnconfirmed($lockedPayment, $nextStatus, $fromBankCheck)) {
                $lockedPayment->update(['status' => PaymentStatusEnum::REFUND_FAILED, 'operation_state' => null, 'last_checked_at' => now()]);

                return;
            }

            if ($this->shouldIgnoreProviderState($lockedPayment, $nextStatus, $providerStatus)) {
                $lockedPayment->update(['last_checked_at' => now()]);

                return;
            }

            $values = $this->paymentStateChanges($providerStatus, $nextStatus);

            $lockedPayment->update($values);

            if ($nextStatus !== null && ! $nextStatus->is($currentStatus)) {
                $this->recordPaymentChange($order, $lockedPayment, $currentStatus, $nextStatus);
            }

            $this->refreshOrderPayment($order);
        });
    }

    private function isRefundUnconfirmed(Payment $payment, ?PaymentStatusEnum $nextStatus, bool $fromBankCheck): bool
    {
        return $fromBankCheck && ($nextStatus?->isPaid() ?? false) && $this->isAwaitingRefund($payment)
            && $payment->refund_requested_at?->lte(now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
    }

    private function isAwaitingRefund(Payment $payment): bool
    {
        return in_array($payment->status, [PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED], true);
    }

    private function shouldIgnoreProviderState(Payment $payment, ?PaymentStatusEnum $nextStatus, TbankPaymentStatusEnum $providerStatus): bool
    {
        if (in_array($payment->provider_status, ['REFUNDED', 'REVERSED'], true)
            || ($this->isAwaitingRefund($payment) && ($nextStatus?->isPaid() ?? false))) {
            return true;
        }

        $uncapturedCancellation = ($nextStatus?->isCancelled() ?? false)
            && ! in_array($payment->provider_status, ['CONFIRMED', 'REFUNDING', 'ASYNC_REFUNDING', 'PARTIAL_REFUNDED'], true);

        return ($payment->status->isPaid() || $this->isAwaitingRefund($payment))
            && ! in_array($nextStatus, [PaymentStatusEnum::PAID, PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUNDED], true)
            && ! $providerStatus->isReversed()
            && ! $uncapturedCancellation;
    }

    private function refreshOrderPayment(Order $order): void
    {
        $statuses = $order->payments()->distinct()->pluck('status');
        $previousStatus = $order->fresh()->payment_status;
        $nextStatus = PaymentStatusEnum::UNPAID;

        foreach ([PaymentStatusEnum::REFUND_FAILED, PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::PAID,
            PaymentStatusEnum::AUTHORIZED, PaymentStatusEnum::PENDING, PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::CANCELLED, PaymentStatusEnum::FAILED] as $candidate) {
            if ($statuses->contains($candidate)) {
                $nextStatus = $candidate;
                break;
            }
        }

        $order->update(['payment_status' => $nextStatus]);

        if ($nextStatus->isPaid() && ! $previousStatus->is($nextStatus)) {
            $this->notify($order, 'PAYMENT_PAID', 'payment-paid:'.$order->id);
        }

        if ($nextStatus->isRefunded() && ! $previousStatus->is($nextStatus)) {
            $this->notify($order, 'REFUNDED', 'payment-refunded:'.$order->id);
        }
    }

    private function mapPaymentStatus(TbankPaymentStatusEnum $status): ?PaymentStatusEnum
    {
        return match ($status) {
            TbankPaymentStatusEnum::CONFIRMED => PaymentStatusEnum::PAID,
            TbankPaymentStatusEnum::AUTHORIZED => PaymentStatusEnum::AUTHORIZED,
            TbankPaymentStatusEnum::REFUNDED => PaymentStatusEnum::REFUNDED,
            TbankPaymentStatusEnum::REFUNDING, TbankPaymentStatusEnum::ASYNC_REFUNDING,
            TbankPaymentStatusEnum::REVERSING => PaymentStatusEnum::REFUND_PENDING,
            TbankPaymentStatusEnum::CANCELED, TbankPaymentStatusEnum::REVERSED,
            TbankPaymentStatusEnum::DEADLINE_EXPIRED => PaymentStatusEnum::CANCELLED,
            TbankPaymentStatusEnum::REJECTED, TbankPaymentStatusEnum::AUTH_FAIL => PaymentStatusEnum::FAILED,
            TbankPaymentStatusEnum::NEW, TbankPaymentStatusEnum::FORM_SHOWED, TbankPaymentStatusEnum::AUTHORIZING,
            TbankPaymentStatusEnum::THREEDS_CHECKING, TbankPaymentStatusEnum::THREEDS_CHECKED,
            TbankPaymentStatusEnum::CONFIRMING => PaymentStatusEnum::PENDING,
            default => null,
        };
    }

    private function assertPayable(Order $order): void
    {
        if (! $order->payment_method->isOnline() || ! $order->status->isNew()
            || $order->payment_status->isPaid()
            || $order->cancellation_state !== null
            || $order->payments()->exists()) {
            throw ValidationException::withMessages(['payment' => 'Оплата этого заказа недоступна.']);
        }
    }

    private function secureRoute(string $name): string
    {
        return preg_replace('~^http://~', 'https://', route($name));
    }

    private function notify(Order $order, string $event, string $key): void
    {
        app(OrderNotificationService::class)->send($order->fresh(), $event, $key);
    }

    private function claimPaymentAttempt(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $this->assertPayable($lockedOrder);
            $payment = $lockedOrder->payments()->create([
                'provider' => 'TBANK',
                'bank_order_id' => $lockedOrder->number.'-1',
                'amount' => $lockedOrder->total,
                'status' => PaymentStatusEnum::PENDING,
                'provider_status' => TbankPaymentStatusEnum::NEW->value,
                'operation_state' => 'INITIATING',
            ]);

            return $payment;
        });
    }

    private function initializePayment(Order $order, Payment $payment): void
    {
        try {
            $result = $this->bank->initPayment(new TbankInitPaymentRequestDto(
                orderId: $payment->bank_order_id,
                amount: $payment->amount,
                description: 'Заказ '.$order->number,
                notificationUrl: $this->secureRoute('tbank.webhook'),
            ));

            $payment->update([
                'transaction_id' => $result->paymentId,
                'payment_url' => $result->paymentUrl,
                'operation_state' => null,
                'error' => null,
            ]);
            $this->applyBankState($payment, $result->status);
        } catch (TbankApiException $exception) {
            $payment->update(['operation_state' => 'RECONCILE_REQUIRED', 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function refundPayment(Order $order, Payment $payment): void
    {
        if (in_array($payment->status, [PaymentStatusEnum::REFUNDED, PaymentStatusEnum::CANCELLED, PaymentStatusEnum::FAILED], true)) {
            return;
        }

        $cancelRequested = false;

        try {
            $this->sync($payment, refundIfCancelled: false);
            $payment->refresh();

            if (! in_array($payment->status, [PaymentStatusEnum::PENDING, PaymentStatusEnum::PAID,
                PaymentStatusEnum::AUTHORIZED, PaymentStatusEnum::REFUND_FAILED], true)) {
                return;
            }

            if ($payment->operation_state === 'REFUNDING'
                && $payment->refund_requested_at?->gt(now()->subMinutes((int) config('orders.reconciliation_stale_minutes')))) {
                return;
            }

            $claimedRows = $this->claimRefund($payment);

            if ($claimedRows === 0) {
                return;
            }

            $cancelRequested = true;
            $order->update(['payment_status' => PaymentStatusEnum::REFUND_PENDING]);
            $result = $this->bank->cancelPayment(new TbankCancelPaymentRequestDto(paymentId: $payment->transaction_id, amount: $payment->amount));
            $this->applyBankState($payment->fresh(), $result->status);
        } catch (TbankApiException $exception) {
            $this->recordRefundError($order, $payment, $exception, $cancelRequested);
        }
    }

    private function claimRefund(Payment $payment): int
    {
        return Payment::query()->whereKey($payment->id)
            ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORIZED, PaymentStatusEnum::REFUND_FAILED])
            ->where(function ($query): void {
                $query->whereNull('operation_state')->orWhere('operation_state', '!=', 'REFUNDING')
                    ->orWhere('refund_requested_at', '<=', now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
            })->update([
                'operation_state' => 'REFUNDING',
                'refund_requested_at' => now(),
                'status' => PaymentStatusEnum::REFUND_PENDING,
                'error' => null,
            ]);
    }

    private function recordRefundError(Order $order, Payment $payment, TbankApiException $exception, bool $cancelRequested): void
    {
        $payment->refresh();
        $payment->update([
            'status' => $cancelRequested ? PaymentStatusEnum::REFUND_FAILED : $payment->status,
            'error' => $exception->getMessage(),
            'operation_state' => $cancelRequested
                ? ($exception->errorCode !== null ? null : 'REFUNDING')
                : $payment->operation_state,
        ]);
        $this->refreshOrderPayment($order);
        $this->notify($order, 'REFUND_FAILED', 'refund-error:'.$payment->id.':'.now()->format('Y-m-d-H'));
    }

    private function paymentStateChanges(TbankPaymentStatusEnum $providerStatus, ?PaymentStatusEnum $nextStatus): array
    {
        $values = ['provider_status' => $providerStatus->value, 'last_checked_at' => now()];

        if ($nextStatus !== null) {
            $values['status'] = $nextStatus;
            $values['error'] = null;

            if (! $nextStatus->isRefundPending()) {
                $values['operation_state'] = null;
            }

            if ($nextStatus->isRefunded()) {
                $values['refunded_at'] = now();
            }
        }

        return $values;
    }

    private function recordPaymentChange(Order $order, Payment $payment, PaymentStatusEnum $currentStatus, PaymentStatusEnum $nextStatus): void
    {
        $order->history()->firstOrCreate(['event_key' => 'payment:'.$payment->id.':'.$nextStatus->value], [
            'from_status' => $order->status->value,
            'to_status' => $order->status->value,
            'source' => 'TBANK',
            'metadata' => [
                'payment_id' => $payment->id,
                'from_payment_status' => $currentStatus->value,
                'to_payment_status' => $nextStatus->value,
                'provider_status' => $payment->provider_status,
            ],
            'created_at' => now(),
        ]);
    }
}
