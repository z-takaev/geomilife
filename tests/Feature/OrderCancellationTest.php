<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\OrderShipment;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Auth\Access\AuthorizationException;
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
        'services.cdek.base_url' => 'https://cdek.test',
        'services.cdek.client_id' => 'test-client',
        'services.cdek.client_secret' => 'test-secret',
    ]);
    Http::preventStrayRequests();
    Mail::fake();
    Notification::fake();
    $this->freezeSecond();
});

it('владелец отменяет новый неоплаченный заказ без банковского возврата', function (): void {
    $order = PaymentFixtures::order();
    Http::fake();

    app(OrderLifecycleService::class)->cancel($order, $order->user, 'Передумал');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and($order->fresh()->total->minorAmount())->toBe(135000);
    Http::assertNothingSent();
});

it('покупатель не отменяет чужой заказ', function (): void {
    $order = PaymentFixtures::order();
    Http::fake();

    expect(fn () => app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::user(), 'Отмена'))
        ->toThrow(AuthorizationException::class);

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('NEW');
    Http::assertNothingSent();
});

it('покупатель не отменяет заказ после нового состояния', function (string $status): void {
    $order = PaymentFixtures::order(['status' => $status, 'payment_status' => 'PAID']);
    Http::fake();

    expect(fn () => app(OrderLifecycleService::class)->cancel($order, $order->user, 'Передумал'))
        ->toThrow(ValidationException::class);

    expect(PaymentFixtures::value($order->fresh()->status))->toBe($status);
    Http::assertNothingSent();
})->with(['CONFIRMED', 'READY_FOR_PICKUP', 'READY_TO_SHIP', 'SHIPPED', 'DELIVERED']);

it('менеджер отменяет оплаченный заказ и возвращает всю сумму включая доставку без дубля', function (): void {
    $order = PaymentFixtures::order(['status' => 'CONFIRMED', 'payment_status' => 'PAID']);
    $payment = PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    Http::fake(fn (Request $request) => Http::response(PaymentFixtures::state($payment,
        str_ends_with($request->url(), '/Cancel') ? 'REFUNDED' : 'CONFIRMED')));
    $manager = PaymentFixtures::manager();

    app(OrderLifecycleService::class)->cancel($order, $manager, 'По просьбе покупателя');
    app(OrderPaymentService::class)->cancelOrRefundPayments($order->fresh());

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED')
        ->and($order->fresh()->total->minorAmount())->toBe(135000);
    expect(Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')))->toHaveCount(1);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')
        && $request['PaymentId'] === '100001' && $request['Amount'] === 135000
        && ! array_key_exists('Receipt', $request->data()));
});

it('отмена оплаты при получении не обращается в интернет эквайринг', function (): void {
    $order = PaymentFixtures::order(['status' => 'CONFIRMED', 'payment_method' => PaymentMethodEnum::PICKUP->value, 'payment_status' => 'UNPAID']);
    Http::fake();

    app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Отмена самовывоза');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('UNPAID');
    Http::assertNothingSent();
});

it('при авторизации снимает блокировку без утверждения о возврате списанных средств', function (): void {
    $order = PaymentFixtures::order(['payment_status' => 'AUTHORIZED']);
    $payment = PaymentFixtures::payment($order, ['status' => 'AUTHORIZED', 'provider_status' => 'AUTHORIZED']);
    Http::fake(fn (Request $request) => Http::response(PaymentFixtures::state($payment,
        str_ends_with($request->url(), '/Cancel') ? 'REVERSED' : 'AUTHORIZED')));

    app(OrderLifecycleService::class)->cancel($order, $order->user, 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('CANCELLED');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel'));
});

it('возврат выполняется только после подтверждённой отмены созданной заявки СДЭК', function (): void {
    $order = PaymentFixtures::order(['status' => 'READY_TO_SHIP', 'delivery_method' => 'CDEK', 'payment_status' => 'PAID']);
    $payment = PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => PaymentFixtures::SHIPMENT_UUID, 'status' => 'CREATED', 'request_state' => 'SUCCESSFUL']);
    $deleted = false;
    Http::fake(function (Request $request) use ($payment, &$deleted) {
        if (str_contains($request->url(), '/oauth/token')) {
            return Http::response(['access_token' => 'test-token', 'expires_in' => 3600]);
        }
        if (str_contains($request->url(), 'cdek.test')) {
            if ($request->method() === 'DELETE') {
                $deleted = true;
            }

            return Http::response(PaymentFixtures::shipment($deleted ? 'CANCELED' : 'CREATED', $deleted ? 'DELETE' : 'CREATE'));
        }
        if (str_ends_with($request->url(), '/Cancel')) {
            expect($deleted)->toBeTrue();
        }

        return Http::response(PaymentFixtures::state($payment,
            str_ends_with($request->url(), '/Cancel') ? 'REFUNDED' : 'CONFIRMED'));
    });

    app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Отмена до сдачи');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && str_contains($request->url(), PaymentFixtures::SHIPMENT_UUID));
});

