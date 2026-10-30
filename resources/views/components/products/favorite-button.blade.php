<button type="button"
    {{ $attributes->class(['hover-tooltip', 'tooltip-left box-icon' => $compact, 'tf-btn-icon btn-add-wishlist' => !$compact]) }}
    x-data="favoriteButton(@js($product->getKey()))" x-on:click="toggle()" x-bind:aria-disabled="!$store.wishlist.ready || $store.wishlist.busy"
    x-bind:aria-pressed="favorite"
    x-bind:aria-label="favorite ? @js(__('Убрать из избранного')) : @js(__('Добавить в избранное'))"
    aria-label="{{ __('Добавить в избранное') }}">
    <span class="icon" x-bind:class="favorite ? 'icon-heart' : 'icon-heart-stroke'" aria-hidden="true"></span>
    <span class="tooltip" x-text="favorite ? @js(__('Убрать из избранного')) : @js(__('В избранное'))"></span>
</button>
