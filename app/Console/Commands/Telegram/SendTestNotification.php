<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use Throwable;

final class SendTestNotification extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Отправить тестовое уведомление всем пользователям с подключённым Telegram';

    public function handle(): int
    {
        $users = User::query()->whereNotNull('telegram_chat_id')->where('telegram_notifications_enabled', true);

        if (blank(config('telegram.bot_token'))) {
            $this->error('Укажите TELEGRAM_BOT_TOKEN в .env.');

            return self::FAILURE;
        }

        $bot = app(Nutgram::class);
        $sent = 0;
        $failed = 0;

        foreach ($users->lazyById() as $user) {
            try {
                $message = $bot->sendMessage('Тестовое уведомление GeoMiLife. Telegram подключён и работает.', chat_id: $user->telegram_chat_id);
                if ($message === null) {
                    $failed++;
                    $this->warn("Не удалось отправить уведомление пользователю {$user->id}.");
                } else {
                    $sent++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $this->warn("Не удалось отправить уведомление пользователю {$user->id}.");
            }

            usleep(1000000);
        }

        $this->info("Отправлено: {$sent}. Ошибок: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
