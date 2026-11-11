<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Actions\Images\ProcessImageAction;
use App\Support\Media\ImageMimeTypes;
use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ImagickException;
use InvalidArgumentException;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Exceptions\CouldNotLoadImage;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class SpatieMediaLibrary extends SpatieMediaLibraryFileUpload
{
    protected ?Closure $imageProcessing = null;

    /**
     * @param  Closure(Image): mixed|null  $callback
     */
    public function processImage(?Closure $callback): static
    {
        $this->imageProcessing = $callback;

        return $this;
    }

    public function getValidationRules(): array
    {
        return [
            'bail',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_array($value)) {
                    $fail(__('filament-forms::validation.tampered_file_path', ['attribute' => $this->getValidationAttribute()]));

                    return;
                }

                $record = $this->getRecord();
                $allowedUuids = $record instanceof HasMedia && $record->exists
                    ? $record->getMedia($this->getCollection() ?? 'default')
                        ->filter(static fn (Media $media): bool => Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()))
                        ->pluck('uuid')->all()
                    : [];
                foreach ($value as $file) {
                    if ($file instanceof TemporaryUploadedFile || (is_string($file) && in_array($file, $allowedUuids, true))) {
                        continue;
                    }

                    $fail(__('filament-forms::validation.tampered_file_path', ['attribute' => $this->getValidationAttribute()]));

                    return;
                }
            },
            ...parent::getValidationRules(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->image()
            ->acceptedFileTypes(ImageMimeTypes::ALLOWED)
            ->maxSize(12288);

        $this->saveUploadedFileUsing(static function (SpatieMediaLibrary $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! $record instanceof HasMedia) {
                return null;
            }

            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }

            try {
                $contents = app(ProcessImageAction::class)->run($file->get(), $component->imageProcessing);
            } catch (ImagickException|InvalidArgumentException|CouldNotLoadImage $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    $component->getStatePath() => __('media.processing_failed'),
                ]);
            }

            $media = $record->addMediaFromString($contents)
                ->addCustomHeaders([...$component->getCustomHeaders(), 'ContentType' => 'image/webp'])
                ->usingFileName(Str::uuid().'.webp')
                ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                ->withCustomProperties($component->getCustomProperties($file))
                ->withManipulations($component->getManipulations())
                ->withResponsiveImagesIf($component->hasResponsiveImages())
                ->withProperties($component->getProperties())
                ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

            return $media->uuid;
        });

        $this->saveRelationshipsUsing(static function (SpatieMediaLibrary $component): void {
            $component->saveUploadedFiles();

            // После сохранения значения содержат UUID медиа, ключи новых загрузок — временные UUID.
            $uuids = array_values($component->getRawState() ?? []);
            $component->rawState(array_combine($uuids, $uuids));
            $component->getRecord()?->unsetRelation('media');
            $component->deleteAbandonedFiles();
        });
    }
}
