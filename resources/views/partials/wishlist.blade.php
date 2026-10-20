<div class="offcanvas offcanvas-end canvas-wishlist" id="wishList">
    <div class="canvas-header">
        <h5 class="title-pop text-black">{{ __('Избранное') }}</h5>
        <span class="icon-close-popup" data-bs-dismiss="offcanvas">
            <i class="icon icon-close"></i>
        </span>
    </div>
    <div class="canvas-body">
        <ul class="list-mini-product">
            <li class="mini-product-cart file-delete">
                <div class="prd-wrap">
                    <div class="prd_image">
                        <img loading="lazy" src="{{ asset('assets/images/product/oil-product-card.png') }}"
                            alt="{{ __('Масло чёрного тмина') }}">
                    </div>
                    <a href="product-single-1.html" class="prd_name link-underline text-black fw-medium">
                        <span>{{ __('Масло чёрного тмина') }}</span>
                        <span class="mini-product-weight">{{ __('250 мл') }}</span>
                    </a>
                </div>
                <div class="prd-action">
                    <a href="#" class="tf-btn-line style-min p-0 remove">
                        <span class="text-caption-01">
                            {{ __('Удалить') }}
                        </span>
                    </a>
                    <div class="quantity-price text-black fw-medium">
                        <span>1</span>
                        <span>{{ __('×') }}</span>
                        <span>{{ __('890 ₽') }}</span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <div class="canvas-footer">
        <div class="d-flex flex-column align-items-center gap-16 text-center">
            <a href="{{ url('/') }}" class="tf-btn-line p-0">
                {{ __('Продолжить покупки') }}
            </a>
        </div>
    </div>
</div>
