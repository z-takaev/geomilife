<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Images\CleanUploadedImagesAction;
use Illuminate\Console\Command;

final class InstallProject extends Command
{
    protected $signature = 'project:install';

    protected $description = 'Установить проект и заполнить начальные данные';

    public function handle(CleanUploadedImagesAction $cleanUploadedImages): int
    {
        $this->components->info('Установка проекта...');

        if ($this->call('optimize:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('key:generate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $cleanUploadedImages->run();

        if ($this->call('migrate', ['--seed' => true, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('storage:link', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info('Проект установлен.');

        return self::SUCCESS;
    }
}
