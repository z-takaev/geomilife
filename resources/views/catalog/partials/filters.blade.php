<nav class="catalog-categories" aria-label="{{ __('Разделы каталога') }}">
    <h2 class="catalog-sidebar-title">{{ __('Категории') }}</h2>
    <ul class="catalog-category-list">
        <li>
            <a href="{{ route('catalog.index') }}" @class(['catalog-category-link', 'active' => $category === null])
                @if ($category === null) aria-current="page" @endif>
                {{ __('Все товары') }}
            </a>
        </li>
        @foreach ($categories as $catalogCategory)
            <li>
                <a href="{{ route('catalog.category', $catalogCategory) }}" @class([
                    'catalog-category-link',
                    'active' => $category?->is($catalogCategory),
                    'catalog-category-link--parent' =>
                        $category?->parent_id === $catalogCategory->getKey(),
                ])
                    @if ($category?->is($catalogCategory)) aria-current="page" @endif>
                    {{ $catalogCategory->name }}
                </a>

                @if ($catalogCategory->children->isNotEmpty())
                    <ul class="catalog-category-list catalog-category-list--nested">
                        @foreach ($catalogCategory->children as $childCategory)
                            <li>
                                <a href="{{ route('catalog.category', $childCategory) }}" @class([
                                    'catalog-category-link',
                                    'active' => $category?->is($childCategory),
                                ])
                                    @if ($category?->is($childCategory)) aria-current="page" @endif>
                                    {{ $childCategory->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>

<form class="catalog-filters" wire:submit="applyFilters">
    <fieldset class="catalog-filter-group">
        <legend>{{ __('Цена, ₽') }}</legend>
        <div class="catalog-price-range">
            <label>
                <span>{{ __('От') }}</span>
                <input type="number" wire:model="priceFrom" name="price_from" min="0"
                    placeholder="{{ __('0') }}">
            </label>
            <span class="catalog-price-range__separator" aria-hidden="true"></span>
            <label>
                <span>{{ __('До') }}</span>
                <input type="number" wire:model="priceTo" name="price_to" min="0"
                    placeholder="{{ __('10 000') }}">
            </label>
        </div>
        @error('priceFrom')
            <span class="catalog-filter-error">{{ $message }}</span>
        @enderror
        @error('priceTo')
            <span class="catalog-filter-error">{{ $message }}</span>
        @enderror
    </fieldset>

    <fieldset class="catalog-filter-group">
        <legend>{{ __('Наличие') }}</legend>
        <div class="catalog-filter-options">
            <label class="catalog-filter-option" for="{{ $filterIdPrefix }}-availability-in-stock">
                <input id="{{ $filterIdPrefix }}-availability-in-stock" type="checkbox" wire:model="availability"
                    name="availability[]" value="in-stock">
                <span>{{ __('В наличии') }}</span>
            </label>
            <label class="catalog-filter-option" for="{{ $filterIdPrefix }}-availability-preorder">
                <input id="{{ $filterIdPrefix }}-availability-preorder" type="checkbox" wire:model="availability"
                    name="availability[]" value="preorder">
                <span>{{ __('Под заказ') }}</span>
            </label>
        </div>
    </fieldset>

    @if ($category !== null && $packageOptions !== [])
        <fieldset class="catalog-filter-group">
            <legend>{{ __('Вес / объём / количество') }}</legend>
            <div class="catalog-filter-options catalog-filter-options--scroll">
                @foreach ($packageOptions as $index => $packageOption)
                    <label class="catalog-filter-option"
                        for="{{ $filterIdPrefix }}-package-value-{{ $index }}">
                        <input id="{{ $filterIdPrefix }}-package-value-{{ $index }}" type="checkbox"
                            wire:model="packageValues" name="package_values[]" value="{{ $packageOption['value'] }}">
                        <span>{{ $packageOption['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    <div class="catalog-filter-actions">
        <button type="submit" class="catalog-filter-apply" wire:loading.attr="disabled"
            @if ($filterIdPrefix === 'mobile') data-bs-dismiss="offcanvas" @endif>
            {{ __('Показать товары') }}
        </button>
        <button type="button" class="catalog-filter-reset" wire:click="resetFilters" wire:loading.attr="disabled">
            {{ __('Сбросить') }}
        </button>
    </div>
</form>
