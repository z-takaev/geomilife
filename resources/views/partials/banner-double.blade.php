<!-- Double Banner -->
@php
    $settings = settings();
    $banners = [
        [
            'image' => $settings?->banner_double_first_image,
            'desktop' => 'assets/images/section/promo-cold-pressed-oils.jpg',
            'mobile' => 'assets/images/section/promo-cold-pressed-oils-mobile.webp',
            'alt' => 'Масла холодного отжима — максимум пользы в каждой капле',
        ],
        [
            'image' => $settings?->banner_double_second_image,
            'desktop' => 'assets/images/section/promo-urbech.jpg',
            'mobile' => 'assets/images/section/promo-urbech-mobile.webp',
            'alt' => 'Натуральный урбеч только из орехов и семян',
        ],
    ];
@endphp

<section class="home-content-section">
    <div class="container">
        <div class="home-banner-grid home-banner-grid--two">
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
<!-- /Double Banner -->
