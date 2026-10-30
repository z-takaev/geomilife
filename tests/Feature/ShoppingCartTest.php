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
    Livewire::test(ShoppingCart::class)->call('add', $variant->id, 2)->assertSee('1 780 ₽')->assertSee('2');
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
