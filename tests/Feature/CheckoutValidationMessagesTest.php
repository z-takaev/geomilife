<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Livewire\Checkout;
use App\Models\ShippingBox;
use App\Services\Cart\CartService;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('выводит причину отказа в общем списке ошибок без отдельной подсказки над кнопкой', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $component = Livewire::test(Checkout::class)->assertDontSee('Выберите город для доставки.')
        ->call('placeOrder')->assertHasErrors(['city_id'])->assertSee('Выберите город для доставки.')
        ->assertDontSeeHtml('id="checkout-block-reason"');
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$component->html());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//div[contains(@class, "checkout-errors")]//li[contains(., "Выберите город для доставки.")]')->length)->toBe(1)
        ->and($xpath->query('//aside//p[contains(., "Выберите город для доставки.")]')->length)->toBe(0);
});

it('переводит название каждого поля оформления в сообщениях валидации', function (string $field): void {
    $attribute = __('validation.attributes.'.$field);
    expect($attribute)->not->toBe('validation.attributes.'.$field)->toMatch('/[а-яё]/ui');
    $validator = Validator::make([], [$field => ['required']]);
    expect($validator->errors()->first($field))->toContain($attribute)->not->toContain($field);
})->with([
    'first_name', 'last_name', 'phone', 'email', 'city_id', 'delivery_method', 'payment_method',
    'agreement', 'address_id', 'street', 'house', 'building', 'entrance', 'floor', 'apartment',
    'intercom', 'delivery_to_door', 'comment', 'delivery_point_code', 'idempotency_key',
]);

it('показывает русские названия контактных и адресных полей при отправке формы', function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    CheckoutFixtures::form($city, ['first_name' => '', 'last_name' => '', 'email' => 'invalid',
        'delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => '', 'house' => '',
        'delivery_to_door' => true, 'floor' => 'wrong', 'apartment' => '', 'agreement' => false])
        ->call('placeOrder')->assertHasErrors(['first_name', 'last_name', 'email', 'street', 'house', 'floor', 'apartment', 'agreement'])
        ->assertSee('Необходимо согласиться с политикой обработки персональных данных.')
        ->assertSee('Поле имя обязательно для заполнения.')
        ->assertSee('Значение поля этаж должно быть целым числом.');
});

it('сохраняет ошибку расчёта доставки в общем списке при отправке формы', function (): void {
    Notification::fake();
    Queue::fake();
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false,
        'is_pickup_available' => false, 'is_courier_available' => false]);
    ShippingBox::query()->create(['name' => 'Коробка', 'length' => 100, 'width' => 100, 'height' => 100, 'weight' => 10, 'is_active' => true]);
    app(CartService::class)->add(CheckoutFixtures::variant(['weight' => 200, 'length' => 10, 'width' => 10, 'height' => 10])->id, 1);
    CheckoutFixtures::fakeCdek($city, 35000, static function (): void {
        throw new CdekApiException('Сервис временно недоступен.');
    });
    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('selectDeliveryPoint', 'MSK100')->assertHasErrors(['quote'])
        ->call('placeOrder')->assertHasErrors(['quote'])
        ->assertSee('Не удалось рассчитать СДЭК. Повторите попытку или выберите другой способ получения.')
        ->assertDontSeeHtml('id="checkout-block-reason"');
    $this->assertDatabaseCount('orders', 0);
});
