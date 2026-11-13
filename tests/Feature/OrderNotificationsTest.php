<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Orders\DeliveryQuoteErrorNotification;
use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Orders\OrderNotificationService;
use App\Support\Values\Money;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CheckoutFixtures;

function notificationOrder(?User $user = null): Order
{
    return Order::query()->create([
        'user_id' => $user?->id,
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
    app(OrderNotificationService::class)->queue($order, 'CREATED', 'created');

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
    app(OrderNotificationService::class)->queue(notificationOrder($user), 'CREATED', 'created');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === 'BUYER'
            && (($notifiable->routes['telegram'] ?? null) === ($enabled ? '1234567' : null)));
})->with([true, false]);

it('уведомляет менеджеров о новом заказе и не отправляет Telegram гостю', function (): void {
    app(OrderNotificationService::class)->queue(notificationOrder(), 'CREATED', 'created');

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
    $service->queue($order, 'CREATED', 'created');
    $service->queue($order, 'CREATED', 'created');

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 2);
});

it('письмо гостю содержит номер заказа и событие без ссылки доступа', function (): void {
    $order = notificationOrder();
    app(OrderNotificationService::class)->queue($order, 'REFUNDED', 'refund-1');

    Notification::assertSentOnDemand(OrderUpdateNotification::class,
        function (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            if ($notification->audience !== 'BUYER') {
                return false;
            }
            $mail = $notification->toMail($notifiable);
            expect($mail->subject)->toContain((string) $notification->order->number)
                ->and(implode(' ', $mail->introLines))->toContain('возврат')
                ->and($mail->actionUrl)->toBeNull();

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

it('включает сохранённый состав и сведения заказа в html и текстовое подтверждение', function (): void {
    $order = notificationOrder();
    $order->update([
        'delivery_price' => Money::fromRubles(350),
        'total' => Money::fromRubles(1550),
        'comment' => 'Позвоните перед выдачей <script>alert(1)</script>',
    ]);
    $variant = CheckoutFixtures::variant();
    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Крем из оформленного заказа',
        'variant_name' => '250 мл',
        'sku' => 'SNAPSHOT-250',
        'price' => Money::fromRubles(600),
        'quantity' => 2,
        'total' => Money::fromRubles(1200),
    ]);
    $variant->product->update(['name' => 'Новое название каталога']);
    $variant->update(['price' => Money::fromRubles(9999)]);
    $mail = (new OrderUpdateNotification($order->fresh(), 'CREATED'))->toMail(new AnonymousNotifiable);
    $html = (string) $mail->render();
    $plain = (string) app(Markdown::class)->renderText($mail->markdown, $mail->data());

    foreach ([$html, $plain] as $body) {
        expect($body)->toContain((string) $order->number, $order->created_at->format('d.m.Y'),
            'Крем из оформленного заказа', '250 мл', 'SNAPSHOT-250', '600 ₽', '2 × 600 ₽',
            '1 200 ₽', '350 ₽', '1 550 ₽', $order->customer_name, $order->customer_phone,
            $order->customer_email, 'Позвоните перед выдачей')
            ->not->toContain('Новое название каталога', '9 999 ₽', '<script>', '600.00', 'Открыть заказ', 'signature=', 'Оплатить заказ можно на странице заказа');
    }

    expect($mail->actionUrl)->toBeNull();
});

it('показывает способ получения и адрес из заказа', function (string $method, array $address, array $expected): void {
    $order = notificationOrder();
    $order->update(['delivery_method' => $method, 'delivery_address' => $address]);
    $mail = (new OrderUpdateNotification($order, 'CREATED'))->toMail(new AnonymousNotifiable);

    expect((string) $mail->render())->toContain(...$expected)
        ->toContain('бесплатно');
})->with([
    'самовывоз' => ['PICKUP', ['city' => 'Владикавказ', 'address' => 'ул. Мира, 10', 'working_hours' => '10:00–19:00'],
        ['Самовывоз', 'ул. Мира, 10', '10:00–19:00', 'Приходите после уведомления о готовности']],
    'курьер' => ['COURIER', ['city' => 'Владикавказ', 'street' => 'ул. Ленина', 'house' => '5', 'apartment' => '12'],
        ['Курьер', 'ул. Ленина', 'дом 5', 'кв. 12', 'Время доставки согласует менеджер']],
    'сдэк' => ['CDEK', ['city' => 'Москва', 'address' => 'ул. Тверская, 15', 'code' => 'MSK123', 'work_time' => '09:00–20:00'],
        ['СДЭК', 'ул. Тверская, 15', 'MSK123', '09:00–20:00', 'Трек-номер появится после подготовки отправления']],
]);

it('не предлагает отложенную оплату в письме о создании заказа', function (string $method, string $status): void {
    $order = notificationOrder(CheckoutFixtures::user());
    $order->update([
        'payment_method' => $method,
        'payment_status' => $status,
    ]);
    $mail = (new OrderUpdateNotification($order, 'CREATED'))->toMail(new AnonymousNotifiable);
    $body = (string) $mail->render();

    expect($body)->toContain($order->payment_method->getLabel(), $order->payment_status->getLabel())
        ->not->toContain('Оплатите до', 'Оплатить заказ можно на странице заказа');
})->with([
    ['ONLINE', 'UNPAID'],
    ['ONLINE', 'PAID'],
    ['ONLINE', 'AUTHORIZED'],
    ['PICKUP', 'UNPAID'],
]);

it('обозначает предзаказ и скрывает незаполненные необязательные сведения', function (): void {
    $order = notificationOrder();
    $order->update(['is_preorder' => true]);
    $body = (string) (new OrderUpdateNotification($order, 'CREATED'))->toMail(new AnonymousNotifiable)->render();

    expect($body)->toContain('Предзаказ', 'Точные сроки доставки согласовываются отдельно с менеджером')
        ->not->toContain('Комментарий к заказу', 'График:', 'Пункт:');
});

it('отображает разделы письма как html без блоков исходного кода', function (): void {
    $mail = (new OrderUpdateNotification(notificationOrder(), 'CREATED'))->toMail(new AnonymousNotifiable);

    expect((string) $mail->render())->toContain('<h2', 'Товары в заказе')
        ->not->toContain('<pre', '&lt;h2&gt;', '&lt;p&gt;');
});

it('оставляет уведомления об изменениях короткими и добавляет трек-номер при отправке', function (): void {
    $order = notificationOrder();
    $order->shipment()->create(['cdek_number' => '1234567890']);
    $mail = (new OrderUpdateNotification($order, 'SHIPPED'))->toMail(new AnonymousNotifiable);

    expect((string) $mail->render())->toContain('1234567890')
        ->not->toContain('Товары в заказе', 'Комментарий к заказу');
});

it('ссылка из письма владельцу требует входа и проверяет владельца заказа', function (): void {
    $user = CheckoutFixtures::user();
    $order = notificationOrder($user);
    $mail = (new OrderUpdateNotification($order, 'CREATED'))->toMail(new AnonymousNotifiable);

    expect($mail->actionUrl)->toBe(route('orders.show', $order));
    $this->getJson($mail->actionUrl)->assertUnauthorized();
    $this->actingAs(CheckoutFixtures::user())->get($mail->actionUrl)->assertNotFound();
    $this->actingAs($user)->get($mail->actionUrl)->assertOk();
});
