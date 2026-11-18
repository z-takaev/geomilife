<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// TODO: как используется класс?
// TODO: Использовать подключеный пакет Nutgram?
final class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);
        $token = config('telegram.bot_token');

        if (! filled($chatId) || ! filled($token)) {
            throw new RuntimeException('Telegram не настроен для отправки уведомления.');
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $notification->toTelegram($notifiable),
            'link_preview_options' => ['is_disabled' => true],
        ];

        $action = method_exists($notification, 'telegramAction') ? $notification->telegramAction() : null;

        if ($action !== null) {
            $payload['reply_markup'] = [
                'inline_keyboard' => [[$action]],
            ];
        }

        $response = Http::connectTimeout(5)
            ->timeout(15)
            ->post('https://api.telegram.org/bot'.$token.'/sendMessage', $payload);

        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException('Telegram не принял уведомление. Проверьте доступ бота к чату.');
        }
    }
}
