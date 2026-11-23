<?php

declare(strict_types=1);

use App\Jobs\Cdek\SyncDeliveryCitiesJob;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty --max-time=50 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:work cdek --queue=cdek --stop-when-empty --max-time=50 --timeout=240')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(OrderNotificationService::class)
    ->retryPending())
    ->name('orders:notifications')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('orders:reconcile')->everyMinute()->withoutOverlapping();

Schedule::job(new SyncDeliveryCitiesJob)->weekly()->withoutOverlapping();
