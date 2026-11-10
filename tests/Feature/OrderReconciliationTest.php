<?php

declare(strict_types=1);

use App\Jobs\Orders\PrepareCdekDocumentsJob;
use App\Models\OrderShipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PaymentFixtures;

it('плановая сверка обновляет пропущенный статус СДЭК без вебхука', function (): void {
    Notification::fake();
    Queue::fake([PrepareCdekDocumentsJob::class]);
    Http::preventStrayRequests();
    config(['services.cdek.base_url' => 'https://cdek.reconcile.test/v2',
        'services.cdek.client_id' => 'client', 'services.cdek.client_secret' => 'secret']);
    $order = PaymentFixtures::order(['status' => 'READY_TO_SHIP', 'payment_status' => 'PAID', 'delivery_method' => 'CDEK']);
    OrderShipment::query()->create(['order_id' => $order->id, 'uuid' => PaymentFixtures::SHIPMENT_UUID,
        'status' => 'CREATED', 'request_state' => 'SUCCESSFUL']);
    Http::fake([
        '*oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        '*orders/*' => Http::response(PaymentFixtures::shipment('DELIVERED')),
    ]);

    $this->artisan('orders:reconcile')->assertSuccessful();

    expect(PaymentFixtures::value($order->fresh()->status))->toBe('DELIVERED');
    Http::assertSent(fn ($request): bool => str_contains($request->url(), PaymentFixtures::SHIPMENT_UUID));
});
