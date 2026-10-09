<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Trees;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Openplain\FilamentTreeView\Fields\IconField;
use Openplain\FilamentTreeView\Fields\TextField;
use Openplain\FilamentTreeView\Tree;

final class CategoriesTree
{
    public static function configure(Tree $tree): Tree
    {
        return $tree
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->withExists(['children', 'products']))
            ->maxDepth(2)
            ->autoSave()
            ->fields([
                TextField::make('name'),
                IconField::make('is_active')->alignEnd(),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(static fn (Category $record): bool => CategoryResource::canEdit($record))
                    ->url(static fn (Category $record): string => CategoryResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->authorize(static fn (Category $record): bool => CategoryResource::canDelete($record)),
            ])
            ->emptyStateHeading('Категории не найдены')
            ->emptyStateDescription('Создайте первую категорию.');
    }
}
