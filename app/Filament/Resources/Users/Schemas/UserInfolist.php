<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('first_name')->label('Имя'),
            TextEntry::make('last_name')->label('Фамилия'),
            TextEntry::make('email')->label('Email'),
            TextEntry::make('phone')->label('Телефон'),
            TextEntry::make('birth_date')->label('Дата рождения')->date('d.m.Y')->placeholder('—'),
            TextEntry::make('city.name')->label('Город'),
            TextEntry::make('telegram_username')->label('Telegram')->placeholder('—'),
            TextEntry::make('created_at')->label('Дата регистрации')->dateTime('d.m.Y H:i'),
            IconEntry::make('is_blocked')->label('Заблокирован')->boolean(),
        ])->columns(2);
    }
}
