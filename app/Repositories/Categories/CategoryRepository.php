<?php

declare(strict_types=1);

namespace App\Repositories\Categories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

final class CategoryRepository
{
    private const string CACHE_KEY = 'catalog_categories';

    /**
     * @return Collection<int, Category>
     */
    public function getActiveTree(): Collection
    {
        return Cache::memo()->remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->withCount([
                    'products as active_products_count' => static fn (Builder $query): Builder => $query
                        ->where('is_active', true),
                ])
                ->with([
                    'media' => static fn (MorphMany $query): MorphMany => $query
                        ->where('collection_name', 'image'),
                    'children' => static function (HasMany $query): void {
                        $query
                            ->where('is_active', true)
                            ->withCount([
                                'products as active_products_count' => static fn (Builder $query): Builder => $query
                                    ->where('is_active', true),
                            ])
                            ->with([
                                'media' => static fn (MorphMany $query): MorphMany => $query
                                    ->where('collection_name', 'image'),
                            ])
                            ->orderBy('order')
                            ->orderBy('id');
                    },
                ])
                ->orderBy('order')
                ->orderBy('id')
                ->get(),
        );
    }

    /**
     * @return Collection<int, Category>
     */
    public function getFeatured(int $limit = 12): Collection
    {
        return $this->getActiveTree()->take($limit)->values();
    }

    public function forget(): void
    {
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
