<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Imagick;
use InvalidArgumentException;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

final class ImageProcessor
{
    /**
     * @param  Closure(Image): mixed|null  $processImage
     */
    public function convert(string $contents, ?Closure $processImage = null): string
    {
        $directory = TemporaryDirectory::make(storage_path('app/private/image-processing'));
        $encodedImage = new Imagick;

        try {
            $sourcePath = $directory->path('source.image');
            file_put_contents($sourcePath, $contents);

            $encodedImage->pingImage($sourcePath);

            if ($encodedImage->getNumberImages() !== 1) {
                throw new InvalidArgumentException('media.static_image_required');
            }

            $encodedImage->clear();

            $image = Image::useImageDriver(ImageDriver::Imagick)->loadFile($sourcePath);
            $processImage?->__invoke($image);

            // PNG передаёт результат обработки кодировщику без промежуточного сжатия с потерями.
            $encodedImage->readImageBlob(base64_decode($image->base64('png', false), true));
            $encodedImage->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            $encodedImage->stripImage();
            $encodedImage->setImageFormat('webp');
            $encodedImage->setImageDepth(8);
            $encodedImage->setImageCompressionQuality(100);
            $encodedImage->setOption('webp:lossless', 'true');
            $encodedImage->setOption('webp:method', '6');

            return $encodedImage->getImageBlob();
        } finally {
            $encodedImage->clear();
            $directory->delete();
        }
    }
}
