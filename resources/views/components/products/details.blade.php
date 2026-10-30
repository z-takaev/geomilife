@props(['product', 'prefix'])

<div {{ $attributes->class(['product-detail-tabs']) }}>
    <nav class="product-detail-tabs__navigation" role="tablist" aria-label="{{ __('Информация о товаре') }}">
        <button class="active" id="{{ $prefix }}-description-tab" type="button" role="tab" data-bs-toggle="tab"
            data-bs-target="#{{ $prefix }}-description" aria-controls="{{ $prefix }}-description"
            aria-selected="true">{{ __('Описание') }}</button>
        <button id="{{ $prefix }}-characteristics-tab" type="button" role="tab" data-bs-toggle="tab"
            data-bs-target="#{{ $prefix }}-characteristics" aria-controls="{{ $prefix }}-characteristics"
            aria-selected="false">{{ __('Характеристики') }}</button>
    </nav>
    <div class="tab-content product-detail-tabs__content">
        <div class="tab-pane fade show active" id="{{ $prefix }}-description" role="tabpanel"
            aria-labelledby="{{ $prefix }}-description-tab" tabindex="0">
            <p>{{ $product->description ?: __('Описание отсутствует') }}</p>
        </div>
        <div class="tab-pane fade" id="{{ $prefix }}-characteristics" role="tabpanel"
            aria-labelledby="{{ $prefix }}-characteristics-tab" tabindex="0">
            <dl class="product-characteristics__list">
                @forelse ($product->attributeValues as $attributeValue)
                    <div class="product-characteristics__item">
                        <dt>{{ $attributeValue->productAttribute->name }}</dt>
                        <span aria-hidden="true"></span>
                        <dd>{{ $attributeValue->value }}</dd>
                    </div>
                @empty
                    <div class="product-characteristics__item">
                        <dt>{{ __('Характеристики отсутствуют') }}</dt>
                    </div>
                @endforelse
            </dl>
        </div>
    </div>
</div>
