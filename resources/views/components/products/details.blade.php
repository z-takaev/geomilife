@props(['product', 'prefix'])

@php
    $hasDescription = filled($product->description);
    $hasCharacteristics = $product->attributeValues->isNotEmpty();
@endphp

@if ($hasDescription || $hasCharacteristics)
    <div {{ $attributes->class(['product-detail-tabs']) }}>
        <nav class="product-detail-tabs__navigation" role="tablist" aria-label="{{ __('Информация о товаре') }}">
            @if ($hasDescription)
                <button class="active" id="{{ $prefix }}-description-tab" type="button" role="tab"
                    data-bs-toggle="tab" data-bs-target="#{{ $prefix }}-description"
                    aria-controls="{{ $prefix }}-description" aria-selected="true">{{ __('Описание') }}</button>
            @endif
            @if ($hasCharacteristics)
                <button @class(['active' => !$hasDescription]) id="{{ $prefix }}-characteristics-tab" type="button"
                    role="tab" data-bs-toggle="tab" data-bs-target="#{{ $prefix }}-characteristics"
                    aria-controls="{{ $prefix }}-characteristics"
                    aria-selected="{{ $hasDescription ? 'false' : 'true' }}">{{ __('Характеристики') }}</button>
            @endif
        </nav>

        <div class="tab-content product-detail-tabs__content">
            @if ($hasDescription)
                <div class="tab-pane fade show active" id="{{ $prefix }}-description" role="tabpanel"
                    aria-labelledby="{{ $prefix }}-description-tab" tabindex="0">
                    <p>{{ $product->description }}</p>
                </div>
            @endif

            @if ($hasCharacteristics)
                <div @class(['tab-pane fade', 'show active' => !$hasDescription]) id="{{ $prefix }}-characteristics" role="tabpanel"
                    aria-labelledby="{{ $prefix }}-characteristics-tab" tabindex="0">
                    <dl class="product-characteristics__list">
                        @foreach ($product->attributeValues as $attributeValue)
                            <div class="product-characteristics__item">
                                <dt>{{ $attributeValue->productAttribute->name }}</dt>
                                <span aria-hidden="true"></span>
                                <dd>{{ $attributeValue->value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>
    </div>
@endif
