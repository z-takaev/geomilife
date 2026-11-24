@php
    $banners = [
        [
            'collection' => 'banner_triple_first_image',
            'mobileCollection' => 'banner_triple_first_mobile_image',
            'url' => settings()?->banner_triple_first_url,
        ],
        [
            'collection' => 'banner_triple_second_image',
            'mobileCollection' => 'banner_triple_second_mobile_image',
            'url' => settings()?->banner_triple_second_url,
        ],
        [
            'collection' => 'banner_triple_third_image',
            'mobileCollection' => 'banner_triple_third_mobile_image',
            'url' => settings()?->banner_triple_third_url,
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

                        <x-home.banner :image="settings()?->getFirstMediaUrl($banner['collection'])" :mobile-image="$hasMobileImage ? settings()?->getFirstMediaUrl($banner['mobileCollection']) : null" :url="$banner['url']"
                            @class([
                                'home-banner-link',
                                'home-banner-link--triple',
                                'home-banner-link--has-mobile-image' => $hasMobileImage,
                            ]) />
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
