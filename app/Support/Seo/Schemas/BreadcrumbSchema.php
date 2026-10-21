<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class BreadcrumbSchema implements SchemaContract
{
    /**
     * @param  array<string, string>  $items
     */
    private function __construct(private array $items) {}

    /**
     * @param  array<string, string>  $items
     */
    public static function make(array $items): self
    {
        return new self($items);
    }

    public function toArray(): array
    {
        $itemList = [];
        $position = 1;

        foreach ($this->items as $name => $url) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }
}
