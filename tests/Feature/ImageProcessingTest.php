<?php

declare(strict_types=1);

use App\Actions\Images\ProcessImageAction;
use Spatie\Image\Image;

it('преобразует изображение в WebP с сохранением прозрачности', function (): void {
    $source = new Imagick;
    $source->newImage(8, 6, new ImagickPixel('transparent'), 'png');
    $source->setImageProperty('comment', 'private metadata');
    $result = app(ProcessImageAction::class)->run($source->getImageBlob());
    $image = new Imagick;
    $image->readImageBlob($result);
    expect($image->getImageFormat())->toBe('WEBP')
        ->and($image->getImageWidth())->toBe(8)->and($image->getImageHeight())->toBe(6)
        ->and($image->getImagePixelColor(0, 0)->getColorValue(Imagick::COLOR_ALPHA))->toBe(0.0)
        ->and($image->getImageProperty('comment'))->toBeFalse();
    $source->clear();
    $image->clear();
});

it('применяет преобразование перед кодированием изображения', function (): void {
    $source = new Imagick;
    $source->newImage(20, 10, new ImagickPixel('red'), 'png');
    $result = app(ProcessImageAction::class)->run($source->getImageBlob(), fn (Image $image): Image => $image->width(10));
    $image = new Imagick;
    $image->readImageBlob($result);
    expect($image->getImageWidth())->toBe(10)->and($image->getImageHeight())->toBe(5);
    $source->clear();
    $image->clear();
});

it('отклоняет анимированное изображение', function (): void {
    $animation = new Imagick;
    foreach (['red', 'blue'] as $color) {
        $frame = new Imagick;
        $frame->newImage(2, 2, new ImagickPixel($color), 'gif');
        $animation->addImage($frame);
        $frame->clear();
    }
    expect(fn () => app(ProcessImageAction::class)->run($animation->getImagesBlob()))
        ->toThrow(InvalidArgumentException::class, 'media.static_image_required');
    $animation->clear();
});

it('очищает временные файлы при ошибке обработки', function (): void {
    $source = new Imagick;
    $source->newImage(2, 2, new ImagickPixel('red'), 'png');
    $before = glob(storage_path('app/private/image-processing/*'));
    expect(fn () => app(ProcessImageAction::class)->run($source->getImageBlob(), function (): never {
        throw new RuntimeException('Ошибка преобразования');
    }))->toThrow(RuntimeException::class, 'Ошибка преобразования');
    expect(glob(storage_path('app/private/image-processing/*')))->toBe($before);
    $source->clear();
});
