<?php

declare(strict_types=1);

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Enums\Orders\PaymentMethodEnum;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingBox;
use App\Services\Cart\CartService;
use App\Support\Values\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
    config([
        'services.cdek.base_url' => 'https://cdek.checkout.test/v2',
        'services.cdek.client_id' => 'client', 'services.cdek.client_secret' => 'secret',
        'tbank.base_url' => 'https://bank.checkout.test/v2', 'tbank.terminal_key' => 'terminal',
        'tbank.password' => 'secret',
    ]);
});

function fakeCdekCheckout(int $cityCode, float $delivery, ?Closure $onCalculation = null): void
{
    Http::fake(static function (Request $request) use ($cityCode, $delivery, $onCalculation) {
        if (str_contains($request->url(), 'oauth/token')) {
            return Http::response(['access_token' => 'token', 'expires_in' => 3600]);
        }
        if (str_contains($request->url(), 'deliverypoints')) {
            return Http::response([[
                'code' => 'MSK1', 'type' => 'PVZ', 'work_time' => 'Ежедневно 09:00–20:00',
                'location' => ['city_code' => $cityCode, 'address_full' => 'Москва, ул. Примерная, 1',
                    'latitude' => 55.75, 'longitude' => 37.61],
            ]]);
        }
        if (str_contains($request->url(), 'calculator/tariff')) {
            if ($onCalculation !== null) {
                $onCalculation();
            }

            return Http::response(['delivery_sum' => $delivery, 'total_sum' => $delivery,
                'currency' => 'RUB', 'period_min' => 2, 'period_max' => 4, 'weight_calc' => 600]);
        }
        if (str_ends_with($request->url(), '/Init')) {
            return Http::response(['Success' => true, 'ErrorCode' => '0', 'Status' => 'NEW',
                'PaymentId' => '1000001', 'OrderId' => $request['OrderId'], 'Amount' => $request['Amount'],
                'PaymentURL' => 'https://bank.checkout.test/pay/1000001']);
        }

        return Http::response([], 404);
    });
}

function addCdekCartItem(): ProductVariant
{
    ShippingBox::query()->create(['name' => 'Коробка', 'length' => 110, 'width' => 110,
        'height' => 210, 'weight' => 100, 'is_active' => true]);
    $variant = CheckoutFixtures::variant(['weight' => 500, 'length' => 80, 'width' => 80, 'height' => 150]);
    app(CartService::class)->add($variant->id, 1);

    return $variant;
}

it('оформляет СДЭК по тарифу 136 с округлённой доставкой и исходным расчётом в снимке', function (float $raw, int $rounded): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false, 'is_pickup_available' => false, 'is_courier_available' => false]);
    addCdekCartItem();
    fakeCdekCheckout(44, $raw);

    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('selectDeliveryPoint', 'MSK1')->call('placeOrder')->assertHasNoErrors();

    $order = Order::query()->sole();
    expect($order->delivery_price->minorAmount())->toBe($rounded * 100)
        ->and($order->total->minorAmount())->toBe((890 + $rounded) * 100)
        ->and($order->delivery_details['original_delivery_minor'])->toBe((int) round($raw * 100))
        ->and($order->delivery_address['code'])->toBe('MSK1')
        ->and($order->packages)->toHaveCount(1);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'calculator/tariff')
        && $request['tariff_code'] === 136 && $request['shipment_point'] === 'VLZ2');
    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_contains($request->url(), 'cdek.checkout.test') && str_ends_with($request->url(), '/orders'));
})->with([[350.49, 350], [350.50, 351]]);

it('не оформляет заказ по старой цене если товар изменён во время запроса расчёта СДЭК', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false, 'is_pickup_available' => false, 'is_courier_available' => false]);
    $variant = addCdekCartItem();
    $changePrice = false;
    fakeCdekCheckout(44, 350.50, static function () use (&$changePrice, $variant): void {
        if ($changePrice) {
            $variant->update(['price' => Money::fromRubles(999)]);
        }
    });
    $component = CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('selectDeliveryPoint', 'MSK1');
    $changePrice = true;

    $component->call('placeOrder');
    expect($variant->fresh()->price->minorAmount())->toBe(99900);
    $component->assertHasErrors(['quoted_total_minor']);
    $this->assertDatabaseCount('orders', 0);
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/Init'));
});

it('не показывает нулевую доставку и окончательный итог до выбора города', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    Livewire::test(Checkout::class)
        ->assertSee('Требуется расчёт')->assertSee('После расчёта');
});

it('выбирает ПВЗ после повторного чтения из кеша базы данных', function (): void {
    config(['cache.default' => 'database']);
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false,
        'is_pickup_available' => false, 'is_courier_available' => false]);
    addCdekCartItem();
    fakeCdekCheckout(44, 350);

    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('selectDeliveryPoint', 'MSK1')
        ->assertHasNoErrors()
        ->assertSet('delivery_point_code', 'MSK1')
        ->assertSet('quoted_total_minor', 124000)
        ->call('recalculate')->assertHasNoErrors();
});

it('ищет ПВЗ по всему списку и показывает все совпадения после ввода', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false,
        'is_pickup_available' => false, 'is_courier_available' => false]);
    addCdekCartItem();
    $points = array_map(fn (int $index) => new CdekDeliveryPointResponseDto(
        code: 'MSK'.$index, type: 'PVZ',
        cityCode: 44, address: 'Москва, Примерная, '.$index, latitude: 55.75, longitude: 37.61,
        workTime: '09:00–20:00',
    ), range(1, 12));
    Cache::put('checkout:cdek:points:44:PVZ', $points, 300);
    fakeCdekCheckout(44, 350);

    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->assertViewHas('deliveryPoints', fn ($points): bool => $points->isEmpty())
        ->set('delivery_point_search', 'Примерная')
        ->assertViewHas('deliveryPoints', fn ($points): bool => $points->count() === 12)
        ->set('delivery_point_search', 'MSK12')
        ->assertViewHas('deliveryPoints', fn ($points): bool => $points->pluck('code')->all() === ['MSK12'])
        ->call('selectDeliveryPoint', 'MSK12')->assertHasNoErrors()
        ->set('delivery_point_search', '')
        ->assertViewHas('deliveryPoints', fn ($points): bool => $points->isEmpty())
        ->assertSet('delivery_point_code', 'MSK12');
});

it('восстанавливает расчёт после выбора недоступного ПВЗ и сохраняет выбор при очистке поиска', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false,
        'is_pickup_available' => false, 'is_courier_available' => false]);
    addCdekCartItem();
    fakeCdekCheckout(44, 350);

    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('selectDeliveryPoint', 'UNAVAILABLE')->assertHasErrors(['delivery_point_code'])
        ->call('selectDeliveryPoint', 'MSK1')->assertHasNoErrors()
        ->set('delivery_point_search', '')
        ->assertViewHas('selectedDeliveryPoint', fn ($point): bool => $point['code'] === 'MSK1')
        ->assertViewHas('deliveryReady', true)
        ->assertSet('quoted_total_minor', 124000);
});
