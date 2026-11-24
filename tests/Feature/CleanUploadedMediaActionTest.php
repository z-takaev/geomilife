<?php

declare(strict_types=1);

use App\Actions\Images\CleanUploadedMediaAction;
use Illuminate\Filesystem\Filesystem;

it('очищает директории изображений и документов', function (): void {
    $filesystem = app(Filesystem::class);
    $testStoragePath = storage_path('framework/testing/clean-uploaded-images');

    $filesystem->deleteDirectory($testStoragePath);
    $this->app->useStoragePath($testStoragePath);

    $imagesPath = storage_path('app/public/images');
    $documentPath = storage_path('app/public/documents/privacy-policy.pdf');

    $filesystem->ensureDirectoryExists("{$imagesPath}/1/conversions");
    $filesystem->put("{$imagesPath}/1/image.webp", 'image');
    $filesystem->put("{$imagesPath}/1/conversions/thumbnail.webp", 'thumbnail');
    $filesystem->ensureDirectoryExists(dirname($documentPath));
    $filesystem->put($documentPath, 'document');

    try {
        app(CleanUploadedMediaAction::class)->run();

        expect($filesystem->isDirectory($imagesPath))->toBeTrue()
            ->and($filesystem->directories($imagesPath))->toBeEmpty()
            ->and($filesystem->files($imagesPath))->toBeEmpty()
            ->and($filesystem->exists($documentPath))->toBeFalse();
    } finally {
        $filesystem->deleteDirectory($testStoragePath);
    }
});
