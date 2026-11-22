<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\Support\Values\Money;

final readonly class CheckoutQuoteDto
{
    public function __construct(
        public string $fingerprint,
        public Money $total,
    ) {}

    public static function tryFromArray(mixed $data): ?self
    {
        if (! is_array($data)
            || ! is_string($data['fingerprint'] ?? null)
            || ! is_int($data['total_minor'] ?? null)
            || $data['total_minor'] < 0) {
            return null;
        }

        return new self($data['fingerprint'], Money::fromMinor($data['total_minor']));
    }

    /** @return array{fingerprint: string, total_minor: int} */
    public function toArray(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'total_minor' => $this->total->minorAmount(),
        ];
    }
}
