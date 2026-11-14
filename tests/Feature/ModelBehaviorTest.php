<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\City;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\Values\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CheckoutFixtures;

it('генерирует уникальные адреса и сохраняет существующий slug при переименовании', function (): void {
    $first = Article::query()->create(['published_at' => today(), 'title' => 'О компании']);
    $second = Article::query()->create(['published_at' => today(), 'title' => 'О компании']);
    expect($first->slug)->toBe('o-kompanii')->and($second->slug)->toBe('o-kompanii-2');
    $first->update(['title' => 'Новый заголовок']);
    expect($first->fresh()->slug)->toBe('o-kompanii');
});

it('уважает явно заданный slug', function (): void {
    $article = Article::query()->create(['published_at' => today(), 'title' => 'О компании', 'slug' => 'about']);
    expect($article->slug)->toBe('about');
});

it('нормализует email во всех сохраняющих их моделях', function (): void {
    $user = new User(['email' => ' USER@EXAMPLE.COM ']);
    $filamentUser = new FilamentUser(['email' => ' ADMIN@EXAMPLE.COM ']);
    $setting = new Setting(['email' => ' STORE@EXAMPLE.COM ', 'admin_email' => ' OWNER@EXAMPLE.COM ']);
    $order = new Order(['customer_email' => ' CUSTOMER@EXAMPLE.COM ']);

    expect($user->email)->toBe('user@example.com')
        ->and($filamentUser->email)->toBe('admin@example.com')
        ->and($setting->email)->toBe('store@example.com')
        ->and($setting->admin_email)->toBe('owner@example.com')
        ->and($order->customer_email)->toBe('customer@example.com');
});

it('сохраняет деньги в копейках и сериализует в рублях', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'courier_delivery_price' => Money::fromRubles('123.45')]);
    expect($city->fresh()->courier_delivery_price->minorAmount())->toBe(12345)
        ->and($city->fresh()->getRawOriginal('courier_delivery_price'))->toBe(12345)
        ->and($city->fresh()->toArray()['courier_delivery_price'])->toBe('123.45');
});

it('сбрасывает кеш настроек после сохранения', function (): void {
    $setting = Setting::query()->create(['phone' => '111']);
    expect(Setting::cached()->phone)->toBe('111');
    $setting->update(['phone' => '222']);
    expect(Setting::cached()->phone)->toBe('222');
});

it('сохраняет и сериализует незаданные денежные поля как null', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'courier_delivery_price' => null]);
    expect($city->fresh()->courier_delivery_price)->toBeNull()
        ->and($city->fresh()->toArray()['courier_delivery_price'])->toBeNull();
});

it('исключает недоступные варианты из выборки для покупки', function (string $unavailable): void {
    $available = CheckoutFixtures::variant();
    $hidden = CheckoutFixtures::variant();

    match ($unavailable) {
        'variant' => $hidden->update(['is_active' => false]),
        'product' => $hidden->product->update(['is_active' => false]),
        'category' => $hidden->product->category->update(['is_active' => false]),
    };

    $expected = $unavailable === 'category' ? [] : [$available->id];

    expect(ProductVariant::query()->available()->pluck('id')->all())->toBe($expected);
})->with(['variant', 'product', 'category']);

it('задаёт ключ идемпотентности при создании заказа', function (): void {
    $this->freezeSecond();
    $order = CheckoutFixtures::order(attributes: [
        'idempotency_key' => null,
        'payment_method' => 'ONLINE',
    ]);

    expect(Str::isUuid($order->idempotency_key))->toBeTrue();
});

it('определяет возможность удаления характеристики без лишнего запроса', function (): void {
    $attribute = ProductAttribute::query()->create(['name' => 'Вкус']);
    $product = CheckoutFixtures::variant()->product;
    $attribute->productValues()->create([
        'product_id' => $product->getKey(),
        'value' => 'Ореховый',
    ]);

    expect($attribute->canBeDeleted())->toBeFalse();

    $attribute = ProductAttribute::query()->withCount('productValues')->findOrFail($attribute->getKey());
    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($attribute->canBeDeleted())->toBeFalse()
        ->and(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});
