<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Support\Values\Money;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    CheckoutFixtures::fakeBank();
});

it('создаёт заказ без хранения отдельного подтверждения согласия', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    expect(Schema::hasColumn('orders', 'consent'))->toBeFalse()
        ->and(Order::query()->sole()->getAttributes())->not->toHaveKey('consent');
});

it('создаёт позицию заказа без неиспользуемого снимка габаритов', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    expect(Schema::hasColumn('order_items', 'transport'))->toBeFalse()
        ->and(Order::query()->sole()->items()->sole()->getAttributes())->not->toHaveKey('transport');
});

it('оформляет гостевой самовывоз по актуальной цене без повторного вычета старой цены', function (): void {
    $city = CheckoutFixtures::city();
    $variant = CheckoutFixtures::variant(['price' => Money::fromRubles(750), 'old_price' => Money::fromRubles(1000)]);
    app(CartService::class)->add($variant->getKey(), 2);

    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    $order = Order::query()->sole();
    $item = $order->items()->sole();
    expect($order->user_id)->toBeNull()
        ->and($order->subtotal->toRubles())->toBe('1500.00')
        ->and($order->delivery_price->toRubles())->toBe('0.00')
        ->and($order->total->toRubles())->toBe('1500.00')
        ->and($item->price->toRubles())->toBe('750.00')
        ->and($item->old_price->toRubles())->toBe('1000.00')
        ->and($item->quantity)->toBe(2)
        ->and($item->total->toRubles())->toBe('1500.00')
        ->and($order->customer_name)->toBe('Анна Петрова');
    $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'status' => 'NEW', 'payment_method' => PaymentMethodEnum::PICKUP->value]);
    $this->assertDatabaseCount('users', 0);
    expect(app(CartService::class)->quantities())->toBe([]);
    Http::assertNothingSent();
});

it('подставляет профиль и сохраняет другого получателя без изменения аккаунта', function (): void {
    $city = CheckoutFixtures::city();
    $user = CheckoutFixtures::user(['city_id' => $city->getKey()]);
    $original = $user->only(['first_name', 'last_name', 'phone', 'email']);
    $this->actingAs($user);
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    Livewire::test(Checkout::class)->assertSet('form.first_name', $user->first_name)
        ->assertSet('form.last_name', $user->last_name)->assertSet('form.phone', $user->phone)->assertSet('form.email', $user->email);

    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    expect($user->fresh()->only(array_keys($original)))->toBe($original)
        ->and($user->orders()->sole()->customer_email)->toBe('recipient@example.com');
});

it('не связывает гостевой заказ с аккаунтом по совпадению email', function (): void {
    $user = CheckoutFixtures::user(['email' => 'recipient@example.com']);
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();
    expect(Order::query()->sole()->user_id)->toBeNull()->and($user->orders()->count())->toBe(0);
});

it('повтор одного оформления возвращает существующий заказ без дубля', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($city);
    $key = $component->get('idempotency_key');
    $component->call('placeOrder')->assertHasNoErrors()->call('placeOrder')->assertHasNoErrors();
    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseCount('order_items', 1);
    expect(Order::query()->sole()->idempotency_key)->toBe($key);
});

it('освобождает блокировку после оформления заказа', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($city);
    $key = $component->get('idempotency_key');

    $component->call('placeOrder')->assertHasNoErrors();

    expect((int) DB::scalar('SELECT IS_FREE_LOCK(?)', ['checkout:'.$key]))->toBe(1);
});

it('отклоняет пустую корзину без сохранения заказа', function (): void {
    CheckoutFixtures::form(CheckoutFixtures::city())->call('placeOrder')->assertHasErrors(['cart']);
    $this->assertDatabaseCount('orders', 0);
    Http::assertNothingSent();
});

it('проверяет контакты и согласие до создания заказа и сохраняет корзину', function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => 'broken', 'agreement' => false])
        ->call('placeOrder')->assertHasErrors(['form.first_name', 'form.last_name', 'form.phone', 'form.email', 'form.agreement']);
    $this->assertDatabaseCount('orders', 0);
    expect(app(CartService::class)->count())->toBe(1);
});

it('требует подтверждения изменившейся цены вместо списания нового итога', function (): void {
    $city = CheckoutFixtures::city();
    $variant = CheckoutFixtures::variant();
    app(CartService::class)->add($variant->getKey(), 1);
    $component = CheckoutFixtures::form($city);
    $variant->update(['price' => Money::fromRubles(1200)]);
    $component->call('placeOrder')->assertHasErrors(['quoted_total_minor']);
    $this->assertDatabaseCount('orders', 0);
    expect(app(CartService::class)->count())->toBe(1);
    Http::assertNothingSent();
});

