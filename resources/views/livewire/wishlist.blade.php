<div wire:ignore.self x-data="wishlistBrowser($wire, @js($authenticated), @js($ids))" x-on:favorites-updated.window="update($event.detail.ids)"
    x-on:favorites-merged.window="clearGuest()"
    x-on:favorites-merge-suggested.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).show()"
    x-on:favorites-merge-resolved.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).hide()">
    <div class="offcanvas offcanvas-end canvas-wishlist" id="wishList" tabindex="-1" wire:ignore.self>
        <div class="canvas-header">
            <h5 class="title-pop text-black">{{ __('Избранное') }}</h5>
            <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>
        </div>
        <div class="canvas-body">
            <ul class="list-mini-product">
                @forelse ($items as $item)
                    <li class="mini-product-cart" wire:key="favorite-{{ $item['id'] }}">
                        <div class="prd-wrap">
                            <div class="prd_image">
                                @if ($item['image'] !== '')
                                    <img loading="lazy" src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                                @endif
                            </div>
                            <a href="{{ route('products.show', $item['slug']) }}"
                                class="prd_name link-underline text-black fw-medium">
                                <span>{{ $item['name'] }}</span>
                            </a>
                        </div>
                        <div class="prd-action">
                            <button type="button" class="tf-btn-line style-min p-0"
                                x-on:click="$store.wishlist.perform('remove', {{ $item['id'] }})"
                                x-bind:aria-disabled="$store.wishlist.busy">
                                <span class="text-caption-01">{{ __('Удалить') }}</span>
                            </button>
                        </div>
                    </li>
                @empty
                    <li wire:key="favorites-empty" role="status">{{ __('В избранном пока нет товаров.') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="canvas-footer">
            <div class="d-flex flex-column align-items-center gap-16 text-center">
                <button type="button" class="tf-btn-line p-0"
                    data-bs-dismiss="offcanvas">{{ __('Продолжить покупки') }}</button>
            </div>
        </div>
    </div>
    <div class="modal modalCentered fade modal-log merge-confirmation" id="mergeWishlist" tabindex="-1"
        aria-labelledby="mergeWishlistTitle" x-ref="mergeModal" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="icon-close-popup" data-bs-dismiss="modal"
                    aria-label="{{ __('Решить позже') }}">
                    <i class="icon icon-close" aria-hidden="true"></i>
                </button>
                <div class="modal-body">
                    <h3 class="title-pop text-black" id="mergeWishlistTitle">{{ __('Объединить избранное?') }}</h3>
                    <p class="merge-confirmation__description">
                        {{ __('У вас остались товары, добавленные до входа. Добавить их в избранное аккаунта?') }}
                    </p>
                    <div class="merge-confirmation__actions">
                        <button type="button" class="tf-btn style-stroke"
                            x-on:click="$store.wishlist.perform('decline')" x-bind:disabled="$store.wishlist.busy">
                            {{ __('Не объединять') }}
                        </button>
                        <button type="button" class="tf-btn animate-btn" x-on:click="$store.wishlist.perform('merge')"
                            x-bind:disabled="$store.wishlist.busy">{{ __('Объединить') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
