<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Forms\QuestionSubmissionForm;
use App\Models\Setting;
use App\Notifications\QuestionSubmittedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

final class QuestionForm extends Component
{
    public QuestionSubmissionForm $form;

    public function submit(): void
    {
        $validated = $this->form->validate();
        $adminEmail = Setting::cached()?->admin_email;

        if (blank($adminEmail)) {
            $this->form->addError('message', __('Не удалось отправить вопрос. Попробуйте позже.'));
            $this->dispatch('notify', message: __('Не удалось отправить вопрос. Попробуйте позже.'), type: 'error');

            return;
        }

        Notification::route('mail', $adminEmail)->notify(new QuestionSubmittedNotification(
            name: $validated['name'],
            phone: $validated['phone'],
            question: $validated['message'],
        ));

        $this->form->reset();
        $this->resetValidation();
        $this->dispatch('question-sent');
        $this->dispatch('notify', message: __('Ваш вопрос отправлен.'), type: 'success');
    }

    public function render(): View
    {
        return view('livewire.question-form');
    }
}
