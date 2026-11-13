<?php

declare(strict_types=1);

use App\Enums\Cdek\CdekPrintStateEnum;
use App\Enums\Cdek\CdekRequestStateEnum;
use App\Enums\Cdek\CdekShipmentWorkflowStateEnum;
use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Users\UserResource;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Http::preventStrayRequests();
    Queue::fake();
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(), 'filament');
});

it('скрывает предзаказ по умолчанию с возможностью включения', function (): void {
    fulfillmentOrder();

    Livewire::test(ListOrders::class)
        ->assertTableColumnExists('is_preorder', fn ($column): bool => $column->isToggleable() && $column->isToggledHidden());
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

it('компонует основные данные заказа по рабочим разделам', function (): void {
    $order = fulfillmentOrder();
    fulfillmentOrderItem($order);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSeeInOrder(['Сводка', 'Обработка заказа', 'Покупатель', 'Получение', 'Товары', 'Упаковка', 'Оплата', 'Доставка СДЭК'])
        ->assertSeeInOrder(['Артикул', 'Товар', 'Фасовка', 'Количество', 'Цена', 'Сумма'])
        ->assertDontSee('Общие сведения')
        ->assertDontSee('Получение и сумма')
        ->assertDontSee('Платежи')
        ->assertDontSee('Попытки');
});

it('показывает товары компактной таблицей', function (): void {
    $order = fulfillmentOrder();
    fulfillmentOrderItem($order);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSeeHtml('fi-in-table-repeatable')
        ->assertSeeHtml('fi-section fi-section-has-header fi-compact');
});

it('показывает ссылку на аккаунт покупателя или гостя', function (): void {
    $user = User::query()->create([
        'first_name' => 'Иван',
        'last_name' => 'Иванов',
        'email' => 'account@example.test',
        'password' => 'Password123',
    ]);
    $order = fulfillmentOrder(['user_id' => $user->id]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Иван Иванов')
        ->assertSeeHtml('href="'.UserResource::getUrl('view', ['record' => $user]).'"');

    $guestOrder = fulfillmentOrder(['user_id' => null]);

    Livewire::test(ViewOrder::class, ['record' => $guestOrder->id])
        ->assertSee('Гость');
});

it('понятно обозначает отсутствующие данные и скрывает неактуальные разделы', function (): void {
    $order = fulfillmentOrder([
        'comment' => null,
        'packages' => [],
        'payment_status' => 'UNPAID',
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Без комментария')
        ->assertSee('Упаковка ещё не подготовлена')
        ->assertSee('Платёж не создавался')
        ->assertSee('Не зарегистрировано в СДЭК')
        ->assertSee('Документы ещё не сформированы')
        ->assertDontSee('Отмена заказа')
        ->assertDontSee('Причина ошибки');
});

it('переводит состояние отмены и показывает его отдельным разделом', function (string $state, string $label): void {
    $order = fulfillmentOrder([
        'cancellation_state' => $state,
        'cancellation_reason' => 'Просьба покупателя',
        'cancelled_at' => $state === 'COMPLETED' ? now() : null,
        'status' => $state === 'COMPLETED' ? 'CANCELLED' : 'NEW',
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Отмена заказа')
        ->assertSee($label)
        ->assertSee('Просьба покупателя')
        ->assertDontSee($state);
})->with([
    ['PENDING', 'Выполняется'],
    ['FAILED', 'Ошибка отмены'],
    ['COMPLETED', 'Завершена'],
]);

it('использует единственное число для банковского платежа и сверки', function (): void {
    $order = fulfillmentOrder();
    $order->payments()->create([
        'provider' => 'TBANK',
        'transaction_id' => 'BANK-ONE',
        'amount' => $order->total,
        'status' => 'PAID',
    ]);

    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Платёж в Т-Банке')
        ->assertSee('BANK-ONE')
        ->assertDontSee('Попытки')
        ->mountAction('checkPayment');

    expect($component->instance()->getMountedAction()->getModalDescription())
        ->toContain('состояние платежа')
        ->not->toContain('всех попыток');
});

it('показывает понятное состояние доставки СДЭК без технических кодов', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create([
        'uuid' => '11111111-1111-4111-8111-111111111111',
        'cdek_number' => 'TRACK-123',
        'status' => 'CREATED',
        'request_state' => 'SUCCESSFUL',
        'operation_state' => 'REGISTERED',
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('TRACK-123')
        ->assertSee('Создан')
        ->assertSee('Зарегистрировано')
        ->assertSee('Технические сведения')
        ->assertDontSee('SUCCESSFUL')
        ->assertDontSee('REGISTERED');
});

it('типизирует состояния интеграции СДЭК без дублирования названия статуса', function (): void {
    expect(Schema::hasColumn('order_shipments', 'status_name'))->toBeFalse();

    $order = fulfillmentOrder();
    $shipment = $order->shipment()->create([
        'request_state' => CdekRequestStateEnum::SUCCESSFUL,
        'operation_state' => CdekShipmentWorkflowStateEnum::REGISTERED,
    ]);
    $document = $order->documents()->create([
        'type' => 'WAYBILL',
        'state' => CdekPrintStateEnum::READY,
    ]);

    expect($shipment->fresh()->request_state)->toBe(CdekRequestStateEnum::SUCCESSFUL)
        ->and($shipment->fresh()->operation_state)->toBe(CdekShipmentWorkflowStateEnum::REGISTERED)
        ->and($document->fresh()->state)->toBe(CdekPrintStateEnum::READY);
});

it('не ломает страницу при неизвестном статусе доставки СДЭК', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['status' => 'NEW_CDEK_STATUS']);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSuccessful()
        ->assertSee('Неизвестный статус СДЭК')
        ->assertDontSee('NEW_CDEK_STATUS');
});

it('показывает восстановительные действия СДЭК только когда они нужны', function (): void {
    $failedOrder = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    $failedOrder->shipment()->create(['operation_state' => 'FAILED']);

    Livewire::test(ViewOrder::class, ['record' => $failedOrder->id])
        ->assertActionVisible('retryShipment');

    $registeredOrder = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    $registeredOrder->shipment()->create([
        'uuid' => '22222222-2222-4222-8222-222222222222',
        'request_state' => 'SUCCESSFUL',
        'operation_state' => 'REGISTERED',
    ]);
    foreach (['WAYBILL', 'BARCODES'] as $type) {
        $registeredOrder->documents()->create(['type' => $type, 'state' => 'READY']);
    }

    Livewire::test(ViewOrder::class, ['record' => $registeredOrder->id])
        ->assertActionHidden('retryShipment')
        ->assertActionHidden('prepareDocuments');
});

it('разрешает сверку оплаты администратору и менеджеру', function (FilamentUserRoleEnum $role): void {
    $this->actingAs(fulfillmentEmployee($role), 'filament');
    $order = fulfillmentOrder();
    Livewire::test(ViewOrder::class, ['record' => $order->id])->assertActionVisible('checkPayment')
        ->mountAction('checkPayment')->assertActionMounted('checkPayment');
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

it('подтверждает оплату и выдачу самовывоза одним действием', function (): void {
    $order = fulfillmentOrder([
        'delivery_method' => 'PICKUP', 'payment_method' => PaymentMethodEnum::PICKUP->value,
        'payment_status' => 'PENDING', 'status' => 'READY_FOR_PICKUP',
    ]);
    $handover = TestAction::make('status_DELIVERED')->schemaComponent('processingActions');
    $component = Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertDontSee('Подтвердить получение оплаты')
        ->assertActionVisible($handover)
        ->assertSee('Подтвердить выдачу')
        ->mountAction($handover)->assertActionMounted($handover);
    expect($component->instance()->getMountedAction()->getModalDescription())
        ->toContain('получена оплата', $order->total->format());
    $component->callMountedAction()->assertHasNoActionErrors();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED)
        ->and($order->fresh()->payment_status->value)->toBe('PAID');
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
