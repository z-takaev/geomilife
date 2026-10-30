<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Livewire\Favorites;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Favorites\FavoriteService;
use App\Support\Values\Money;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

function favoriteProduct(string $name = 'Масло'): Product
{
    $category = Category::query()->firstOrCreate(['slug' => 'favorites'], ['name' => 'Масла', 'is_active' => true]);
    $product = Product::query()->create(['category_id' => $category->id, 'name' => $name, 'slug' => (string) Str::uuid(), 'is_active' => true]);
    $product->variants()->create(['sku' => (string) Str::uuid(), 'unit' => ProductUnit::MILLILITER, 'value' => 250, 'price' => Money::fromRubles(890), 'is_active' => true]);

    return $product;
}

function favoriteUser(): User
{
    return User::query()->create(['first_name' => 'Покупатель', 'email' => Str::uuid().'@example.com', 'password' => 'Password123']);
}

it('возвращает гостю список и уведомление без записи в базу', function (): void {
    $product = favoriteProduct();
    Livewire::test(Favorites::class)->call('initialize', [])
        ->call('toggle', $product->id)->assertSee($product->name)
        ->assertDispatched('notify', message: 'Товар добавлен в избранное.', type: 'success')
        ->assertDispatched('favorites-updated', ids: [$product->id]);
    $this->assertDatabaseCount('favorites', 0);
});

it('принимает список браузера и удаляет гостевой товар', function (): void {
    $product = favoriteProduct();
    Livewire::test(Favorites::class)->call('initialize', [$product->id])
        ->assertSee($product->name)->call('remove', $product->id)
        ->assertSee('В избранном пока нет товаров.')->assertDispatched('favorites-updated', ids: []);
    $this->assertDatabaseCount('favorites', 0);
});

it('добавляет и удаляет товар аккаунта повторным нажатием сердца', function (): void {
    $user = favoriteUser();
    $product = favoriteProduct();

    $component = Livewire::actingAs($user)->test(Favorites::class)
        ->call('toggle', $product->id)->assertSee($product->name);
    expect($user->favorites()->pluck('product_id')->all())->toBe([$product->id]);
    $component->call('toggle', $product->id)->assertSee('В избранном пока нет товаров.');
    expect($user->favorites()->count())->toBe(0);
});

it('не создаёт дубли при повторном добавлении через сервис', function (): void {
    $user = favoriteUser();
    $this->actingAs($user);
    $product = favoriteProduct();
    app(FavoriteService::class)->add($product->id);
    app(FavoriteService::class)->add($product->id);
    expect($user->favorites()->count())->toBe(1);
});

it('автоматически переносит гостевой список в пустой аккаунт', function (): void {
    $product = favoriteProduct();
    $user = favoriteUser();

    Livewire::actingAs($user)->test(Favorites::class)
        ->call('initialize', [$product->id])->assertNotDispatched('favorites-merge-suggested')
        ->assertDispatched('favorites-merged')->assertSee($product->name);

    expect($user->favorites()->pluck('product_id')->all())->toBe([$product->id]);
});

it('предлагает объединение и переносит товары без дублей только после согласия', function (): void {
    $user = favoriteUser();
    $existing = favoriteProduct('Масло льняное');
    $new = favoriteProduct('Масло тмина');
    $user->favorites()->create(['product_id' => $existing->id]);

    $component = Livewire::actingAs($user)->test(Favorites::class)
        ->call('initialize', [$existing->id, $new->id])->assertDispatched('favorites-merge-suggested')->assertDontSee($new->name);
    $component->assertSet('guestIds', [$existing->id, $new->id]);
    $component->call('merge')->assertSet('guestIds', [])->assertDispatched('favorites-merged')->assertSee($new->name)
        ->assertDispatched('favorites-merge-resolved');
    expect($user->favorites()->count())->toBe(2);
});

