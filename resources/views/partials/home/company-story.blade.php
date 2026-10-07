<!-- Company Story -->
@php
    $settings = settings();
    $storyTitle = $settings?->story_title ?: 'Из сердца Осетии — с заботой о вашем здоровье';
    $storyDescription =
        $settings?->story_description ?:
        "GeoMiLife выросла из простой идеи: создавать натуральные продукты, в составе которых мы уверены сами. Мы бережно отбираем сырьё, производим масла холодного отжима небольшими партиями и контролируем каждый этап — от семени до готовой бутылки.\n\nПосмотрите короткое видео о людях, принципах и месте, с которых начинается каждый наш продукт.";
    $storyVideoUrl =
        $settings?->story_video_url ?:
        'https://vkvideo.ru/video_ext.php?oid=-139157852&id=456239750&hash=d79c769f2d5d3f53&hd=3&autoplay=1&js_api=1';
@endphp

<section id="company-story" class="home-content-section home-story">
    <div class="container">
        <div class="home-story-card wow fadeInUp">
            <div class="home-story-content">
                <p class="home-story-eyebrow">{{ __('Наша история') }}</p>
                <h2 class="home-story-title">{{ __($storyTitle) }}</h2>
                <p class="home-story-text">
                    {!! nl2br(e(__($storyDescription))) !!}
                </p>
                <a href="{{ $storyVideoUrl }}" class="tf-btn home-story-button popup-video"
                    data-video-title="{{ __('История GeoMiLife на VK Видео') }}">
                    <i class="icon icon-Play" aria-hidden="true"></i>
                    {{ __('Смотреть нашу историю') }}
                </a>
            </div>

            <div class="about-video home-story-media">
                <div class="video-thumb">
                    <img loading="lazy"
                        src="{{ setting_image_url($settings?->story_image, 'assets/images/section/company-story.jpg') }}"
                        alt="{{ __('Бережное выращивание натуральных продуктов') }}">
                </div>
                <div class="home-story-location">
                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                    {{ __('Северная Осетия') }}
                </div>
                <a href="{{ $storyVideoUrl }}" class="btn-view-video popup-video"
                    data-video-title="{{ __('История GeoMiLife на VK Видео') }}"
                    aria-label="{{ __('Смотреть видео о GeoMiLife') }}">
                    <i class="icon icon-Play" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</section>
<!-- /Company Story -->
