<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Actions\Images\ProcessImageAction;
use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Finder\SplFileInfo;

final class FixtureImages
{
    private const array ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'webp'];

    public function __construct(
        private readonly ProcessImageAction $processImage,
    ) {}

    /**
     * @param  Closure(Image): mixed|null  $processImage
     */
    public function attach(
        HasMedia $model,
        string $file,
        string $collection = 'image',
        ?string $name = null,
        ?Closure $processImage = null,
    ): Media {
        return $this->attachFile(
            model: $model,
            path: $this->path($file),
            collection: $collection,
            name: $name,
            processImage: $processImage,
        );
    }

    /**
     * @param  Closure(Image): mixed|null  $processImage
     */
    public function attachRandom(
        HasMedia $model,
        string $directory,
        string $collection = 'image',
        ?Closure $processImage = null,
    ): Media {
        $files = array_values(array_filter(
            File::files($this->path($directory)),
            static fn (SplFileInfo $file): bool => in_array(
                strtolower($file->getExtension()),
                self::ALLOWED_EXTENSIONS,
                true,
            ),
        ));

        if ($files === []) {
            throw new RuntimeException("В директории фикстур {$directory} нет изображений.");
        }

        /** @var SplFileInfo $file */
        $file = fake()->randomElement($files);

        return $this->attachFile(
            model: $model,
            path: $file->getPathname(),
            collection: $collection,
            name: $file->getFilenameWithoutExtension(),
            processImage: $processImage,
        );
    }

    /**
     * @param  Closure(Image): mixed|null  $processImage
     */
    private function attachFile(
        HasMedia $model,
        string $path,
        string $collection,
        ?string $name,
        ?Closure $processImage,
    ): Media {
        if (! File::isFile($path)) {
            throw new RuntimeException("Изображение фикстуры {$path} не найдено.");
        }

        $contents = ($this->processImage)->run(File::get($path), $processImage);

        return $model
            ->addMediaFromString($contents)
            ->usingName($name ?? pathinfo($path, PATHINFO_FILENAME))
            ->usingFileName(Str::uuid().'.webp')
            ->toMediaCollection($collection);
    }

    private function path(string $path): string
    {
        return base_path('database/fixtures/images/'.ltrim($path, '/'));
    }
}
