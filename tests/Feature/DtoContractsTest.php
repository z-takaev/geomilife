<?php

declare(strict_types=1);

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\DTO\Cdek\CdekOrderItemDto;
use App\DTO\Cdek\CdekPaymentDto;
use App\Services\Cdek\CdekResponseMapper;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Services\Orders\CheckoutService;
use App\Services\Tbank\TbankResponseMapper;
use App\Services\Tbank\TbankService;
use App\Support\Values\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\CheckoutFixtures;

it('преобразует рублёвые суммы расчёта СДЭК в Money с округлением до копеек', function (): void {
    $result = app(CdekResponseMapper::class)->calculation([
        'delivery_sum' => 123.45, 'total_sum' => 150.555, 'currency' => 'RUB',
        'period_min' => 2, 'period_max' => 4, 'weight_calc' => 1000,
    ]);

    expect($result->deliverySum->minorAmount())->toBe(12345)
        ->and($result->totalSum->minorAmount())->toBe(15056);
});

it('отклоняет некорректные денежные данные расчёта СДЭК', function (string $field, mixed $value): void {
    $data = [
        'delivery_sum' => 100, 'total_sum' => 150, 'currency' => 'RUB',
        'period_min' => 2, 'period_max' => 4, 'weight_calc' => 1000,
    ];
    $data[$field] = $value;

    expect(fn () => app(CdekResponseMapper::class)->calculation($data))->toThrow(CdekApiException::class);
})->with([
    ['currency', 'USD'], ['delivery_sum', -1], ['total_sum', -0.001],
    ['total_sum', INF], ['total_sum', NAN], ['total_sum', 1e30],
    ['total_sum', '150.00'], ['total_sum', null],
]);

it('сериализует денежные поля СДЭК в рублях и сохраняет нулевые суммы', function (): void {
    $payment = new CdekPaymentDto(value: Money::fromMinor(0), vatSum: Money::fromMinor(123), vatRate: 0);
    $item = new CdekOrderItemDto(
        name: 'Товар', wareKey: 'SKU', payment: $payment,
        weight: 100, amount: 2, cost: Money::fromMinor(12345),
    );

    expect($payment->toArray())->toBe(['value' => 0.0, 'vat_sum' => 1.23, 'vat_rate' => 0])
        ->and($item->toArray()['cost'])->toBe(123.45)
        ->and((new CdekPaymentDto(value: Money::fromMinor(0)))->toArray())->toBe(['value' => 0.0]);
});

it('требует сумму НДС при указании ставки в платеже СДЭК', function (): void {
    expect(fn () => new CdekPaymentDto(value: Money::fromMinor(100), vatRate: 22))
        ->toThrow(InvalidArgumentException::class);
});

it('преобразует суммы отмены Т-Банка из копеек в Money', function (): void {
    $mapper = app(TbankResponseMapper::class);
    $result = $mapper->cancelPayment([
        'PaymentId' => '123', 'Status' => 'REFUNDED', 'OriginalAmount' => 12345, 'NewAmount' => 0,
    ]);
    $withoutAmounts = $mapper->cancelPayment(['PaymentId' => '123', 'Status' => 'CANCELED']);

    expect($result->originalAmount->minorAmount())->toBe(12345)
        ->and($result->newAmount->minorAmount())->toBe(0)
        ->and($withoutAmounts->originalAmount)->toBeNull()
        ->and($withoutAmounts->newAmount)->toBeNull();
});

it('проверяет идентификаторы Т-Банка до отправки запроса', function (string $method, string $identifier): void {
    Http::fake();

    expect(fn () => app(TbankService::class)->{$method}($identifier))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([
    ['getPaymentState', ''], ['getPaymentState', '123abc'], ['getPaymentState', str_repeat('9', 21)],
    ['checkOrder', ' '], ['checkOrder', str_repeat('я', 51)],
]);

it('отправляет строковые идентификаторы Т-Банка без изменения', function (): void {
    config(['tbank.base_url' => 'https://bank.test', 'tbank.terminal_key' => 'TEST', 'tbank.password' => 'secret']);
    Http::preventStrayRequests();
    Http::fake([
        'bank.test/GetState' => Http::response([
            'Success' => true, 'ErrorCode' => '0', 'PaymentId' => '000123',
            'OrderId' => 'order-1', 'Amount' => 12345, 'Status' => 'NEW',
        ]),
        'bank.test/CheckOrder' => Http::response([
            'Success' => true, 'ErrorCode' => '0', 'OrderId' => 'order-1', 'Payments' => [],
        ]),
    ]);

    expect(app(TbankService::class)->getPaymentState('000123')->paymentId)->toBe('000123')
        ->and(app(TbankService::class)->checkOrder('order-1')->orderId)->toBe('order-1');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/GetState') && $request['PaymentId'] === '000123');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/CheckOrder') && $request['OrderId'] === 'order-1');
});

it('перезагружает кеш ПВЗ с объектами прежнего формата', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44]);
    $cacheKey = 'checkout:cdek:points:44:PVZ';
    Cache::put($cacheKey, [new stdClass], 300);
    config(['services.cdek.base_url' => 'https://cdek.test', 'services.cdek.client_id' => 'test', 'services.cdek.client_secret' => 'secret']);
    Http::preventStrayRequests();
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([[
            'code' => 'MSK1', 'type' => 'PVZ', 'work_time' => '09:00–20:00',
            'location' => ['city_code' => 44, 'address_full' => 'Москва, ул. Примерная, 1', 'latitude' => 55.75, 'longitude' => 37.61],
        ]]),
    ]);

    $points = app(CheckoutService::class)->deliveryPoints($city);

    expect($points)->toHaveCount(1)
        ->and($points[0]->code)->toBe('MSK1')
        ->and(Cache::get($cacheKey)[0])->toBeInstanceOf(CdekDeliveryPointResponseDto::class);
});
