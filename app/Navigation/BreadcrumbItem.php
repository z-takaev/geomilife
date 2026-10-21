<?php

declare(strict_types=1);

namespace App\Navigation;

final readonly class BreadcrumbItem
{
    private function __construct(
        public string $label,
        public string $url,
    ) {}

    public static function make(string $label, string $url): self
    {
        return new self($label, $url);
    }
}
