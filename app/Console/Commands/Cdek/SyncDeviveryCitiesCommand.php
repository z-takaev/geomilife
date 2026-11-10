<?php

declare(strict_types=1);

namespace App\Console\Commands\Cdek;

use App\Actions\Cdek\UpdateDeliveryCitiesAction;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Console\Command;

final class SyncDeviveryCitiesCommand extends Command
{
    protected $signature = 'cdek:sync-cities';

    protected $description = 'Синхронизировать города со справочником СДЭК';

    public function handle(UpdateDeliveryCitiesAction $updateDeliveryCities): int
    {
        $this->components->info('Синхронизация городов СДЭК...');

        try {
            $synchronizedCitiesCount = $updateDeliveryCities->run();
        } catch (CdekApiException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Синхронизировано городов: {$synchronizedCitiesCount}.");

        return self::SUCCESS;
    }
}
