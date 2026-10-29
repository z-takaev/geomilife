<div class="offcanvas offcanvas-end canvas-quickview" id="quickView" tabindex="-1" aria-labelledby="quickViewTitle"
    wire:ignore.self>
    <div class="quick-view-loading" wire:loading.flex>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Загрузка') }}</span>
        </div>
    </div>

    <div class="mini-quick-image" wire:loading.remove wire:key="quick-view-images-{{ $opening }}">
        @if ($product !== null && $images->isNotEmpty())
            <div id="quickViewGallery" class="carousel slide quick-view-gallery" data-bs-interval="false">
                <div class="carousel-inner quick-view-gallery__main">
                    @foreach ($images as $image)
                        <div @class(['carousel-item', 'active' => $loop->first])>
                            <img loading="lazy" src="{{ $image->getUrl() }}" alt="{{ $product->name }}">
                        </div>
                    @endforeach
                </div>

                @if ($images->count() > 1)
                    <div class="carousel-indicators quick-view-gallery__thumbnails">
                        @foreach ($images as $image)
                            <button type="button" @class(['active' => $loop->first]) data-bs-target="#quickViewGallery"
                                data-bs-slide-to="{{ $loop->index }}"
                                @if ($loop->first) aria-current="true" @endif
                                aria-label="{{ __('Показать изображение :number', ['number' => $loop->iteration]) }}">
                                <img loading="lazy" src="{{ $image->getUrl() }}"
                                    alt="{{ __('Превью товара :number', ['number' => $loop->iteration]) }}">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <span class="home-product-card__image-placeholder">{{ __('Изображение отсутствует') }}</span>
        @endif
    </div>

    <div class="wrap-canvas">
        <div class="canvas-header ps-md-0">
            <h5 class="title-pop text-primary" id="quickViewTitle">{{ __('Быстрый просмотр') }}</h5>
            <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>
        </div>

        <div class="canvas-body ps-md-0 tf-quick-view" wire:loading.remove
            wire:key="quick-view-product-{{ $opening }}" x-data="productVariantSelector(@js($productViewModel?->variantOptions ?? []), @js($productViewModel->selectedVariant['id'] ?? null), @js($productViewModel?->productUrl))">
            @if ($product !== null)

                <div class="quickview-product-info">
                    <div class="product-info-meta">
                        <a href="{{ route('catalog.category', $product->category) }}"
                            class="infor_cate product-detail__category fw-semibold text-uppercase">{{ $product->category->name }}</a>
                        @if ($product->is_hit)
                            <span class="product-card-badge product-card-badge--hit">{{ __('Хит') }}</span>
                        @endif
                        @if ($product->is_new)
                            <span class="product-card-badge product-card-badge--new">{{ __('Новинка') }}</span>
                        @endif
                        @if ($productViewModel->hasDiscount)
                            <span class="product-card-badge product-card-badge--sale">{{ __('Скидка') }}</span>
                        @endif
                    </div>
                    <h3 class="product-infor-name text-black">{{ $product->name }}</h3>
                    <x-products.price :variant="$productViewModel->selectedVariant" />
                </div>
                <div class="quickview-product-variant">
                    <div class="variant-picker-item variant-unit">
                        <x-products.variants :variants="$productViewModel->variantOptions" :selected-id="$productViewModel->selectedVariant['id']" />
                    </div>
                </div>
                <div class="quickview-product-total-btn">
                    <div class="group-btn">
                        <x-products.quantity />
                        <x-products.buy-button :variant="$productViewModel->selectedVariant" />
                        <x-products.favorite-button :product="$product" />
                    </div>
                </div>
                <a href="{{ $productViewModel->productUrl }}" x-bind:href="selectedProductUrl"
                    class="link-underline text-primary">{{ __('Подробнее о товаре') }}</a>
            @elseif ($productId !== null)
                <p role="status">{{ __('Товар больше недоступен.') }}</p>
            @endif
        </div>
    </div>

    @if ($product !== null)
        <x-products.information :product="$product" prefix="quick-view" class="quick-view-tabs" wire:loading.remove
            wire:key="quick-view-information-{{ $opening }}" />
    @endif
</div>
