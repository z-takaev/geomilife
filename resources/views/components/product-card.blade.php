@props([
    'image',
    'price',
    'name',
    'category',
    'badge' => null,
    'oldPrice' => null,
])

<div class="swiper-slide">
    <div class="card-product">
        <div class="card-product_wrapper">
            <a href="#" class="product-img">
                <img class="img-product" width="300" height="300"
                    src="{{ asset('assets/images/product/' . $image) }}" alt="{{ $name }}">
            </a>
            @if ($badge !== null)
                <ul class="list-badge">
                    <li class="badge-sale text-caption-02">{{ $badge }}</li>
                </ul>
            @endif
            <ul class="product-action_list">
                <li class="wishlist">
                    <a href="javascript:void(0);" class="hover-tooltip tooltip-left box-icon">
                        <span class="icon icon-heart-stroke"></span>
                        <span class="tooltip">Add to Wishlist</span>
                    </a>
                </li>
                <li>
                    <a href="#quickView" data-bs-toggle="offcanvas" class="hover-tooltip tooltip-left box-icon">
                        <span class="icon icon-eye"></span>
                        <span class="tooltip">Quick view</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-product_info">
            <div class="product_info_wrap">
                <div class="price-wrap">
                    <span class="price-new text-primary fw-medium lh-28">{{ $price }}</span>
                    @if ($oldPrice !== null)
                        <span class="price-old text-caption-01">{{ $oldPrice }}</span>
                    @endif
                </div>
                <a href="#" class="name-product lh-28 fw-medium link-underline text-primary">{{ $name }}</a>
                <div class="rate-product">
                    <ul class="rate-list">
                        <li><i class="icon icon-star-3 text-primary"></i></li>
                        <li><i class="icon icon-star-3 text-primary"></i></li>
                        <li><i class="icon icon-star-3 text-primary"></i></li>
                        <li><i class="icon icon-star-3 text-primary"></i></li>
                        <li><i class="icon icon-star-3"></i></li>
                    </ul>
                    <span class="text-caption-02 fw-medium text-primary">(54)</span>
                </div>
                <p class="product-cate text-caption-02 fw-medium">{{ $category }}</p>
            </div>
            <div class="btn-add-to-card">
                <span class="text text-1 text-caption-01">Add To Cart</span>
                <span class="text text-2 text-caption-01">Select Options</span>
                <ul class="list-option">
                    <li><a href="#shoppingCart" data-bs-toggle="offcanvas" class="item-option text-caption-02">250G</a>
                    </li>
                    <li><a href="#shoppingCart" data-bs-toggle="offcanvas" class="item-option text-caption-02">500G</a>
                    </li>
                    <li><a href="#shoppingCart" data-bs-toggle="offcanvas" class="item-option text-caption-02">750G</a>
                    </li>
                    <li><a href="#shoppingCart" data-bs-toggle="offcanvas" class="item-option text-caption-02">1KG</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
