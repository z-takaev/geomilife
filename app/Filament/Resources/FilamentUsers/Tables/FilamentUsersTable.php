<?php

declare(strict_types=1);

namespace App\Filament\Resources\FilamentUsers\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FilamentUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Менеджеры не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->searchable(false)
            ->paginated(false);
    }
}
