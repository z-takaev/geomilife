<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;

final class TelegramService
{
    public function isConfigured(): bool
    {
        return filled(config('telegram.bot_username')) && filled(config('telegram.bot_token'));
    }

    public function createConnectionUrl(User $user): string
    {
        $token = Str::random(64);
        $user->forceFill(['telegram_link_token' => $token])->save();
        $username = ltrim((string) config('telegram.bot_username'), '@');

        return "https://t.me/{$username}?start={$token}";
    }

    public function disconnect(User $user): void
    {
        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => null,
            'telegram_notifications_enabled' => false,
        ])->save();
    }

    public function handleMessage(Nutgram $bot): void
    {
        if (! $bot->chat()?->isPrivate()) {
            return;
        }

        $text = trim($bot->message()?->text ?? '');
        if (preg_match('/^\/start(?:@\w+)?\s+([a-zA-Z0-9]{64})$/', $text, $matches) !== 1) {
            $bot->sendMessage('Чтобы подключить уведомления, откройте ссылку подключения в профиле сайта.');

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
