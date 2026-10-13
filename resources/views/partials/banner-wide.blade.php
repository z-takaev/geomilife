<!-- Wide Banner -->
@if (settings()?->hasMedia('banner_wide_image'))
    <section id="promotions" class="home-content-section">
        <div class="container">
            <a href="#" class="home-banner-link home-banner-link--wide">
                <img loading="lazy" src="{{ settings()?->getFirstMediaUrl('banner_wide_image') }}"
                    alt="{{ __('Натуральная сила Осетии — сыродавленные масла собственного производства') }}">
            </a>
        </div>
    </section>
@endif
<!-- /Wide Banner -->