it('при отказе сохраняет оба списка и не спрашивает повторно в текущем входе', function (): void {
    $user = favoriteUser();
    $existing = favoriteProduct('Масло льняное');
    $new = favoriteProduct('Масло тмина');
    $user->favorites()->create(['product_id' => $existing->id]);
    Livewire::actingAs($user)->test(Favorites::class)
        ->call('initialize', [$new->id])->call('decline')->assertSet('guestIds', [$new->id])->assertNotDispatched('favorites-merged');

    expect($user->favorites()->pluck('product_id')->all())->toBe([$existing->id]);
    Livewire::test(Favorites::class)->call('initialize', [$new->id])->assertNotDispatched('favorites-merge-suggested');
});

it('очищает список браузера от дублей и недоступных товаров', function (): void {
    $product = favoriteProduct();
    $hidden = favoriteProduct('Скрытый товар');
    $hidden->update(['is_active' => false]);
    Livewire::test(Favorites::class)->call('initialize', [$product->id, $product->id, $hidden->id, 999999])
        ->assertSet('guestIds', [$product->id])->assertDispatched('favorites-updated', ids: [$product->id]);
});

it('проверяет идентификаторы из браузера', function (): void {
    Livewire::test(Favorites::class)->call('initialize', ['invalid'])->assertHasErrors('ids.0');
    $this->assertDatabaseCount('favorites', 0);
});

it('не позволяет добавить скрытый товар', function (): void {
    $product = favoriteProduct();
    $product->update(['is_active' => false]);
    Livewire::test(Favorites::class)->call('toggle', $product->id)->assertNotFound();
    $this->assertDatabaseCount('favorites', 0);
});

it('удаление не затрагивает избранное другого пользователя', function (): void {
    $owner = favoriteUser();
    $other = favoriteUser();
    $product = favoriteProduct();
    $owner->favorites()->create(['product_id' => $product->id]);
    Livewire::actingAs($other)->test(Favorites::class)->call('remove', $product->id);
    expect($owner->favorites()->count())->toBe(1);
});

it('повторный вход сбрасывает отказ от объединения', function (): void {
    $user = favoriteUser();
    $this->withSession(['favorites.merge_declined' => $user->id])
        ->post(route('login'), ['email' => $user->email, 'password' => 'Password123'])
        ->assertSessionMissing('favorites.merge_declined');
    $this->assertAuthenticatedAs($user);
});

it('после входа показывает предложение объединения на следующей странице', function (): void {
    $user = favoriteUser();
    $existing = favoriteProduct('Масло льняное');
    $new = favoriteProduct('Масло тмина');
    $user->favorites()->create(['product_id' => $existing->id]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123'])
        ->assertRedirect();
    $this->get(route('verification.notice'))->assertOk();
    Livewire::test(Favorites::class)->call('initialize', [$new->id])->assertDispatched('favorites-merge-suggested');
    expect($user->favorites()->count())->toBe(1);
});

it('переносит гостевое избранное после регистрации', function (): void {
    Notification::fake();
    $product = favoriteProduct();

    $this->post(route('register'), [
        'first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => 'favorite-registration@example.com',
        'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => true,
    ])->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))->assertOk();

    Livewire::test(Favorites::class)->call('initialize', [$product->id])->assertDispatched('favorites-merged');
    $user = User::query()->where('email', 'favorite-registration@example.com')->sole();
    expect($user->favorites()->pluck('product_id')->all())->toBe([$product->id]);
});

it('не разрешает гостю объединять избранное', function (): void {
    Livewire::test(Favorites::class)->call('merge')->assertForbidden();
});

it('не перерисовывает список аккаунта при предложении объединения и отказе', function (): void {
    $user = favoriteUser();
    $existing = favoriteProduct('Масло льняное');
    $guest = favoriteProduct('Масло тмина');
    $user->favorites()->create(['product_id' => $existing->id]);

    Livewire::actingAs($user)->test(Favorites::class)
        ->call('initialize', [$guest->id])->assertRenderSkipped()
        ->assertDispatched('favorites-merge-suggested')
        ->call('decline')->assertRenderSkipped()
        ->assertDispatched('favorites-merge-resolved');
});
