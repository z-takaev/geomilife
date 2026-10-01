<!-- Shopping Cart -->
<div class="offcanvas offcanvas-end canvas-shop-cart" id="shoppingCart">
    <div class="canvas-wrapper">
        <div class="canvas-header">
            <h5 class="title-pop text-primary">{{ __('Shopping Cart') }}</h5>
            <span class="icon-close-popup" data-bs-dismiss="offcanvas">
                <i class="icon icon-close"></i>
            </span>
        </div>
        <div class="wrap list-file-delete">
            <div class="tf-mini-cart-threshold">
                <p class="text text-primary">
                    {{ __('Buy') }}
                    <span class="fw-medium">
                        $70.00
                    </span>
                    {{ __('more to get') }}
                    <span class="fw-medium">
                        {{ __('freeship') }}
                    </span>
                </p>
                <div class="tf-progress-bar tf-progress-ship">
                    <div class="value" style="width: 75%;" data-progress="75">
                        <i class="icon icon-Truck"></i>
                    </div>
                </div>
            </div>
            <div class="tf-mini-cart-wrap">
                <div class="tf-mini-cart-sroll">
                    <ul class="tf-mini-cart-items list-mini-product">
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" width="100" height="100"
                                        src="{{ asset('assets/images/product/product-12.jpg') }}"
                                        alt="{{ __('Image') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Smackn’ Grapes Tomates - 1KG') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Remove') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('x') }}</span>
                                    <span class="tf-mini-card-price">$3.99</span>
                                </div>
                            </div>
                        </li>
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" width="100" height="100"
                                        src="{{ asset('assets/images/product/product-11.jpg') }}"
                                        alt="{{ __('Image') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Range Large Brown Eggs, 18 Count') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Remove') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('x') }}</span>
                                    <span class="tf-mini-card-price">$3.99</span>
                                </div>
                            </div>
                        </li>
                        <li class="mini-product-cart file-delete">
                            <div class="prd-wrap">
                                <div class="prd_image">
                                    <img loading="lazy" width="100" height="100"
                                        src="{{ asset('assets/images/product/product-17.jpg') }}"
                                        alt="{{ __('Image') }}">
                                </div>
                                <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                                    {{ __('Earthbound Farm Organic Baby Spinach - 250GR') }}
                                </a>
                            </div>
                            <div class="prd-action">
                                <a href="#" class="tf-btn-line style-min p-0 remove">
                                    <span class="text-caption-01">
                                        {{ __('Remove') }}
                                    </span>
                                </a>
                                <div class="quantity-price text-primary fw-medium">
                                    <span>1</span>
                                    <span>{{ __('x') }}</span>
                                    <span class="tf-mini-card-price">$3.99</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="canvas-footer">
            <div class="tf-cart-totals-discounts fw-medium text-primary">
                <h4 class="tf-cart-total-text">{{ __('Subtotal') }}</h4>
                <h4 class="tf-totals-total-value">$186,99</h4>
            </div>
            <div class="checkbox-wrap">
                <input id="total" type="checkbox" class="tf-check style-4 radius-3">
                <label for="total" class="text-primary">
                    {{ __('I agree with') }}
                    <a href="#" class="text-decoration-underline fw-medium text-primary link-2">
                        {{ __('Terms & Conditions') }}
                    </a>
                </label>
            </div>
            <div class="group-btn">
                <a href="shopping-cart.html" class="tf-btn animate-btn w-100">
                    {{ __('View Cart') }}
                </a>
                <a href="check-out.html" class="tf-btn style-stroke w-100">
                    {{ __('Check Out') }}
                </a>
            </div>
            <div class="text-center">
                <a href="shop-left-sidebar.html" class="fw-semibold text-primary link-2 text-decoration-underline">
                    {{ __('Or continue shopping') }}
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Shopping Cart -->
