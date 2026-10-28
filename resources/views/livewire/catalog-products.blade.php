<div>
    <section class="catalog-page flat-spacing">
        <div class="container">
            <div class="catalog-toolbar">
                <button type="button" class="catalog-filter-button" data-bs-toggle="offcanvas"
                    data-bs-target="#catalogFilters" aria-controls="catalogFilters">
                    <span class="icon icon-SlidersHorizontal" aria-hidden="true"></span>
                    <span>{{ __('Фильтры') }}</span>
                </button>

                <label class="catalog-sorting">
                    <span>{{ __('Сортировка') }}</span>
                    <span class="catalog-sorting__control">
                        <span class="icon icon-ArrowsLeftRight" aria-hidden="true"></span>
                        <select wire:model.live="sort" name="sort" aria-label="{{ __('Сортировка товаров') }}">
                            <option value="">{{ __('По умолчанию') }}</option>
                            @foreach ($sortOptions as $sortOption)
                                <option value="{{ $sortOption->value }}">{{ __($sortOption->label()) }}</option>
                            @endforeach
                        </select>
                    </span>
                </label>
            </div>

            <div class="catalog-layout">
                <aside class="catalog-sidebar" aria-label="{{ __('Фильтры каталога') }}">
                    @include('catalog.partials.filters', ['filterIdPrefix' => 'desktop'])
                </aside>

                <div class="catalog-content" wire:loading.class="catalog-content--loading">
                    @if ($products->isNotEmpty())
                        <div class="catalog-products-grid" wire:key="catalog-results-{{ $catalogKey }}">
                            @foreach ($products as $product)
                                <x-product-card :product="$product"
                                    :wire:key="'catalog-product-'.$product->getKey().'-'.$product->getAttribute('catalog_variant_id')" />
                            @endforeach
                        </div>

                        @if ($products->hasPages())
                            <div class="catalog-pagination">
                                @if ($hasMoreProducts)
                                    <button type="button" class="catalog-load-more" wire:click="loadMore"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove
                                            wire:target="loadMore">{{ __('Загрузить ещё') }}</span>
                                        <span wire:loading wire:target="loadMore">{{ __('Загрузка…') }}</span>
                                    </button>
                                @endif

                                {{ $products->onEachSide(1)->links('livewire.catalog-pagination', ['scrollTo' => '.catalog-page']) }}
                            </div>
                        @endif
                    @else
                        <x-empty-state :title="__('Товары не найдены')" :description="__('Попробуйте изменить параметры фильтрации.')" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="offcanvas offcanvas-start catalog-filter-canvas" tabindex="-1" id="catalogFilters"
        aria-labelledby="catalogFiltersTitle" wire:ignore.self>
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="catalogFiltersTitle">{{ __('Фильтры') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}"></button>
        </div>
        <div class="offcanvas-body">
            @include('catalog.partials.filters', ['filterIdPrefix' => 'mobile'])
        </div>
    </div>
</div>
