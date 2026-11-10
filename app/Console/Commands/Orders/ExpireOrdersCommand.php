<?php

declare(strict_types=1);

namespace App\Console\Commands\Orders;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Console\Command;
use Throwable;

final class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Отменить неоплаченные онлайн-заказы после истечения срока оплаты';

    public function handle(OrderPaymentService $payments, OrderLifecycleService $statuses): int
    {
        Order::query()
            ->where('status', OrderStatusEnum::NEW->value)
            ->where('payment_method', PaymentMethodEnum::ONLINE)
            ->where('payment_expires_at', '<=', now())
            ->whereNot('payment_status', PaymentStatusEnum::PAID->value)
            ->chunkById(100, function ($orders) use ($payments, $statuses): void {
                foreach ($orders as $order) {
                    try {
                        foreach ($order->payments as $payment) {
                            $payments->sync($payment);
                        }

                        $order->refresh();

                        if (! in_array($order->payment_status, [PaymentStatusEnum::PAID, PaymentStatusEnum::AUTHORIZED], true)) {
                            $statuses->cancel($order, null, 'Истёк часовой срок онлайн-оплаты.', onlyIfUnpaid: true);
                        } elseif ($order->payment_status === PaymentStatusEnum::AUTHORIZED) {
                            $statuses->cancel($order, null, 'Истёк срок оплаты, авторизация отменяется.', onlyIfUnpaid: true);
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        app(OrderNotificationService::class)
                            ->send($order, 'PAYMENT_RECONCILE_FAILED', 'expiry-error:'.$order->id.':'.now()->format('Y-m-d-H'));
                    }
                }
            });

        return self::SUCCESS;
    }
}
