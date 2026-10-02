@props(['date', 'description', 'image', 'imageAlt', 'title', 'url' => '#'])

<div {{ $attributes->class(['home-offers-item']) }}>
    <article class="article-blog style-2 home-offer-card hover-img4 wow fadeInUp">
        <a href="{{ $url }}" class="entry-image img-style4">
            <img loading="lazy" width="410" height="273" src="{{ asset('assets/images/blog/' . $image) }}"
                alt="{{ $imageAlt }}">
        </a>
        <div class="entry-content gap-0">
            <div class="entry_meta">
                <p class="date cl-text-2">{{ $date }}</p>
            </div>
            <h5 class="entry_title">
                <a href="{{ $url }}" class="text-primary font-sora">
                    {{ $title }}
                </a>
            </h5>
            <p class="home-offer-description">{{ $description }}</p>
            <a href="{{ $url }}" class="home-offer-link">
                {{ __('Смотреть') }}
                <i class="icon icon-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </article>
</div>
