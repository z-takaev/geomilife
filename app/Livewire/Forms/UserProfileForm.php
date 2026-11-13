<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Support\Phone\PhoneFormat;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class UserProfileForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $birth_date = null;

    public ?string $phone = null;

    public ?int $city_id = null;

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'regex:'.PhoneFormat::COMPACT_PATTERN, Rule::unique('users', 'phone')->ignore(auth()->id())],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
        ];
    }
}
