@props(['compact' => false])

<div @class([
    'product-card-quantity' => $compact,
    'wg-quantity quick-view-quantity' => !$compact,
])>
    <button type="button" @class([
        'product-card-quantity__button product-card-quantity__minus' => $compact,
        'btn-quantity minus-prd quick-view-quantity__button' => !$compact,
    ]) x-on:click="decreaseQuantity"
        aria-label="{{ __('Уменьшить количество') }}">
        <span class="icon icon-minus" aria-hidden="true"></span>
    </button>
    <input @class([
        'product-card-quantity__input' => $compact,
        'quantity-product quick-view-quantity__input' => !$compact,
    ]) type="number" name="quantity" min="1" step="1" inputmode="numeric"
        x-model.number="quantity" x-on:change="normalizeQuantity" aria-label="{{ __('Количество') }}">
    <button type="button" @class([
        'product-card-quantity__button product-card-quantity__plus' => $compact,
        'btn-quantity plus-prd quick-view-quantity__button' => !$compact,
    ]) x-on:click="increaseQuantity"
        aria-label="{{ __('Увеличить количество') }}">
        <span class="icon icon-plus" aria-hidden="true"></span>
    </button>
</div>
