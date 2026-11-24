@props(['variant', 'compact' => false])

<button type="button" @class([
    'product-card-buy',
    'quick-view-buy w-100' => !$compact,
    'is-preorder hover-tooltip' => $variant->isPreorder,
])
    x-bind:class="{
        'is-preorder hover-tooltip': selectedVariant.isPreorder,
        'quick-view-buy--standalone': !isInCart,
    }"
    x-bind:aria-label="selectedVariant.isPreorder ? @js(__('Под заказ. Срок поставки уточнит менеджер')) : @js(__('В корзину'))"
    x-show="!isInCart" x-cloak x-on:click="addToCart()" x-bind:disabled="$store.cart.busy">
    <span
        x-text="selectedVariant.isPreorder ? @js(__('Под заказ')) : @js(__('В корзину'))">{{ $variant->isPreorder ? __('Под заказ') : __('В корзину') }}</span>
    <span class="product-card-preorder-icon" x-show="selectedVariant.isPreorder" aria-hidden="true">i</span>
    <span class="tooltip" x-show="selectedVariant.isPreorder" role="tooltip">
        {{ __('Товар доступен под заказ. Срок поставки уточнит менеджер') }}
    </span>
</button>
