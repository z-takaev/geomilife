<section id="advantages" class="home-content-section home-benefits">
    <div class="container">
        <div class="swiper tf-swiper" data-preview="5" data-tablet="3" data-mobile-sm="2" data-mobile="1" data-space-lg="24"
            data-space-md="20" data-space="15" data-pagination="1" data-pagination-sm="2" data-pagination-md="3"
            data-pagination-lg="5">
            <div class="swiper-wrapper">
                @foreach (settings()?->benefits ?? [] as $benefit)
                    <div class="swiper-slide">
                        <div class="wg-benefit wow fadeInLeft"
                            @if (!$loop->first) data-wow-delay="{{ $loop->index / 10 }}s" @endif>
                            <div class="ic-wrap">
                                <i
                                    class="icon {{ ['icon-Plant', 'icon-farmer', 'icon-Truck', 'icon-Invoice', 'icon-Certificate'][$loop->index] }}"></i>
                            </div>
                            <div class="benefit-infor">
                                <h4 class="benefit_name text-primary">{{ __($benefit['title']) }}</h4>
                                <p class="benefit_sub">{{ __($benefit['description']) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-dot-default style-small tf-sw-pagination d-xl-none"></div>
        </div>
    </div>
</section>
