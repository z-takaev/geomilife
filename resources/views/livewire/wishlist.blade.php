<div wire:ignore.self x-data="wishlistBrowser($wire, @js($authenticated), @js($ids))" x-on:favorites-updated.window="update($event.detail.ids)"
    x-on:favorites-merged.window="clearGuest()"
    x-on:favorites-merge-suggested.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).show()"
    x-on:favorites-merge-resolved.window="bootstrap.Modal.getOrCreateInstance($refs.mergeModal).hide()">
    @include('partials.wishlist')
    <div class="modal fade" id="mergeWishlist" tabindex="-1" aria-labelledby="mergeWishlistTitle" x-ref="mergeModal"
        wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mergeWishlistTitle">{{ __('Объединить избранное?') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Решить позже') }}"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('У вас остались товары, добавленные до входа. Добавить их в избранное аккаунта?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tf-btn-line" x-on:click="$store.wishlist.perform('decline')"
                        x-bind:aria-disabled="$store.wishlist.busy">{{ __('Не объединять') }}</button>
                    <button type="button" class="tf-btn" x-on:click="$store.wishlist.perform('merge')"
                        x-bind:aria-disabled="$store.wishlist.busy">{{ __('Объединить') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
