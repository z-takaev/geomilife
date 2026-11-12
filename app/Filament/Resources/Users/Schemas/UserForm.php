<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\City;
use App\Support\Email\EmailNormalizer;
use App\Support\Phone\PhoneFormat;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(50),
                TextInput::make('last_name')
                    ->label('Фамилия')
                    ->maxLength(50),
                DatePicker::make('birth_date')
                    ->label('Дата рождения')
                    ->before('today'),
                Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->getOptionLabelFromRecordUsing(static fn (City $city): string => self::cityOptionLabel($city))
                    ->searchable(['name']),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->mutateStateForValidationUsing(static fn (?string $state): ?string => EmailNormalizer::normalize($state))
                    ->dehydrateStateUsing(static fn (?string $state): ?string => EmailNormalizer::normalize($state))
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->mask(PhoneFormat::MASK)
                    ->regex(PhoneFormat::FORMATTED_OR_COMPACT_PATTERN)
                    ->unique(ignoreRecord: true)
                    ->maxLength(40),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->rule(Password::defaults())
                    ->maxLength(50)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->helperText('Оставьте пустым, чтобы сохранить текущий пароль.'),
                TextInput::make('telegram_username')
                    ->label('Имя пользователя Telegram')
                    ->disabled()
                    ->dehydrated(false),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Checkbox::make('is_blocked')
                            ->label('Заблокирован'),
                        Checkbox::make('telegram_notifications_enabled')
                            ->label('Уведомления в Telegram')
                            ->default(true),
                    ]),
            ]);
    }

    private static function cityOptionLabel(City $city): string
    {
        return blank($city->region)
            ? $city->name
            : $city->name.', '.$city->region;
    }
}
