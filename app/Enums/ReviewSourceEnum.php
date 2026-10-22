<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReviewSourceEnum: string implements HasLabel
{
    case YANDEX = 'YANDEX';
    case GOOGLE = 'GOOGLE';

    public function getLabel(): string
    {
        return match ($this) {
            self::YANDEX => 'Яндекс',
            self::GOOGLE => 'Google',
        };
    }

    public function logoPath(): string
    {
        return match ($this) {
            self::YANDEX => 'assets/images/reviews/ya.webp',
            self::GOOGLE => 'assets/images/reviews/google.webp',
        };
    }
}
