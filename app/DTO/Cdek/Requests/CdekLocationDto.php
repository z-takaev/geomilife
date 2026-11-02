<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CdekLocationDto
{
    use Makeable;

    /**
     * @param  int  $code  Код населённого пункта СДЭК.
     * @param  string|null  $address  Полный адрес; обязателен при создании курьерского отправления.
     */
    public function __construct(
        public int $code,
        public ?string $address = null,
    ) {
        if ($code < 1 || ($address !== null && trim($address) === '')) {
            throw new InvalidArgumentException('Укажите корректный код города и непустой адрес, если он передаётся.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'address' => $this->address,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
