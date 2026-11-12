<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\Email\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;

final class SignInRequest extends FormRequest
{
    protected $errorBag = 'signIn';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:50'],
            'password' => ['required', 'string', 'max:50'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => EmailNormalizer::normalize((string) $this->input('email')),
            'remember' => $this->boolean('remember'),
        ]);
    }
}
