<?php

declare(strict_types=1);

namespace App\Repositories\Categories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

final class CategoryRepository
{
    private const string CACHE_KEY = 'catalog_categories';

    /**
     * @return Collection<int, Category>
     */
    public function getActiveCatalog(): Collection
    {
        return Cache::memo()->remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => Category::query()
                ->whereNull('parent_id')
                ->active()
                ->withCount('activeProducts')
                ->with('media')
                ->with([
                    'children' => static fn (HasMany $query): HasMany => $query
                        ->active()
                        ->withCount('activeProducts')
                        ->with('media')
                        ->orderBy('order')
                        ->orderBy('id'),
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
        return $this->getActiveCatalog()->take($limit)->values();
    }

    public function forget(): void
    {
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
