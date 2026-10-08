<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Actions\Images\ProcessImageAction;
use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ImagickException;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Exceptions\CouldNotLoadImage;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;

final class MediaImageUpload extends SpatieMediaLibraryFileUpload
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(12288);

        $this->saveUploadedFileUsing(static function (MediaImageUpload $component, TemporaryUploadedFile $file, Model $record): string {
            try {
                $contents = app(ProcessImageAction::class)->run($file->get(), $component->imageProcessing);
            } catch (ImagickException|InvalidArgumentException|CouldNotLoadImage $exception) {
                report($exception);

                throw ValidationException::withMessages([
                    $component->getStatePath() => __('media.processing_failed'),
                ]);
            }

            /** @var Model&HasMedia $record */
            $media = $record->addMediaFromString($contents)
                ->addCustomHeaders([...$component->getCustomHeaders(), 'ContentType' => 'image/webp'])
                ->usingFileName(Str::uuid().'.webp')
                ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->withCustomProperties($component->getCustomProperties($file))
                ->withProperties($component->getProperties())
                ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

            return $media->uuid;
        });

        $this->saveRelationshipsUsing(static function (MediaImageUpload $component): void {
            $component->saveUploadedFiles();

            // После сохранения значения содержат UUID медиа, ключи новых загрузок — временные UUID.
            $uuids = array_values($component->getRawState() ?? []);
            $component->rawState(array_combine($uuids, $uuids));
            $component->getRecord()?->unsetRelation('media');
            $component->deleteAbandonedFiles();
        });
    }
}
