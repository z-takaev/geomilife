<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Enums\Orders\OrderCancellationStateEnum;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;

final readonly class OrderDetailsViewModel
{
    /**
     * @var list<array{label: string, done: bool}>
     */
    public array $stages;

    public OrderDeliveryViewModel $delivery;

    public bool $canCancel;

    public string $cancelUrl;

    public bool $isCancelling;

    public function __construct(Order $order, OrderLifecycleService $lifecycle)
    {
        $this->delivery = new OrderDeliveryViewModel($order);
        $this->canCancel = $order->user_id !== null
            && $order->status->isNew()
            && $order->cancellation_state === null;
        $this->cancelUrl = route('orders.cancel', $order);
        $this->isCancelling = in_array($order->cancellation_state, [
            OrderCancellationStateEnum::PENDING->value,
            OrderCancellationStateEnum::FAILED->value,
        ], true);
        $this->stages = $this->makeStages($order, $lifecycle);
    }

    /**
     * @return list<array{label: string, done: bool}>
     */
    private function makeStages(Order $order, OrderLifecycleService $lifecycle): array
    {
        $steps = $lifecycle->stages($order);
        $position = array_search($order->status, $steps, true);
        $stages = [];

        foreach ($steps as $index => $step) {
            $stages[] = [
                'label' => $step->getLabel(),
                'done' => $position !== false && $index <= $position,
            ];
        }

        return $stages;
    }
}
