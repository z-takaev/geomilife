<?php

declare(strict_types=1);

namespace App\Filament\Resources\FilamentUsers\Schemas;

use App\Support\Email\EmailNormalizer;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

final class FilamentUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->mutateStateForValidationUsing(static fn (?string $state): ?string => EmailNormalizer::normalize($state))
                    ->dehydrateStateUsing(static fn (?string $state): ?string => EmailNormalizer::normalize($state))
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->autocomplete('new-password')
                    ->rule(Password::default())
                    ->maxLength(50)
                    ->confirmed()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('password_confirmation')
                    ->label('Подтверждение пароля')
                    ->password()
                    ->autocomplete('new-password')
                    ->maxLength(50)
                    ->requiredWith('password')
                    ->dehydrated(false),
            ]);
    }
}
