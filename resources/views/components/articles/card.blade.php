@props(['image', 'title', 'url' => '#'])

<article {{ $attributes->class(['article-list-item']) }}>
    <a href="{{ $url }}" class="article-list-thumbnail img-style4">
        <img loading="lazy" src="{{ $image }}" alt="{{ $title }}">
    </a>
    <div class="article-list-content">
        <h2 class="article-list-title h5">
            <a href="{{ $url }}" class="cl-text-main link-underline">{{ $title }}</a>
        </h2>
        <a href="{{ $url }}" class="tf-btn-line">{{ __('Смотреть') }}</a>
    </div>
</article>
