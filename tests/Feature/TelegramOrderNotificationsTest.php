<?php

declare(strict_types=1);

use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Notification::fake();
    config(['telegram.manager_chat_id' => '-1001234567890']);
});

it('отправляет покупателю текст события и трек без ссылки', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['cdek_number' => 'TRACK-123']);
    $notification = new OrderUpdateNotification($order, 'PAYMENT_PAID');
    $text = $notification->toTelegram(new AnonymousNotifiable);

    expect($text)->toBe("Заказ №{$order->number}\nОплата заказа подтверждена.\nСумма: {$order->total->format()}\nТрек-номер: TRACK-123");
});

it('сообщает менеджерам о новом заказе отдельным текстом без ссылки', function (): void {
    $order = fulfillmentOrder();
    $notification = new OrderUpdateNotification($order, 'CREATED', 'MANAGER');

    expect($notification->toTelegram(new AnonymousNotifiable))
        ->toBe("Заказ №{$order->number}\nПоступил новый заказ.\nСумма: {$order->total->format()}\n{$order->customer_name}\n{$order->customer_phone}");

    app(OrderNotificationService::class)->queue($order, 'CREATED', 'created');
    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 2);
    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'MANAGER'
            && $notifiable->routes['telegram'] === '-1001234567890');
});

it('уведомляет о последующих статусах только покупателя', function (string $event): void {
    app(OrderNotificationService::class)->queue(fulfillmentOrder(), $event, $event);

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 1);
    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification): bool => $notification->audience === 'BUYER');
})->with(['PAYMENT_PAID', 'PAID', 'CONFIRMED', 'READY_FOR_PICKUP', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED']);

it('сохраняет сообщения менеджерам об ошибках', function (string $event): void {
    app(OrderNotificationService::class)->queue(fulfillmentOrder(), $event, $event);

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 1);
    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification): bool => $notification->audience === 'MANAGER');
})->with(['REFUND_FAILED', 'CANCELLATION_FAILED', 'SHIPPING_FAILED', 'DOCUMENT_FAILED', 'PAYMENT_RECONCILE_FAILED', 'CANCELLATION_REJECTED']);

it('объясняет менеджерам причину технического уведомления', function (string $event, string $message): void {
    $notification = new OrderUpdateNotification(fulfillmentOrder(), $event, 'MANAGER');
    $text = $notification->toTelegram(new AnonymousNotifiable);

    expect($text)->toContain($message)->not->toContain('http://', 'https://', 'Обновление заказа.');
})->with([
    ['PAYMENT_RECONCILE_FAILED', 'Не удалось сверить платёж с банком. Требуется проверка.'],
    ['CANCELLATION_REJECTED', 'СДЭК отклонил отмену отправления. Требуется проверка.'],
]);

it('не добавляет ссылку доступа в письмо о заказе без владельца', function (): void {
    $notification = new OrderUpdateNotification(fulfillmentOrder(), 'CREATED');
    $mail = $notification->toMail(new AnonymousNotifiable);

    expect($mail->actionUrl)->toBeNull()
        ->and(implode(' ', $mail->introLines))->toContain('Ваш заказ принят.');
});
