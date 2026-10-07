<!-- Wide Banner -->
@php
    $bannerImage = settings()?->banner_wide_image;
    $bannerImageUrl = setting_image_url($bannerImage, 'assets/images/section/promo-natural-ossetia.jpg');
@endphp

<section id="promotions" class="home-content-section">
    <div class="container">
        <a href="#" class="home-banner-link home-banner-link--wide">
            <picture>
                <source media="(max-width: 575px)"
                    srcset="{{ $bannerImage ? $bannerImageUrl : asset('assets/images/section/promo-natural-ossetia-mobile.webp') }}">
                <img loading="lazy" src="{{ $bannerImageUrl }}"
                    alt="{{ __('Натуральная сила Осетии — сыродавленные масла собственного производства') }}">
            </picture>
        </a>
    </div>
</section>
<!-- /Wide Banner -->
