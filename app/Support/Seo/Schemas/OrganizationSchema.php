<?php

declare(strict_types=1);

namespace App\Support\Seo\Schemas;

use App\Models\Setting;
use App\Support\Seo\Schemas\Contracts\SchemaContract;

final readonly class OrganizationSchema implements SchemaContract
{
    private function __construct(private ?Setting $settings) {}

    public static function make(?Setting $settings): self
    {
        return new self($settings);
    }

    public function toArray(): array
    {
        $sameAs = array_values(array_filter([
            $this->settings?->instagram_url,
            $this->settings?->youtube_url,
            $this->settings?->vk_url,
            $this->settings?->telegram_url,
            $this->settings?->whatsapp_url,
        ]));

        return array_filter([
            '@type' => 'Organization',
            '@id' => route('home').'#organization',
            'name' => 'GeoMiLife',
            'url' => route('home'),
            'logo' => asset('assets/images/logo/logo-default.svg'),
            'email' => $this->settings?->email,
            'telephone' => $this->settings?->phone,
            'address' => $this->settings?->address !== null ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->settings->address,
            ] : null,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
