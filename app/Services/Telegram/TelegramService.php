<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

final class TelegramService
{
    public function handleMessage(Nutgram $bot): void
    {
        if (! $bot->chat()?->isPrivate()) {
            return;
        }

        $text = trim($bot->message()?->text ?? '');
        if (preg_match('/^\/start(?:@\w+)?\s+([a-zA-Z0-9]{64})$/', $text, $matches) !== 1) {
            $bot->sendMessage('Чтобы подключить уведомления, откройте ссылку подключения в профиле GeoMiLife.');

            return;
        }

        $updated = User::query()->where('telegram_link_token', $matches[1])->update([
            'telegram_chat_id' => $bot->chatId(),
            'telegram_username' => $bot->user()?->username,
            'telegram_link_token' => null,
            'telegram_notifications_enabled' => true,
        ]);

        $bot->sendMessage($updated > 0
            ? 'Telegram подключён. Вы будете получать уведомления о заказах.'
            : 'Ссылка подключения недействительна или уже использована. Создайте новую в профиле.');
    }
}
