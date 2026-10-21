<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class WebSiteSchema implements SchemaContract
{
    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    public function toArray(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'name' => 'GeoMiLife',
            'url' => route('home'),
            'publisher' => ['@id' => route('home').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search.index').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
