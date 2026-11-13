<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderLifecycleService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;
use Tests\Support\PaymentFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    Http::preventStrayRequests();
});

it('не создаёт таблицу технической истории заказа', function (): void {
    expect(Schema::hasTable('order_histories'))->toBeFalse();
});

it('оформляет и обрабатывает самовывоз без технической истории', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($city)->call('placeOrder')->assertHasNoErrors();

    $order = Order::query()->sole();
    $actor = PaymentFixtures::manager();
    $lifecycle = app(OrderLifecycleService::class);
    $lifecycle->advanceStatus($order, OrderStatusEnum::CONFIRMED, $actor);
    $lifecycle->advanceStatus($order->fresh(), OrderStatusEnum::READY_FOR_PICKUP, $actor);
    $lifecycle->advanceStatus($order->fresh(), OrderStatusEnum::DELIVERED, $actor);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatusEnum::PAID)
        ->and($order->items()->count())->toBe(1);
    Http::assertNothingSent();
});

it('отменяет заказ без технической истории и сохраняет причину отмены', function (): void {
    $order = PaymentFixtures::order(['payment_method' => PaymentMethodEnum::PICKUP]);
    app(OrderLifecycleService::class)->cancel($order, PaymentFixtures::manager(), 'Просьба покупателя');

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CANCELLED)
        ->and($order->fresh()->cancellation_state)->toBe('COMPLETED')
        ->and($order->fresh()->cancellation_reason)->toBe('Просьба покупателя')
        ->and($order->fresh()->cancelled_at)->not->toBeNull();
    Http::assertNothingSent();
});

it('открывает карточку заказа без блока технической истории', function (FilamentUserRoleEnum $role): void {
    $actor = PaymentFixtures::manager();
    $actor->update(['role' => $role]);
    Filament::setCurrentPanel('admin');
    $this->actingAs($actor, 'filament');
    $order = PaymentFixtures::order();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSuccessful()->assertSee('Обработка заказа')->assertDontSee('История');
})->with([FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER]);
