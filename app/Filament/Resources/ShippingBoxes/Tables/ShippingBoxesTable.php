<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

final class ShippingBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Название')->searchable()->sortable(),
            ToggleColumn::make('is_active')->label('Доступна'),
        ])
            ->defaultSort('name')->recordActions([EditAction::make()])
            ->searchable(false)
            ->paginated(false);
    }
}
