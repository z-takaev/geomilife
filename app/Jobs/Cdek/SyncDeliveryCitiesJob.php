<?php

declare(strict_types=1);

namespace App\Jobs\Cdek;

use App\Actions\Cdek\SyncDeliveryCitiesAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncDeliveryCitiesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(SyncDeliveryCitiesAction $syncDeliveryCities): void
    {
        $syncDeliveryCities->run();
    }
}
