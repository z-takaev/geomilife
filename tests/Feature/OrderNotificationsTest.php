<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Orders\DeliveryQuoteErrorNotification;
use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Orders\OrderNotificationService;
use App\Support\Values\Money;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

function notificationOrder(?User $user = null): Order
{
    return Order::query()->create([
        'user_id' => $user?->id,
        'number' => 'NOTIFY-001',
        'status' => 'NEW',
        'payment_status' => 'UNPAID',
        'payment_method' => PaymentMethodEnum::ONLINE->value,
        'delivery_method' => 'PICKUP',
        'delivery_price' => Money::fromRubles(0),
        'subtotal' => Money::fromRubles(1200),
        'total' => Money::fromRubles(1200),
        'customer_name' => 'Получатель заказа',
        'customer_phone' => '+79991234567',
        'customer_email' => 'recipient@example.test',
        'delivery_address' => ['city' => 'Владикавказ'],
    ]);
}

beforeEach(function (): void {
    Notification::fake();
    config(['telegram.manager_chat_id' => '-1001234567890']);
});

it('направляет уведомление на email получателя из снимка заказа, а не текущего профиля', function (): void {
    $user = User::query()->create([
        'first_name' => 'Владелец', 'email' => 'profile@example.test', 'password' => 'password',
    ]);
    $order = notificationOrder($user);
    app(OrderNotificationService::class)->send($order, 'CREATED', 'created');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'BUYER'
            && $notification->event === 'CREATED'
            && $notifiable->routes['mail'] === 'recipient@example.test'
            && in_array('mail', $channels, true));
});

it('добавляет Telegram покупателя только при включённых уведомлениях подключённого пользователя', function (bool $enabled): void {
    $user = User::query()->create([
        'first_name' => 'Владелец', 'email' => 'profile@example.test', 'password' => 'password',
        'telegram_chat_id' => '1234567', 'telegram_notifications_enabled' => $enabled,
    ]);
    app(OrderNotificationService::class)->send(notificationOrder($user), 'CREATED', 'created');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'BUYER'
            && (($notifiable->routes['telegram'] ?? null) === ($enabled ? '1234567' : null)));
})->with([true, false]);

it('уведомляет менеджеров о новом заказе и не отправляет Telegram гостю', function (): void {
    app(OrderNotificationService::class)->send(notificationOrder(), 'CREATED', 'created');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'MANAGER'
            && $notifiable->routes['telegram'] === '-1001234567890'
            && ! isset($notifiable->routes['mail']));
    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'BUYER' && ! isset($notifiable->routes['telegram']));
});

it('не создаёт повторных уведомлений при повторной обработке одного бизнес-события', function (): void {
    $order = notificationOrder();
    $service = app(OrderNotificationService::class);
    $service->send($order, 'CREATED', 'created');
    $service->send($order, 'CREATED', 'created');

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 2);
});

it('письмо содержит номер заказа, понятное событие и защищённую ссылку без служебных данных', function (): void {
    $order = notificationOrder();
    app(OrderNotificationService::class)->send($order, 'REFUNDED', 'refund-1');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        function (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            if ($notification->audience !== 'BUYER') {
                return false;
            }
            $mail = $notification->toMail($notifiable);
            expect($mail->subject)->toContain('NOTIFY-001')
                ->and(implode(' ', $mail->introLines))->toContain('возврат')
                ->and($mail->actionUrl)->toContain('signature=')
                ->and($mail->actionUrl)->not->toContain('/admin/');

            return true;
        });
});

it('уведомляет менеджеров об ошибке расчёта сдэк без заказа и объединяет повторы', function (): void {
    $service = app(OrderNotificationService::class);
    $service->notifyQuoteError(44, 'Ошибка API');
    $service->notifyQuoteError(44, 'Ошибка API');

    Notification::assertSentOnDemandTimes(DeliveryQuoteErrorNotification::class, 1);
    Notification::assertSentOnDemand(DeliveryQuoteErrorNotification::class,
        fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['telegram'] === '-1001234567890'
            && str_contains($notification->toTelegram($notifiable), '44'));
});
