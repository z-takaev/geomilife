<?php

declare(strict_types=1);

namespace App\Console\Commands\Cdek;

use App\Actions\Cdek\SyncDeliveryCitiesAction;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Console\Command;

final class SyncDeliveryCitiesCommand extends Command
{
    protected $signature = 'cdek:sync-cities';

    protected $description = 'Синхронизировать города со справочником СДЭК';

    public function handle(SyncDeliveryCitiesAction $syncDeliveryCities): int
    {
        $this->components->info('Синхронизация городов СДЭК...');

        try {
            $synchronizedCitiesCount = $syncDeliveryCities->run();
        } catch (CdekApiException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Синхронизировано городов: {$synchronizedCitiesCount}.");

        return self::SUCCESS;
    }
}
