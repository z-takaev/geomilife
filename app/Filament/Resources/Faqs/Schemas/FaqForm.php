<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')
                    ->label('Вопрос')
                    ->maxLength(200)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('answer')
                    ->label('Ответ')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->columnSpanFull(),
            ]);
    }
}
