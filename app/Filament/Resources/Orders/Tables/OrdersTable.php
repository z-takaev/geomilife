<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with('shipment'))
            ->columns([
                TextColumn::make('number')->label('Номер')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('customer_name')->label('Получатель')->searchable(),
                TextColumn::make('customer_phone')->label('Телефон')->searchable(),
                TextColumn::make('customer_email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')->label('Итого')->formatStateUsing(fn ($state): string => $state->format())->sortable(),
                TextColumn::make('delivery_method')->label('Получение'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('payment_status')->label('Оплата')->badge(),
                IconColumn::make('is_preorder')->label('Предзаказ')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shipment.cdek_number')->label('Трек-номер')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('status')->label('Статус заказа')->options(OrderStatusEnum::class),
                SelectFilter::make('payment_status')->label('Оплата')->options(PaymentStatusEnum::class),
                SelectFilter::make('delivery_method')->label('Получение')->options(DeliveryMethodEnum::class),
                Filter::make('created_at')->label('Период оформления')->schema([
                    DatePicker::make('from')->label('С даты'), DatePicker::make('until')->label('По дату'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])->defaultSort('created_at', 'desc')->recordActions([ViewAction::make()]);
    }
}
