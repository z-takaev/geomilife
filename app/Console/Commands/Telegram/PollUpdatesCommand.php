<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

final class PollUpdatesCommand extends Command
{
    protected $signature = 'telegram:poll';

    protected $description = 'Получать команды Telegram-бота через long polling';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Polling доступен только локально. На сервере используйте telegram:webhook.');

            return self::FAILURE;
        }

        if (blank(config('telegram.bot_token'))) {
            $this->error('Укажите TELEGRAM_BOT_TOKEN в .env.');

            return self::FAILURE;
        }

        $bot = app(Nutgram::class);
        $bot->deleteWebhook();
        $this->info('Ожидание сообщений Telegram.');
        $bot->run();

        return self::SUCCESS;
    }
}
