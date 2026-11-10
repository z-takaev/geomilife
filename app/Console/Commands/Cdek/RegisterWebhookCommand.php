<?php

declare(strict_types=1);

namespace App\Console\Commands\Cdek;

use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Console\Command;

final class RegisterWebhookCommand extends Command
{
    protected $signature = 'cdek:webhook';

    protected $description = 'Зарегистрировать вебхук статусов заказов СДЭК';

    public function handle(): int
    {
        $url = route('cdek.webhook');

        if (filter_var($url, FILTER_VALIDATE_URL) === false || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $this->error('Укажите публичный HTTPS-адрес сайта в APP_URL.');

            return self::FAILURE;
        }

        try {
            $cdek = app(CdekService::class);

            foreach ($cdek->getWebhooks() as $webhook) {
                if ($webhook->type === 'ORDER_STATUS' && $webhook->url === $url) {
                    $this->info("Вебхук СДЭК уже зарегистрирован: {$url}");

                    return self::SUCCESS;
                }
            }

            $registration = $cdek->createOrderStatusWebhook($url);

            if ($registration->errors !== [] || ($registration->state?->isInvalid() ?? false) || $registration->uuid === null) {
                $this->error('Не удалось зарегистрировать вебхук СДЭК.');

                return self::FAILURE;
            }
        } catch (CdekApiException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Вебхук СДЭК зарегистрирован: {$url}");

        return self::SUCCESS;
    }
}
