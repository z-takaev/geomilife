@props(['variant', 'compact' => false])

<div @class([
    'price-wrap home-product-card__price' => $compact,
    'product-infor-price' => !$compact,
])>
    <span @class([
        'price-new text-primary' => $compact,
        'product-detail__price' => !$compact,
    ]) x-text="selectedVariant.price">{{ $variant['price'] }}</span>
    <span @class([
        'price-old' => $compact,
        'product-detail__old-price' => !$compact,
    ]) x-show="selectedVariant.oldPrice !== null"
        x-text="selectedVariant.oldPrice ?? ''">{{ $variant['oldPrice'] }}</span>
</div>
