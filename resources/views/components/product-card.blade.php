@props([
    'image',
    'price',
    'name',
    'badge' => null,
    'badgeVariant' => 'default',
    'oldPrice' => null,
    'weights' => ['250 г', '500 г', '750 г', '1 кг'],
])

<div class="swiper-slide">
    <article class="card-product home-product-card">
        <div class="card-product_wrapper home-product-card__media">
            <a href="#" class="product-img" aria-label="{{ $name }}">
                <img class="img-product" width="300" height="300"
                    src="{{ asset('assets/images/product/' . $image) }}" alt="{{ $name }}">
            </a>

            @if ($badge !== null)
                <ul class="list-badge" aria-label="Метки товара">
                    <li class="product-card-badge product-card-badge--{{ $badgeVariant }}">{{ $badge }}</li>
                </ul>
            @endif

            <ul class="product-action_list">
                <li class="wishlist" data-tooltip-add="В избранное" data-tooltip-remove="Убрать из избранного">
                    <button type="button" class="hover-tooltip tooltip-left box-icon" aria-label="Добавить в избранное">
                        <span class="icon icon-heart-stroke" aria-hidden="true"></span>
                        <span class="tooltip">В избранное</span>
                    </button>
                </li>
                <li>
                    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#quickView"
                        class="hover-tooltip tooltip-left box-icon" aria-label="Быстрый просмотр">
                        <span class="icon icon-eye" aria-hidden="true"></span>
                        <span class="tooltip">Быстрый просмотр</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-product_info home-product-card__info">
            <a href="#" class="name-product home-product-card__name link-underline text-primary">
                {{ $name }}
            </a>

            <div class="product-card-order">
                <div class="product-card-weights" role="group" aria-label="Выберите фасовку">
                    @foreach ($weights as $weight)
                        <button type="button" class="product-card-weight{{ $loop->first ? ' is-active' : '' }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $weight }}
                        </button>
                    @endforeach
                </div>

                <div class="price-wrap home-product-card__price">
                    <span class="price-new text-primary">{{ $price }} ₽</span>
                    @if ($oldPrice !== null)
                        <span class="price-old">{{ $oldPrice }} ₽</span>
                    @endif
                </div>

                <div class="product-card-purchase">
                    <div class="product-card-quantity" aria-label="Количество товара">
                        <button type="button" class="product-card-quantity__button product-card-quantity__minus"
                            aria-label="Уменьшить количество">
                            <span class="icon icon-minus" aria-hidden="true"></span>
                        </button>
                        <input class="product-card-quantity__input" type="number" name="quantity" value="1"
                            min="1" step="1" inputmode="numeric" aria-label="Количество">
                        <button type="button" class="product-card-quantity__button product-card-quantity__plus"
                            aria-label="Увеличить количество">
                            <span class="icon icon-plus" aria-hidden="true"></span>
                        </button>
                    </div>

                    <button type="button" class="product-card-buy" data-bs-toggle="offcanvas"
                        data-bs-target="#shoppingCart">
                        Купить
                    </button>
                </div>
            </div>
        </div>
    </article>
</div>
