<?php

declare(strict_types=1);

use App\Jobs\Cdek\SyncDeliveryCitiesJob;
use Illuminate\Console\Scheduling\Schedule;

it('сохраняет короткий таймаут основного воркера очереди', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(static fn ($event): bool => str_contains($event->command ?? '', 'queue:work --'));

    expect($event)->not->toBeNull()
        ->and($event->command)->toContain('--timeout=60')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('обрабатывает долгие задания СДЭК отдельным воркером', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(static fn ($event): bool => str_contains($event->command ?? '', 'queue:work cdek'));

    expect($event)->not->toBeNull()
        ->and($event->command)->toContain('--timeout=240')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('еженедельно синхронизирует города СДЭК без пересечений', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(static fn ($event): bool => $event->description === SyncDeliveryCitiesJob::class);

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * 0')
        ->and($event->withoutOverlapping)->toBeTrue();
});
