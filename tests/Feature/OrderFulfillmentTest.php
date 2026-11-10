<?php

declare(strict_types=1);

use App\Enums\OrderStatusEnum;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Models\OrderShipment;
use App\Models\Setting;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderShipmentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    config([
        'services.cdek.base_url' => 'https://cdek.test',
        'services.cdek.client_id' => 'test-client',
        'services.cdek.client_secret' => 'test-secret',
        'services.cdek.shipment_point' => 'VLZ2',
        'services.cdek.sender_company' => 'GeoMiLife',
        'services.cdek.sender_name' => 'Туаев Георгий Аслабекович',
    ]);
    Http::preventStrayRequests();
    Setting::query()->create(['phone' => '+7 (999) 123-45-67']);
});

it('не создаёт заявку при подтверждении и ставит её создание в очередь только при готовности', function (): void {
    $order = fulfillmentOrder();
    $employee = fulfillmentEmployee();
    $statuses = app(OrderLifecycleService::class);

    $statuses->transition($order, OrderStatusEnum::CONFIRMED, $employee);
    Queue::assertNotPushed(CreateCdekShipmentJob::class);
    Http::assertNothingSent();

    $statuses->transition($order->fresh(), OrderStatusEnum::READY_TO_SHIP, $employee);

    Queue::assertPushed(CreateCdekShipmentJob::class, fn ($job): bool => $job->orderId === $order->id);
    expect(fulfillmentValue($order->fresh()->status))->toBe('READY_TO_SHIP');
});

it('использует тот же этап готовности для предзаказа', function (): void {
    $order = fulfillmentOrder(['is_preorder' => true]);
    $employee = fulfillmentEmployee();
    $statuses = app(OrderLifecycleService::class);
    $statuses->transition($order, OrderStatusEnum::CONFIRMED, $employee);
    Queue::assertNotPushed(CreateCdekShipmentJob::class);

    $statuses->transition($order->fresh(), OrderStatusEnum::READY_TO_SHIP, $employee);

    Queue::assertPushed(CreateCdekShipmentJob::class);
});

it('отправляет тариф 136 внешние размеры с округлением вверх и сохранённый полный вес', function (): void {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $order = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    fulfillmentOrderItem($order);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/orders' => Http::response(fulfillmentCdekResponse($uuid, state: 'ACCEPTED'), 202),
    ]);

    app(OrderShipmentService::class)->create($order);

    Http::assertSent(function (Request $request) use ($order): bool {
        return $request->method() === 'POST' && str_ends_with($request->url(), '/v2/orders')
            && $request['tariff_code'] === 136
            && $request['number'] === $order->number
            && $request['shipment_point'] === 'VLZ2'
            && $request['delivery_point'] === 'MSK1'
            && $request['sender']['company'] === 'GeoMiLife'
            && $request['sender']['name'] === 'Туаев Георгий Аслабекович'
            && $request['sender']['phones'][0]['number'] === '+79991234567'
            && $request['packages'][0]['length'] === 26
            && $request['packages'][0]['width'] === 8
            && $request['packages'][0]['height'] === 7
            && $request['packages'][0]['weight'] === 600;
    });
    expect($order->fresh()->shipment->uuid)->toBe($uuid)
        ->and(fulfillmentValue($order->fresh()->status))->toBe('READY_TO_SHIP');
});

it('не повторяет создание заявки после принятого запроса и повторного запуска', function (): void {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $order = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    fulfillmentOrderItem($order);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/orders' => Http::response(fulfillmentCdekResponse($uuid, state: 'ACCEPTED'), 202),
        'cdek.test/v2/orders/'.$uuid => Http::response(fulfillmentCdekResponse($uuid)),
        'cdek.test/v2/print/*' => Http::response(['entity' => ['uuid' => '22222222-2222-4222-8222-222222222222']]),
    ]);
    $service = app(OrderShipmentService::class);
    $service->create($order);
    $service->create($order->fresh());

    expect(Http::recorded(fn (Request $request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/v2/orders')))->toHaveCount(1);
    expect(OrderShipment::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('не начинает перевозку для неподходящего статуса оплаты или способа получения', function (array $attributes): void {
    $order = fulfillmentOrder($attributes);

    expect(fn () => app(OrderShipmentService::class)->create($order))->toThrow(ValidationException::class);
    Http::assertNothingSent();
})->with([
    'только подтверждён' => [['status' => 'CONFIRMED']],
    'не оплачен' => [['status' => 'READY_TO_SHIP', 'payment_status' => 'UNPAID']],
    'отменён' => [['status' => 'CANCELLED']],
    'самовывоз' => [['status' => 'READY_TO_SHIP', 'delivery_method' => 'PICKUP']],
]);

it('не считает создание заявки и прибытие в пункт выдачи получением заказа', function (string $cdekStatus, string $expectedStatus): void {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $order = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => $uuid, 'request_state' => 'ACCEPTED']);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/orders/'.$uuid => Http::response(fulfillmentCdekResponse($uuid, $cdekStatus)),
        'cdek.test/v2/print/*' => Http::response(['entity' => ['uuid' => '22222222-2222-4222-8222-222222222222']]),
    ]);

    app(OrderShipmentService::class)->sync($order);

    expect(fulfillmentValue($order->fresh()->status))->toBe($expectedStatus)
        ->and($order->fresh()->shipment->cdek_number)->toBe('0000123456');
})->with([
    'заявка создана' => ['CREATED', 'READY_TO_SHIP'],
    'передан перевозчику' => ['RECEIVED_AT_SHIPMENT_WAREHOUSE', 'SHIPPED'],
    'прибыл в ПВЗ' => ['READY_FOR_DELIVERY', 'SHIPPED'],
    'получен' => ['DELIVERED', 'DELIVERED'],
]);

it('не изменяет отменённый заказ после запоздалого статуса перевозчика', function (): void {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $order = fulfillmentOrder(['status' => 'CANCELLED']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => $uuid, 'request_state' => 'SUCCESSFUL']);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/orders/'.$uuid => Http::response(fulfillmentCdekResponse($uuid, 'DELIVERED')),
    ]);

    app(OrderShipmentService::class)->sync($order);

    expect(fulfillmentValue($order->fresh()->status))->toBe('CANCELLED');
});
