<?php

declare(strict_types=1);

use App\Livewire\QuestionForm;
use App\Models\Setting;
use App\Notifications\QuestionSubmittedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('отправляет вопрос администратору и очищает форму', function (): void {
    Notification::fake();
    Setting::query()->create(['admin_email' => 'admin@example.com']);

    Livewire::test(QuestionForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Когда доставят заказ?')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('name', '')
        ->assertSet('phone', '')
        ->assertSet('message', '')
        ->assertDispatched('question-sent');

    Notification::assertSentOnDemandTimes(QuestionSubmittedNotification::class, 1);
    Notification::assertSentOnDemand(QuestionSubmittedNotification::class,
        function (QuestionSubmittedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === 'admin@example.com'
                && $channels === ['mail']
                && $mail instanceof MailMessage
                && $mail->subject === 'Новый вопрос с сайта'
                && $mail->greeting === 'Новый вопрос с сайта'
                && $mail->introLines === [
                    'Имя: Иван',
                    'Контактный телефон: +7 (999) 123-45-67',
                    'Вопрос: Когда доставят заказ?',
                ];
        });
});

it('проверяет обязательные поля и формат телефона', function (): void {
    Notification::fake();

    Livewire::test(QuestionForm::class)
        ->set('phone', '89991234567')
        ->call('submit')
        ->assertHasErrors([
            'name' => 'required',
            'phone' => 'regex',
            'message' => 'required',
        ]);

    Notification::assertNothingSent();
});

it('не отправляет вопрос без адреса администратора', function (): void {
    Notification::fake();

    Livewire::test(QuestionForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Вопрос')
        ->call('submit')
        ->assertHasErrors('message');

    Notification::assertNothingSent();
});
