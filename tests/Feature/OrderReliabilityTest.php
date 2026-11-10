<?php

declare(strict_types=1);

use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Jobs\Orders\PrepareCdekDocumentsJob;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderNotificationDelivery;
use App\Models\OrderShipment;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Services\Orders\CdekShipmentService;
use App\Services\Orders\OrderDocumentService;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Http::preventStrayRequests();
    config(['queue.default' => 'database', 'telegram.manager_chat_id' => '-1001234567890',
        'services.cdek.base_url' => 'https://refactoring.cdek.test',
        'services.cdek.client_id' => 'client', 'services.cdek.client_secret' => 'secret']);
});

it('сохраняет событие при сбое постановки в очередь и повторяет его без отката заказа', function (): void {
    $order = fulfillmentOrder();
    Event::listen(JobQueueing::class, static function (): void {
        throw new RuntimeException('Очередь недоступна');
    });
    try {
        app(OrderLifecycleService::class)->advanceStatus($order, OrderStatusEnum::CONFIRMED, fulfillmentEmployee());
    } catch (RuntimeException) {
    } finally {
        Event::forget(JobQueueing::class);
    }

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CONFIRMED)
        ->and(OrderNotificationDelivery::query()->sole()->dispatched_at)->toBeNull();
    app(OrderNotificationService::class)->retryPending();
    expect(OrderNotificationDelivery::query()->sole()->dispatched_at)->not->toBeNull();
    $this->assertDatabaseCount('jobs', 1);
    app(OrderNotificationService::class)->retryPending();
    $this->assertDatabaseCount('jobs', 1);
});

it('откатывает статус если событие не удалось сохранить', function (): void {
    Notification::fake();
    $order = fulfillmentOrder();
    $event = 'eloquent.creating: '.OrderNotificationDelivery::class;
    Event::listen($event, static function (): void {
        throw new RuntimeException('Запись события не удалась');
    });
    try {
        expect(fn () => app(OrderLifecycleService::class)->advanceStatus($order, OrderStatusEnum::CONFIRMED, fulfillmentEmployee()))
            ->toThrow(RuntimeException::class);
    } finally {
        Event::forget($event);
    }
    expect($order->fresh()->status)->toBe(OrderStatusEnum::NEW);
    $this->assertDatabaseCount('order_notification_deliveries', 0);
    Notification::assertNothingSent();
});

function fakeCdekDocuments(OrderDocument $document): void
{
    Http::fake(static function (Request $request) use ($document) {
        if (str_contains($request->url(), 'oauth/token')) {
            return Http::response(['access_token' => 'token', 'expires_in' => 3600]);
        }
        if ($request->method() === 'POST') {
            return Http::response(['entity' => ['uuid' => '22222222-2222-4222-8222-222222222222']]);
        }
        if (str_ends_with($request->url(), '.pdf')) {
            return Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf']);
        }

        return Http::response(['entity' => ['uuid' => $document->uuid ?? '22222222-2222-4222-8222-222222222222',
            'url' => 'https://refactoring.cdek.test/document.pdf', 'statuses' => [['code' => 'READY', 'date_time' => now()->toIso8601String()]]]]);
    });
}

it('восстанавливает просроченный захват формирования документа', function (): void {
    Notification::fake();
    $order = fulfillmentOrder();
    $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'request_state' => 'SUCCESSFUL']);
    $document = $order->documents()->create(['type' => 'WAYBILL', 'state' => 'CREATING']);
    $document->forceFill(['updated_at' => now()->subMinutes(10)])->save();
    fakeCdekDocuments($document);

    expect(app(OrderDocumentService::class)->downloadCdekDocument($order, 'WAYBILL'))->toBe('%PDF-1.4 test');
    Http::assertSentCount(4);
    expect($document->fresh()->state)->toBe('READY');
});

it('не повторяет создание документа при свежем захвате', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'request_state' => 'SUCCESSFUL']);
    $order->documents()->create(['type' => 'WAYBILL', 'state' => 'CREATING']);

    expect(fn () => app(OrderDocumentService::class)->downloadCdekDocument($order, 'WAYBILL'))->toThrow(ValidationException::class);
    Http::assertNothingSent();
});

it('повторно использует UUID появившийся между чтением и захватом документа', function (): void {
    $order = fulfillmentOrder();
    $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'request_state' => 'SUCCESSFUL']);
    $document = $order->documents()->create(['type' => 'WAYBILL', 'state' => 'PENDING']);
    fakeCdekDocuments($document);
    $changed = false;
    $event = 'eloquent.retrieved: '.OrderDocument::class;
    Event::listen($event, static function (OrderDocument $retrieved) use (&$changed): void {
        if (! $changed) {
            $changed = true;
            DB::table('order_documents')->where('id', $retrieved->id)->update([
                'uuid' => '22222222-2222-4222-8222-222222222222', 'state' => 'ACCEPTED',
            ]);
        }
    });
    try {
        expect(app(OrderDocumentService::class)->downloadCdekDocument($order, 'WAYBILL'))->toBe('%PDF-1.4 test');
    } finally {
        Event::forget($event);
    }
    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST' && str_contains($request->url(), '/print/'));
});

