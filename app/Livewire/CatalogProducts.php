<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTO\Products\CatalogFiltersDto;
use App\Enums\Catalog\CatalogSortEnum;
use App\Livewire\Forms\CatalogFiltersForm;
use App\Models\Category;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\CatalogRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

final class CatalogProducts extends Component
{
    use WithPagination;

    public ?Category $category = null;

    public CatalogFiltersForm $form;

    public array $appliedFilters = [
        'priceFrom' => '',
        'priceTo' => '',
        'availability' => [],
        'packageValues' => [],
    ];

    public string $sort = '';

    public int $additionalPages = 0;

    public function mount(?Category $category = null): void
    {
        $this->category = $category;
        $this->normalizeFilters();
        $this->form->fill($this->appliedFilters);
    }

    public function updatingPaginators(): void
    {
        $this->additionalPages = 0;
    }

    public function updatedSort(): void
    {
        $this->normalizeSort();
        $this->resetCatalogPage();
    }

    public function updatedAppliedFilters(): void
    {
        $this->normalizeFilters();
        $this->form->fill($this->appliedFilters);
        $this->resetValidation();
    }

    public function applyFilters(): void
    {
        $this->resetValidation();
        $values = $this->form->validateFilters($this->packageOptions());

        if ($values === null) {
            return;
        }

        $this->appliedFilters = $values;
        $this->normalizeFilters();
        $this->form->fill($this->appliedFilters);
        $this->resetCatalogPage();
        $this->dispatch('catalog-filters-applied');
    }

    public function resetFilters(): void
    {
        $this->appliedFilters = (new CatalogFiltersDto)->toArray();
        $this->form->fill($this->appliedFilters);
        $this->reset('sort');
        $this->resetValidation();
        $this->resetCatalogPage();
    }

    public function loadMore(): void
    {
        $this->additionalPages++;
    }

    public function render(CatalogRepository $catalogRepository, CategoryRepository $categoryRepository): View
    {
        $products = $this->catalogProducts($catalogRepository);
        $hasMoreProducts = $products->currentPage() + $this->additionalPages < $products->lastPage();
        $catalogKey = hash('xxh128', json_encode([$this->appliedFilters, $this->sort, $products->currentPage()]));
        $packageOptions = $this->packageOptions();
        $sortOptions = CatalogSortEnum::cases();
        $categories = $this->category === null
            ? $categoryRepository->getActiveCatalog()
            : $categoryRepository->getActiveChildren($this->category);
        [$categoryBackUrl, $categoryBackLabel] = $this->categoryBackLink();

        return view('livewire.catalog-products.catalog-products', compact(
            'products', 'categories', 'packageOptions', 'sortOptions', 'hasMoreProducts', 'categoryBackUrl', 'categoryBackLabel', 'catalogKey',
        ));
    }

    protected function queryString(): array
    {
        return [
            'appliedFilters.priceFrom' => ['as' => 'price_from', 'except' => '', 'history' => true],
            'appliedFilters.priceTo' => ['as' => 'price_to', 'except' => '', 'history' => true],
            'appliedFilters.availability' => ['as' => 'availability', 'except' => [], 'history' => true],
            'appliedFilters.packageValues' => ['as' => 'package_values', 'except' => [], 'history' => true],
            'sort' => ['except' => '', 'history' => true],
            'additionalPages' => ['as' => 'more', 'except' => 0, 'history' => true],
        ];
    }

    private function normalizeFilters(): CatalogFiltersDto
    {
        $filters = CatalogFiltersDto::fromArray($this->appliedFilters, $this->packageOptions());
        $this->appliedFilters = $filters->toArray();

        return $filters;
    }

    private function catalogProducts(CatalogRepository $catalogRepository): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters();
        $sort = $this->normalizeSort();
        $products = $catalogRepository->getProducts(
            $this->category, $filters, $sort, (int) $this->getPage(), $this->additionalPages,
        );
        $this->paginators['page'] = $products->currentPage();
        $this->additionalPages = min(max(0, $this->additionalPages), $products->lastPage() - $products->currentPage());

        return $products;
    }

    private function categoryBackLink(): array
    {
        if ($this->category === null) {
            return [null, null];
        }

        $parent = $this->category->parent;

        return $parent === null
            ? [route('catalog.index'), __('Весь каталог')]
            : [route('catalog.category', $parent), $parent->name];
    }

    private function packageOptions(): array
    {
        return once(fn (): array => $this->category === null
            ? [] : app(CatalogRepository::class)->getPackageOptions($this->category));
    }

    private function normalizeSort(): ?CatalogSortEnum
    {
        $sort = CatalogSortEnum::tryFrom($this->sort);

        if ($sort === null && $this->sort !== '') {
            $this->reset('sort');
        }

        return $sort;
    }

    private function resetCatalogPage(): void
    {
        $this->additionalPages = 0;
        $this->resetPage();
    }
}
