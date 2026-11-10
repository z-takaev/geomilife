<?php

declare(strict_types=1);

use App\Services\Orders\OrderNotificationService;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --max-time=50 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(OrderNotificationService::class)
    ->retryPending())
    ->name('orders:notifications')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('orders:reconcile')->everyMinute()->withoutOverlapping();