it('не принимает подменённый подтверждённый итог', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city)->set('quoted_total_minor', 1)->call('placeOrder')->assertHasErrors(['quoted_total_minor']);
    $this->assertDatabaseCount('orders', 0);
});

it('не исключает ставший недоступным товар молча при оформлении', function (): void {
    $city = CheckoutFixtures::city();
    $variant = CheckoutFixtures::variant();
    app(CartService::class)->add($variant->getKey(), 1);
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($city);
    $variant->update(['is_active' => false]);
    $component->call('placeOrder')->assertHasErrors(['cart']);
    $this->assertDatabaseCount('orders', 0);
    expect(session('cart.items'))->toHaveCount(2);
});

it('перепроверяет доступность самовывоза перед созданием', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($city);
    $city->update(['is_pickup_available' => false]);
    $component->call('placeOrder')->assertHasErrors(['form.delivery_method']);
    $this->assertDatabaseCount('orders', 0);
});

it('не оформляет получение в отключённом городе', function (): void {
    $city = CheckoutFixtures::city(['is_active' => false]);
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city)->call('placeOrder')->assertHasErrors(['form.city_id']);
    $this->assertDatabaseCount('orders', 0);
});

it('запрещает сдэк в городе исключения даже при подмене выбора', function (): void {
    $city = CheckoutFixtures::city(['is_cdek_excluded' => true]);
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'CDEK', 'payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('placeOrder')->assertHasErrors(['form.delivery_method']);
    $this->assertDatabaseCount('orders', 0);
});

it('запрещает оплату при получении для курьера', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'street' => 'Мира', 'house' => '10'])
        ->call('placeOrder')->assertHasErrors(['form.payment_method']);
    $this->assertDatabaseCount('orders', 0);
});

it('считает курьера с дверью и бесплатным первым этажом', function (int $floor, int $rubles): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '10', 'delivery_to_door' => true, 'floor' => (string) $floor, 'apartment' => '12'])
        ->call('placeOrder')->assertHasNoErrors();
    $order = Order::query()->sole();
    expect($order->delivery_price->toRubles())->toBe($rubles.'.00')->and($order->total->toRubles())->toBe((890 + $rubles).'.00');
})->with([[1, 400], [3, 500]]);

it('без услуги до двери не учитывает скрытый этаж и не требует квартиру или домофон', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '10', 'delivery_to_door' => false, 'floor' => '999', 'apartment' => '', 'intercom' => ''])
        ->call('placeOrder')->assertHasNoErrors();
    expect(Order::query()->sole()->delivery_price->toRubles())->toBe('300.00');
});

it('для услуги до двери требует положительный целый этаж', function (string $floor): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '10', 'delivery_to_door' => true, 'floor' => $floor])
        ->call('placeOrder')->assertHasErrors(['form.floor']);
    $this->assertDatabaseCount('orders', 0);
})->with(['', '0', '-1', '1.5']);

it('сохраняет снимки товара и самовывоза независимо от справочников', function (): void {
    $city = CheckoutFixtures::city();
    $variant = CheckoutFixtures::variant();
    app(CartService::class)->add($variant->getKey(), 1);
    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();
    $order = Order::query()->sole();
    $snapshot = $order->delivery_address;
    $name = $variant->product->name;
    $variant->update(['price' => Money::fromRubles(999)]);
    $variant->product->update(['name' => 'Другое название']);
    $city->update(['pickup_address' => 'Другой адрес']);
    expect($order->fresh()->delivery_address)->toBe($snapshot)
        ->and(json_encode($snapshot, JSON_UNESCAPED_UNICODE))->toContain('улица Тестовая, 15')
        ->and($order->items()->sole()->product_name)->toBe($name)
        ->and($order->items()->sole()->price->toRubles())->toBe('890.00');
});

it('показывает предупреждение предзаказа и сохраняет его признак', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant(['is_preorder' => true])->getKey(), 1);
    CheckoutFixtures::form($city)->assertSee('Товары в вашем заказе поставляются под заказ. Точные сроки доставки менеджер согласует с вами отдельно.')
        ->call('placeOrder')->assertHasNoErrors();
    expect(Order::query()->sole()->is_preorder)->toBeTrue();
});

it('курьерский адрес требует улицу и дом на оформлении', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => '', 'house' => ''])
        ->call('placeOrder')->assertHasErrors(['form.street', 'form.house']);
    $this->assertDatabaseCount('orders', 0);
});

