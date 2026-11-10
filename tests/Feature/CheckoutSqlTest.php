<?php

declare(strict_types=1);

use App\DTO\Tbank\TbankPaymentNotificationDto;
use App\Enums\Orders\PaymentStatusEnum;
use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNotificationDelivery;
use App\Models\Payment;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CheckoutFixtures;
use Tests\Support\PaymentFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
});

it('индексирует поиск позиций и упорядоченных попыток оплаты по заказу', function (): void {
    expect(collect(Schema::getIndexes('order_items'))->contains(
        fn (array $index): bool => $index['columns'] === ['order_id'],
    ))->toBeTrue();

    expect(collect(Schema::getIndexes('payments'))->contains(
        fn (array $index): bool => $index['columns'] === ['order_id', 'id'],
    ))->toBeTrue();
});

it('считает количество товаров в карточках без загрузки полных позиций заказа', function (): void {
    $user = PaymentFixtures::user();
    $order = PaymentFixtures::order(['user_id' => $user->id]);
    $emptyOrder = PaymentFixtures::order(['user_id' => $user->id]);
    fulfillmentOrderItem($order, ['quantity' => 3]);
    fulfillmentOrderItem($order, ['quantity' => 2]);
    $loadedItems = 0;
    Event::listen('eloquent.retrieved: '.OrderItem::class, function () use (&$loadedItems): void {
        $loadedItems++;
    });

    $this->actingAs($user)->get(route('orders.index'))->assertOk()
        ->assertViewHas('presentations', fn ($presentations): bool => $presentations[$order->id]['quantity'] === 5
            && $presentations[$emptyOrder->id]['quantity'] === 0);

    expect($loadedItems)->toBe(0);

    $this->get(route('orders.show', $order))->assertOk()->assertSee('Масло тмина');

    expect($loadedItems)->toBe(2);
});

it('повтор уведомления читает свежий заказ без неиспользуемой предварительной загрузки', function (): void {
    config(['telegram.manager_chat_id' => 'test-manager']);
    $order = PaymentFixtures::order();
    $event = OrderNotificationDelivery::query()->create([
        'order_id' => $order->id, 'event' => 'CREATED', 'event_key' => hash('sha256', 'sql-test'),
    ]);
    DB::enableQueryLog();

    app(OrderNotificationService::class)->retryPending();

    $orderQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => preg_match('/^select .* from "orders" /i', $query['query']) === 1,
    );
    DB::disableQueryLog();

    expect($orderQueries)->toHaveCount(1)
        ->and($event->fresh()->dispatched_at)->not->toBeNull();
    Notification::assertCount(2);

    app(OrderNotificationService::class)->retryPending();

    Notification::assertCount(2);
});

it('обновляет итог оплаты без загрузки payload исторических попыток', function (): void {
    config([
        'tbank.base_url' => 'https://bank.test/v2',
        'tbank.terminal_key' => 'TEST_TERMINAL',
        'tbank.password' => 'test-password',
    ]);
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);

    foreach (range(1, 10) as $attempt) {
        PaymentFixtures::payment($order, [
            'transaction_id' => 'failed-'.$attempt,
            'bank_order_id' => $order->number.'-failed-'.$attempt,
            'status' => PaymentStatusEnum::FAILED,
            'payload' => ['history' => str_repeat('details', 1000)],
        ]);
    }

    $loadedHistoricalPayloads = 0;
    Event::listen('eloquent.retrieved: '.Payment::class, function (Payment $attempt) use (&$loadedHistoricalPayloads): void {
        if ($attempt->status === PaymentStatusEnum::FAILED && array_key_exists('payload', $attempt->getAttributes())) {
            $loadedHistoricalPayloads++;
        }
    });
    Http::fake(['bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'CONFIRMED'))]);

    app(OrderPaymentService::class)->sync($payment);

    expect($order->fresh()->payment_status)->toBe(PaymentStatusEnum::PAID)
        ->and($loadedHistoricalPayloads)->toBe(0);
});

it('не перечитывает один заказ подряд без изменения данных при сверке оплаты', function (string $operation): void {
    config([
        'tbank.base_url' => 'https://bank.test/v2',
        'tbank.terminal_key' => 'TEST_TERMINAL',
        'tbank.password' => 'test-password',
    ]);
    $order = PaymentFixtures::order();
    $payment = PaymentFixtures::payment($order);
    Http::fake(['bank.test/v2/GetState' => Http::response(PaymentFixtures::state($payment, 'NEW'))]);
    DB::enableQueryLog();

    if ($operation === 'notification') {
        app(OrderPaymentService::class)->handleBankNotification(new TbankPaymentNotificationDto(
            'TEST_TERMINAL', $payment->transaction_id, $payment->bank_order_id,
            $payment->amount, TbankPaymentStatusEnum::NEW, true, '0',
        ));
    } else {
        app(OrderPaymentService::class)->sync($payment);
    }

    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    $previousOrderRead = null;
    $duplicateOrderReads = 0;

    foreach ($queries as $query) {
        $isOrderRead = preg_match('/^select .* from "orders" where /i', $query['query']) === 1
            && ! str_contains($query['query'], 'for update');

        if ($isOrderRead && $previousOrderRead === $query['bindings']) {
            $duplicateOrderReads++;
        }

        $previousOrderRead = $isOrderRead ? $query['bindings'] : null;
    }

    expect($duplicateOrderReads)->toBe(0)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatusEnum::PENDING);
})->with(['sync', 'notification']);

it('блокирует заказанные строки корзины одним запросом и сохраняет добавленные товары', function (int $itemCount): void {
    $user = CheckoutFixtures::user();
    $this->actingAs($user);
    $city = CheckoutFixtures::city();
    $variantIds = [];

    foreach (range(1, $itemCount) as $number) {
        $variantIds[] = $variantId = CheckoutFixtures::variant()->id;
        app(CartService::class)->add($variantId, 2);
    }

    $unrelatedVariant = CheckoutFixtures::variant();
    $component = CheckoutFixtures::form($city);
    $cart = $user->cart()->firstOrFail();
    Event::listen('eloquent.created: '.Order::class, function () use ($cart, $variantIds, $unrelatedVariant): void {
        $cart->items()->where('product_variant_id', $variantIds[0])->increment('quantity', 2);
        $cart->items()->create(['product_variant_id' => $unrelatedVariant->id, 'quantity' => 5]);
    });
    DB::enableQueryLog();

    $component->call('placeOrder')->assertHasNoErrors();

    $lockedCartReads = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => preg_match('/^select .* from "cart_items" /i', $query['query']) === 1
            && str_contains($query['query'], 'for update'),
    );
    DB::disableQueryLog();

    expect($lockedCartReads)->toHaveCount(1)
        ->and($user->orders()->sole()->items()->sum('quantity'))->toBe($itemCount * 2)
        ->and($cart->items()->orderBy('product_variant_id')->pluck('quantity', 'product_variant_id')->all())->toBe([
            $variantIds[0] => 2,
            $unrelatedVariant->id => 5,
        ]);
})->with([1, 4]);
