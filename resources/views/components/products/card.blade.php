<div {{ $attributes->class(['swiper-slide']) }}>
    <article class="card-product home-product-card" x-data="productVariantSelector(@js($productViewModel->variantOptions), @js($productViewModel->selectedVariant['id']), @js($productViewModel->productUrl))">
        <div class="card-product_wrapper home-product-card__media">
            <a href="{{ $productViewModel->productUrl }}" x-bind:href="selectedProductUrl" class="product-img"
                aria-label="{{ $product->name }}">
                @if ($imageUrl !== '')
                    <img class="img-product" src="{{ $imageUrl }}" alt="{{ $product->name }}">
                @else
                    <span class="home-product-card__image-placeholder">{{ __('Изображение отсутствует') }}</span>
                @endif
            </a>

            @if ($productViewModel->hasDiscount || $product->is_new || $product->is_hit)
                <ul class="list-badge" aria-label="{{ __('Метки товара') }}">
                    @if ($productViewModel->hasDiscount)
                        <li class="product-card-badge product-card-badge--sale">{{ __('Скидка') }}</li>
                    @endif
                    @if ($product->is_new)
                        <li class="product-card-badge product-card-badge--new">{{ __('Новинка') }}</li>
                    @endif
                    @if ($product->is_hit)
                        <li class="product-card-badge product-card-badge--hit">{{ __('Хит') }}</li>
                    @endif
                </ul>
            @endif

            <ul class="product-action_list">
                <li>
                    <x-products.favorite-button :product="$product" compact />
                </li>
                <li>
                    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#quickView"
                        x-on:click="Livewire.dispatchTo('product-quick-view', 'open-quick-view', { productId: {{ $product->getKey() }}, variantId: selectedVariantId })"
                        class="hover-tooltip tooltip-left box-icon" aria-label="{{ __('Быстрый просмотр') }}">
                        <span class="icon icon-eye" aria-hidden="true"></span>
                        <span class="tooltip">{{ __('Быстрый просмотр') }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-product_info home-product-card__info">
            <a href="{{ $productViewModel->productUrl }}" x-bind:href="selectedProductUrl"
                class="name-product home-product-card__name link-underline text-primary">
                {{ $product->name }}
            </a>

            <div class="product-card-order">
                <x-products.variant-selector :variants="$productViewModel->variantOptions" :selected-id="$productViewModel->selectedVariant['id']" compact />
                <x-products.price :variant="$productViewModel->selectedVariant" compact />
                <div class="product-card-purchase">
                    <x-products.quantity-selector compact />
                    <x-products.add-to-cart-button :variant="$productViewModel->selectedVariant" compact />
                </div>
            </div>
        </div>
    </article>
</div>
