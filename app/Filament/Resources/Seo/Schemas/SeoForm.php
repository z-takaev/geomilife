<?php

declare(strict_types=1);

namespace App\Filament\Resources\Seo\Schemas;

use App\Models\Seo;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SeoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::section(showPage: true),
            ]);
    }

    public static function relationshipSection(): Section
    {
        return self::section()
            ->relationship('seo');
    }

    private static function section(bool $showPage = false): Section
    {
        return Section::make('Поисковая оптимизация')
            ->description('Пустой SEO-заголовок будет заполнен заголовком страницы. При пустом SEO-описании meta description не выводится.')
            ->schema([
                ...($showPage ? [
                    TextEntry::make('path')
                        ->label('Страница')
                        ->url(static fn (?Seo $record): ?string => $record?->url)
                        ->openUrlInNewTab()
                        ->placeholder('Страница недоступна'),
                ] : []),
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
