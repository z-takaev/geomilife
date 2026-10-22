<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewSourceEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('author_name')
                    ->label('Имя комментатора')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('reviewed_at')
                    ->label('Дата отзыва')
                    ->required(),
                TextInput::make('rating')
                    ->label('Рейтинг')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->step(0.1)
                    ->required(),
                Select::make('source')
                    ->label('Источник отзыва')
                    ->options(ReviewSourceEnum::class)
                    ->placeholder('Не указан'),
                Textarea::make('text')
                    ->label('Текст отзыва')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->columnSpanFull(),
            ]);
    }
}
