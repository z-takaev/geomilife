<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class CatalogProducts extends Component
{
    use WithPagination;

    /** @var list<string> */
    private const array AVAILABILITY_OPTIONS = ['in-stock', 'preorder'];

    /** @var list<string> */
    private const array SORT_OPTIONS = ['popular', 'new', 'price-asc', 'price-desc', 'name'];

    #[Locked]
    public ?Category $category = null;

    #[Url(except: '', as: 'price_from', history: true)]
    public string $priceFrom = '';

    #[Url(except: '', as: 'price_to', history: true)]
    public string $priceTo = '';

    /** @var list<string> */
    #[Url(except: [], history: true)]
    public array $availability = [];

    /** @var list<string> */
    #[Url(except: [], as: 'package_values', history: true)]
    public array $packageValues = [];

    #[Url(except: 'popular', history: true)]
    public string $sort = 'popular';

    #[Locked]
    public int $additionalPages = 0;

    public function mount(?Category $category = null): void
    {
        $this->category = $category;
        $this->normalizeFilterValues();
    }

    public function applyFilters(ProductRepository $productRepository): void
    {
        $this->resetValidation();
        $this->validate();

        if ($this->priceFrom !== '' && $this->priceTo !== '' && (int) $this->priceFrom > (int) $this->priceTo) {
            $this->addError('priceTo', __('Максимальная цена должна быть не меньше минимальной.'));

            return;
        }

        $this->normalizeFilterValues($productRepository);
        $this->resetCatalogPage();
    }

    public function resetFilters(): void
    {
        $this->priceFrom = '';
        $this->priceTo = '';
        $this->availability = [];
        $this->packageValues = [];
        $this->sort = 'popular';
        $this->resetValidation();
        $this->resetCatalogPage();
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, self::SORT_OPTIONS, true)) {
            $this->sort = 'popular';
        }

        $this->resetCatalogPage();
    }

    public function loadMore(): void
    {
        $this->additionalPages++;
    }

    public function updatingPaginators(): void
    {
        $this->additionalPages = 0;
    }

    public function render(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): View {
        $page = (int) $this->getPage();
        $filters = $this->filters();
        $products = $productRepository->getFilteredCatalogProducts(
            category: $this->category,
            filters: $filters,
            sort: $this->sort,
            page: $page,
            additionalPages: $this->additionalPages,
        );
        $packageOptions = $this->category === null
            ? []
            : $productRepository->getCatalogPackageOptions($this->category);
        $categories = $this->category === null
            ? $categoryRepository->getActiveCatalog()
            : $categoryRepository->getActiveChildren($this->category);
        $parentCategory = $this->category?->parent;
        $categoryBackUrl = $this->category === null
            ? null
            : ($parentCategory === null
                ? route('catalog.index')
                : route('catalog.category', $parentCategory));
        $categoryBackLabel = $this->category === null
            ? null
            : ($parentCategory?->name ?? __('Весь каталог'));
        $hasMoreProducts = $page + $this->additionalPages < $products->lastPage();

        return view('livewire.catalog-products', compact(
            'categories',
            'products',
            'packageOptions',
            'categoryBackUrl',
            'categoryBackLabel',
            'hasMoreProducts',
        ));
    }

    protected function rules(): array
    {
        return [
            'priceFrom' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'priceTo' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'availability' => ['array'],
            'availability.*' => [Rule::in(self::AVAILABILITY_OPTIONS)],
            'packageValues' => ['array'],
            'packageValues.*' => ['string', 'max:64'],
            'sort' => [Rule::in(self::SORT_OPTIONS)],
        ];
    }

    /**
     * @return array{
     *     priceFrom: string,
     *     priceTo: string,
     *     availability: list<string>,
     *     packageValues: list<string>
     * }
     */
    private function filters(): array
    {
        return [
            'priceFrom' => $this->priceFrom,
            'priceTo' => $this->priceTo,
            'availability' => $this->availability,
            'packageValues' => $this->category === null ? [] : $this->packageValues,
        ];
    }

    private function normalizeFilterValues(?ProductRepository $productRepository = null): void
    {
        $this->sort = in_array($this->sort, self::SORT_OPTIONS, true) ? $this->sort : 'popular';
        $this->availability = array_values(array_unique(array_intersect(
            self::AVAILABILITY_OPTIONS,
            $this->availability,
        )));

        if ($this->category === null) {
            $this->packageValues = [];

            return;
        }

        if ($productRepository === null) {
            return;
        }

        $availablePackageValues = array_column(
            $productRepository->getCatalogPackageOptions($this->category),
            'value',
        );

        $this->packageValues = array_values(array_unique(array_intersect(
            $availablePackageValues,
            $this->packageValues,
        )));
    }

    private function resetCatalogPage(): void
    {
        $this->additionalPages = 0;
        $this->resetPage();
    }
}
