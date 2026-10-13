<!-- Shopping Cart -->
<div class="offcanvas offcanvas-end canvas-shop-cart" id="shoppingCart">
    <div class="canvas-wrapper">
        <div class="canvas-header">
            <h5 class="title-pop text-primary">{{ __('Корзина') }}</h5>
            <span class="icon-close-popup" data-bs-dismiss="offcanvas">
                <i class="icon icon-close"></i>
            </span>
        </div>
        <div class="wrap list-file-delete">
            <div class="tf-mini-cart-wrap">
                <div class="tf-mini-cart-sroll">
                    <ul class="tf-mini-cart-items list-mini-product">
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" src="{{ asset('assets/images/product/product-12.jpg') }}"
                                        alt="{{ __('Масло чёрного тмина') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Масло чёрного тмина, 250 мл') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Удалить') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('×') }}</span>
                                    <span class="tf-mini-card-price">{{ __('890 ₽') }}</span>
                                </div>
                            </div>
                        </li>
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" src="{{ asset('assets/images/product/product-11.jpg') }}"
                                        alt="{{ __('Урбеч из грецкого ореха') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Урбеч из грецкого ореха, 400 г') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Удалить') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('×') }}</span>
                                    <span class="tf-mini-card-price">{{ __('1 250 ₽') }}</span>
                                </div>
                            </div>
                        </li>
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" src="{{ asset('assets/images/product/product-17.jpg') }}"
                                        alt="{{ __('Горный мёд') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Горный мёд, 500 г') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Удалить') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('×') }}</span>
                                    <span class="tf-mini-card-price">{{ __('470 ₽') }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="canvas-footer">
            <div class="tf-cart-totals-discounts fw-medium text-primary">
                <h4 class="tf-cart-total-text">{{ __('Итого') }}</h4>
                <h4 class="tf-totals-total-value">{{ __('2 610 ₽') }}</h4>
            </div>
            <div class="group-btn">
                <a href="shopping-cart.html" class="tf-btn animate-btn w-100">
                    {{ __('В корзину') }}
                </a>
                <a href="check-out.html" class="tf-btn style-stroke w-100">
                    {{ __('Оформить заказ') }}
                </a>
            </div>
            <div class="text-center">
                <a href="shop-left-sidebar.html" class="fw-semibold text-primary link-2 text-decoration-underline">
                    {{ __('Продолжить покупки') }}
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Shopping Cart -->
