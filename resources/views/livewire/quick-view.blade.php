<div class="offcanvas offcanvas-end canvas-quickview" id="quickView" tabindex="-1" aria-labelledby="quickViewTitle"
    wire:ignore.self>
    <div class="quick-view-loading" wire:loading.flex>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Загрузка') }}</span>
        </div>
    </div>

    <div class="mini-quick-image" wire:loading.remove>
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

    <div class="wrap-canvas" wire:key="quick-view-product-{{ $product?->getKey() ?? 'empty' }}" wire:loading.remove
        x-data="productVariantSelector(@js($variantOptions))">
        <div class="canvas-header ps-md-0">
            <h5 class="title-pop text-primary" id="quickViewTitle">{{ __('Быстрый просмотр') }}</h5>
            <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>
        </div>

        <div class="canvas-body ps-md-0 tf-quick-view">
            <div class="quickview-product-info">
                @if ($product !== null)
                    <div class="product-info-meta">
                        <a href="{{ route('catalog.index') }}"
                            class="infor_cate product-detail__category fw-semibold text-uppercase">
                            {{ $product->category->name }}
                        </a>
                        @if ($product->is_hit)
                            <span class="product-card-badge product-card-badge--hit">{{ __('Хит') }}</span>
                        @endif
                        @if ($product->is_new)
                            <span class="product-card-badge product-card-badge--new">{{ __('Новинка') }}</span>
                        @endif
                        @if ($hasDiscount)
                            <span class="product-card-badge product-card-badge--sale">{{ __('Скидка') }}</span>
                        @endif
                    </div>
                    <h3 class="product-infor-name text-black">{{ $product->name }}</h3>
                @endif

                @if ($selectedVariant !== null)
                    <div class="product-infor-price">
                        <h2 class="text-black price-on-sale" x-text="selectedVariant.price">
                            {{ $selectedVariant->currentPrice()->format() }}</h2>
                        <p class="text-body-large cl-text-2 text-decoration-line-through"
                            x-show="selectedVariant.oldPrice !== null" x-text="selectedVariant.oldPrice ?? ''">
                            {{ $selectedVariant->oldPrice()?->format() }}</p>
                    </div>
                @endif
            </div>

            @if ($product !== null && $product->variants->isNotEmpty())
                <div class="quickview-product-variant">
                    <div class="variant-picker-item variant-unit">
                        <div class="variant-picker-values" role="group"
                            aria-label="{{ __('Выберите объём или вес') }}">
                            @foreach ($product->variants as $variant)
                                <button type="button" @class(['unit-btn', 'active' => $loop->first])
                                    x-on:click="selectVariant({{ $variant->getKey() }})"
                                    x-bind:class="{ 'active': selectedVariantId === {{ $variant->getKey() }} }"
                                    x-bind:aria-pressed="selectedVariantId === {{ $variant->getKey() }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $variant->label() }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($selectedVariant !== null)
                <div class="quickview-product-total-btn">
                    <div class="group-btn">
                        <div class="wg-quantity quick-view-quantity">
                            <button type="button" class="btn-quantity minus-prd quick-view-quantity__button"
                                x-on:click="decreaseQuantity" aria-label="{{ __('Уменьшить количество') }}">
                                <i class="icon icon-minus" aria-hidden="true"></i>
                            </button>
                            <input class="quantity-product quick-view-quantity__input" type="number" name="number"
                                min="1" step="1" inputmode="numeric" x-model.number="quantity"
                                x-on:change="normalizeQuantity" aria-label="{{ __('Количество') }}">
                            <button type="button" class="btn-quantity plus-prd quick-view-quantity__button"
                                x-on:click="increaseQuantity" aria-label="{{ __('Увеличить количество') }}">
                                <i class="icon icon-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                        <a href="#shoppingCart" data-bs-toggle="offcanvas" class="product-card-buy quick-view-buy w-100"
                            x-bind:class="{ 'is-preorder hover-tooltip': selectedVariant.isPreorder }"
                            x-bind:aria-label="selectedVariant.isPreorder ? @js(__('Под заказ. Срок поставки уточнит менеджер')) : @js(__('Купить'))">
                            <span
                                x-text="selectedVariant.isPreorder ? @js(__('Под заказ')) : @js(__('Купить'))">{{ $selectedVariant->is_preorder ? __('Под заказ') : __('Купить') }}</span>
                            <span class="product-card-preorder-icon" x-show="selectedVariant.isPreorder"
                                aria-hidden="true">i</span>
                            <span class="tooltip" x-show="selectedVariant.isPreorder" role="tooltip">
                                {{ __('Товар доступен под заказ. Срок поставки уточнит менеджер') }}
                            </span>
                        </a>
                        <button type="button" class="tf-btn-icon hover-tooltip btn-add-wishlist"
                            aria-label="{{ __('Добавить в избранное') }}">
                            <i class="icon icon-heart-stroke" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if ($product !== null)
                <a href="{{ route('products.show', $product) }}" class="link-underline text-primary">
                    {{ __('Подробнее о товаре') }}
                </a>
            @endif
        </div>
    </div>

    <div class="product-detail-tabs quick-view-tabs" wire:loading.remove>
        <nav class="product-detail-tabs__navigation" role="tablist" aria-label="{{ __('Информация о товаре') }}">
            <button class="active" id="quick-view-description-tab" type="button" role="tab"
                data-bs-toggle="tab" data-bs-target="#quick-view-description" aria-controls="quick-view-description"
                aria-selected="true">
                {{ __('Описание') }}
            </button>
            <button id="quick-view-characteristics-tab" type="button" role="tab" data-bs-toggle="tab"
                data-bs-target="#quick-view-characteristics" aria-controls="quick-view-characteristics"
                aria-selected="false">
                {{ __('Характеристики') }}
            </button>
        </nav>

        <div class="tab-content product-detail-tabs__content">
            <div class="tab-pane fade show active" id="quick-view-description" role="tabpanel"
                aria-labelledby="quick-view-description-tab" tabindex="0">
                <p>{{ $product?->description ?: __('Описание отсутствует') }}</p>
            </div>

            <div class="tab-pane fade" id="quick-view-characteristics" role="tabpanel"
                aria-labelledby="quick-view-characteristics-tab" tabindex="0">
                <dl class="product-characteristics__list">
                    @forelse ($product?->attributeValues ?? [] as $attributeValue)
                        <div class="product-characteristics__item">
                            <dt>{{ $attributeValue->productAttribute->name }}</dt>
                            <span aria-hidden="true"></span>
                            <dd>{{ $attributeValue->value }}</dd>
                        </div>
                    @empty
                        <div class="product-characteristics__item">
                            <dt>{{ __('Характеристики отсутствуют') }}</dt>
                        </div>
                    @endforelse
                </dl>
            </div>
        </div>
    </div>
</div>
