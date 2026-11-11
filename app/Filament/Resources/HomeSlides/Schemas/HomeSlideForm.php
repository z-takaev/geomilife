<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Schemas;

use App\Filament\Forms\Components\SpatieMediaLibrary;
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
                SpatieMediaLibrary::make('desktop_image')
                    ->label('Изображение для компьютера')
                    ->collection('desktop_image')
                    ->required()
                    ->maxFiles(1)
                    ->helperText('Рекомендуемый размер: 1920×720 пикселей.')
                    ->processImage(static fn (Image $image): Image => $image->fit(
                        Fit::Fill,
                        1920,
                        720,
                        backgroundColor: '#000',
                    )),
                SpatieMediaLibrary::make('mobile_image')
                    ->label('Изображение для телефона')
                    ->collection('mobile_image')
                    ->required()
                    ->maxFiles(1)
                    ->helperText('Рекомендуемый размер: 1448×1086 пикселей.')
                    ->processImage(static fn (Image $image): Image => $image->fit(
                        Fit::Fill,
                        1448,
                        1086,
                        backgroundColor: '#000',
                    )),
                TextInput::make('link_url')
                    ->label('Ссылка')
                    ->helperText('Например: /catalog, #catalog или https://example.com.')
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
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->columnSpanFull(),
            ]);
    }
}