it('использует одно поле поиска и выбора города без восстановления ссылки на оформлении', function (): void {
    Livewire::test(Checkout::class)
        ->assertSee('Город')
        ->assertDontSee('Найти город')
        ->assertDontSee('Город получения')
        ->assertDontSee('Уже оформили заказ, но потеряли ссылку?')
        ->assertSeeHtml('role="combobox"');
});

it('показывает выбранный город в поле поиска', function (): void {
    $city = CheckoutFixtures::city();

    Livewire::test(Checkout::class)
        ->call('selectCity', $city->id)
        ->assertSet('form.city_id', $city->id)
        ->assertSet('city_search', $city->name);
});

it('ищет активные города и сбрасывает прежний выбор при изменении запроса', function (): void {
    $selected = CheckoutFixtures::city();
    $matching = CheckoutFixtures::city(['name' => 'Владимир']);
    $inactive = CheckoutFixtures::city(['name' => 'Владимир отключённый', 'is_active' => false]);

    Livewire::test(Checkout::class)
        ->call('selectCity', $selected->id)
        ->set('city_search', 'владим')
        ->assertSet('form.city_id', null)
        ->assertSet('form.delivery_method', '')
        ->assertViewHas('cities', fn ($cities): bool => $cities->modelKeys() === [$matching->id])
        ->call('selectCity', $matching->id)
        ->assertSet('form.city_id', $matching->id)
        ->assertSet('city_search', $matching->name);
});

it('показывает пустой результат поиска города и не принимает произвольное название', function (): void {
    $city = CheckoutFixtures::city();

    Livewire::test(Checkout::class)
        ->call('selectCity', $city->id)
        ->set('city_search', 'Несуществующий город')
        ->assertSet('form.city_id', null)
        ->assertSee('Город не найден. Попробуйте изменить запрос.');
});

it('не предлагает города до ввода и показывает все совпадения', function (): void {
    foreach (range(1, 10) as $index) {
        CheckoutFixtures::city(['name' => 'Тестоград '.$index]);
    }

    Livewire::test(Checkout::class)
        ->assertViewHas('cities', fn ($cities): bool => $cities->isEmpty())
        ->set('city_search', 'Тестоград')
        ->assertViewHas('cities', fn ($cities): bool => $cities->count() === 10)
        ->set('city_search', '   ')
        ->assertViewHas('cities', fn ($cities): bool => $cities->isEmpty());
});

it('не предлагает СДЭК для города без действительного кода перевозчика', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => -1, 'is_cdek_excluded' => false]);
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    Livewire::test(Checkout::class)->call('selectCity', $city->id)
        ->assertViewHas('availableDeliveryMethods', fn (array $methods): bool => ! array_key_exists('CDEK', $methods));
});

it('не хранит токен гостевого доступа к заказу', function (): void {
    expect(Schema::hasColumn('orders', 'guest_token'))->toBeFalse();
});

it('после гостевого оформления открывает общую страницу подтверждения', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors()->assertRedirect(route('checkout.success'));

    $this->get(route('checkout.success'))->assertOk()
        ->assertSee('Заказ успешно оформлен')
        ->assertSee('Подтверждение отправлено на указанный email.');
});

it('после оформления владельцем открывает заказ в личном кабинете', function (): void {
    $this->actingAs(CheckoutFixtures::user());
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    $component->assertRedirect(route('orders.show', Order::query()->sole()));
});

it('гость переходит к оплате в банке без гостевой страницы заказа', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    CheckoutFixtures::form($city, ['payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('placeOrder')->assertHasNoErrors()
        ->assertRedirect('https://tbank.checkout.test/payment/731000001');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://tbank.checkout.test/v2/Init'
        && $request['SuccessURL'] === route('payment.success')
        && $request['FailURL'] === route('payment.fail'));
});

it('при ошибке оплаты гостю не предлагают недоступную страницу заказа', function (): void {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['tbank.checkout.test/*' => Http::response([
        'Success' => false, 'ErrorCode' => '999', 'Message' => 'Ошибка банка',
    ])]);
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);

    CheckoutFixtures::form($city, ['payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('placeOrder')->assertHasNoErrors()->assertRedirect(route('payment.fail'));

    $this->get(route('payment.fail'))->assertOk()
        ->assertSee('Заказ сохранён, но открыть оплату не удалось. Свяжитесь с нами для уточнения оплаты.')
        ->assertDontSee('Повторите оплату на странице заказа.');
    $this->assertDatabaseCount('orders', 1);
});
