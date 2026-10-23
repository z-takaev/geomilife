<?php

declare(strict_types=1);

use App\Livewire\QuestionForm;
use App\Mail\QuestionSubmitted;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('отправляет вопрос администратору и очищает форму', function (): void {
    Mail::fake();
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

    Mail::assertSent(QuestionSubmitted::class, function (QuestionSubmitted $mail): bool {
        return $mail->hasTo('admin@example.com')
            && $mail->name === 'Иван'
            && $mail->phone === '+7 (999) 123-45-67'
            && $mail->question === 'Когда доставят заказ?';
    });
});

it('проверяет обязательные поля и формат телефона', function (): void {
    Mail::fake();

    Livewire::test(QuestionForm::class)
        ->set('phone', '89991234567')
        ->call('submit')
        ->assertHasErrors([
            'name' => 'required',
            'phone' => 'regex',
            'message' => 'required',
        ]);

    Mail::assertNothingSent();
});

it('не отправляет вопрос без адреса администратора', function (): void {
    Mail::fake();

    Livewire::test(QuestionForm::class)
        ->set('name', 'Иван')
        ->set('phone', '+7 (999) 123-45-67')
        ->set('message', 'Вопрос')
        ->call('submit')
        ->assertHasErrors('message');

    Mail::assertNothingSent();
});
