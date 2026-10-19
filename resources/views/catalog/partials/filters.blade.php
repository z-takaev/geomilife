<form class="catalog-filters">
    <fieldset class="catalog-filter-group">
        <legend>{{ __('Категории') }}</legend>
        <div class="catalog-filter-options catalog-filter-options--scroll">
            @foreach ($categories as $category)
                @php
                    $categoryProductsCount =
                        $category->active_products_count + $category->children->sum('active_products_count');
                    $categoryInputId = $filterIdPrefix . '-category-' . $category->getKey();
                @endphp
                <label class="catalog-filter-option" for="{{ $categoryInputId }}">
                    <input id="{{ $categoryInputId }}" type="checkbox" name="categories[]"
                        value="{{ $category->getKey() }}">
                    <span>{{ $category->name }}</span>
                    <small>{{ $categoryProductsCount }}</small>
                </label>
            @endforeach
        </div>
    </fieldset>

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
