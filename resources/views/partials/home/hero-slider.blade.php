<!-- Slide Show -->
<div class="container home-hero-slider-wrap">
    <div class="tf-slideshow home-hero-slider tf-btn-swiper-main hover-sw-nav">
        <div dir="ltr" class="swiper tf-swiper sw-slide-show slider_effect_fade" data-auto="true" data-loop="true"
            data-effect="fade" data-delay="3000">
            <div class="swiper-wrapper">
                <div class="swiper-slide home-hero-slide home-hero-slide--opening">
                    <a href="#catalog" class="slider-wrap d-block" aria-label="{{ __('Смотреть каталог GeoMiLife') }}">
                        <div class="sld_image">
                            <img loading="lazy" width="1920" height="720"
                                src="{{ asset('assets/images/slider/hero-opening.jpg') }}"
                                alt="{{ __('Открытое производство сыродавленных масел GeoMiLife в Северной Осетии') }}">
                        </div>
                    </a>
                </div>
                <div class="swiper-slide home-hero-slide home-hero-slide--promo">
                    <a href="#catalog" class="slider-wrap d-block" aria-label="{{ __('Выбрать масла GeoMiLife') }}">
                        <div class="sld_image">
                            <img loading="lazy" width="1920" height="720"
                                src="{{ asset('assets/images/slider/hero-oils-promo.jpg') }}"
                                alt="{{ __('Набор сыродавленных масел GeoMiLife холодного отжима') }}">
                        </div>
                    </a>
                </div>
            </div>
            <div class="sw-dot-default style-white tf-sw-pagination"></div>
        </div>

        <div class="group-btn">
            <div class="tf-sw-nav style-2 nav-prev-swiper">
                <i class="icon icon-caret-left"></i>
            </div>
            <div class="tf-sw-nav style-2 nav-next-swiper">
                <i class="icon icon-caret-right"></i>
            </div>
        </div>
    </div>
</div>
<!-- /Slide Show -->
