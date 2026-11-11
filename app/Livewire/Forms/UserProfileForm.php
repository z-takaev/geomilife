<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

final class UserProfileForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $birth_date = null;

    public ?string $phone = null;

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore(auth()->id())],
        ];
    }
}
