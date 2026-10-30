<div x-data="{
    init() {
        $store.cart.wire = $wire;
        $store.cart.count = @js($details['count']);
    }
}"
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
                            @forelse ($details['items'] as $item)
                                <li class="mini-product-cart" wire:key="cart-{{ $item['id'] }}">
                                    <div class="prd-wrap">
                                        <div class="prd_image"><img loading="lazy" src="{{ $item['image'] }}"
                                                alt="{{ $item['name'] }}"></div>
                                        <a href="{{ $item['url'] }}"
                                            class="prd_name link-underline text-black fw-medium"><span>{{ $item['name'] }}</span><span
                                                class="mini-product-weight">{{ $item['label'] }}</span></a>
                                    </div>
                                    <div class="prd-action">
                                        <button type="button" class="tf-btn-line style-min p-0"
                                            wire:click="remove({{ $item['id'] }})">{{ __('Удалить') }}</button>
                                        <div class="quantity-price text-black fw-medium">
                                            <span>{{ $item['quantity'] }}</span><span>{{ __('×') }}</span><span
                                                class="tf-mini-card-price">{{ $item['price'] }}</span>
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
                    <h4 class="tf-totals-total-value">{{ $details['total'] }}</h4>
                </div>
                <div class="group-btn">
                    <a href="{{ route('checkout') }}" class="tf-btn animate-btn w-100">{{ __('Оформить заказ') }}</a>
                </div>
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
