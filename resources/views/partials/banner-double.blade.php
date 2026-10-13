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
                        <a href="#" class="home-banner-link">
                            <img loading="lazy" src="{{ settings()?->getFirstMediaUrl($banner['collection']) }}"
                                alt="{{ __($banner['alt']) }}">
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
<!-- /Double Banner -->
