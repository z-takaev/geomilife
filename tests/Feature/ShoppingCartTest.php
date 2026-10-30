<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Livewire\ShoppingCart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Values\Money;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function cartVariant(bool $preorder = false, int $rubles = 890): ProductVariant
{
    $category = Category::query()->firstOrCreate(['slug' => 'cart'], ['name' => 'Категория', 'is_active' => true]);
    $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Товар', 'slug' => (string) Str::uuid(), 'is_active' => true]);

    return $product->variants()->create(['sku' => (string) Str::uuid(), 'unit' => ProductUnit::MILLILITER, 'value' => 250, 'price' => Money::fromRubles($rubles), 'is_active' => true, 'is_preorder' => $preorder]);
}

function cartUser(): User
{
    return User::query()->create(['first_name' => 'Покупатель', 'email' => Str::uuid().'@example.com', 'password' => 'Password123']);
}

it('хранит гостевую корзину в сессии и считает количество и итог', function (): void {
    $variant = cartVariant();
    Livewire::test(ShoppingCart::class)->call('add', $variant->id, 2)->assertViewHas('count', 2)->assertViewHas('total', fn (Money $total): bool => $total->toRubles() === '1780.00')
        ->assertDispatched('cart-updated', count: 2);
    expect(session('cart.items'))->toBe([$variant->id => 2]);
    expect(app(CartService::class)->count())->toBe(2);
    $this->assertDatabaseCount('carts', 0);
    Livewire::test(ShoppingCart::class)->call('remove', $variant->id)->assertSee('Корзина пока пуста.');
});

it('сохраняет вариации пользователя в базе и увеличивает количество без дублей', function (): void {
    $variant = cartVariant();
    $user = cartUser();
    Livewire::actingAs($user)->test(ShoppingCart::class)->call('add', $variant->id, 2)->call('add', $variant->id, 3)->assertSee('4 450 ₽');
    expect($user->cart->items()->first()->quantity)->toBe(5);
    $this->assertDatabaseCount('cart_items', 1);
});

it('не смешивает товар в наличии и под заказ', function (): void {
    $available = cartVariant();
    $preorder = cartVariant(true);
    $cart = app(CartService::class);
    $cart->add($available->id, 1);
    expect(fn () => $cart->add($preorder->id, 1))->toThrow(ValidationException::class);
    expect($cart->quantities())->toBe([$available->id => 1]);
});

it('предлагает объединить корзины и складывает количества после согласия', function (): void {
    $variant = cartVariant();
    $user = cartUser();
    app(CartService::class)->add($variant->id, 2);
    $this->actingAs($user);
    app(CartService::class)->add($variant->id, 1);
    Livewire::actingAs($user)->test(ShoppingCart::class)->assertDispatched('cart-merge-suggested')->call('merge')->assertSee('2 670 ₽');
    expect(session('cart.items'))->toBeNull();
    expect($user->cart->items()->first()->quantity)->toBe(3);
});

it('отклоняет объединение при конфликте типов и сохраняет обе корзины', function (): void {
    $available = cartVariant();
    $preorder = cartVariant(true);
    $user = cartUser();
    app(CartService::class)->add($preorder->id, 1);
    $this->actingAs($user);
    app(CartService::class)->add($available->id, 1);
    Livewire::actingAs($user)->test(ShoppingCart::class)->call('merge')->assertDispatched('notify', message: 'В корзине нельзя смешивать товары в наличии и под заказ.', type: 'error');
    expect(session('cart.items'))->toBe([$preorder->id => 1]);
    expect($user->cart->items()->count())->toBe(1);
});

it('отклоняет количество за пределами лимитов без записи', function (int $quantity, bool $authenticated): void {
    $variant = cartVariant();
    if ($authenticated) {
        $this->actingAs(cartUser());
    }
    $cart = app(CartService::class);
    expect(fn () => $cart->add($variant->id, $quantity))->toThrow(ValidationException::class);
    expect($cart->quantities())->toBe([]);
    $this->assertDatabaseCount('cart_items', 0);
})->with([0, -1, 1001])->with([false, true]);

