<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use JsonException;

final class TbankWebhookRequest extends FormRequest
{
    /** @var array<string, mixed> */
    private array $payload = [];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    protected function prepareForValidation(): void
    {
        abort_unless($this->isJson(), 415);

        try {
            $payload = json_decode($this->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['notification' => 'Некорректный JSON уведомления Т-Банка.']);
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw ValidationException::withMessages(['notification' => 'Ожидается JSON-объект уведомления Т-Банка.']);
        }

        $this->payload = $payload;
    }
}
