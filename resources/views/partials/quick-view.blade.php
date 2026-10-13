<!-- Quick View -->
<div class="offcanvas offcanvas-end canvas-quickview" id="quickView" tabindex="-1" aria-labelledby="quickViewTitle">
    <div class="mini-quick-image">
        <div class="wrap-quick">
            <div class="image">
                <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-7.jpg') }}"
                    alt="{{ __('Масло чёрного тмина') }}">
            </div>
            <div class="image">
                <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-4.jpg') }}"
                    alt="{{ __('Масло чёрного тмина') }}">
            </div>
            <div class="image">
                <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-6.jpg') }}"
                    alt="{{ __('Масло чёрного тмина') }}">
            </div>
            <div class="image">
                <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-5.jpg') }}"
                    alt="{{ __('Масло чёрного тмина') }}">
            </div>
        </div>
    </div>
    <div class="wrap-canvas">
        <div class="canvas-header ps-md-0">
            <h5 class="title-pop text-primary" id="quickViewTitle">{{ __('Быстрый просмотр') }}</h5>
            <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}">
                <i class="icon icon-close"></i>
            </button>
        </div>
        <div class="canvas-body ps-md-0 tf-quick-view">
            <div class="quickview-product-info">
                <div class="product-info-meta">
                    <p class="infor_cate fw-semibold cl-text-main">{{ __('МАСЛА') }}</p>
                    <div class="br-line type-vertical"></div>
                    <div class="infor_rate">
                        <ul class="rate-list">
                            <li><i class="icon icon-star-4 text-primary fs-16"></i></li>
                            <li><i class="icon icon-star-4 text-primary fs-16"></i></li>
                            <li><i class="icon icon-star-4 text-primary fs-16"></i></li>
                            <li><i class="icon icon-star-4 text-primary fs-16"></i></li>
                            <li><i class="icon icon-star-4 text-primary fs-16"></i></li>
                        </ul>
                        <p class="review-count text-caption-01 cl-text-main">{{ __('(134 отзыва)') }}</p>
                    </div>
                </div>
                <h3 class="product-infor-name text-primary">
                    {{ __('Сыродавленное масло чёрного тмина') }}
                </h3>
                <div class="product-infor-price">
                    <h2 class="text-primary price-on-sale">{{ __('890 ₽') }}</h2>
                    <p class="text-body-large cl-text-2 text-decoration-line-through">{{ __('1 190 ₽') }}</p>
                    <span class="badge-sale text-caption-01">{{ __('Скидка 25%') }}</span>
                </div>
                <p class="product-infor-desc cl-text-main">
                    {{ __('Натуральное сыродавленное масло с насыщенным вкусом и ароматом. Подходит для салатов, каш и холодных блюд.') }}
                </p>
            </div>
            <div class="quickview-product-variant">
                <div class="variant-picker-item variant-unit">
                    <div class="variant-picker-label">
                        <div class="text-body-large text-primary">
                            {{ __('Объём:') }}
                            <span class="variant-picker-label-value value-currentUnit">{{ __('250 мл') }}</span>
                        </div>
                    </div>
                    <div class="variant-picker-values">
                        <span class="unit-btn active" data-unit="250 мл" data-price="890">{{ __('250 мл') }}</span>
                        <span class="unit-btn" data-unit="500 мл" data-price="1490">{{ __('500 мл') }}</span>
                        <span class="unit-btn" data-unit="1 л" data-price="2690">{{ __('1 л') }}</span>
                    </div>
                </div>
                <div class="variant-quantity">
                    <div class="variant-picker-label">
                        <div class="text-body-large text-primary">{{ __('Количество:') }}</div>
                    </div>
                    <div class="wg-quantity">
                        <button type="button" class="btn-quantity minus-prd"
                            aria-label="{{ __('Уменьшить количество') }}">
                            <i class="icon icon-minus"></i>
                        </button>
                        <input class="quantity-product" type="text" name="number" value="1" inputmode="numeric"
                            aria-label="{{ __('Количество') }}">
                        <button type="button" class="btn-quantity plus-prd"
                            aria-label="{{ __('Увеличить количество') }}">
                            <i class="icon icon-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="quickview-product-total-btn">
                <div class="group-btn">
                    <a href="#shoppingCart" data-bs-toggle="offcanvas"
                        class="tf-btn style-btn-fill-sec animate-btn animate-dark w-100">
                        <span class="fw-semibold">{{ __('В корзину —') }} <span
                                class="price-add">{{ __('890 ₽') }}</span></span>
                    </a>
                    <button type="button" class="tf-btn-icon hover-tooltip btn-add-wishlist"
                        aria-label="{{ __('Добавить в избранное') }}">
                        <i class="icon icon-heart-stroke"></i>
                    </button>
                </div>
                <a href="#" class="tf-btn style-btn-fill-pri animate-btn">
                    <span class="fw-semibold">{{ __('Купить сейчас') }}</span>
                </a>
            </div>
            <div class="box-action">
                <a href="#" class="tf-btn-line">{{ __('Подробнее о товаре') }}</a>
            </div>
        </div>
    </div>
</div>
<!-- /Quick View -->
