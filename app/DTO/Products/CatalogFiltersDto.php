<?php

declare(strict_types=1);

namespace App\DTO\Products;

final readonly class CatalogFiltersDto
{
    public const array AVAILABILITY = ['in-stock', 'preorder'];

    public const int MAX_PRICE = 1_000_000_000;

    public function __construct(
        public ?int $priceFrom = null,
        public ?int $priceTo = null,
        public array $availability = [],
        public array $packageValues = [],
    ) {}

    public static function fromArray(array $values, array $packageOptions = []): self
    {
        $priceFrom = self::price($values['priceFrom'] ?? null);
        $priceTo = self::price($values['priceTo'] ?? null);

        if ($priceFrom !== null && $priceTo !== null && $priceFrom > $priceTo) {
            $priceFrom = $priceTo = null;
        }

        return new self(
            priceFrom: $priceFrom,
            priceTo: $priceTo,
            availability: self::allowedValues($values['availability'] ?? [], self::AVAILABILITY),
            packageValues: self::allowedValues($values['packageValues'] ?? [], array_column($packageOptions, 'value')),
        );
    }

    public function toArray(): array
    {
        return [
            'priceFrom' => $this->priceFrom === null ? '' : (string) $this->priceFrom,
            'priceTo' => $this->priceTo === null ? '' : (string) $this->priceTo,
            'availability' => $this->availability,
            'packageValues' => $this->packageValues,
        ];
    }

    private static function price(mixed $value): ?int
    {
        if ((! is_int($value) && ! is_string($value)) || preg_match('/^\d{1,10}$/', (string) $value) !== 1) {
            return null;
        }

        return (int) $value <= self::MAX_PRICE ? (int) $value : null;
    }

    private static function allowedValues(mixed $values, array $allowed): array
    {
        return is_array($values)
            ? array_values(array_intersect($allowed, array_filter($values, is_string(...))))
            : [];
    }
}
