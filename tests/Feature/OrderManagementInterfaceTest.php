<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Jobs\Orders\CreateCdekShipmentJob;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Http::preventStrayRequests();
    Queue::fake();
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(), 'filament');
});

it('скрывает предзаказ и трек-номер по умолчанию с возможностью включения', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['cdek_number' => 'TRACK-123']);
    Livewire::test(ListOrders::class)
        ->assertDontSee('TRACK-123')
        ->assertTableColumnExists('is_preorder', fn ($column): bool => $column->isToggleable() && $column->isToggledHidden())
        ->assertTableColumnExists('shipment.cdek_number', fn ($column): bool => $column->isToggleable() && $column->isToggledHidden());
});

it('оставляет место название и вес упаковки и убирает технические поля', function (): void {
    $order = fulfillmentOrder();
    $order->payments()->create([
        'provider' => 'TBANK', 'transaction_id' => 'BANK-123', 'amount' => $order->total,
        'status' => 'PAID', 'provider_status' => 'SECRET-BANK-STATUS', 'operation_state' => 'SECRET-OPERATION',
        'error' => 'SECRET-PAYMENT-ERROR',
    ]);
    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Место')->assertSee('Коробка')->assertSee('Полный вес, г')->assertSee('600')
        ->assertSee('BANK-123')
        ->assertDontSee('Длина, мм')->assertDontSee('Ширина, мм')->assertDontSee('Высота, мм')
        ->assertDontSee('SECRET-BANK-STATUS')->assertDontSee('SECRET-OPERATION')->assertDontSee('SECRET-PAYMENT-ERROR')
        ->assertDontSee('Оплатить до');
});

it('разрешает сверку оплаты только администратору', function (FilamentUserRoleEnum $role): void {
    $this->actingAs(fulfillmentEmployee($role), 'filament');
    $order = fulfillmentOrder();
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id]);
    if ($role === FilamentUserRoleEnum::ADMIN) {
        $component->assertActionVisible('checkPayment');
    } else {
        $component->assertActionHidden('checkPayment');
        $component->call('mountAction', 'checkPayment');
        expect($component->instance()->mountedActions)->toBeEmpty();
        Http::assertNothingSent();
    }
})->with([FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER]);

it('показывает этапы и подтверждает следующий переход с уведомлением покупателя', function (FilamentUserRoleEnum $role): void {
    $this->actingAs(fulfillmentEmployee($role), 'filament');
    $order = fulfillmentOrder(['delivery_method' => 'PICKUP']);
    $action = TestAction::make('status_CONFIRMED')->schemaComponent('processingActions');
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Обработка заказа')->assertSee('Подтвердить и начать сборку')
        ->assertSeeHtml('aria-current="step"')->assertActionVisible($action)
        ->mountAction($action)->assertActionMounted($action);
    expect($component->instance()->getMountedAction()->getModalDescription())
        ->toContain('Новый → Подтверждён и собирается', 'Покупателю будет отправлено уведомление');
    expect($order->fresh()->status)->toBe(OrderStatusEnum::NEW);
    $component->callMountedAction()->assertHasNoActionErrors();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::CONFIRMED);
})->with([FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER]);

it('объясняет ожидание онлайн-оплаты и запрещает изменение статуса', function (): void {
    $order = fulfillmentOrder(['payment_status' => 'PENDING']);
    $action = TestAction::make('status_CONFIRMED')->schemaComponent('processingActions');
    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Ожидается онлайн-оплата')->assertDontSee('Подтвердить и начать сборку')
        ->mountAction($action)->assertActionNotMounted($action);
    expect($order->fresh()->status)->toBe(OrderStatusEnum::NEW);
});

it('объясняет регистрацию СДЭК и сохраняет автоматические статусы перевозчика', function (): void {
    $order = fulfillmentOrder(['status' => 'CONFIRMED']);
    $action = TestAction::make('status_READY_TO_SHIP')->schemaComponent('processingActions');
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->mountAction($action)->assertActionMounted($action);
    expect($component->instance()->getMountedAction()->getModalDescription())->toContain('Будет создана заявка СДЭК');
    $component->callMountedAction()->assertHasNoActionErrors()
        ->assertSee('Статусы отправки и доставки обновляются автоматически по данным СДЭК');
    Queue::assertPushed(CreateCdekShipmentJob::class);
    expect($order->fresh()->status)->toBe(OrderStatusEnum::READY_TO_SHIP);
});

it('требует отдельного подтверждения оплаты перед выдачей самовывоза', function (): void {
    $order = fulfillmentOrder([
        'delivery_method' => 'PICKUP', 'payment_method' => PaymentMethodEnum::PICKUP->value,
        'payment_status' => 'PENDING', 'status' => 'READY_FOR_PICKUP',
    ]);
    $payment = TestAction::make('markPaid')->schemaComponent('processingActions');
    $handover = TestAction::make('status_DELIVERED')->schemaComponent('processingActions');
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Подтвердите получение оплаты перед выдачей заказа')
        ->assertActionVisible($payment)->assertDontSee('Подтвердить выдачу')
        ->mountAction($payment)->assertActionMounted($payment);
    expect($component->instance()->getMountedAction()->getModalDescription())->toContain($order->total->format());
    $component->callMountedAction()->assertHasNoActionErrors()->assertActionVisible($handover)
        ->assertSee('Подтвердить выдачу')->callAction($handover);
    expect($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED);
});

it('не применяет устаревшее подтверждение повторно', function (): void {
    $order = fulfillmentOrder();
    $action = TestAction::make('status_CONFIRMED')->schemaComponent('processingActions');
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])->mountAction($action);
    $order->update(['status' => 'CANCELLED']);
    $component->callMountedAction();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::CANCELLED);
});

it('описывает возврат при отмене только для оплаченного онлайн-заказа', function (): void {
    $online = fulfillmentOrder();
    $component = Livewire::test(ViewOrder::class, ['record' => $online->id])->mountAction('cancel');
    expect($component->instance()->getMountedAction()->getModalDescription())
        ->toContain('возвращена полная онлайн-оплата', $online->total->format());
    $pickup = fulfillmentOrder(['delivery_method' => 'PICKUP', 'payment_method' => PaymentMethodEnum::PICKUP->value, 'payment_status' => 'PENDING']);
    $component = Livewire::test(ViewOrder::class, ['record' => $pickup->id])->mountAction('cancel');
    expect($component->instance()->getMountedAction()->getModalDescription())->not->toContain('возвращена');
    $component->callMountedAction()->assertHasActionErrors(['reason' => 'required']);
    expect($pickup->fresh()->status)->toBe(OrderStatusEnum::NEW);
});
