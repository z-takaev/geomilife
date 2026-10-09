<?php

declare(strict_types=1);

namespace App\Support\Media;

final class ImageMimeTypes
{
    /**
     * @var list<string>
     */
    public const array ALLOWED = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
}
