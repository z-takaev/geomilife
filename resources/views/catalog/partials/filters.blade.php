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

<form class="catalog-filters">
    <fieldset class="catalog-filter-group">
        <legend>{{ __('Цена, ₽') }}</legend>
        <div class="catalog-price-range">
            <label>
                <span>{{ __('От') }}</span>
                <input type="number" name="price_from" min="0" placeholder="{{ __('0') }}">
            </label>
            <span class="catalog-price-range__separator" aria-hidden="true"></span>
            <label>
                <span>{{ __('До') }}</span>
                <input type="number" name="price_to" min="0" placeholder="{{ __('10 000') }}">
            </label>
        </div>
    </fieldset>

    <fieldset class="catalog-filter-group">
        <legend>{{ __('Наличие') }}</legend>
        <div class="catalog-filter-options">
            <label class="catalog-filter-option" for="{{ $filterIdPrefix }}-availability-in-stock">
                <input id="{{ $filterIdPrefix }}-availability-in-stock" type="checkbox" name="availability[]"
                    value="in-stock">
                <span>{{ __('В наличии') }}</span>
            </label>
            <label class="catalog-filter-option" for="{{ $filterIdPrefix }}-availability-preorder">
                <input id="{{ $filterIdPrefix }}-availability-preorder" type="checkbox" name="availability[]"
                    value="preorder">
                <span>{{ __('Под заказ') }}</span>
            </label>
        </div>
    </fieldset>

    <fieldset class="catalog-filter-group">
        <legend>{{ __('Вес / объём упаковки') }}</legend>
        <div class="catalog-filter-options">
            @foreach ([__('До 250 г / мл'), __('250–500 г / мл'), __('500–1000 г / мл'), __('Более 1 кг / л')] as $index => $packageSize)
                <label class="catalog-filter-option" for="{{ $filterIdPrefix }}-package-size-{{ $index }}">
                    <input id="{{ $filterIdPrefix }}-package-size-{{ $index }}" type="checkbox"
                        name="package_sizes[]" value="{{ $index }}">
                    <span>{{ $packageSize }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="catalog-filter-actions">
        <button type="button" class="catalog-filter-apply">{{ __('Показать товары') }}</button>
        <button type="reset" class="catalog-filter-reset">{{ __('Сбросить') }}</button>
    </div>
</form>
