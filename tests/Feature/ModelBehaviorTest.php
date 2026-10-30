<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\City;
use App\Models\Setting;
use App\Support\Values\Money;

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
