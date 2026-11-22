<?php

declare(strict_types=1);

use Tests\Support\PaymentFixtures;

it('использует публичный номер заказа как внешний номер в production', function (): void {
    config(['orders.external_number_prefix' => null]);
    $order = PaymentFixtures::order();

    expect($order->externalNumber())->toBe((string) $order->number);
});

it('формирует стабильный уникальный внешний номер вне production', function (): void {
    config(['orders.external_number_prefix' => 'local']);
    $firstOrder = PaymentFixtures::order([
        'idempotency_key' => 'a1b2c3d4-e5f6-4789-8123-456789abcdef',
    ]);
    $secondOrder = PaymentFixtures::order([
        'idempotency_key' => '12345678-90ab-4cde-8f12-3456789abcde',
    ]);
    expect($firstOrder->externalNumber())
        ->toBe('local-'.$firstOrder->number.'-a1b2c3d4e5f64789')
        ->toBe($firstOrder->externalNumber())
        ->not->toBe($secondOrder->externalNumber())
        ->and(strlen($firstOrder->externalNumber()))->toBeLessThanOrEqual(30);
});
