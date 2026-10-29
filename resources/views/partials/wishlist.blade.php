<div class="offcanvas offcanvas-end canvas-wishlist" id="wishList" tabindex="-1" wire:ignore.self>
    <div class="canvas-header">
        <h5 class="title-pop text-black">{{ __('Избранное') }}</h5>
        <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas" aria-label="{{ __('Закрыть') }}">
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
                        <a href="{{ $item['viewModel']->productUrl }}"
                            class="prd_name link-underline text-black fw-medium">
                            <span>{{ $item['name'] }}</span>
                            <span class="mini-product-weight">{{ $item['viewModel']->selectedVariant['label'] }}</span>
                        </a>
                    </div>
                    <div class="prd-action">
                        <button type="button" class="tf-btn-line style-min p-0"
                            x-on:click="$store.wishlist.perform('remove', {{ $item['id'] }})"
                            x-bind:aria-disabled="$store.wishlist.busy">
                            <span class="text-caption-01">{{ __('Удалить') }}</span>
                        </button>
                        <div class="quantity-price text-black fw-medium">
                            <span>{{ $item['viewModel']->selectedVariant['price'] }}</span>
                        </div>
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
