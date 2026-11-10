<?php

declare(strict_types=1);

namespace App\Notifications\Orders;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class DeliveryQuoteErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $cityId)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable): string
    {
        return __('Не удалось рассчитать доставку СДЭК для города с ID :id. Проверьте журнал интеграции.', ['id' => $this->cityId]);
    }
}
