@if ($slides->isNotEmpty())
    <div class="container home-hero-slider-wrap">
        <div class="tf-slideshow home-hero-slider tf-btn-swiper-main hover-sw-nav">
            <div dir="ltr" class="swiper tf-swiper sw-slide-show slider_effect_fade"
                data-auto="{{ $slides->count() > 1 ? 'true' : 'false' }}"
                data-loop="{{ $slides->count() > 1 ? 'true' : 'false' }}" data-effect="fade" data-delay="3000">
                <div class="swiper-wrapper">
                    @foreach ($slides as $slide)
                        <div class="swiper-slide home-hero-slide">
                            <a href="{{ $slide->link_url ?: 'javascript:void(0)' }}" class="slider-wrap d-block"
                                @if ($slide->link_url && $slide->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                <div class="sld_image">
                                    <picture>
                                        <source media="(max-width: 575px)"
                                            srcset="{{ $slide->getFirstMediaUrl('mobile_image') }}">
                                        <img loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            @if ($loop->first) fetchpriority="high" @endif
                                            src="{{ $slide->getFirstMediaUrl('desktop_image') }}"
                                            alt="{{ __('Слайд :number', ['number' => $loop->iteration]) }}">
                                    </picture>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                @if ($slides->count() > 1)
                    <div class="sw-dot-default style-white tf-sw-pagination"></div>
                @endif
            </div>

            @if ($slides->count() > 1)
                <div class="group-btn">
                    <div class="tf-sw-nav style-2 nav-prev-swiper">
                        <i class="icon icon-caret-left"></i>
                    </div>
                    <div class="tf-sw-nav style-2 nav-next-swiper">
                        <i class="icon icon-caret-right"></i>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
