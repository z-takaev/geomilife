<div class="swiper-slide">
    <article class="card-product home-product-card" x-data="productVariantSelector(@js($variantOptions))">
        <div class="card-product_wrapper home-product-card__media">
            <a href="{{ route('products.show', $product) }}" class="product-img" aria-label="{{ $product->name }}">
                @if ($imageUrl !== '')
                    <img class="img-product" src="{{ $imageUrl }}" alt="{{ $product->name }}">
                @else
                    <span class="home-product-card__image-placeholder">{{ __('Изображение отсутствует') }}</span>
                @endif
            </a>

            @if ($hasDiscount || $product->is_new || $product->is_hit)
                <ul class="list-badge" aria-label="{{ __('Метки товара') }}">
                    @if ($hasDiscount)
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
                <li class="wishlist" data-tooltip-add="{{ __('В избранное') }}"
                    data-tooltip-remove="{{ __('Убрать из избранного') }}">
                    <button type="button" class="hover-tooltip tooltip-left box-icon"
                        aria-label="{{ __('Добавить в избранное') }}">
                        <span class="icon icon-heart-stroke" aria-hidden="true"></span>
                        <span class="tooltip">{{ __('В избранное') }}</span>
                    </button>
                </li>
                <li>
                    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#quickView"
                        wire:click="$dispatchTo('quick-view', 'open-quick-view', { productId: {{ $product->getKey() }} })"
                        class="hover-tooltip tooltip-left box-icon" aria-label="{{ __('Быстрый просмотр') }}">
                        <span class="icon icon-eye" aria-hidden="true"></span>
                        <span class="tooltip">{{ __('Быстрый просмотр') }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-product_info home-product-card__info">
            <a href="{{ route('products.show', $product) }}"
                class="name-product home-product-card__name link-underline text-primary">
                {{ $product->name }}
            </a>

            <div class="product-card-order">
                <div class="product-card-weights" role="group" aria-label="{{ __('Выберите вариацию') }}">
                    @foreach ($product->variants as $variant)
                        <button type="button" @class(['product-card-weight', 'is-active' => $loop->first])
                            x-on:click="selectVariant({{ $variant->getKey() }})"
                            x-bind:class="{ 'is-active': selectedVariantId === {{ $variant->getKey() }} }"
                            x-bind:aria-pressed="selectedVariantId === {{ $variant->getKey() }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $variant->label() }}
                        </button>
                    @endforeach
                </div>

                <div class="price-wrap home-product-card__price">
                    <span class="price-new text-primary" x-text="selectedVariant.price">{{ $price->format() }}</span>
                    <span class="price-old" x-show="selectedVariant.oldPrice !== null"
                        x-text="selectedVariant.oldPrice ?? ''">{{ $oldPrice?->format() }}</span>
                </div>

                <div class="product-card-purchase">
                    <div class="product-card-quantity" aria-label="{{ __('Количество товара') }}">
                        <button type="button" class="product-card-quantity__button product-card-quantity__minus"
                            x-on:click="decreaseQuantity" aria-label="{{ __('Уменьшить количество') }}">
                            <span class="icon icon-minus" aria-hidden="true"></span>
                        </button>
                        <input class="product-card-quantity__input" type="number" name="quantity" min="1"
                            step="1" inputmode="numeric" x-model.number="quantity" x-on:change="normalizeQuantity"
                            aria-label="{{ __('Количество') }}">
                        <button type="button" class="product-card-quantity__button product-card-quantity__plus"
                            x-on:click="increaseQuantity" aria-label="{{ __('Увеличить количество') }}">
                            <span class="icon icon-plus" aria-hidden="true"></span>
                        </button>
                    </div>

                    <button type="button" @class([
                        'product-card-buy',
                        'is-preorder hover-tooltip' => $isPreorder,
                    ])
                        x-bind:class="{ 'is-preorder hover-tooltip': selectedVariant.isPreorder }"
                        x-bind:aria-label="selectedVariant.isPreorder ? @js(__('Под заказ. Срок поставки уточнит менеджер')) : @js(__('Купить'))"
                        data-bs-toggle="offcanvas" data-bs-target="#shoppingCart">
                        <span
                            x-text="selectedVariant.isPreorder ? @js(__('Под заказ')) : @js(__('Купить'))">{{ $isPreorder ? __('Под заказ') : __('Купить') }}</span>
                        <span class="product-card-preorder-icon" x-show="selectedVariant.isPreorder"
                            aria-hidden="true">i</span>
                        <span class="tooltip" x-show="selectedVariant.isPreorder" role="tooltip">
                            {{ __('Товар доступен под заказ. Срок поставки уточнит менеджер') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </article>
</div>
