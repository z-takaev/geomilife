<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class QuestionSubmittedNotification extends Notification
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $question,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Новый вопрос с сайта'))
            ->greeting(__('Новый вопрос с сайта'))
            ->line(__('Имя: :name', ['name' => $this->name]))
            ->line(__('Контактный телефон: :phone', ['phone' => $this->phone]))
            ->line(__('Вопрос: :question', ['question' => $this->question]));
    }
}
