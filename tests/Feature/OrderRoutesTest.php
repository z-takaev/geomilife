<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Support\CheckoutFixtures;

it('размещает список и детали заказов под общим префиксом orders', function (): void {
    expect(route('orders.index', [], false))->toBe('/orders')
        ->and(route('orders.show', ['order' => 123], false))->toBe('/orders/123');
});

it('не предоставляет маршрут повторной отправки гостевого доступа', function (): void {
    expect(Route::has('orders.guest.resend'))->toBeFalse();

    $this->post('/orders/access')->assertNotFound();
});

it('размещает действия с заказом вне профиля и сохраняет защиту', function (string $action): void {
    $route = Route::getRoutes()->getByName('orders.'.$action);

    expect(route('orders.'.$action, ['order' => 123], false))->toBe('/orders/123/'.$action)
        ->and($route->methods())->toBe(['POST'])
        ->and($route->gatherMiddleware())->toContain('auth', 'verified', 'throttle:6,1');

    $this->post('/profile/orders/123/'.$action)->assertNotFound();
})->with(['cancel', 'repeat']);

it('требует авторизацию для действий с заказом', function (string $action): void {
    $this->postJson('/orders/123/'.$action)->assertUnauthorized();
})->with(['cancel', 'repeat']);

it('требует подтверждённый email для действий с заказом', function (string $action): void {
    $user = CheckoutFixtures::user();
    $user->forceFill(['email_verified_at' => null])->save();
    $order = CheckoutFixtures::order($user);

    $this->actingAs($user)->post('/orders/'.$order->getRouteKey().'/'.$action)
        ->assertRedirect(route('verification.notice'));
})->with(['cancel', 'repeat']);

it('не регистрирует маршруты гостевого просмотра оплаты и отмены заказа', function (): void {
    foreach (['show', 'pay', 'cancel'] as $action) {
        expect(Route::has('orders.guest.'.$action))->toBeFalse();
    }
});
