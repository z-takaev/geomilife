<!-- Triple Banner -->
@php
    $settings = settings();
    $banners = [
        [
            'image' => $settings?->banner_triple_first_image,
            'desktop' => 'assets/images/section/promo-honey-landscape.webp',
            'mobile' => 'assets/images/section/promo-honey-mobile.webp',
            'alt' => 'Мёд и продукты пчеловодства — сила природы в каждой ложке',
        ],
        [
            'image' => $settings?->banner_triple_second_image,
            'desktop' => 'assets/images/section/promo-healthy-sweets-landscape.webp',
            'mobile' => 'assets/images/section/promo-healthy-sweets-mobile.webp',
            'alt' => 'Полезные натуральные сладости',
        ],
        [
            'image' => $settings?->banner_triple_third_image,
            'desktop' => 'assets/images/section/promo-daily-health-landscape.webp',
            'mobile' => 'assets/images/section/promo-daily-health-mobile.webp',
            'alt' => 'БАДы, травы и суперфуды для здоровья каждый день',
        ],
    ];
@endphp

<section class="home-content-section">
    <div class="container">
        <div class="home-banner-grid home-banner-grid--three">
            @foreach ($banners as $banner)
                @php($bannerImageUrl = setting_image_url($banner['image'], $banner['desktop']))
                <a href="#" class="home-banner-link">
                    <picture>
                        <source media="(max-width: 575px)"
                            srcset="{{ $banner['image'] ? $bannerImageUrl : asset($banner['mobile']) }}">
                        <img loading="lazy" src="{{ $bannerImageUrl }}" alt="{{ __($banner['alt']) }}">
                    </picture>
                </a>
            @endforeach
        </div>
    </div>
</section>
<!-- /Triple Banner -->
