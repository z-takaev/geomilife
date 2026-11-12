<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Support\Values\Money;
use App\ViewModel\Orders\OrderViewModel;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

final class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Обработка заказа')->schema([
                View::make('filament.orders.processing-stages')
                    ->viewData(fn (Order $record): array => ['stages' => OrderViewModel::processingStages($record)]),
                TextEntry::make('processing_hint')->hiddenLabel()
                    ->state(fn (Order $record): string => OrderViewModel::processingHint($record)),
                Actions::make(fn (ViewOrder $livewire): array => $livewire->processingActions())->key('processingActions'),
            ]),
            Section::make('Общие сведения')->schema([
                TextEntry::make('number')->label('Номер'),
                TextEntry::make('created_at')->label('Оформлен')->dateTime('d.m.Y H:i'),
                TextEntry::make('status')->label('Статус')->badge(),
                TextEntry::make('payment_status')->label('Оплата')->badge(),
                TextEntry::make('is_preorder')->label('Предзаказ')->formatStateUsing(fn (bool $state): string => $state ? 'Да' : 'Нет'),
                TextEntry::make('cancellation_state')->label('Операция отмены')->placeholder('—'),
                TextEntry::make('cancellation_reason')->label('Причина отмены')->placeholder('—'),
            ])->columns(3),
            Section::make('Покупатель')->schema([
                TextEntry::make('customer_name')->label('Получатель'),
                TextEntry::make('customer_phone')->label('Телефон'),
                TextEntry::make('customer_email')->label('Email'),
                TextEntry::make('user_id')->label('Аккаунт')->placeholder('Гость'),
                TextEntry::make('comment')->label('Комментарий покупателя')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
            Section::make('Товары')->schema([
                RepeatableEntry::make('items')->label('Состав')->schema([
                    TextEntry::make('sku')->label('Артикул'), TextEntry::make('product_name')->label('Товар'),
                    TextEntry::make('variant_name')->label('Фасовка'), TextEntry::make('quantity')->label('Количество'),
                    TextEntry::make('price')->label('Цена')->formatStateUsing(fn ($state): string => $state->format()),
                    TextEntry::make('total')->label('Сумма')->formatStateUsing(fn ($state): string => $state->format()),
                ])->columns(3),
            ]),
            Section::make('Получение и сумма')->schema([
                TextEntry::make('delivery_label')->label('Способ')->state(fn (Order $record): string => OrderViewModel::deliveryLabel($record)),
                TextEntry::make('address_text')->label('Адрес / ПВЗ')->state(fn (Order $record): string => OrderViewModel::address($record)),
                TextEntry::make('subtotal')->label('Товары')->formatStateUsing(fn ($state): string => $state->format()),
                TextEntry::make('delivery_price')->label('Доставка')->formatStateUsing(fn ($state): string => $state->format()),
                TextEntry::make('total')->label('Итого')->formatStateUsing(fn ($state): string => $state->format()),
                TextEntry::make('payment_method')->label('Способ оплаты'),
            ])->columns(2),
            Section::make('Расчёт курьера')->schema([
                TextEntry::make('delivery_details.base_minor')->label('Курьер')->formatStateUsing(fn ($state): string => Money::fromMinor((int) $state)->format()),
                TextEntry::make('delivery_details.door_minor')->label('До двери')->formatStateUsing(fn ($state): string => Money::fromMinor((int) $state)->format()),
                TextEntry::make('delivery_details.lifting_minor')->label('Подъём')->formatStateUsing(fn ($state): string => Money::fromMinor((int) $state)->format()),
            ])->columns(3)->visible(fn (Order $record): bool => $record->delivery_method->isCourier()),
            Section::make('Упаковка')->schema([
                RepeatableEntry::make('packages')->label('Грузовые места')->schema([
                    TextEntry::make('number')->label('Место'), TextEntry::make('box_name')->label('Коробка'),
                    TextEntry::make('weight')->label('Полный вес, г'),
                ])->columns(3),
            ])->visible(fn (Order $record): bool => $record->delivery_method->isCdek()),
            Section::make('Платежи')->schema([
                RepeatableEntry::make('payments')->label('Попытки')->schema([
                    TextEntry::make('transaction_id')->label('Платёж банка'),
                    TextEntry::make('amount')->label('Сумма')->formatStateUsing(fn ($state): string => $state->format()),
                    TextEntry::make('status')->label('Состояние'),
                ])->columns(3),
            ]),
            Section::make('СДЭК')->schema([
                TextEntry::make('shipment.uuid')->label('UUID'), TextEntry::make('shipment.cdek_number')->label('Трек-номер')->copyable(),
                TextEntry::make('shipment.status')->label('Статус перевозчика'), TextEntry::make('shipment.request_state')->label('Регистрация'),
                TextEntry::make('shipment.operation_state')->label('Операция'),
                TextEntry::make('shipment.error')->label('Причина ошибки')->color('danger')
                    ->visible(fn (Order $record): bool => filled($record->shipment?->error))->columnSpanFull(),
            ])->columns(2)->visible(fn (Order $record): bool => $record->delivery_method->isCdek()),
            Section::make('Документы СДЭК')->schema([
                RepeatableEntry::make('documents')->label('Документы')->schema([
                    TextEntry::make('type')->label('Документ')
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'WAYBILL' => 'Квитанция',
                            'BARCODES' => 'Наклейки',
                            default => $state,
                        }),
                    TextEntry::make('state')->label('Статус')->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'PENDING' => 'Ожидает формирования',
                            'CREATING', 'ACCEPTED', 'PROCESSING' => 'Формируется',
                            'READY' => 'Готов',
                            'INVALID' => 'Не удалось подготовить',
                            'REMOVED' => 'Требуется повторное формирование',
                            default => $state,
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'READY' => 'success',
                            'INVALID' => 'danger',
                            default => 'warning',
                        }),
                    TextEntry::make('error')->label('Причина ошибки')->color('danger')
                        ->visible(fn (OrderDocument $record): bool => filled($record->error)),
                ])->columns(3),
            ])->visible(fn (Order $record): bool => $record->delivery_method->isCdek()),
        ])->columns(1);
    }
}