it('начавшееся движение СДЭК запрещает отмену и возврат даже при устаревшем локальном статусе', function (): void {
    $order = PaymentFixtures::order(['status' => 'READY_TO_SHIP', 'delivery_method' => 'CDEK', 'payment_status' => 'PAID']);
    PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => PaymentFixtures::SHIPMENT_UUID, 'status' => 'CREATED']);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
        'cdek.test/v2/orders/*' => Http::response(PaymentFixtures::shipment('ACCEPTED')),
    ]);

    expect(fn () => app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Отмена'))
        ->toThrow(ValidationException::class);

    expect(PaymentFixtures::value($order->fresh()->status))->not->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID');
    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'DELETE'
        || str_ends_with($request->url(), '/Cancel'));
});

it('принятие асинхронной отмены СДЭК не считается её завершением и не запускает возврат', function (): void {
    $order = PaymentFixtures::order(['status' => 'READY_TO_SHIP', 'delivery_method' => 'CDEK', 'payment_status' => 'PAID']);
    PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => PaymentFixtures::SHIPMENT_UUID, 'status' => 'CREATED']);
    Http::fake(function (Request $request) {
        if (str_contains($request->url(), '/oauth/token')) {
            return Http::response(['access_token' => 'test-token', 'expires_in' => 3600]);
        }
        if ($request->method() === 'DELETE') {
            $response = PaymentFixtures::shipment('CREATED', 'DELETE');
            $response['requests'][0]['state'] = 'ACCEPTED';

            return Http::response($response, 202);
        }

        return Http::response(PaymentFixtures::shipment());
    });

    app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->not->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID');
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel'));
});

it('ошибка банковского возврата не восстанавливает отменённый заказ а повтор возвращает один раз', function (): void {
    $order = PaymentFixtures::order(['status' => 'CONFIRMED', 'payment_status' => 'PAID']);
    $payment = PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    Http::fake([
        'bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'CONFIRMED')),
        'bank.test/v2/Cancel' => Http::sequence()
            ->push(['Success' => false, 'ErrorCode' => '999', 'Message' => 'Временная ошибка'])
            ->push(PaymentFixtures::state($payment, 'REFUNDED')),
    ]);

    app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUND_FAILED');

    app(OrderPaymentService::class)->cancelOrRefundPayments($order->fresh());

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
});

it('возврат в обработке завершается по вебхуку а не по одному факту принятия запроса', function (): void {
    $order = PaymentFixtures::order(['payment_status' => 'PAID']);
    $payment = PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    Http::fake(fn (Request $request) => Http::response(PaymentFixtures::state($payment,
        str_ends_with($request->url(), '/Cancel') ? 'REFUNDING' : 'CONFIRMED')));

    app(OrderLifecycleService::class)->cancel($order, $order->user, 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUND_PENDING');

    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'REFUNDED'))->assertOk();
    $this->postJson(route('tbank.webhook'), PaymentFixtures::notification($payment, 'CONFIRMED'))->assertOk();

    expect(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
    expect(Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')))->toHaveCount(1);
});

it('после временного сбоя СДЭК повторяет отмену без преждевременного возврата', function (): void {
    $order = PaymentFixtures::order(['status' => 'READY_TO_SHIP', 'delivery_method' => 'CDEK', 'payment_status' => 'PAID']);
    $payment = PaymentFixtures::payment($order, ['status' => 'PAID', 'provider_status' => 'CONFIRMED']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => PaymentFixtures::SHIPMENT_UUID, 'status' => 'CREATED']);
    $available = false;
    $deleted = false;
    Http::fake(function (Request $request) use ($payment, &$available, &$deleted) {
        if (str_contains($request->url(), '/oauth/token')) {
            return Http::response(['access_token' => 'test-token', 'expires_in' => 3600]);
        }
        if (str_contains($request->url(), 'cdek.test')) {
            if (! $available) {
                return Http::response(['errors' => [['code' => 'UNAVAILABLE', 'message' => 'Временная ошибка']]], 503);
            }
            if ($request->method() === 'DELETE') {
                $deleted = true;
            }

            return Http::response(PaymentFixtures::shipment($deleted ? 'CANCELED' : 'CREATED', $deleted ? 'DELETE' : 'CREATE'));
        }

        return Http::response(PaymentFixtures::state($payment,
            str_ends_with($request->url(), '/Cancel') ? 'REFUNDED' : 'CONFIRMED'));
    });
    $manager = PaymentFixtures::manager();

    app(OrderLifecycleService::class)->cancel($order, $manager, 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->not->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('PAID');
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel'));

    $available = true;
    app(OrderLifecycleService::class)->cancel($order->fresh(), $manager, 'Отмена');

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('CANCELLED')
        ->and(PaymentFixtures::value($order->fresh()->payment_status))->toBe('REFUNDED');
    expect(Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/Cancel')))->toHaveCount(1);
});
