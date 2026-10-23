<?php

declare(strict_types=1);

namespace App\Repositories\Categories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

final class CategoryRepository
{
    private const string CACHE_KEY = 'catalog_categories_flat';

    /**
     * @return Collection<int, Category>
     */
    public function getActiveCatalog(): Collection
    {
        $categories = Cache::memo()->remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => Category::query()
                ->active()
                ->withCount('activeProducts')
                ->with('media')
                ->orderBy('order')
                ->orderBy('id')
                ->get(),
        );

        return $this->buildTree($categories);
    }

    /**
     * @return Collection<int, Category>
     */
    public function getFeatured(int $limit = 12): Collection
    {
        return $this->getActiveCatalog()->take($limit)->values();
    }

    /**
     * @return Collection<int, Category>
     */
    public function getActiveChildren(Category $category): Collection
    {
        return $this->findCategory($this->getActiveCatalog(), (int) $category->getKey())?->children
            ?? new Collection;
    }

    /**
     * @return Collection<int, Category>
     */
    public function search(string $searchQuery, ?int $limit = null): Collection
    {
        $searchQuery = trim($searchQuery);

        if ($searchQuery === '') {
            return new Collection;
        }

        $query = Category::query()
            ->active()
            ->whereLike('name', '%'.$searchQuery.'%')
            ->withCount('activeProducts')
            ->with('media')
            ->with([
                'children' => static fn (HasMany $query): HasMany => $query
                    ->active()
                    ->withCount('activeProducts')
                    ->orderBy('order')
                    ->orderBy('id'),
            ])
            ->orderBy('order')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function forget(): void
    {
        Cache::memo()->forget(self::CACHE_KEY);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    private function buildTree(Collection $categories): Collection
    {
        $rootCategories = new Collection;

        /** @var array<int, list<Category>> $childrenByParentId */
        $childrenByParentId = [];

        foreach ($categories as $category) {
            if ($category->parent_id === null) {
                $rootCategories->push($category);
            }

            if ($category->parent_id !== null) {
                $childrenByParentId[$category->parent_id][] = $category;
            }
        }

        foreach ($categories as $category) {
            $category->setRelation(
                'children',
                new Collection($childrenByParentId[(int) $category->getKey()] ?? []),
            );
        }

        return $rootCategories;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function findCategory(Collection $categories, int $categoryId): ?Category
    {
        foreach ($categories as $category) {
            if ($category->getKey() === $categoryId) {
                return $category;
            }

            $foundCategory = $this->findCategory($category->children, $categoryId);

            if ($foundCategory !== null) {
                return $foundCategory;
            }
        }

        return null;
    }
}
