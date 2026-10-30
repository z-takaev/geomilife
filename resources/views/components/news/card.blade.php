@props(['date', 'description', 'image', 'title', 'url' => '#'])

<article {{ $attributes->class(['article-blog style-list news-archive-card hover-img4']) }}>
    <a href="{{ $url }}" class="entry-image img-style4">
        <img loading="lazy" src="{{ $image }}" alt="{{ $title }}">
    </a>
    <div class="entry-content">
        <div class="box-head">
            <div class="entry_meta">
                <time class="date cl-text-2">{{ $date }}</time>
            </div>
            <h2 class="entry_title h5">
                <a href="{{ $url }}" class="text-primary font-sora">{{ $title }}</a>
            </h2>
        </div>
        <p class="entry_desc">{{ $description }}</p>
        <a href="{{ $url }}" class="tf-btn-line">{{ __('Смотреть') }}</a>
    </div>
</article>
