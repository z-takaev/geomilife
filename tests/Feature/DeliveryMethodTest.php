<?php

declare(strict_types=1);

use App\Enums\Orders\DeliveryMethodEnum;
use App\Services\Cart\CartService;
use App\Services\Orders\CheckoutService;
use Filament\Support\Contracts\HasLabel;
use Tests\Support\CheckoutFixtures;

it('предоставляет подписи способов доставки', function (): void {
    expect(DeliveryMethodEnum::PICKUP)->toBeInstanceOf(HasLabel::class)
        ->and(DeliveryMethodEnum::PICKUP->getLabel())->toBe('Самовывоз')
        ->and(DeliveryMethodEnum::COURIER->getLabel())->toBe('Курьер')
        ->and(DeliveryMethodEnum::CDEK->getLabel())->toBe('СДЭК');
});

it('ограничивает способы доставки настройками города', function (): void {
    $city = CheckoutFixtures::city(['is_courier_available' => false, 'is_cdek_excluded' => true]);

    expect(app(CheckoutService::class)->deliveryMethods($city))->toBe([
        DeliveryMethodEnum::PICKUP->value => DeliveryMethodEnum::PICKUP->getLabel(),
    ]);
});

it('отклоняет неизвестный способ доставки при оформлении', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    CheckoutFixtures::form($city, ['delivery_method' => 'UNKNOWN'])
        ->call('placeOrder')->assertHasErrors(['delivery_method']);
});

it('сохраняет способ доставки и восстанавливает enum', function (string $value): void {
    $method = DeliveryMethodEnum::from($value);
    $order = CheckoutFixtures::order(attributes: ['delivery_method' => $method]);

    expect($order->fresh()->delivery_method)->toBe($method);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'delivery_method' => $value]);
})->with(['PICKUP', 'COURIER', 'CDEK']);
