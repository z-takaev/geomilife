<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

final class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'cdek_code',
        'name', 'region', 'sub_region',
        'is_active', 'is_suggested',
        'is_pickup_available', 'is_courier_available', 'is_cdek_excluded',
        'door_delivery_price', 'courier_delivery_price', 'floor_lifting_price',
        'pickup_address', 'pickup_working_hours', 'pickup_phone',
    ];

    protected function casts(): array
    {
        return [
            'cdek_code' => 'integer',
            'is_active' => 'boolean',
            'is_suggested' => 'boolean',
            'is_cdek_excluded' => 'boolean',
            'is_pickup_available' => 'boolean',
            'is_courier_available' => 'boolean',
            'courier_delivery_price' => Money::class,
            'floor_lifting_price' => Money::class,
            'door_delivery_price' => Money::class,
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (self $city): void {
            self::forgetCache($city->getKey());
        });

        self::deleted(static function (self $city): void {
            self::forgetCache($city->getKey());
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function savedDeliveryPoints(): HasMany
    {
        return $this->hasMany(SavedDeliveryPoint::class);
    }

    public function scopeSearchByName(Builder $query, string $search): void
    {
        $query->whereLike('name', '%'.addcslashes(trim($search), '\\%_').'%');
    }

    public static function cached(): Collection
    {
        $cache = Cache::memo();
        $key = (string) config('cache.items.city_selector.key');
        $cities = $cache->get($key);

        if (! is_array($cities) || array_filter($cities, static fn (mixed $city): bool => ! is_array($city)) !== []) {
            $cache->forget($key);
        }

        $cities = $cache->rememberForever(
            $key,
            static fn (): array => self::query()
                ->select([
                    'id', 'name', 'region', 'is_suggested',
                    'is_pickup_available', 'is_courier_available',
                ])
                ->where('is_active', true)
                ->where(static fn (Builder $query): Builder => $query
                    ->where('is_suggested', true)
                    ->orWhere('is_pickup_available', true)
                    ->orWhere('is_courier_available', true))
                ->orderBy('name')
                ->get()
                ->map(static fn (self $city): array => $city->getAttributes())
                ->all(),
        );

        return new Collection(array_map(
            static fn (array $attributes): self => (new self)->newFromBuilder($attributes),
            $cities,
        ));
    }

    public static function cachedById(int $id): ?self
    {
        $cache = Cache::memo();
        $key = self::cacheKey($id);
        $attributes = $cache->get($key);

        if ($attributes !== null && ! is_array($attributes)) {
            $cache->forget($key);
            $attributes = null;
        }

        $attributes ??= $cache->rememberForever(
            $key,
            static fn (): array => self::query()
                ->where('is_active', true)
                ->find($id)?->getAttributes() ?? [],
        );

        return $attributes === [] ? null : (new self)->newFromBuilder($attributes);
    }

    public static function forgetCache(?int $id = null): void
    {
        $cache = Cache::memo();
        $cache->forget((string) config('cache.items.city_selector.key'));

        if ($id !== null) {
            $cache->forget(self::cacheKey($id));

            return;
        }

        self::query()->pluck('id')->each(
            static fn (int $cityId) => $cache->forget(self::cacheKey($cityId)),
        );
    }

    private static function cacheKey(int $id): string
    {
        return (string) config('cache.items.cities.key_prefix').$id;
    }

    public function optionLabel(): string
    {
        return blank($this->region)
            ? $this->name
            : $this->name.', '.$this->region;
    }
}
