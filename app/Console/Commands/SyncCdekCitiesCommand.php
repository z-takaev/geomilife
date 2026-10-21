<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Cdek\SyncCdekCitiesAction;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Console\Command;

final class SyncCdekCitiesCommand extends Command
{
    protected $signature = 'cdek:sync-cities';

    protected $description = 'Синхронизировать города со справочником СДЭК';

    public function handle(SyncCdekCitiesAction $syncCdekCities): int
    {
        $this->components->info('Синхронизация городов СДЭК...');

        try {
            $synchronizedCitiesCount = $syncCdekCities->run();
        } catch (CdekApiException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Синхронизировано городов: {$synchronizedCitiesCount}.");

        return self::SUCCESS;
    }
}
