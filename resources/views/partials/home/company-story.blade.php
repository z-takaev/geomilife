<!-- Company Story -->
@php
    $storyVideoUrl = settings()?->storyVideoEmbedUrl();
@endphp

<section id="company-story" class="home-content-section home-story">
    <div class="container">
        <div class="home-story-card wow fadeInUp">
            <div class="home-story-content">
                <p class="home-story-eyebrow">{{ __('Наша история') }}</p>
                @if (settings()?->story_title)
                    <h2 class="home-story-title">{{ __(settings()?->story_title) }}</h2>
                @endif
                @if (settings()?->story_description)
                    <p class="home-story-text">
                        {!! nl2br(e(__(settings()?->story_description))) !!}
                    </p>
                @endif
                @if ($storyVideoUrl)
                    <a href="#home-story-video-popup" class="tf-btn home-story-button popup-video">
                        <i class="icon icon-Play" aria-hidden="true"></i>
                        {{ __('Смотреть нашу историю') }}
                    </a>
                @endif
            </div>

            <div class="about-video home-story-media">
                @if (settings()?->hasMedia('story_image'))
                    <div class="video-thumb">
                        <img loading="lazy" src="{{ settings()?->getFirstMediaUrl('story_image') }}"
                            alt="{{ __('Бережное выращивание натуральных продуктов') }}">
                    </div>
                @endif
                @if ($storyVideoUrl)
                    <a href="#home-story-video-popup" class="btn-view-video popup-video"
                        aria-label="{{ __('Смотреть видео о GeoMiLife') }}">
                        <i class="icon icon-Play" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>

@if ($storyVideoUrl)
    <div id="home-story-video-popup" class="home-story-video-dialog mfp-hide">
        <div class="home-story-video-frame">
            <iframe id="home-story-video-frame" src="{{ $storyVideoUrl }}"
                title="{{ __('История GeoMiLife на VK Видео') }}"
                allow="autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock"
                allowfullscreen></iframe>
        </div>
    </div>
@endif
<!-- /Company Story -->
