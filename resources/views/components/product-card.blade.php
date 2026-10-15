@props(['product'])

@php
    $discountPercent = $product->variants->max('discount_percent');
    $imageUrl = $product->getFirstMediaUrl('image', 'card');
    $selectedVariant = $product->variants->first();
    $oldPrice = $selectedVariant?->discount_percent === null ? null : (float) $selectedVariant->price;
    $price =
        $oldPrice === null
            ? (float) $selectedVariant->price
            : ($oldPrice * (100 - $selectedVariant->discount_percent)) / 100;
    $formatPrice = static fn(float $value): string => number_format($value, 0, '', ' ');
@endphp

<div class="swiper-slide">
    <article class="card-product home-product-card">
        <div class="card-product_wrapper home-product-card__media">
            <a href="#" class="product-img" aria-label="{{ $product->name }}">
                @if ($imageUrl !== '')
                    <img class="img-product" src="{{ $imageUrl }}" alt="{{ $product->name }}">
                @else
                    <span class="home-product-card__image-placeholder">{{ __('Изображение отсутствует') }}</span>
                @endif
            </a>

            @if ($discountPercent !== null || $product->is_new || $product->is_hit)
                <ul class="list-badge" aria-label="{{ __('Метки товара') }}">
                    @if ($discountPercent !== null)
                        <li class="product-card-badge product-card-badge--sale">
                            {{ __('Скидка :percent%', ['percent' => $discountPercent]) }}
                        </li>
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
                        class="hover-tooltip tooltip-left box-icon" aria-label="{{ __('Быстрый просмотр') }}">
                        <span class="icon icon-eye" aria-hidden="true"></span>
                        <span class="tooltip">{{ __('Быстрый просмотр') }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-product_info home-product-card__info">
            <a href="#" class="name-product home-product-card__name link-underline text-primary">
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
                    <span class="price-new text-primary">{{ $formatPrice($price) }} ₽</span>
                    @if ($oldPrice !== null)
                        <span class="price-old">{{ $formatPrice($oldPrice) }} ₽</span>
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

                    <button type="button" class="product-card-buy" data-bs-toggle="offcanvas"
                        data-bs-target="#shoppingCart">
                        {{ __('Купить') }}
                    </button>
                </div>
            </div>
        </div>
    </article>
</div>
