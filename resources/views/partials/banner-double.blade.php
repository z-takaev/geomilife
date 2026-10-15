<!-- Double Banner -->
@php
    $banners = [
        [
            'collection' => 'banner_double_first_image',
            'alt' => 'Масла холодного отжима — максимум пользы в каждой капле',
        ],
        [
            'collection' => 'banner_double_second_image',
            'alt' => 'Натуральный урбеч только из орехов и семян',
        ],
    ];
@endphp

@if (settings()?->hasMedia('banner_double_first_image') || settings()?->hasMedia('banner_double_second_image'))
    <section class="home-content-section">
        <div class="container">
            <div class="home-banner-grid home-banner-grid--two">
                @foreach ($banners as $banner)
                    @if (settings()?->hasMedia($banner['collection']))
                        <a href="#" class="home-banner-link home-banner-link--double">
                            <picture>
                                <source media="(max-width: 767px)"
                                    srcset="{{ settings()?->getFirstMediaUrl($banner['collection'], 'banner_double_mobile') }}">
                                <img loading="lazy"
                                    src="{{ settings()?->getFirstMediaUrl($banner['collection'], 'banner_double') }}"
                                    alt="{{ __($banner['alt']) }}">
                            </picture>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
<!-- /Double Banner -->
