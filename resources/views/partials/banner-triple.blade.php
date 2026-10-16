<!-- Triple Banner -->
@php
    $banners = [
        [
            'collection' => 'banner_triple_first_image',
            'mobileCollection' => 'banner_triple_first_mobile_image',
            'alt' => 'Мёд и продукты пчеловодства — сила природы в каждой ложке',
        ],
        [
            'collection' => 'banner_triple_second_image',
            'mobileCollection' => 'banner_triple_second_mobile_image',
            'alt' => 'Полезные натуральные сладости',
        ],
        [
            'collection' => 'banner_triple_third_image',
            'mobileCollection' => 'banner_triple_third_mobile_image',
            'alt' => 'БАДы, травы и суперфуды для здоровья каждый день',
        ],
    ];
@endphp

@if (settings()?->hasMedia('banner_triple_first_image') ||
        settings()?->hasMedia('banner_triple_second_image') ||
        settings()?->hasMedia('banner_triple_third_image'))
    <section class="home-content-section">
        <div class="container">
            <div class="home-banner-grid home-banner-grid--three">
                @foreach ($banners as $banner)
                    @if (settings()?->hasMedia($banner['collection']))
                        @php
                            $hasMobileImage = settings()?->hasMedia($banner['mobileCollection']) ?? false;
                        @endphp

                        <a href="#" @class([
                            'home-banner-link',
                            'home-banner-link--triple',
                            'home-banner-link--has-mobile-image' => $hasMobileImage,
                        ])>
                            <picture>
                                @if ($hasMobileImage)
                                    <source media="(max-width: 991px)"
                                        srcset="{{ settings()?->getFirstMediaUrl($banner['mobileCollection'], 'banner_mobile') }}">
                                @endif
                                <img loading="lazy"
                                    src="{{ settings()?->getFirstMediaUrl($banner['collection'], 'banner_triple') }}"
                                    alt="{{ __($banner['alt']) }}">
                            </picture>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
<!-- /Triple Banner -->
