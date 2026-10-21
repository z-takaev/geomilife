<?php

declare(strict_types=1);

namespace App\Navigation;

final readonly class Breadcrumbs
{
    /**
     * @param  list<BreadcrumbItem>  $items
     */
    private function __construct(private array $items) {}

    public static function make(BreadcrumbItem ...$items): self
    {
        return new self([
            BreadcrumbItem::make(__('Главная'), route('home')),
            ...$items,
        ]);
    }

    /**
     * @return list<BreadcrumbItem>
     */
    public function items(): array
    {
        return $this->items;
    }
}
