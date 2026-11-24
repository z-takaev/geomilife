<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Enums\Cdek\CdekOrderStatusEnum;
use App\Enums\Cdek\CdekRequestStateEnum;
use App\Enums\Orders\OrderCancellationStateEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;

final readonly class OrderManagementViewModel
{
    public function __construct(
        private OrderLifecycleService $lifecycle,
    ) {}

    /**
     * @return list<array{label: string, current: bool, color: string}>
     */
    public function processingStages(Order $order): array
    {
        $steps = $this->lifecycle->stages($order);
        $position = array_search($order->status, $steps, true);
        $stages = [];

        foreach ($steps as $index => $step) {
            $stages[] = [
                'label' => $step->getLabel(),
                'current' => $step->is($order->status),
                'color' => $step->is($order->status)
                    ? 'primary'
                    : ($position !== false && $index < $position ? 'success' : 'gray'),
            ];
        }

        if ($order->status->isCancelled()) {
            $stages[] = [
                'label' => OrderStatusEnum::CANCELLED->getLabel(),
                'current' => true,
                'color' => 'danger',
            ];
        }

        return $stages;
    }

    public function processingHint(Order $order): string
    {
        if ($order->status->isCancelled()) {
            return __('Заказ отменён. Дальнейшая обработка недоступна.');
        }

        if (in_array($order->cancellation_state, [
            OrderCancellationStateEnum::PENDING->value,
            OrderCancellationStateEnum::FAILED->value,
        ], true)) {
            return __('Выполняется отмена заказа. Изменение статуса недоступно.');
        }

        if ($order->status->isDelivered()) {
            return __('Заказ завершён.');
        }

        if ($order->payment_method->isOnline() && ! $order->payment_status->isPaid()) {
            return __('Ожидается онлайн-оплата. Обработка станет доступна после подтверждения оплаты банком.');
        }

        if ($order->delivery_method->isCdek() && in_array($order->status, [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED], true)) {
            return __('Статусы отправки и доставки обновляются автоматически по данным СДЭК.');
        }

        if ($order->delivery_method->isPickup() && $order->status->isReadyForPickup()
            && $order->payment_method->isPickup() && ! $order->payment_status->isPaid()) {
            return __('При выдаче подтвердите получение оплаты и передачу заказа покупателю.');
        }

        return __('Проверьте заказ и выполните следующий шаг.');
    }

    public function transitionLabel(Order $order, OrderStatusEnum $status): string
    {
        return match ($status) {
            OrderStatusEnum::CONFIRMED => __('Подтвердить и начать сборку'),
            OrderStatusEnum::READY_FOR_PICKUP => __('Подготовить к выдаче'),
            OrderStatusEnum::READY_TO_SHIP => __('Подготовить к отправке'),
            OrderStatusEnum::DELIVERED => $order->delivery_method->isPickup()
                ? __('Подтвердить выдачу')
                : __('Подтвердить доставку'),
            default => $status->getLabel(),
        };
    }

    public function transitionDescription(Order $order, OrderStatusEnum $status): string
    {
        $description = __(':from → :to. Покупателю будет отправлено уведомление.', [
            'from' => $order->status->getLabel(),
            'to' => $status->getLabel(),
        ]);

        if ($status->isReadyToShip()) {
            $description .= ' '.__('Будет создана заявка СДЭК. Убедитесь, что заказ собран и упакован.');
        }

        if ($status->isDelivered()) {
            $description .= $order->delivery_method->isPickup() && $order->payment_method->isPickup()
                ? ' '.__('Подтвердите, что получена оплата :amount и заказ выдан покупателю.', ['amount' => $order->total->format()])
                : ' '.__('Подтвердите, что покупатель получил заказ.');
        }

        return $description;
    }

    public function cancellationDescription(Order $order): string
    {
        $description = __('Заказ будет отменён. Укажите причину отмены.');

        if ($order->delivery_method->isCdek()) {
            $description .= ' '.__('Будет отменена заявка СДЭК при её наличии. Отмена недоступна после движения груза.');
        }

        if ($order->payment_method->isOnline()) {
            $description .= ' '.($order->payment_status->isPaid()
                ? __('Будет возвращена полная онлайн-оплата: :amount.', ['amount' => $order->total->format()])
                : __('Если банк подтвердит оплату отменённого заказа, будет выполнен возврат.'));
        }

        return $description;
    }

    public function cancellationStateLabel(?string $state): string
    {
        return match ($state) {
            OrderCancellationStateEnum::PENDING->value => __('Выполняется'),
            OrderCancellationStateEnum::FAILED->value => __('Ошибка отмены'),
            OrderCancellationStateEnum::COMPLETED->value => __('Завершена'),
            default => '—',
        };
    }

    public function accountLabel(Order $order): string
    {
        if ($order->user === null) {
            return __('Гость');
        }

        $name = trim($order->user->first_name.' '.$order->user->last_name);

        return $name !== '' ? $name : $order->user->email;
    }

    public function cdekStatusLabel(Order $order): string
    {
        $status = $order->shipment?->status;

        if ($status === null) {
            return __('Статус ещё не получен');
        }

        return CdekOrderStatusEnum::tryFrom($status)?->getLabel() ?? __('Неизвестный статус СДЭК');
    }

    public function cdekRegistrationLabel(Order $order): string
    {
        $shipment = $order->shipment;

        if ($shipment === null) {
            return __('Не зарегистрировано в СДЭК');
        }

        if ($shipment->operation_state !== null) {
            return $shipment->operation_state->getLabel();
        }

        return match ($shipment->request_state) {
            CdekRequestStateEnum::ACCEPTED, CdekRequestStateEnum::WAITING => __('Регистрируется'),
            CdekRequestStateEnum::SUCCESSFUL => __('Зарегистрировано'),
            CdekRequestStateEnum::INVALID => __('Ошибка регистрации'),
            default => $shipment->uuid === null
                ? __('Не зарегистрировано в СДЭК')
                : __('Зарегистрировано'),
        };
    }
}
