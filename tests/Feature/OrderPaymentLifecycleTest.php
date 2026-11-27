<?php

declare(strict_types=1);

use App\Enums\Orders\OrderNotificationAudienceEnum;
use App\Enums\Orders\OrderNotificationEventEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Setting;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\Exceptions\TbankApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Support\PaymentFixtures;

beforeEach(function (): void {
    config([
        'tbank.base_url' => 'https://bank.test/v2',
        'tbank.terminal_key' => 'TEST_TERMINAL',
        'tbank.password' => 'test-password',
    ]);
    Http::preventStrayRequests();
    Mail::fake();
    Notification::fake();
    config(['telegram.manager_chat_id' => '-1001234567890']);
    $this->freezeSecond();
});

it('инициирует онлайн оплату нового заказа на полную сумму без кассового чека', function (): void {
    config(['orders.external_number_prefix' => 'local']);
    $order = PaymentFixtures::order([
        'idempotency_key' => 'a1b2c3d4-e5f6-4789-8123-456789abcdef',
    ]);
    Http::fake(fn (Request $request) => Http::response([
        'Success' => true, 'ErrorCode' => '0', 'PaymentId' => '100001',
        'OrderId' => $request['OrderId'], 'Amount' => $request['Amount'],
        'Status' => 'NEW', 'PaymentURL' => 'https://bank.test/pay/100001',
    ]));

    $payment = app(OrderPaymentService::class)->startOnlinePayment($order);

    expect($payment->order_id)->toBe($order->id)
        ->and($payment->amount->minorAmount())->toBe(135000)
        ->and($payment->payment_url)->toBe('https://bank.test/pay/100001')
        ->and(PaymentFixtures::value($order->fresh()->status))->toBe('NEW');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/Init')
        && $request['Amount'] === 135000
        && $request['OrderId'] === 'local-'.$order->number.'-a1b2c3d4e5f64789'
        && ! array_key_exists('Receipt', $request->data())
        && ! array_key_exists('RedirectDueDate', $request->data()));
});

it('не инициирует оплаченный отменённый или офлайн заказ', function (array $attributes): void {
    $order = PaymentFixtures::order($attributes);
    Http::fake();

    expect(fn () => app(OrderPaymentService::class)->startOnlinePayment($order))->toThrow(ValidationException::class);

    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/Init'));
})->with([
    'оплаченный' => [['payment_status' => 'PAID']],
    'отменённый' => [['status' => 'CANCELLED']],
    'при получении' => [['payment_method' => PaymentMethodEnum::PICKUP->value]],
]);

it('ошибка банка не удаляет созданный заказ и не меняет его итог', function (): void {
    $order = PaymentFixtures::order();
    Http::fake(['bank.test/*' => Http::failedConnection()]);

    try {
        app(OrderPaymentService::class)->startOnlinePayment($order);
    } catch (TbankApiException) {
    }

    expect($order->fresh())->not->toBeNull()
        ->and($order->fresh()->total->minorAmount())->toBe(135000)
        ->and(PaymentFixtures::value($order->fresh()->status))->toBe('NEW');
});

it('отклоняет неподписанный вебхук без изменения оплаты', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    $data = PaymentFixtures::notification($payment, 'CONFIRMED');
    $data['Token'] = str_repeat('0', 64);

    $this->postJson(route('tbank.webhook'), $data)->assertForbidden();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('UNPAID');
});

it('принимает только корректный JSON-объект вебхука', function (): void {
    $this->post(route('tbank.webhook'))->assertUnsupportedMediaType();

    $this->call(
        method: 'POST',
        uri: route('tbank.webhook'),
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: '{',
    )->assertUnprocessable()->assertJsonValidationErrors('notification');

    $this->call(
        method: 'POST',
        uri: route('tbank.webhook'),
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['notification'], JSON_THROW_ON_ERROR),
    )->assertUnprocessable()->assertJsonValidationErrors('notification');
});

it('подтверждённый вебхук обновляет оплату один раз и не откатывается авторизацией', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    Http::fake();
    $confirmed = PaymentFixtures::notification($payment, 'CONFIRMED');

    $this->postJson(route('tbank.webhook'), $confirmed)->assertOk();
    $this->postJson(route('tbank.webhook'), $confirmed)->assertOk();
    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'AUTHORIZED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID')
        ->and(PaymentFixtures::value($payment->fresh()->status))->toBe('PAID')
        ->and($order->payments()->count())->toBe(1)
        ->and(PaymentFixtures::value($order->fresh()->status))->toBe('NEW');
    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 2);
    Notification::assertSentOnDemand(
        OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification): bool => $notification->event === OrderNotificationEventEnum::CREATED,
    );
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'cdek'));
});

it('авторизация не считается окончательной оплатой', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'AUTHORIZED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('AUTHORIZED');
    Notification::assertNothingSent();
});

it('вебхук с чужой суммой или заказом не подтверждает оплату', function (array $overrides): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED', $overrides));

    expect(PaymentFixtures::value($order->fresh()->payment_status))->not->toBe('PAID');
})->with([
    'сумма' => [['Amount' => 1]],
    'номер заказа' => [['OrderId' => 'another-order']],
]);

