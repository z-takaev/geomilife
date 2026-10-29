<div wire:ignore.self x-data="wishlistBrowser($wire, @js($authenticated), @js($ids))" x-on:favorites-updated.window="update($event.detail.ids)"
    x-on:favorites-merged.window="clearGuest()"
    x-on:favorites-merge-suggested.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).show()"
    x-on:favorites-merge-resolved.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).hide()">
    @include('partials.wishlist')
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
