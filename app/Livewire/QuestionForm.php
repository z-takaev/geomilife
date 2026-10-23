<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Mail\QuestionSubmitted;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

final class QuestionForm extends Component
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

            return;
        }

        Mail::to($adminEmail)->send(new QuestionSubmitted(
            name: $validated['name'],
            phone: $validated['phone'],
            question: $validated['message'],
        ));

        $this->reset(['name', 'phone', 'message']);
        $this->resetValidation();
        $this->dispatch('question-sent');
    }

    public function render(): View
    {
        return view('livewire.question-form');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\\+7 \\(\\d{3}\\) \\d{3}-\\d{2}-\\d{2}$/'],
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
