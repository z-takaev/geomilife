<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CdekContactDto
{
    use Makeable;

    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email = null,
        public ?string $company = null,
    ) {
        if (trim($name) === '' || trim($phone) === '') {
            throw new InvalidArgumentException('Имя и телефон контакта обязательны.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phones' => [['number' => $this->phone]],
            'email' => $this->email,
            'company' => $this->company,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
