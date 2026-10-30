@php
    $banners = [
        [
            'collection' => 'banner_double_first_image',
            'mobileCollection' => 'banner_double_first_mobile_image',
        ],
        [
            'collection' => 'banner_double_second_image',
            'mobileCollection' => 'banner_double_second_mobile_image',
        ],
    ];
@endphp

@if (settings()?->hasMedia('banner_double_first_image') || settings()?->hasMedia('banner_double_second_image'))
    <section class="home-content-section">
        <div class="container">
            <div class="home-banner-grid home-banner-grid--two">
                @foreach ($banners as $banner)
                    @if (settings()?->hasMedia($banner['collection']))
                        @php
                            $hasMobileImage = settings()?->hasMedia($banner['mobileCollection']) ?? false;
                        @endphp

                        <a href="#" @class([
                            'home-banner-link',
                            'home-banner-link--double',
                            'home-banner-link--has-mobile-image' => $hasMobileImage,
                        ])>
                            <picture>
                                @if ($hasMobileImage)
                                    <source media="(max-width: 991px)"
                                        srcset="{{ settings()?->getFirstMediaUrl($banner['mobileCollection']) }}">
                                @endif
                                <img loading="lazy" src="{{ settings()?->getFirstMediaUrl($banner['collection']) }}"
                                    alt="">
                            </picture>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
