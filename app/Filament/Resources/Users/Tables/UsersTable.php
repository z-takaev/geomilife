<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Users\ExportUsersAction;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Фамилия')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('is_blocked')
                    ->label('Заблокирован')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_blocked')->label('Блокировка')->trueLabel('Заблокированные')->falseLabel('Незаблокированные'),
                SelectFilter::make('city')->label('Город')->relationship('city', 'name')->searchable(),
                Filter::make('created_at')
                    ->label('Дата регистрации')
                    ->schema([
                        DatePicker::make('from')->label('С даты'),
                        DatePicker::make('until')->label('По дату')->afterOrEqual('from'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_filter([
                        filled($data['from'] ?? null) ? 'Регистрация с: '.$data['from'] : null,
                        filled($data['until'] ?? null) ? 'Регистрация по: '.$data['until'] : null,
                    ])),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Экспорт в Excel')
                    ->authorize(fn (): bool => auth('filament')->user()?->can('export', User::class) ?? false)
                    ->action(fn (ListUsers $livewire): StreamedResponse => app(ExportUsersAction::class)->handle($livewire->getFilteredSortedTableQuery())),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(static fn (User $record): bool => ! UserResource::canEdit($record)),
                EditAction::make(),
            ]);
    }
}
