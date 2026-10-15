<!-- Triple Banner -->
@php
    $banners = [
        [
            'collection' => 'banner_triple_first_image',
            'alt' => 'Мёд и продукты пчеловодства — сила природы в каждой ложке',
        ],
        [
            'collection' => 'banner_triple_second_image',
            'alt' => 'Полезные натуральные сладости',
        ],
        [
            'collection' => 'banner_triple_third_image',
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
                        <a href="#" class="home-banner-link home-banner-link--triple">
                            <picture>
                                <source media="(max-width: 767px)"
                                    srcset="{{ settings()?->getFirstMediaUrl($banner['collection'], 'banner_triple_mobile') }}">
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
