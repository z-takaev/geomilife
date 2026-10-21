<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class BreadcrumbSchema implements SchemaContract
{
    private function __construct(private Breadcrumbs $breadcrumbs) {}

    public static function make(Breadcrumbs $breadcrumbs): self
    {
        return new self($breadcrumbs);
    }

    public function toArray(): array
    {
        $itemList = [];
        $position = 1;

        foreach ($this->breadcrumbs->items() as $item) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item->label,
                'item' => $item->url,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }
}
