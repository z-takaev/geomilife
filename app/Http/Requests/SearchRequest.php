<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchRequest extends FormRequest
{
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
            'q' => ['string', 'max:255'],
        ];
    }

    public function searchQuery(): string
    {
        return $this->string('q')->toString();
    }

    protected function prepareForValidation(): void
    {
        $query = $this->query('q', '');

        $this->merge([
            'q' => is_string($query) ? mb_substr(trim($query), 0, 255) : '',
        ]);
    }
}
