@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Каталог')" :items="[['label' => __('Каталог')]]" />

    <section class="catalog-page flat-spacing">
        <div class="container">
            <div class="catalog-toolbar">
                <button type="button" class="catalog-filter-button" data-bs-toggle="offcanvas" data-bs-target="#catalogFilters"
                    aria-controls="catalogFilters">
                    <span class="icon icon-SlidersHorizontal" aria-hidden="true"></span>
                    <span>{{ __('Фильтры') }}</span>
                </button>

                <label class="catalog-sorting">
                    <span>{{ __('Сортировка') }}</span>
                    <span class="catalog-sorting__control">
                        <span class="icon icon-ArrowsLeftRight" aria-hidden="true"></span>
                        <select name="sort" aria-label="{{ __('Сортировка товаров') }}">
                            <option value="popular">{{ __('По популярности') }}</option>
                            <option value="new">{{ __('По новизне') }}</option>
                            <option value="price-asc">{{ __('Сначала дешевле') }}</option>
                            <option value="price-desc">{{ __('Сначала дороже') }}</option>
                            <option value="name">{{ __('По названию') }}</option>
                        </select>
                    </span>
                </label>
            </div>

            <div class="catalog-layout">
                <aside class="catalog-sidebar" aria-label="{{ __('Фильтры каталога') }}">
                    @include('catalog.partials.filters', ['filterIdPrefix' => 'desktop'])
                </aside>

                <div class="catalog-content">
                    @if ($products->isNotEmpty())
                        <div class="catalog-products-grid">
                            @foreach ($products as $product)
                                <livewire:product-card :product="$product"
                                    :wire:key="'catalog-product-'.$product->getKey()" />
                            @endforeach
                        </div>

                        <div class="catalog-pagination">
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="catalog-load-more">
                                    {{ __('Загрузить ещё') }}
                                </a>
                            @else
                                <button type="button" class="catalog-load-more" disabled>
                                    {{ __('Загрузить ещё') }}
                                </button>
                            @endif

                            @if ($products->hasPages())
                                {{ $products->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
                            @else
                                <nav aria-label="{{ __('Навигация по страницам') }}">
                                    <ul class="wg-pagination archive-pagination justify-content-center">
                                        <li><span class="pagination-item active" aria-current="page">1</span></li>
                                    </ul>
                                </nav>
                            @endif
                        </div>
                    @else
                        <x-empty-state :title="__('Товары не найдены')" :description="__('Попробуйте изменить параметры фильтрации.')" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="offcanvas offcanvas-start catalog-filter-canvas" tabindex="-1" id="catalogFilters"
        aria-labelledby="catalogFiltersTitle">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="catalogFiltersTitle">{{ __('Фильтры') }}</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Закрыть') }}"></button>
        </div>
        <div class="offcanvas-body">
            @include('catalog.partials.filters', ['filterIdPrefix' => 'mobile'])
        </div>
    </div>
@endsection
