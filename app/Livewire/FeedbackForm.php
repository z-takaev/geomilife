<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use App\Notifications\FeedbackNotification;
use App\Rules\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

final class FeedbackForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $message = '';

    public function submit(): void
    {
        $validated = $this->validate();
        $adminEmail = Setting::cached()?->admin_email;

        if (blank($adminEmail)) {
            $this->addError('message', __('Не удалось отправить вопрос. Попробуйте позже.'));
            $this->dispatch('notify', message: __('Не удалось отправить вопрос. Попробуйте позже.'), type: 'error');

            return;
        }

        Notification::route('mail', $adminEmail)->notify(new FeedbackNotification(
            name: $validated['name'],
            phone: $validated['phone'],
            question: $validated['message'],
        ));

        $this->reset('name', 'phone', 'message');
        $this->resetValidation();
        $this->dispatch('question-sent');
        $this->dispatch('notify', message: __('Ваш вопрос отправлен.'), type: 'success');
    }

    public function render(): View
    {
        return view('livewire.feedback-form');
    }

    /** @return array<string, array<int, string|PhoneNumber>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new PhoneNumber],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('имя'),
            'phone' => __('контактный телефон'),
            'message' => __('вопрос'),
        ];
    }
}
