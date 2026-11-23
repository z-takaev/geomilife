<?php

declare(strict_types=1);

use App\Models\City;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Support\Facades\Validator;

it('принимает поддерживаемые форматы российского телефона', function (string $phone): void {
    $validator = Validator::make(['phone' => $phone], ['phone' => [new PhoneNumber]]);

    expect($validator->passes())->toBeTrue();
})->with([
    '+7 с маской' => '+7 (999) 123-45-67',
    '+7 без маски' => '+79991234567',
    'через 8' => '89991234567',
    'десять цифр' => '9991234567',
]);

it('отклоняет некорректные российские телефоны', function (mixed $phone): void {
    $validator = Validator::make(['phone' => $phone], ['phone' => [new PhoneNumber]]);

    expect($validator->fails())->toBeTrue();
})->with([
    'короткий номер' => '999123456',
    'другая страна' => '+375291234567',
    'текст' => 'не телефон',
    'текст с номером' => 'телефон +7 (999) 123-45-67',
    'плюс восемь' => '+89991234567',
    'не строка' => 79991234567,
]);

it('нормализует телефоны при записи в модели', function (): void {
    $user = new User(['phone' => '+7 (999) 123-45-67']);
    $setting = new Setting(['phone' => '89991234567']);
    $city = new City(['pickup_phone' => '9991234567']);
    $order = new Order(['customer_phone' => '+79991234567']);

    expect($user->phone)->toBe('+79991234567')
        ->and($setting->phone)->toBe('+79991234567')
        ->and($city->pickup_phone)->toBe('+79991234567')
        ->and($order->customer_phone)->toBe('+79991234567');
});

it('возвращает существующий телефон в каноническом формате без миграции', function (): void {
    $setting = (new Setting)->newFromBuilder(['phone' => '+7 (999) 123-45-67']);

    expect($setting->phone)->toBe('+79991234567');
});

it('не позволяет записать некорректный телефон в модель', function (): void {
    expect(fn (): User => new User(['phone' => '123']))
        ->toThrow(InvalidArgumentException::class, 'Некорректный номер телефона.');
});

it('сохраняет пустой необязательный телефон как null', function (): void {
    expect((new City(['pickup_phone' => '']))->pickup_phone)->toBeNull();
});
