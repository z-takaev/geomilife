<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

final class CdekWebhookRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:ORDER_STATUS'],
            'date_time' => ['required', 'date'],
            'uuid' => ['required', 'uuid'],
            'attributes' => ['required', 'array'],
            'attributes.is_return' => ['required', 'boolean'],
            'attributes.cdek_number' => ['required', 'string'],
            'attributes.status_code' => ['required', 'string'],
            'attributes.status_date_time' => ['required', 'date'],
            'attributes.code' => ['required', 'string'],
            'attributes.is_reverse' => ['required', 'boolean'],
            'attributes.is_client_return' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        abort_unless($this->isJson(), 415);
    }
}
