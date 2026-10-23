@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Результаты поиска')" :breadcrumbs="$breadcrumbs" />

    <section class="search-results-page flat-spacing">
        <div class="container">
            @if ($categories->isNotEmpty())
                @php
                    $categoryBackgrounds = [
                        'bg-peach-blush',
                        'bg-pale-cream',
                        'bg-mint-whisper',
                        'bg-ice-blue',
                        'bg-lavender-mist',
                        'bg-lilac-cloud',
                    ];
                @endphp

                <section class="search-results-section" aria-labelledby="search-categories-title">
                    <div class="search-results-section__heading">
                        <h2 id="search-categories-title" class="search-results-section__title">
                            {{ __('Найденные категории') }}</h2>
                        <span class="search-results-section__count">{{ $categories->count() }}</span>
                    </div>

                    <div class="featured-categories-grid">
                        @foreach ($categories as $category)
                            @php
                                $productsCount = $category->totalActiveProductsCount();
                            @endphp
                            <x-category-card :background="$categoryBackgrounds[$loop->index % count($categoryBackgrounds)]" :image="$category->getFirstMediaUrl('image')" :name="$category->name" :product-count="trans_choice(
                                '{0} Нет товаров|{1} :count товар|[2,4] :count товара|[5,*] :count товаров',
                                $productsCount,
                                ['count' => $productsCount],
                            )"
                                :url="route('catalog.index')" />
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="search-results-section" aria-labelledby="search-products-title">
                <div class="search-results-section__heading">
                    <h2 id="search-products-title" class="search-results-section__title">{{ __('Найденные товары') }}</h2>
                    <span class="search-results-section__count">{{ $products->total() }}</span>
                </div>

                @if ($products->isNotEmpty())
                    <div class="catalog-products-grid search-products-grid">
                        @foreach ($products as $product)
                            <livewire:product-card :product="$product" :wire:key="'search-product-'.$product->getKey()" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <div class="search-results-pagination">
                            {{ $products->onEachSide(1)->withQueryString()->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @endif
                @else
                    <div class="catalog-empty">
                        <span class="icon icon-magnifying-glass" aria-hidden="true"></span>
                        <h2>{{ __('Товары не найдены') }}</h2>
                        <p>{{ __('Попробуйте изменить поисковый запрос.') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </section>
@endsection