it('сбой другой попытки не отменяет уже подтверждённую оплату заказа', function (): void {
    $order = PaymentFixtures::order(['payment_status' => 'PAID']);
    PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    $other = PaymentFixtures::payment($order, ['transaction_id' => '100002', 'bank_order_id' => $order->number.'-2']);

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($other, 'REJECTED', ['Success' => false, 'ErrorCode' => '100']))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID');
});

it('сверяет отменённый банком платёж без автоматической отмены заказа', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    Http::fake(['bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'DEADLINE_EXPIRED'))]);

    app(OrderPaymentService::class)->syncPayment($payment);

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('NEW')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('CANCELLED');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/GetState') && $request['PaymentId'] === '100001');
});

it('при сверке сохраняет подтверждённую банком оплату', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    Http::fake(['bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'CONFIRMED'))]);

    app(OrderPaymentService::class)->syncPayment($payment);

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('NEW')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID');
});

it('недоступность банка при сверке не меняет статус заказа и оплаты', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    Http::fake(['bank.test/*' => Http::failedConnection()]);

    expect(fn () => app(OrderPaymentService::class)->syncPayment($payment))->toThrow(TbankApiException::class);

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('NEW')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('UNPAID');
});

it('позднюю оплату отменённого заказа возвращает полностью без восстановления', function (): void {
    $order = PaymentFixtures::order(['status' => 'CANCELLED']);
    $payment = PaymentFixtures::payment($order);
    Http::fake(fn (Request $request) => Http::response(PaymentFixtures::state(
        $payment,
        str_ends_with($request->url(), '/Cancel') ? 'REFUNDED' : 'CONFIRMED',
    )));

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();
    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
    $this->assertDatabaseMissing('order_notification_events', ['order_id' => $order->id, 'event' => 'CREATED']);
    expect(Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')))->toHaveCount(1);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')
        && $request['Amount'] === 135000 && ! array_key_exists('Receipt', $request->data()));
});

it('после таймаута инициализации восстанавливает платёж сверкой без повторной оплаты', function (): void {
    $order = PaymentFixtures::order();
    Http::fake(['bank.test/v2/Init' => Http::failedConnection()]);

    try {
        app(OrderPaymentService::class)->startOnlinePayment($order);
    } catch (TbankApiException) {
    }

    $pending = $order->payments()->sole();
    expect($pending->bank_order_id)->toBeString()->not->toBeEmpty();
    $bankOrderId = $pending->bank_order_id;
    Http::fake(function (Request $request) use ($bankOrderId, $order) {
        if (str_ends_with($request->url(), '/CheckOrder')) {
            expect($request['OrderId'])->toBe($bankOrderId);

            return Http::response([
                'Success' => true,
                'ErrorCode' => '0',
                'OrderId' => $bankOrderId,
                'Payments' => [[
                    'PaymentId' => '100001',
                    'Amount' => $order->total->minorAmount(),
                    'Status' => 'NEW',
                ]],
            ]);
        }

        return Http::response([
            'Success' => true,
            'ErrorCode' => '0',
            'PaymentId' => '100001',
            'OrderId' => $bankOrderId,
            'Amount' => $order->total->minorAmount(),
            'Status' => 'NEW',
        ]);
    });

    app(OrderPaymentService::class)->syncPayment($pending);
    $recovered = $pending->fresh();

    expect($recovered->id)->toBe($pending->id)
        ->and($recovered->bank_order_id)->toBe($bankOrderId)
        ->and($recovered->transaction_id)->toBe('100001')
        ->and($order->payments()->count())->toBe(1);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/CheckOrder'));
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/Init'));
});

it('уведомляет о новом заказе после восстановления оплаты сверкой только один раз', function (): void {
    $order = PaymentFixtures::order();
    $order->user->update(['telegram_chat_id' => '1234567', 'telegram_notifications_enabled' => true]);
    Setting::query()->create(['admin_email' => 'admin@example.test']);
    $payment = PaymentFixtures::payment($order);
    Http::fake(['bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'CONFIRMED'))]);

    app(OrderPaymentService::class)->syncPayment($payment);
    app(OrderPaymentService::class)->syncPayment($payment);
    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();

    Notification::assertSentOnDemandTimes(OrderUpdateNotification::class, 2);
    foreach ([
        [OrderNotificationAudienceEnum::BUYER, $order->customer_email, '1234567'],
        [OrderNotificationAudienceEnum::MANAGER, 'admin@example.test', '-1001234567890'],
    ] as [$audience, $email, $chatId]) {
        Notification::assertSentOnDemand(
            OrderUpdateNotification::class,
            fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === $audience
                && $notifiable->routes['mail'] === $email
                && $notifiable->routes['telegram'] === $chatId
                && in_array('mail', $channels, true)
                && in_array(TelegramChannel::class, $channels, true),
        );
    }
    $this->assertDatabaseHas('order_notification_events', ['order_id' => $order->id, 'event' => 'CREATED']);
    $this->assertDatabaseCount('order_notification_events', 1);
});
