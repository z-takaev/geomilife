<?php

declare(strict_types=1);

use App\Actions\Orders\RepeatOrderAction;
use App\Enums\Orders\OrderStatusEnum;
use App\Http\Controllers\CheckoutController;
use App\Livewire\CitySelector;
use App\Livewire\RegistrationCitySelector;
use App\Livewire\UserProfile;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

it('выдаёт заказам числовые номера начиная с 1000', function (): void {
    $first = CheckoutFixtures::order();
    $second = CheckoutFixtures::order();

    expect($first->number)->toBeInt()->toBe($first->id + 999)
        ->and($second->number)->toBeInt()->toBe($first->number + 1);
});

it('показывает общие страницы результата без сведений о заказе', function (string $route, string $text): void {
    CheckoutFixtures::order(null, [
        'customer_email' => 'secret@example.test',
        'customer_name' => 'Секретный покупатель',
    ]);

    $this->get(route($route))->assertOk()
        ->assertSee($text)
        ->assertDontSee('secret@example.test')
        ->assertDontSee('Секретный покупатель')
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
})->with([
    ['checkout.success', 'Заказ успешно оформлен'],
    ['payment.success', 'Платёж завершён'],
    ['payment.fail', 'Оплата не завершена'],
]);

it('закрывает оформление заказа от индексации', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    $this->get(route('checkout'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('обрабатывает все страницы результата в контроллере оформления', function (string $route, string $method): void {
    expect(Route::getRoutes()->getByName($route)?->getActionName())
        ->toBe(CheckoutController::class.'@'.$method);
})->with([
    ['checkout.success', 'orderSuccess'],
    ['payment.success', 'paymentSuccess'],
    ['payment.fail', 'paymentFail'],
]);

it('использует общий лимит подсказок города', function (string $component, string $property, string $collection): void {
    config(['pagination.per_page.city_search' => 1]);
    $selectedCity = CheckoutFixtures::city([
        'cdek_code' => 1,
        'name' => 'Киров',
        'region' => 'Кировская область',
    ]);
    CheckoutFixtures::city([
        'cdek_code' => 2,
        'name' => 'Киров',
        'region' => 'Калужская область',
    ]);

    if ($component === UserProfile::class) {
        $this->actingAs(CheckoutFixtures::user(['city_id' => $selectedCity->id]));
    }

    Livewire::test($component)
        ->set($property, 'Киров')
        ->assertViewHas($collection, fn ($cities): bool => $cities->count() === 1);
})->with([
    [CitySelector::class, 'query', 'searchResults'],
    [RegistrationCitySelector::class, 'query', 'cities'],
    [UserProfile::class, 'city_search', 'cities'],
]);

it('подтверждает оплату на месте и выдачу одним действием', function (): void {
    $order = fulfillmentOrder([
        'delivery_method' => 'PICKUP',
        'payment_method' => 'PICKUP',
        'payment_status' => 'UNPAID',
        'status' => 'READY_FOR_PICKUP',
    ]);

    expect(app(OrderLifecycleService::class)->nextManualStatus($order))->toBe(OrderStatusEnum::DELIVERED);

    app(OrderLifecycleService::class)->advanceStatus($order, OrderStatusEnum::DELIVERED, fulfillmentEmployee());

    expect($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED)
        ->and($order->fresh()->payment_status->value)->toBe('PAID');

});

it('заменяет корзину владельца доступными товарами из заказа', function (): void {
    $user = CheckoutFixtures::user();
    $oldVariant = CheckoutFixtures::variant();
    $orderedVariant = CheckoutFixtures::variant();
    $this->actingAs($user);
    app(CartService::class)->add($oldVariant->id, 1);
    $order = CheckoutFixtures::order($user);
    $order->items()->create([
        'product_id' => $orderedVariant->product_id,
        'product_variant_id' => $orderedVariant->id,
        'product_name' => $orderedVariant->product->name,
        'variant_name' => $orderedVariant->label(),
        'sku' => $orderedVariant->sku,
        'price' => $orderedVariant->price,
        'quantity' => 3,
        'total' => $orderedVariant->price->multiply(3),
    ]);

    app(RepeatOrderAction::class)->execute($user, $order);

    expect(app(CartService::class)->quantities())->toBe([$orderedVariant->id => 3]);
});

it('не изменяет корзину если товар повторяемого заказа недоступен', function (): void {
    $user = CheckoutFixtures::user();
    $currentVariant = CheckoutFixtures::variant();
    $unavailableVariant = CheckoutFixtures::variant();
    $this->actingAs($user);
    app(CartService::class)->add($currentVariant->id, 2);
    $order = CheckoutFixtures::order($user);
    $order->items()->create([
        'product_id' => $unavailableVariant->product_id,
        'product_variant_id' => $unavailableVariant->id,
        'product_name' => $unavailableVariant->product->name,
        'variant_name' => $unavailableVariant->label(),
        'sku' => $unavailableVariant->sku,
        'price' => $unavailableVariant->price,
        'quantity' => 1,
        'total' => $unavailableVariant->price,
    ]);
    $unavailableVariant->update(['is_active' => false]);

    expect(fn () => app(RepeatOrderAction::class)->execute($user, $order))
        ->toThrow(ValidationException::class);
    expect(app(CartService::class)->quantities())->toBe([$currentVariant->id => 2]);
});

it('не позволяет повторить чужой заказ', function (): void {
    $owner = CheckoutFixtures::user();
    $other = CheckoutFixtures::user();
    $order = CheckoutFixtures::order($owner);

    $this->actingAs($other)
        ->post(route('orders.repeat', $order))
        ->assertNotFound();
});

it('повторяет свой заказ и переводит на оформление', function (): void {
    $user = CheckoutFixtures::user();
    $variant = CheckoutFixtures::variant();
    $order = CheckoutFixtures::order($user);
    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => $variant->product->name,
        'variant_name' => $variant->label(),
        'sku' => $variant->sku,
        'price' => $variant->price,
        'quantity' => 2,
        'total' => $variant->price->multiply(2),
    ]);

    $this->actingAs($user)
        ->post(route('orders.repeat', $order))
        ->assertRedirect(route('checkout'));

    expect($user->cart->items()->pluck('quantity', 'product_variant_id')->all())
        ->toBe([$variant->id => 2]);
});
