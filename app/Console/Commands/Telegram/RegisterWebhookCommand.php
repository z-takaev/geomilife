<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

final class RegisterWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook';

    protected $description = 'Зарегистрировать webhook Telegram';

    public function handle(): int
    {
        $secret = (string) config('telegram.webhook_secret');
        $url = route('telegram.webhook');

        if (blank(config('telegram.bot_token')) || preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $secret) !== 1) {
            $this->error('Укажите TELEGRAM_BOT_TOKEN и TELEGRAM_WEBHOOK_SECRET.');

            return self::FAILURE;
        }

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $this->error('Укажите публичный HTTPS-адрес сайта в APP_URL.');

            return self::FAILURE;
        }

        if (app(Nutgram::class)->setWebhook($url, allowed_updates: ['message'], secret_token: $secret) !== true) {
            $this->error('Не удалось зарегистрировать webhook Telegram.');

            return self::FAILURE;
        }

        $this->info("Webhook зарегистрирован: {$url}");

        return self::SUCCESS;
    }
}
