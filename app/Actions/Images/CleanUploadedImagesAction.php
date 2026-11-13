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
        $imagesStoragePath = storage_path('app/public/images');

        $this->filesystem->ensureDirectoryExists($imagesStoragePath);

        foreach ($this->filesystem->directories($imagesStoragePath) as $directory) {
            if (! $this->filesystem->deleteDirectory($directory)) {
                throw new RuntimeException("Не удалось удалить директорию {$directory}.");
            }
        }

        foreach ($this->filesystem->files($imagesStoragePath) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            if (! $this->filesystem->delete($file->getPathname())) {
                throw new RuntimeException("Не удалось удалить файл {$file->getPathname()}.");
            }
        }
    }
}
