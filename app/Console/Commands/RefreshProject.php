<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\UploadedImageCleaner;
use Illuminate\Console\Command;

final class RefreshProject extends Command
{
    protected $signature = 'project:refresh {--force : Выполнить без подтверждения}';

    protected $description = 'Пересоздать базу данных и заполнить начальные данные';

    public function handle(UploadedImageCleaner $uploadedImageCleaner): int
    {
        if (! $this->option('force') && ! $this->confirm('Все данные в базе будут удалены. Продолжить?')) {
            $this->components->warn('Обновление проекта отменено.');

            return self::SUCCESS;
        }

        $this->components->info('Обновление проекта...');

        if ($this->call('optimize:clear') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $uploadedImageCleaner->clean();

        if ($this->call('migrate:fresh', ['--seed' => true, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('storage:link', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info('Проект обновлён.');

        return self::SUCCESS;
    }
}
