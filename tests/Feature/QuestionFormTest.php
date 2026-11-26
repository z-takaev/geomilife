<?php

declare(strict_types=1);

use App\Livewire\FeedbackForm;
use App\Models\Setting;
use App\Notifications\FeedbackNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('отправляет вопрос администратору и очищает форму', function (): void {
    Notification::fake();
    Setting::query()->create(['admin_email' => 'admin@example.com']);

    config(['captcha.characters' => ['A']]);
    $this->get('/captcha/default')->assertOk();

    Livewire::test(FeedbackForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Когда доставят заказ?')
        ->set('captcha', 'AAAAAA')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('name', '')
        ->assertSet('phone', '')
        ->assertSet('message', '')
        ->assertDispatched('question-sent');

    Notification::assertSentOnDemandTimes(FeedbackNotification::class, 1);
    Notification::assertSentOnDemand(
        FeedbackNotification::class,
        function (FeedbackNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
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
        },
    );
});

it('проверяет обязательные поля и формат телефона', function (): void {
    Notification::fake();

    Livewire::test(FeedbackForm::class)
        ->set('phone', '123')
        ->call('submit')
        ->assertHasErrors([
            'name' => 'required',
            'phone',
            'message' => 'required',
        ]);

    Notification::assertNothingSent();
});

it('не отправляет вопрос без адреса администратора', function (): void {
    Notification::fake();

    config(['captcha.characters' => ['A']]);
    $this->get('/captcha/default')->assertOk();

    Livewire::test(FeedbackForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Вопрос')
        ->set('captcha', 'AAAAAA')
        ->call('submit')
        ->assertHasErrors('message')
        ->assertSee('Не удалось отправить вопрос. Попробуйте позже.')
        ->assertSet('name', 'Иван')
        ->assertSet('phone', '+7 (999) 123-45-67')
        ->assertSet('message', 'Вопрос');

    Notification::assertNothingSent();
});

it('не отправляет вопрос без кода с картинки', function (): void {
    Notification::fake();
    Setting::query()->create(['admin_email' => 'admin@example.com']);

    Livewire::test(FeedbackForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Вопрос')
        ->call('submit')
        ->assertHasErrors(['captcha' => 'required']);

    Notification::assertNothingSent();
});

it('отклоняет неверный код и обновляет картинку, сохраняя вопрос', function (): void {
    Notification::fake();
    Setting::query()->create(['admin_email' => 'admin@example.com']);
    config(['captcha.characters' => ['A']]);
    $this->get('/captcha/default')->assertOk();

    $form = Livewire::test(FeedbackForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Вопрос')
        ->set('captcha', 'BBBBBB');
    $initialImageUrl = $form->get('captchaImageUrl');

    $form->call('submit')
        ->assertHasErrors(['captcha' => 'captcha'])
        ->assertSet('captcha', '')
        ->assertSet('message', 'Вопрос');

    expect($form->get('captchaImageUrl'))->not->toBe($initialImageUrl);
    Notification::assertNothingSent();
});
