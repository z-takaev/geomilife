<!-- Wide Banner -->
@php
    $mobileCollection = 'banner_wide_mobile_image';
    $hasMobileImage = settings()?->hasMedia($mobileCollection) ?? false;
@endphp

@if (settings()?->hasMedia('banner_wide_image'))
    <section id="promotions" class="home-content-section">
        <div class="container">
            <a href="#" @class([
                'home-banner-link',
                'home-banner-link--wide',
                'home-banner-link--has-mobile-image' => $hasMobileImage,
            ])>
                <picture>
                    @if ($hasMobileImage)
                        <source media="(max-width: 991px)"
                            srcset="{{ settings()?->getFirstMediaUrl($mobileCollection, 'banner_mobile') }}">
                    @endif
                    <img loading="lazy" src="{{ settings()?->getFirstMediaUrl('banner_wide_image', 'banner_wide') }}"
                        alt="{{ __('Натуральная сила Осетии — сыродавленные масла собственного производства') }}">
                </picture>
            </a>
        </div>
    </section>
@endif
<!-- /Wide Banner -->
