<!-- Special Offers -->
@if ($products->isNotEmpty())
    <section class="home-content-section">
        <div class="container">
            <div class="sect-head type-2 mb-30 wow fadeInUp">
                <h4 class="s-title text-primary">{{ $title }}</h4>
                <a href="#" class="tf-btn-line pb-0">
                    <span class="d-none d-sm-inline">{{ __('Все товары') }}</span>
                    <span class="d-sm-none">{{ __('Все') }}</span>
                </a>
            </div>
            <x-product-carousel class="home-products-slider wow fadeInUp">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </x-product-carousel>
        </div>
    </section>
@endif
<!-- /Special Offers -->
