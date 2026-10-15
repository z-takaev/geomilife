<!-- Wide Banner -->
@if (settings()?->hasMedia('banner_wide_image'))
    <section id="promotions" class="home-content-section">
        <div class="container">
            <a href="#" class="home-banner-link home-banner-link--wide">
                <picture>
                    <source media="(max-width: 767px)"
                        srcset="{{ settings()?->getFirstMediaUrl('banner_wide_image', 'banner_wide_mobile') }}">
                    <img loading="lazy" src="{{ settings()?->getFirstMediaUrl('banner_wide_image', 'banner_wide') }}"
                        alt="{{ __('Натуральная сила Осетии — сыродавленные масла собственного производства') }}">
                </picture>
            </a>
        </div>
    </section>
@endif
<!-- /Wide Banner -->