it('применяет ответ СДЭК атомарно и игнорирует более старый статус', function (): void {
    Notification::fake();
    Queue::fake();
    $order = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    $shipment = $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111',
        'status' => 'CREATED', 'status_at' => now()->subDay()]);
    $event = 'eloquent.updating: '.OrderShipment::class;
    $levels = [];
    Event::listen($event, static function () use (&$levels): void {
        $levels[] = DB::transactionLevel();
    });
    $responses = [
        fulfillmentCdekResponse($shipment->uuid, 'DELIVERED'),
        fulfillmentCdekResponse($shipment->uuid, 'CREATED'),
    ];
    $responses[0]['entity']['statuses'][0]['date_time'] = now()->toIso8601String();
    $responses[1]['entity']['statuses'][0]['date_time'] = now()->subHour()->toIso8601String();
    Http::fake([
        'refactoring.cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'refactoring.cdek.test/v2/orders/*' => Http::sequence()->push($responses[0])->push($responses[1]),
    ]);
    try {
        app(CdekShipmentService::class)->sync($order);
        app(CdekShipmentService::class)->sync($order);
    } finally {
        Event::forget($event);
    }
    expect($shipment->fresh()->status)->toBe('DELIVERED')
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED)
        ->and(min($levels))->toBeGreaterThan(1);
});

it('не ставит документы после конкурентного начала отмены при сверке СДЭК', function (): void {
    Notification::fake();
    Queue::fake();
    $order = fulfillmentOrder(['status' => 'READY_TO_SHIP']);
    $shipment = $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111',
        'status' => 'CREATED', 'status_at' => now()->subDay(), 'operation_state' => 'REGISTERING']);
    $changed = false;
    $event = 'eloquent.retrieved: '.Order::class;
    Event::listen($event, static function () use (&$changed, $shipment): void {
        if (! $changed) {
            $changed = true;
            DB::table('order_shipments')->where('id', $shipment->id)->update([
                'operation_state' => 'CANCELLING', 'status_at' => now(),
            ]);
        }
    });
    Http::fake([
        'refactoring.cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'refactoring.cdek.test/v2/orders/*' => Http::response(fulfillmentCdekResponse($shipment->uuid)),
    ]);
    try {
        app(CdekShipmentService::class)->sync($order);
    } finally {
        Event::forget($event);
    }
    expect($changed)->toBeTrue()
        ->and($shipment->fresh()->operation_state)->toBe('CANCELLING');
    Queue::assertNotPushed(PrepareCdekDocumentsJob::class);
});

it('выдерживает паузу после неизвестного результата создания документа', function (bool $serverError, bool $obsoleteUuid): void {
    Notification::fake();
    $order = fulfillmentOrder();
    $order->shipment()->create(['uuid' => '11111111-1111-4111-8111-111111111111', 'request_state' => 'SUCCESSFUL']);
    if ($obsoleteUuid) {
        $order->documents()->create(['type' => 'WAYBILL', 'state' => 'REMOVED', 'uuid' => '33333333-3333-4333-8333-333333333333']);
    }
    $creates = 0;
    Http::fake(static function (Request $request) use (&$creates, $serverError) {
        if (str_contains($request->url(), 'oauth/token')) {
            return Http::response(['access_token' => 'token', 'expires_in' => 3600]);
        }
        if ($request->method() === 'POST') {
            $creates++;
            if ($creates === 1) {
                return $serverError ? Http::response([], 500) : Http::failedConnection();
            }

            return Http::response(['entity' => ['uuid' => '22222222-2222-4222-8222-222222222222']]);
        }
        if (str_ends_with($request->url(), '.pdf')) {
            return Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf']);
        }

        return Http::response(['entity' => ['uuid' => '22222222-2222-4222-8222-222222222222',
            'url' => 'https://refactoring.cdek.test/document.pdf',
            'statuses' => [['code' => 'READY', 'date_time' => now()->toIso8601String()]]]]);
    });
    $service = app(OrderDocumentService::class);
    expect(fn () => $service->downloadCdekDocument($order, 'WAYBILL'))->toThrow(CdekApiException::class);
    expect(fn () => $service->downloadCdekDocument($order, 'WAYBILL'))->toThrow(ValidationException::class);
    expect($creates)->toBe(1);
    $this->travel(3)->minutes();
    expect($service->downloadCdekDocument($order, 'WAYBILL'))->toBe('%PDF-1.4 test')
        ->and($creates)->toBe(2);
})->with([[false, false], [true, false], [false, true], [true, true]]);

it('не позволяет создать заказ с неизвестным способом доставки', function (): void {
    expect(fn () => fulfillmentOrder(['delivery_method' => 'UNKNOWN']))->toThrow(ValueError::class);
});

it('определяет следующий ручной статус по доставке и оплате', function (string $delivery, string $status, string $payment, ?OrderStatusEnum $expected): void {
    $order = fulfillmentOrder(['delivery_method' => $delivery, 'status' => $status,
        'payment_status' => $payment, 'payment_method' => $delivery === 'PICKUP' ? PaymentMethodEnum::PICKUP->value : PaymentMethodEnum::ONLINE->value]);
    expect(app(OrderLifecycleService::class)->nextManualStatus($order))->toBe($expected);
})->with([
    ['PICKUP', 'NEW', 'UNPAID', OrderStatusEnum::CONFIRMED],
    ['PICKUP', 'CONFIRMED', 'UNPAID', OrderStatusEnum::READY_FOR_PICKUP],
    ['PICKUP', 'READY_FOR_PICKUP', 'UNPAID', null],
    ['PICKUP', 'READY_FOR_PICKUP', 'PAID', OrderStatusEnum::DELIVERED],
    ['COURIER', 'CONFIRMED', 'PAID', OrderStatusEnum::DELIVERED],
    ['COURIER', 'CONFIRMED', 'UNPAID', null],
    ['CDEK', 'CONFIRMED', 'PAID', OrderStatusEnum::READY_TO_SHIP],
    ['CDEK', 'READY_TO_SHIP', 'PAID', null],
    ['CDEK', 'CANCELLED', 'PAID', null],
]);
