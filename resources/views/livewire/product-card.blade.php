<div class="swiper-slide">
    <article class="card-product home-product-card">
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
                        <button type="button" class="product-card-weight{{ $loop->first ? ' is-active' : '' }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $variant->label() }}
                        </button>
                    @endforeach
                </div>

                <div class="price-wrap home-product-card__price">
                    <span class="price-new text-primary">{{ $price->format() }}</span>
                    @if ($oldPrice !== null)
                        <span class="price-old">{{ $oldPrice->format() }}</span>
                    @endif
                </div>

                <div class="product-card-purchase">
                    <div class="product-card-quantity" aria-label="{{ __('Количество товара') }}">
                        <button type="button" class="product-card-quantity__button product-card-quantity__minus"
                            aria-label="{{ __('Уменьшить количество') }}">
                            <span class="icon icon-minus" aria-hidden="true"></span>
                        </button>
                        <input class="product-card-quantity__input" type="number" name="quantity" value="1"
                            min="1" step="1" inputmode="numeric" aria-label="{{ __('Количество') }}">
                        <button type="button" class="product-card-quantity__button product-card-quantity__plus"
                            aria-label="{{ __('Увеличить количество') }}">
                            <span class="icon icon-plus" aria-hidden="true"></span>
                        </button>
                    </div>

                    <button type="button" @class([
                        'product-card-buy',
                        'is-preorder hover-tooltip' => $isPreorder,
                    ]) data-bs-toggle="offcanvas"
                        data-bs-target="#shoppingCart"
                        aria-label="{{ $isPreorder ? __('Под заказ. Срок поставки уточнит менеджер') : __('Купить') }}">
                        {{ $isPreorder ? __('Под заказ') : __('Купить') }}
                        @if ($isPreorder)
                            <span class="product-card-preorder-icon" aria-hidden="true">i</span>
                            <span class="tooltip" role="tooltip">
                                {{ __('Товар доступен под заказ. Срок поставки уточнит менеджер') }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </article>
</div>
