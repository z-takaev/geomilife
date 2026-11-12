<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\Email\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    protected $errorBag = 'resetPassword';

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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:50'],
            'password' => ['required', 'confirmed', 'max:50', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailNormalizer::normalize((string) $this->input('email')),
        ]);
    }
}
