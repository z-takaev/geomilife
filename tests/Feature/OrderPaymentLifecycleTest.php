<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\Exceptions\TbankApiException;
use Illuminate\Http\Client\Request;
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
    $this->freezeSecond();
});

it('инициирует онлайн оплату нового заказа на полную сумму без кассового чека', function (): void {
    $order = PaymentFixtures::order();
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
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'cdek'));
});

it('авторизация не считается окончательной оплатой', function (): void {
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'AUTHORIZED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('AUTHORIZED');
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
    Http::fake(fn (Request $request) => Http::response(PaymentFixtures::state($payment,
        str_ends_with($request->url(), '/Cancel') ? 'REFUNDED' : 'CONFIRMED')));

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();
    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
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
