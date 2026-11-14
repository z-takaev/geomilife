<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('хранит события уведомлений в таблице с предметным названием', function (): void {
    expect(Schema::hasTable('order_notification_events'))->toBeTrue()
        ->and(Schema::hasTable('order_notification_deliveries'))->toBeFalse();
});
