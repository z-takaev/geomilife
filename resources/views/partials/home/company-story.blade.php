<!-- Company Story -->
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
                @if (settings()?->story_video_url)
                    <a href="{{ settings()?->story_video_url }}" class="tf-btn home-story-button popup-video"
                        data-video-title="{{ __('История GeoMiLife на VK Видео') }}">
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
                @if (settings()?->story_video_url)
                    <a href="{{ settings()?->story_video_url }}" class="btn-view-video popup-video"
                        data-video-title="{{ __('История GeoMiLife на VK Видео') }}"
                        aria-label="{{ __('Смотреть видео о GeoMiLife') }}">
                        <i class="icon icon-Play" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
<!-- /Company Story -->
