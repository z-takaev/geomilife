<?php

declare(strict_types=1);

namespace App\Services\Favorites;

use App\Models\Product;
use App\Repositories\Products\ProductRepository;
use App\ViewModel\Products\ProductViewModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FavoriteService
{
    private ?array $productIds = null;

    public function ids(array $guestIds = []): array
    {
        return auth()->check()
            ? ($this->productIds ??= auth()->user()->favorites()->pluck('product_id')->all())
            : $this->validateGuestIds($guestIds);
    }

    public function validateGuestIds(array $ids): array
    {
        Validator::make(['ids' => $ids], ['ids' => ['array', 'max:1000'], 'ids.*' => ['integer', 'min:1']])->validate();

        return array_values(array_unique(array_map(intval(...), $ids)));
    }

    public function availableGuestIds(array $ids): array
    {
        $ids = $this->validateGuestIds($ids);

        return $ids === [] ? [] : Product::query()->available()->whereKey($ids)->pluck('id')->all();
    }

    public function items(array $guestIds = []): Collection
    {
        $ids = $this->ids($guestIds);

        if ($ids === []) {
            return collect();
        }

        return app(ProductRepository::class)->cardQuery()->whereKey($ids)->latest('id')->get()
            ->map(static fn (Product $product): array => [
                'id' => $product->getKey(),
                'name' => $product->name,
                'image' => $product->getFirstMediaUrl('image', 'card'),
                'viewModel' => new ProductViewModel($product),
            ]);
    }

    public function add(int $productId, array $guestIds = []): array
    {
        abort_unless(Product::query()->available()->whereKey($productId)->exists(), 404);

        if (! auth()->check()) {
            return $this->validateGuestIds([...$guestIds, $productId]);
        }

        auth()->user()->favorites()->firstOrCreate(['product_id' => $productId]);
        $this->productIds = null;

        return $this->ids();
    }

    public function remove(int $productId, array $guestIds = []): array
    {
        if (! auth()->check()) {
            return array_values(array_diff($this->validateGuestIds($guestIds), [$productId]));
        }

        auth()->user()->favorites()->where('product_id', $productId)->delete();
        $this->productIds = null;

        return $this->ids();
    }

    public function shouldSuggestMerge(array $guestIds): bool
    {
        return auth()->check()
            && session('favorites.merge_declined') !== auth()->id()
            && $this->validateGuestIds($guestIds) !== [];
    }

    public function merge(array $guestIds): void
    {
        abort_unless(auth()->check(), 403);
        $ids = $this->availableGuestIds($guestIds);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $productId) {
                auth()->user()->favorites()->firstOrCreate(['product_id' => $productId]);
            }
        });

        $this->productIds = null;
    }

    public function declineMerge(): void
    {
        abort_unless(auth()->check(), 403);
        session()->put('favorites.merge_declined', auth()->id());
    }
}
