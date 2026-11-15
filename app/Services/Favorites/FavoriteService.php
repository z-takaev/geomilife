<?php

declare(strict_types=1);

namespace App\Services\Favorites;

use App\Models\Product;
use App\Repositories\Products\ProductRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FavoriteService
{
    private ?array $productIds = null;

    /**
     * Возвращает идентификаторы избранных товаров пользователя или гостя.
     */
    public function ids(array $guestIds = []): array
    {
        return Auth::check()
            ? ($this->productIds ??= Cache::memo()->rememberForever(
                $this->cacheKey(),
                static fn (): array => auth()->user()->favorites()->pluck('product_id')->all(),
            ))
            : $this->validateGuestIds($guestIds);
    }

    /**
     * Проверяет гостевой список идентификаторов и удаляет повторы.
     */
    public function validateGuestIds(array $ids): array
    {
        Validator::make(['ids' => $ids], ['ids' => ['array', 'max:1000'], 'ids.*' => ['integer', 'min:1']])->validate();

        return array_values(array_unique(array_map(intval(...), $ids)));
    }

    /**
     * Возвращает идентификаторы доступных товаров из гостевого избранного.
     */
    public function availableGuestIds(array $ids): array
    {
        $ids = $this->validateGuestIds($ids);

        return $ids === [] ? [] : Product::query()->available()->whereKey($ids)->pluck('id')->all();
    }

    /**
     * Подготавливает доступные избранные товары для отображения.
     */
    public function items(array $guestIds = []): Collection
    {
        $ids = $this->ids($guestIds);

        if ($ids === []) {
            return collect();
        }

        return app(ProductRepository::class)
            ->cardQuery()
            ->whereKey($ids)
            ->latest('id')
            ->get()
            ->map(static fn (Product $product): array => [
                'id' => $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->getFirstMediaUrl('image', 'thumbnail'),
            ]);
    }

    /**
     * Добавляет доступный товар в избранное и возвращает обновлённый список идентификаторов.
     */
    public function add(int $productId, array $guestIds = []): array
    {
        abort_unless(Product::query()->available()->whereKey($productId)->exists(), 404);

        if (! Auth::check()) {
            return $this->validateGuestIds([...$guestIds, $productId]);
        }

        auth()->user()->favorites()->firstOrCreate(['product_id' => $productId]);
        $this->forgetCache();

        return $this->ids();
    }

    /**
     * Удаляет товар из избранного и возвращает обновлённый список идентификаторов.
     */
    public function remove(int $productId, array $guestIds = []): array
    {
        if (! Auth::check()) {
            return array_values(array_diff($this->validateGuestIds($guestIds), [$productId]));
        }

        auth()->user()->favorites()->where('product_id', $productId)->delete();
        $this->forgetCache();

        return $this->ids();
    }

    /**
     * Проверяет, нужно ли предложить перенос гостевого избранного в профиль.
     */
    public function shouldSuggestMerge(array $guestIds): bool
    {
        return Auth::check()
            && session('favorites.merge_declined') !== auth()->id()
            && $this->validateGuestIds($guestIds) !== [];
    }

    /**
     * Добавляет доступные товары из гостевого избранного в профиль пользователя.
     */
    public function merge(array $guestIds): void
    {
        abort_unless(Auth::check(), 403);

        $ids = $this->availableGuestIds($guestIds);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $productId) {
                auth()->user()->favorites()->firstOrCreate(['product_id' => $productId]);
            }
        });

        $this->forgetCache();
    }

    /**
     * Запоминает отказ пользователя от переноса гостевого избранного.
     */
    public function declineMerge(): void
    {
        abort_unless(Auth::check(), 403);

        session()->put('favorites.merge_declined', auth()->id());
    }

    private function forgetCache(): void
    {
        Cache::memo()->forget($this->cacheKey());
        $this->productIds = null;
    }

    private function cacheKey(): string
    {
        return (string) config('cache.items.favorite_ids.key_prefix').auth()->id();
    }
}
