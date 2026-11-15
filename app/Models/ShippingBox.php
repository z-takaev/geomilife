<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class ShippingBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_active',
        'length', 'width', 'height', 'weight',
    ];

    protected function casts(): array
    {
        return [
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            self::forgetCache();
        });

        self::deleted(static function (): void {
            self::forgetCache();
        });
    }

    public static function cached(): Collection
    {
        $boxes = Cache::memo()->rememberForever(
            (string) config('cache.items.shipping_boxes.key'),
            static fn (): array => self::query()
                ->where('is_active', true)
                ->orderByRaw('CAST(length AS UNSIGNED) * width * height')
                ->orderBy('id')
                ->get()
                ->map(static fn (self $box): array => $box->getAttributes())
                ->all(),
        );

        return new Collection(array_map(
            static fn (array $attributes): self => (new self)->newFromBuilder($attributes),
            $boxes,
        ));
    }

    public static function forgetCache(): void
    {
        Cache::memo()->forget((string) config('cache.items.shipping_boxes.key'));
    }
}
