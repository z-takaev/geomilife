<?php

declare(strict_types=1);

namespace App\Filament\Resources\FilamentUsers;

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Resources\FilamentUsers\Pages\CreateFilamentUser;
use App\Filament\Resources\FilamentUsers\Pages\EditFilamentUser;
use App\Filament\Resources\FilamentUsers\Pages\ListFilamentUsers;
use App\Filament\Resources\FilamentUsers\Schemas\FilamentUserForm;
use App\Filament\Resources\FilamentUsers\Tables\FilamentUsersTable;
use App\Models\FilamentUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FilamentUserResource extends Resource
{
    protected static ?string $model = FilamentUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'managers';

    protected static ?string $navigationLabel = 'Менеджеры';

    protected static ?string $modelLabel = 'менеджера';

    protected static ?string $pluralModelLabel = 'Менеджеры';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return FilamentUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FilamentUsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', FilamentUserRoleEnum::MANAGER);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFilamentUsers::route('/'),
            'create' => CreateFilamentUser::route('/create'),
            'edit' => EditFilamentUser::route('/{record}/edit'),
        ];
    }
}
