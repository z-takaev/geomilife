@props(['archive' => false, 'date', 'description', 'image', 'title', 'url' => '#'])

<div {{ $attributes->class(['home-offers-item', 'archive-promotion-card' => $archive]) }}>
    <article class="article-blog style-2 home-offer-card hover-img4 wow fadeInUp">
        <a href="{{ $url }}" class="entry-image img-style4">
            <img loading="lazy" src="{{ $image }}" alt="{{ $title }}">
        </a>
        <div class="entry-content gap-0">
            <div class="entry_meta">
                <p class="date cl-text-2">{{ $date }}</p>
            </div>
            <h5 class="entry_title">
                <a href="{{ $url }}" class="font-sora">
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
