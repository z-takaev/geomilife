<?php

declare(strict_types=1);

use App\Filament\Resources\Orders\OrderResource;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Notification::fake();
    config(['telegram.bot_token' => 'test-token', 'telegram.manager_chat_id' => '-1001234567890']);
});

it('не отправляет покупателю уведомление об успешной оплате', function (string $event): void {
    app(OrderNotificationService::class)->queue(fulfillmentOrder(), $event, $event);

    Notification::assertNothingSent();
})->with(['PAYMENT_PAID', 'PAID']);

it('сообщает менеджерам о новом заказе с подписанными полями и кнопкой', function (): void {
    $order = fulfillmentOrder();
    $notification = new OrderUpdateNotification($order, 'CREATED', 'MANAGER');

    expect($notification->toTelegram(new AnonymousNotifiable))
        ->toBe("Новый заказ №{$order->number}\nПокупатель: {$order->customer_name}\nТелефон: {$order->customer_phone}\nEmail: {$order->customer_email}\nПолучение: СДЭК\nОплата: Картой онлайн\nСумма: {$order->total->format()}")
        ->and($notification->telegramAction())->toBe([
            'text' => 'Открыть заказ',
            'url' => OrderResource::getUrl('view', ['record' => $order]),
        ]);

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
})->with(['CONFIRMED', 'READY_FOR_PICKUP', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED']);

it('сохраняет сообщения менеджерам об ошибках', function (string $event): void {
    app(OrderNotificationService::class)->queue(fulfillmentOrder(), $event, $event);

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 1);
    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification): bool => $notification->audience === 'MANAGER');
})->with(['REFUND_FAILED', 'CANCELLATION_FAILED', 'SHIPPING_FAILED', 'DOCUMENT_FAILED', 'PAYMENT_RECONCILE_FAILED', 'CANCELLATION_REJECTED']);

it('объясняет менеджерам причину технического уведомления', function (string $event, string $message): void {
    $notification = new OrderUpdateNotification(fulfillmentOrder(), $event, 'MANAGER');
    $text = $notification->toTelegram(new AnonymousNotifiable);

    expect($text)->toContain($message)->not->toContain('Трек-номер', 'Обновление заказа.');
})->with([
    ['PAYMENT_RECONCILE_FAILED', 'Не удалось сверить платёж с банком. Требуется проверка.'],
    ['CANCELLATION_REJECTED', 'СДЭК отклонил отмену отправления. Требуется проверка.'],
]);

it('показывает трек только покупателю при отправке и добавляет кнопку заказа', function (): void {
    $user = CheckoutFixtures::user();
    $order = fulfillmentOrder(['user_id' => $user->id, 'status' => 'SHIPPED']);
    $order->shipment()->create(['cdek_number' => '1234567890']);
    $notification = new OrderUpdateNotification($order, 'SHIPPED');

    expect($notification->toTelegram(new AnonymousNotifiable))
        ->toContain("Заказ №{$order->number} передан в СДЭК.", 'Трек-номер СДЭК: 1234567890')
        ->not->toContain('Сумма:')
        ->and($notification->telegramAction())->toBe([
            'text' => 'Открыть заказ',
            'url' => route('orders.show', $order),
        ]);
});

it('не добавляет трек в уведомления об отмене и возврате', function (string $event): void {
    $order = fulfillmentOrder(['cancellation_reason' => 'Просьба покупателя']);
    $order->shipment()->create(['cdek_number' => '1234567890']);
    $text = (new OrderUpdateNotification($order, $event))->toTelegram(new AnonymousNotifiable);

    expect($text)->not->toContain('1234567890', 'Трек-номер', 'Сумма:');
})->with(['CANCELLED', 'REFUNDED']);

it('передаёт Telegram кнопку открытия заказа', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    $order = fulfillmentOrder();
    $notification = new OrderUpdateNotification($order, 'CREATED', 'MANAGER');
    $notifiable = (new AnonymousNotifiable)->route('telegram', '-1001234567890');

    app(TelegramChannel::class)->send($notifiable, $notification);

    Http::assertSent(fn ($request): bool => $request['reply_markup'] === [
        'inline_keyboard' => [[[
            'text' => 'Открыть заказ',
            'url' => OrderResource::getUrl('view', ['record' => $order]),
        ]]],
    ]);
});

it('не добавляет ссылку доступа в письмо о заказе без владельца', function (): void {
    $notification = new OrderUpdateNotification(fulfillmentOrder(), 'CREATED');
    $mail = $notification->toMail(new AnonymousNotifiable);

    expect($mail->actionUrl)->toBeNull()
        ->and((string) $mail->render())->toContain('Ваш заказ принят');
});
