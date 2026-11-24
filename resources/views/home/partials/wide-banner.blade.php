@php
    $mobileCollection = 'banner_wide_mobile_image';
    $hasMobileImage = settings()?->hasMedia($mobileCollection) ?? false;
    $bannerUrl = settings()?->banner_wide_url;
@endphp

@if (settings()?->hasMedia('banner_wide_image'))
    <section id="promotions" class="home-content-section">
        <div class="container">
            <x-home.banner :image="settings()?->getFirstMediaUrl('banner_wide_image')" :mobile-image="$hasMobileImage ? settings()?->getFirstMediaUrl($mobileCollection) : null" :url="$bannerUrl" @class([
                'home-banner-link',
                'home-banner-link--wide',
                'home-banner-link--has-mobile-image' => $hasMobileImage,
            ]) />
        </div>
    </section>
@endif
