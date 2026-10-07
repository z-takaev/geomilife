<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Schemas;

use App\Filament\Forms\Components\MediaImageUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

final class HomeSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::imageUpload('desktop_image', 'Изображение для компьютера', 1920, 720),
                self::imageUpload('mobile_image', 'Изображение для телефона', 1448, 1086),
                TextInput::make('link_url')
                    ->label('Ссылка')
                    ->helperText('Необязательно. Например: /catalog, #catalog или https://example.com.')
                    ->string()
                    ->maxLength(2048)
                    ->rules([
                        'not_regex:/[\s\x00-\x1F\x7F\\\\]/u',
                        Rule::anyOf([
                            ['url:http,https'],
                            ['regex:~\A/(?!/).*\z~u'],
                            ['regex:~\A#.+\z~u'],
                        ]),
                    ])
                    ->columnSpanFull(),
                Toggle::make('open_in_new_tab')
                    ->label('Открывать ссылку в новой вкладке')
                    ->rules(['required'])
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->rules(['required'])
                    ->columnSpanFull(),
            ]);
    }

    private static function imageUpload(string $collection, string $label, int $width, int $height): MediaImageUpload
    {
        return MediaImageUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->required()
            ->maxFiles(1)
            ->helperText("Статичное JPEG, PNG или WebP до 12 МБ. Результат: {$width} × {$height}, без обрезки и увеличения, с прозрачными полями. Исходник не сохраняется.")
            ->processImage(static fn (Image $image): Image => $image->fit(
                Fit::Fill,
                $width,
                $height,
                backgroundColor: '#000',
            ));
    }
}
