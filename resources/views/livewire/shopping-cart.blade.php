<div x-data="shoppingCart($wire, @js($count))"
    x-on:cart-merge-suggested.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).show()"
    x-on:cart-merge-resolved.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).hide()"
    x-on:cart-updated.window="$store.cart.count = $event.detail.count">
    <div class="offcanvas offcanvas-end canvas-shop-cart" id="shoppingCart" x-ref="canvas" wire:ignore.self>
        <div class="canvas-wrapper">
            <div class="canvas-header">
                <h5 class="title-pop text-black">{{ __('Корзина') }}</h5>
                <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                    aria-label="{{ __('Закрыть') }}"><i class="icon icon-close"></i></button>
            </div>
            <div class="wrap">
                <div class="tf-mini-cart-wrap">
                    <div class="tf-mini-cart-sroll">
                        <ul class="tf-mini-cart-items list-mini-product">
                            @forelse ($items as $item)
                                <li class="mini-product-cart shopping-cart-item" wire:key="cart-{{ $item['id'] }}">
                                    <div class="prd_image"><img loading="lazy" src="{{ $item['image'] }}"
                                            alt="{{ $item['name'] }}"></div>
                                    <div class="shopping-cart-item__details">
                                        <div class="mini-product-cart__header">
                                            <a href="{{ $item['url'] }}"
                                                class="prd_name link-underline text-black fw-medium"><span>{{ $item['name'] }}</span><span
                                                    class="mini-product-weight">{{ $item['label'] }}</span></a>
                                            <button type="button" class="mini-product-cart__remove"
                                                wire:click="remove({{ $item['id'] }})" wire:loading.attr="disabled"
                                                wire:target="add, changeQuantity, remove, merge"
                                                aria-label="{{ __('Удалить товар из корзины') }}"
                                                title="{{ __('Удалить товар из корзины') }}">
                                                <span class="icon icon-trash" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                        <div class="shopping-cart-item__order">
                                            <div class="product-card-quantity shopping-cart-item__quantity"
                                                role="group" aria-label="{{ __('Количество товара') }}">
                                                <button type="button" class="product-card-quantity__button"
                                                    wire:click="changeQuantity({{ $item['id'] }}, -1)"
                                                    wire:loading.attr="disabled"
                                                    wire:target="add, changeQuantity, remove, merge"
                                                    aria-label="{{ __('Уменьшить количество') }}">
                                                    <span class="icon icon-minus" aria-hidden="true"></span>
                                                </button>
                                                <span class="shopping-cart-item__count"
                                                    aria-live="polite">{{ $item['quantity'] }}</span>
                                                <button type="button" class="product-card-quantity__button"
                                                    wire:click="changeQuantity({{ $item['id'] }}, 1)"
                                                    wire:loading.attr="disabled"
                                                    wire:target="add, changeQuantity, remove, merge"
                                                    aria-label="{{ __('Увеличить количество') }}">
                                                    <span class="icon icon-plus" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                            <div class="quantity-price text-black fw-medium">
                                                <span>{{ $item['quantity'] }}</span><span>{{ __('×') }}</span><span
                                                    class="tf-mini-card-price">{{ $item['price']->format() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li role="status">{{ __('Корзина пока пуста.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="canvas-footer">
                <div class="tf-cart-totals-discounts fw-medium text-primary">
                    <h4 class="tf-cart-total-text">{{ __('Итого') }}</h4>
                    <h4 class="tf-totals-total-value">{{ $total->format() }}</h4>
                </div>
                @if ($count > 0)
                    <div class="group-btn">
                        <a href="{{ route('checkout') }}"
                            class="tf-btn animate-btn w-100">{{ __('Оформить заказ') }}</a>
                    </div>
                @endif
                <div class="text-center"><button type="button"
                        class="fw-semibold text-primary link-2 text-decoration-underline"
                        data-bs-dismiss="offcanvas">{{ __('Продолжить покупки') }}</button></div>
            </div>
        </div>
    </div>
    <div class="modal modalCentered fade modal-log merge-confirmation" id="mergeCart" tabindex="-1"
        aria-labelledby="mergeCartTitle" x-ref="mergeModal" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="icon-close-popup" data-bs-dismiss="modal"
                    aria-label="{{ __('Решить позже') }}">
                    <i class="icon icon-close" aria-hidden="true"></i>
                </button>
                <div class="modal-body">
                    <h3 class="title-pop text-black" id="mergeCartTitle">{{ __('Объединить корзины?') }}</h3>
                    <p class="merge-confirmation__description">
                        {{ __('Добавить товары гостевой корзины в корзину аккаунта?') }}</p>
                    <div class="merge-confirmation__actions">
                        <button type="button" class="tf-btn style-stroke" wire:click="decline"
                            wire:loading.attr="disabled"
                            wire:target="merge, decline">{{ __('Не объединять') }}</button>
                        <button type="button" class="tf-btn animate-btn" wire:click="merge"
                            wire:loading.attr="disabled" wire:target="merge, decline">{{ __('Объединить') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
