<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\Orders\OrderDocumentTypeEnum;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Users\UserResource;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\Payment;
use App\Support\Values\Money;
use App\ViewModel\Orders\OrderViewModel;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

final class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Сводка')->schema([
                TextEntry::make('number')->label('Номер')->placeholder('—'),
                TextEntry::make('created_at')->label('Оформлен')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('status')->label('Статус')->badge()->placeholder('—'),
                TextEntry::make('payment_status')->label('Оплата')->badge()->placeholder('—'),
                TextEntry::make('delivery_method')->label('Получение')->placeholder('—'),
                TextEntry::make('total')->label('Итого')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                TextEntry::make('is_preorder')->label('Предзаказ')->state('Да')
                    ->visible(fn (Order $record): bool => $record->is_preorder),
            ])->columns(3)->columnSpanFull(),

            Section::make('Обработка заказа')->schema([
                View::make('filament.orders.processing-stages')
                    ->viewData(fn (Order $record): array => ['stages' => OrderViewModel::processingStages($record)]),
                TextEntry::make('processing_hint')->hiddenLabel()
                    ->state(fn (Order $record): string => OrderViewModel::processingHint($record)),
                Actions::make(fn (ViewOrder $livewire): array => $livewire->processingActions())->key('processingActions'),
            ])->columnSpanFull(),

            Section::make('Покупатель')->schema([
                TextEntry::make('customer_name')->label('Получатель')->placeholder('—'),
                TextEntry::make('customer_phone')->label('Телефон')->placeholder('—')->copyable(),
                TextEntry::make('customer_email')->label('Email')->placeholder('—')->copyable(),
                TextEntry::make('customer_account')->label('Аккаунт')
                    ->state(fn (Order $record): string => OrderViewModel::accountLabel($record))
                    ->url(fn (Order $record): ?string => $record->user === null
                        ? null
                        : UserResource::getUrl('view', ['record' => $record->user])),
                TextEntry::make('comment')->label('Комментарий покупателя')->placeholder('Без комментария')->columnSpanFull(),
            ])->columns(2),

            Section::make('Получение')->schema([
                TextEntry::make('delivery_label')->label('Способ')
                    ->state(fn (Order $record): string => OrderViewModel::deliveryLabel($record)),
                TextEntry::make('address_text')
                    ->label(fn (Order $record): string => match (true) {
                        $record->delivery_method->isCdek() => 'ПВЗ',
                        $record->delivery_method->isCourier() => 'Адрес',
                        default => 'Место получения',
                    })
                    ->state(fn (Order $record): string => OrderViewModel::address($record) ?: '—'),
                TextEntry::make('shipment.cdek_number')->label('Трек-номер')->placeholder('—')->copyable()
                    ->visible(fn (Order $record): bool => $record->delivery_method->isCdek()),
                TextEntry::make('delivery_details.base_minor')->label('Курьер')
                    ->formatStateUsing(fn ($state): string => self::moneyFromNullableMinor($state))
                    ->visible(fn (Order $record): bool => $record->delivery_method->isCourier()),
                TextEntry::make('delivery_details.door_minor')->label('До двери')
                    ->formatStateUsing(fn ($state): string => self::moneyFromNullableMinor($state))
                    ->visible(fn (Order $record): bool => $record->delivery_method->isCourier()),
                TextEntry::make('delivery_details.lifting_minor')->label('Подъём')
                    ->formatStateUsing(fn ($state): string => self::moneyFromNullableMinor($state))
                    ->visible(fn (Order $record): bool => $record->delivery_method->isCourier()),
            ])->columns(2),

            Section::make('Товары')->schema([
                TextEntry::make('items_empty')->hiddenLabel()->state('Состав заказа отсутствует')
                    ->visible(fn (Order $record): bool => $record->items->isEmpty())
                    ->columnSpanFull(),
                RepeatableEntry::make('items')->hiddenLabel()
                    ->table([
                        TableColumn::make('Артикул')->width('140px'),
                        TableColumn::make('Товар'),
                        TableColumn::make('Фасовка')->width('160px'),
                        TableColumn::make('Количество')->width('100px')->alignment(Alignment::End),
                        TableColumn::make('Цена')->width('120px')->alignment(Alignment::End),
                        TableColumn::make('Сумма')->width('120px')->alignment(Alignment::End),
                    ])->schema([
                        TextEntry::make('sku')->placeholder('—'),
                        TextEntry::make('product_name')->placeholder('—'),
                        TextEntry::make('variant_name')->placeholder('—'),
                        TextEntry::make('quantity')->placeholder('—'),
                        TextEntry::make('price')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                        TextEntry::make('total')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                    ])->visible(fn (Order $record): bool => $record->items->isNotEmpty())
                    ->columnSpanFull(),
                TextEntry::make('subtotal')->label('Товары')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                TextEntry::make('delivery_price')->label('Доставка')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                TextEntry::make('total')->label('Итого')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
            ])->columns(3)->compact()->columnSpanFull(),

            Section::make('Упаковка')->schema([
                TextEntry::make('packages_empty')->hiddenLabel()->state('Упаковка ещё не подготовлена')
                    ->visible(fn (Order $record): bool => empty($record->packages)),
                RepeatableEntry::make('packages')->label('Грузовые места')->schema([
                    TextEntry::make('number')->label('Место')->placeholder('—'),
                    TextEntry::make('box_name')->label('Коробка')->placeholder('—'),
                    TextEntry::make('weight')->label('Полный вес, г')->placeholder('—'),
                ])->columns(3)->visible(fn (Order $record): bool => ! empty($record->packages)),
            ])->visible(fn (Order $record): bool => $record->delivery_method->isCdek())->columnSpanFull(),

            Section::make('Оплата')->schema([
                TextEntry::make('payment_method')->label('Способ')->placeholder('—'),
                TextEntry::make('payment_status')->label('Статус')->badge()->placeholder('—'),
                TextEntry::make('payments_empty')->hiddenLabel()->state('Платёж не создавался')
                    ->visible(fn (Order $record): bool => $record->payment_method->isOnline() && $record->payments->isEmpty())
                    ->columnSpanFull(),
                RepeatableEntry::make('payments')->label('Платёж в Т-Банке')->schema([
                    TextEntry::make('transaction_id')->label('Идентификатор')->placeholder('—')->copyable(),
                    TextEntry::make('amount')->label('Сумма')->formatStateUsing(fn ($state): string => $state->format())->placeholder('—'),
                    TextEntry::make('status')->label('Состояние')->placeholder('—'),
                    TextEntry::make('error')->label('Причина ошибки')->color('danger')
                        ->visible(fn (Payment $record): bool => filled($record->error)
                            && in_array($record->status->value, ['FAILED', 'REFUND_FAILED'], true))
                        ->columnSpanFull(),
                ])->columns(3)
                    ->visible(fn (Order $record): bool => $record->payment_method->isOnline() && $record->payments->isNotEmpty())
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),

            Section::make('Отмена заказа')->schema([
                TextEntry::make('cancellation_state')->label('Состояние')
                    ->formatStateUsing(fn (?string $state): string => OrderViewModel::cancellationStateLabel($state)),
                TextEntry::make('cancellation_reason')->label('Причина')->placeholder('—'),
                TextEntry::make('cancelled_at')->label('Дата отмены')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('payment_status')->label('Состояние возврата')->badge()
                    ->visible(fn (Order $record): bool => $record->payment_method->isOnline()),
            ])->columns(2)
                ->visible(fn (Order $record): bool => $record->cancellation_state !== null || $record->status->isCancelled())
                ->columnSpanFull(),

            Section::make('Доставка СДЭК')->schema([
                TextEntry::make('shipment_status')->label('Статус доставки')
                    ->state(fn (Order $record): string => OrderViewModel::cdekStatusLabel($record)),
                TextEntry::make('shipment_registration')->label('Состояние регистрации')
                    ->state(fn (Order $record): string => OrderViewModel::cdekRegistrationLabel($record)),
                TextEntry::make('shipment.error')->label('Причина ошибки')->color('danger')
                    ->visible(fn (Order $record): bool => filled($record->shipment?->error))->columnSpanFull(),
                TextEntry::make('documents_empty')->hiddenLabel()->state('Документы ещё не сформированы')
                    ->visible(fn (Order $record): bool => $record->documents->isEmpty())->columnSpanFull(),
                RepeatableEntry::make('documents')->label('Документы')->schema([
                    TextEntry::make('type')->label('Документ')
                        ->formatStateUsing(fn (OrderDocumentTypeEnum $state): string => $state->getLabel()),
                    TextEntry::make('state')->label('Статус')->badge()
                        ->placeholder('Состояние неизвестно'),
                    TextEntry::make('download')->label('Действие')->state('Скачать')
                        ->url(fn (OrderDocument $record): string => route(
                            $record->type->isWaybill() ? 'admin.orders.cdek-waybill' : 'admin.orders.cdek-barcodes',
                            $record->order_id,
                        ))
                        ->visible(fn (OrderDocument $record): bool => $record->state?->isReady() ?? false),
                    TextEntry::make('error')->label('Причина ошибки')->color('danger')
                        ->visible(fn (OrderDocument $record): bool => filled($record->error))->columnSpanFull(),
                ])->columns(3)->visible(fn (Order $record): bool => $record->documents->isNotEmpty())->columnSpanFull(),
                Section::make('Технические сведения')->schema([
                    TextEntry::make('shipment.uuid')->label('UUID СДЭК')->placeholder('—')->copyable(),
                ])->collapsible()->collapsed()->columnSpanFull(),
            ])->columns(2)
                ->visible(fn (Order $record): bool => $record->delivery_method->isCdek())
                ->columnSpanFull(),
        ])->columns(2);
    }

    private static function moneyFromNullableMinor(mixed $state): string
    {
        return $state === null ? '—' : Money::fromMinor((int) $state)->format();
    }
}
