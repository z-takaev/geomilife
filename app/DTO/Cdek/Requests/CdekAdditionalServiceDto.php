<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;

final readonly class CdekAdditionalServiceDto
{
    use Makeable;

    /**
     * @param  string  $code  Код услуги СДЭК, например INSURANCE или SMS.
     * @param  string|null  $parameter  Параметр услуги: сумма, количество или номер телефона.
     */
    public function __construct(
        public string $code,
        public ?string $parameter = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'parameter' => $this->parameter,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
