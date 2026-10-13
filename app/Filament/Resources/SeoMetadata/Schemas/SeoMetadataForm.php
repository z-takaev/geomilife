<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoMetadata\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SeoMetadataForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::section(),
            ]);
    }

    public static function relationshipSection(): Section
    {
        return self::section()
            ->relationship('seoMetadata');
    }

    private static function section(): Section
    {
        return Section::make('Поисковая оптимизация')
            ->description('Настройте отображение страницы в результатах поиска. Пустые поля будут заполнены данными страницы.')
            ->schema([
                TextInput::make('title')
                    ->label('SEO-заголовок')
                    ->helperText('Рекомендуемая длина — около 60 символов.')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('SEO-описание')
                    ->helperText('Рекомендуемая длина — около 160 символов.')
                    ->rows(4),
            ])
            ->columnSpanFull();
    }
}
