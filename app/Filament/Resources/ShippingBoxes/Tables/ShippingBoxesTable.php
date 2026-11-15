<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ShippingBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Название')->searchable()->sortable(),
            IconColumn::make('is_active')->label('Доступна')->boolean(),
        ])
            ->defaultSort('id', 'desc')->recordActions([EditAction::make()])
            ->searchable(false)
            ->paginated(false);
    }
}
