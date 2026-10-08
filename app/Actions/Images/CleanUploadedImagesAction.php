<?php

declare(strict_types=1);

namespace App\Actions\Images;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class CleanUploadedImagesAction
{
    public function __construct(private readonly Filesystem $filesystem) {}

    public function run(): void
    {
        $publicStoragePath = storage_path('app/public');

        $this->filesystem->ensureDirectoryExists($publicStoragePath);

        foreach ($this->filesystem->directories($publicStoragePath) as $directory) {
            if (! $this->filesystem->deleteDirectory($directory)) {
                throw new RuntimeException("Не удалось удалить директорию {$directory}.");
            }
        }

        foreach ($this->filesystem->files($publicStoragePath) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            if (! $this->filesystem->delete($file->getPathname())) {
                throw new RuntimeException("Не удалось удалить файл {$file->getPathname()}.");
            }
        }
    }
}
