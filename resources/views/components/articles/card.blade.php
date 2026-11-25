@props(['image', 'title', 'url' => '#'])

<a href="{{ $url }}" {{ $attributes->class(['article-list-item']) }}>
    <span class="article-list-thumbnail">
        <img loading="lazy" src="{{ $image }}" alt="">
    </span>

    <h2 class="article-list-title h5">{{ $title }}</h2>
</a>
