<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
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
                    ->required()
                    ->columnSpanFull(),
                RichEditor::make('answer')
                    ->label('Ответ')
                    ->required()
                    ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->preventFileAttachmentPathTampering()
                    ->resizableImages()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->columnSpanFull(),
            ]);
    }
}
