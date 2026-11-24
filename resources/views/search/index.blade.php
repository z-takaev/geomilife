@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <x-breadcrumbs :title="__('Результаты поиска')" :breadcrumbs="$breadcrumbs" />

    <section class="search-results-page flat-spacing">
        <div class="container">
            <form action="{{ route('search') }}" method="get" class="form-search mb-30">
                <fieldset>
                    <input type="search" name="q" value="{{ $query }}" maxlength="255" required
                        placeholder="{{ __('Найти товар') }}" aria-label="{{ __('Поисковый запрос') }}">
                </fieldset>
                <button type="submit" class="link-2" aria-label="{{ __('Искать') }}">
                    <i class="icon icon-magnifying-glass" aria-hidden="true"></i>
                </button>
            </form>
            <div class="search-results-summary">
                <div>
                    <p class="search-results-summary__eyebrow">{{ __('Поисковый запрос') }}</p>
                    <p class="search-results-summary__query">{{ $query !== '' ? $query : __('Не задан') }}</p>
                </div>
                <dl class="search-results-counts">
                    <div class="search-results-counts__item">
                        <dt>{{ __('Категории') }}</dt>
                        <dd>{{ $categories->count() }}</dd>
                    </div>
                    <div class="search-results-counts__item">
                        <dt>{{ __('Товары') }}</dt>
                        <dd>{{ $products->total() }}</dd>
                    </div>
                </dl>
            </div>

            @if ($categories->isNotEmpty())
                <section class="search-results-section" aria-labelledby="search-categories-title">
                    <div class="search-results-section__heading">
                        <h2 id="search-categories-title" class="search-results-section__title">
                            {{ __('Найденные категории') }}</h2>
                        <span class="search-results-section__count">{{ $categories->count() }}</span>
                    </div>

                    <x-categories.list :categories="$categories" />
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
                            <x-products.card :product="$product" :wire:key="'search-product-'.$product->getKey()" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <div class="search-results-pagination">
                            {{ $products->onEachSide(1)->withQueryString()->links() }}
                        </div>
                    @endif
                @else
                    <x-empty-state :title="__('Товары не найдены')" :description="__('Попробуйте изменить поисковый запрос.')" icon="icon-magnifying-glass" />
                @endif
            </section>
        </div>
    </section>
@endsection
