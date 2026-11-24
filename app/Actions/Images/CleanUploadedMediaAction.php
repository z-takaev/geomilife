<?php

declare(strict_types=1);

namespace App\Actions\Images;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class CleanUploadedMediaAction
{
    public function __construct(private readonly Filesystem $filesystem) {}

    public function run(): void
    {
        foreach (['images', 'documents'] as $directory) {
            $this->cleanDirectory(storage_path("app/public/{$directory}"));
        }
    }

    private function cleanDirectory(string $path): void
    {
        $this->filesystem->ensureDirectoryExists($path);

        foreach ($this->filesystem->directories($path) as $directory) {
            if (! $this->filesystem->deleteDirectory($directory)) {
                throw new RuntimeException("Не удалось удалить директорию {$directory}.");
            }
        }

        foreach ($this->filesystem->files($path) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            if (! $this->filesystem->delete($file->getPathname())) {
                throw new RuntimeException("Не удалось удалить файл {$file->getPathname()}.");
            }
        }
    }
}
