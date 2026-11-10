<?php

declare(strict_types=1);

use App\Filament\Resources\Orders\Pages\ViewOrder;
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

it('показывает понятные названия готовых документов без пустых полей ошибок', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['request_state' => 'SUCCESSFUL']);
    foreach (['WAYBILL', 'BARCODES'] as $type) {
        $order->documents()->create(['type' => $type, 'state' => 'READY']);
    }

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Квитанция')->assertSee('Наклейки')->assertSee('Готов')
        ->assertDontSee('WAYBILL')->assertDontSee('BARCODES')->assertDontSee('READY')
        ->assertDontSee('Причина ошибки')->assertDontSee('Ошибка');
});

it('показывает понятное состояние документа', function (string $state, string $label): void {
    $order = fulfillmentOrder();
    $order->documents()->create(['type' => 'WAYBILL', 'state' => $state]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee($label)->assertDontSee($state)->assertDontSee('Причина ошибки');
})->with([
    ['CREATING', 'Формируется'],
    ['ACCEPTED', 'Формируется'],
    ['PROCESSING', 'Формируется'],
    ['INVALID', 'Не удалось подготовить'],
    ['REMOVED', 'Требуется повторное формирование'],
]);

it('показывает причины ошибок отправления и документа при их наличии', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['error' => 'Не удалось зарегистрировать отправление']);
    $order->documents()->create(['type' => 'WAYBILL', 'state' => 'INVALID', 'error' => 'Не удалось сформировать квитанцию']);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertSee('Причина ошибки')
        ->assertSee('Не удалось зарегистрировать отправление')
        ->assertSee('Не удалось сформировать квитанцию');
});
