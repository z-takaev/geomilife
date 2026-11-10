<?php

declare(strict_types=1);

use App\Jobs\Orders\SyncCdekShipmentJob;
use App\Models\OrderShipment;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Queue::fake();
});

it('отклоняет запрос без JSON до проверки полей', function (): void {
    $this->post(route('cdek.webhook'))->assertUnsupportedMediaType();
    Queue::assertNothingPushed();
});

it('возвращает ошибки обязательных полей уведомления', function (): void {
    $this->postJson(route('cdek.webhook'), [
        'type' => 'ORDER_STATUS',
        'attributes' => ['is_return' => false],
    ])->assertUnprocessable()->assertJsonValidationErrors(['uuid', 'attributes.status_code']);
    Queue::assertNothingPushed();
});

it('принимает уведомление и ставит синхронизацию в очередь только для известного отправления', function (bool $shipmentExists): void {
    $uuid = '11111111-1111-4111-8111-111111111111';
    $order = CheckoutFixtures::order();

    if ($shipmentExists) {
        OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => $uuid, 'request_state' => 'ACCEPTED']);
    }

    $this->postJson(route('cdek.webhook'), [
        'type' => 'ORDER_STATUS',
        'date_time' => '2026-01-20T12:00:00+03:00',
        'uuid' => $uuid,
        'attributes' => [
            'is_return' => false,
            'cdek_number' => '1234567890',
            'status_code' => 'CREATED',
            'status_date_time' => '2026-01-20T12:00:00+03:00',
            'code' => 'CREATED',
            'is_reverse' => false,
            'is_client_return' => false,
        ],
    ])->assertNoContent(200);

    if ($shipmentExists) {
        Queue::assertPushed(SyncCdekShipmentJob::class, fn (SyncCdekShipmentJob $job): bool => $job->orderId === $order->id && $job->afterCommit === true);
    } else {
        Queue::assertNothingPushed();
    }
})->with([true, false]);