it('не превышает лимит при повторном добавлении', function (bool $authenticated): void {
    $variant = cartVariant();
    if ($authenticated) {
        $this->actingAs(cartUser());
    }
    $cart = app(CartService::class);
    $cart->add($variant->id, 1000);
    expect(fn () => $cart->add($variant->id, 1))->toThrow(ValidationException::class);
    expect($cart->quantities())->toBe([$variant->id => 1000]);
})->with([false, true]);

it('не добавляет отключенную фасовку, товар или товар скрытой категории', function (string $target): void {
    $variant = cartVariant();
    match ($target) {
        'variant' => $variant->update(['is_active' => false]),
        'product' => $variant->product->update(['is_active' => false]),
        'category' => $variant->product->category->update(['is_active' => false]),
    };
    Livewire::test(ShoppingCart::class)->call('add', $variant->id, 1)->assertNotFound();
    expect(session('cart.items', []))->toBe([]);
})->with(['variant', 'product', 'category']);

it('считает итог по текущей цене со скидкой и исключает недоступные позиции', function (): void {
    $variant = cartVariant(false, 1000);
    $variant->update(['discount_percent' => 25]);
    $hidden = cartVariant();
    session()->put('cart.items', [$variant->id => 3, $hidden->id => 5]);
    $hidden->update(['is_active' => false]);
    $cart = app(CartService::class);
    expect($cart->count())->toBe(3)->and($cart->total()->toRubles())->toBe('2250.00');
    $cart->remove($variant->id);
    expect($cart->count())->toBe(0)->and($cart->total()->toRubles())->toBe('0.00');
});

it('удаляет товар только из корзины текущего пользователя', function (): void {
    $variant = cartVariant();
    $owner = cartUser();
    $other = cartUser();
    $owner->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);
    $other->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);
    $this->actingAs($other);
    app(CartService::class)->remove($variant->id);
    expect($owner->cart->items()->sole()->quantity)->toBe(2)
        ->and($other->cart->items()->count())->toBe(0);
});

it('не переносит часть корзины при превышении лимита объединения', function (): void {
    $first = cartVariant();
    $overflow = cartVariant();
    session()->put('cart.items', [$first->id => 2, $overflow->id => 2]);
    $user = cartUser();
    $this->actingAs($user);
    $cart = app(CartService::class);
    $cart->add($overflow->id, 999);
    expect(fn () => $cart->merge())->toThrow(ValidationException::class);
    expect($cart->quantities())->toBe([$overflow->id => 999])
        ->and(session('cart.items'))->toBe([$first->id => 2, $overflow->id => 2]);
});

it('после отказа сохраняет обе корзины и не предлагает перенос повторно', function (): void {
    $guest = cartVariant();
    $account = cartVariant();
    session()->put('cart.items', [$guest->id => 2]);
    $this->actingAs(cartUser());
    $cart = app(CartService::class);
    $cart->add($account->id, 1);
    $cart->declineMerge();
    expect($cart->shouldSuggestMerge())->toBeFalse()
        ->and($cart->quantities())->toBe([$account->id => 1])
        ->and(session('cart.items'))->toBe([$guest->id => 2]);
});

it('не разрешает гостю объединять корзину', function (): void {
    Livewire::test(ShoppingCart::class)->call('merge')->assertForbidden();
});

it('не объединяет смешанную гостевую корзину с пустым аккаунтом', function (): void {
    $available = cartVariant();
    $preorder = cartVariant(true);
    session()->put('cart.items', [$available->id => 1, $preorder->id => 1]);
    $this->actingAs(cartUser());
    $cart = app(CartService::class);
    expect(fn () => $cart->merge())->toThrow(ValidationException::class);
    expect($cart->quantities())->toBe([])
        ->and(session('cart.items'))->toBe([$available->id => 1, $preorder->id => 1]);
    $this->assertDatabaseCount('cart_items', 0);
});
