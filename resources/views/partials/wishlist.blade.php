<!-- Wishlist -->
<div class="offcanvas offcanvas-end canvas-wishlist" id="wishList">
    <div class="canvas-header">
        <h5 class="title-pop text-primary">{{ __('Избранное') }}</h5>
        <span class="icon-close-popup" data-bs-dismiss="offcanvas">
            <i class="icon icon-close"></i>
        </span>
    </div>
    <div class="canvas-body">
        <ul class="list-mini-product">
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
                        <span>{{ __('890 ₽') }}</span>
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
                        <span>{{ __('1 250 ₽') }}</span>
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
                        <span>{{ __('470 ₽') }}</span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <div class="canvas-footer">
        <div class="d-flex flex-column align-items-center gap-16 text-center">
            <a href="https://tforvana.vercel.app/wishlist.html" class="tf-btn animate-btn w-100">
                {{ __('Посмотреть всё избранное') }}
            </a>
            <a href="{{ url('/') }}" class="tf-btn-line p-0">
                {{ __('Продолжить покупки') }}
            </a>
        </div>
    </div>
</div>
<!-- /Wishlist -->
