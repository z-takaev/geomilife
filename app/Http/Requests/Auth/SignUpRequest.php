<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\Email\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class SignUpRequest extends FormRequest
{
    protected $errorBag = 'signUp';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:30'],
            'last_name' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:50', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', 'max:50', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => EmailNormalizer::normalize((string) $this->input('email')),
        ]);
    }
}
