<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

final class QuestionSubmissionForm extends Form
{
    public string $name = '';

    public string $phone = '';

    public string $message = '';

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
