@props(['background', 'image', 'name', 'productCount', 'url' => '#'])

<a href="{{ $url }}" {{ $attributes->class(['category-v01', 'hover-img', 'style-2', $background]) }}>
    <div class="cate-image img-style">
        <img loading="lazy" src="{{ $image }}" alt="{{ __($name) }}">
    </div>
    <div class="cate-info">
        <h5 class="info_name text-primary link-underline">{{ __($name) }}</h5>
        <p class="info_quanity text-caption-01">{{ $productCount }}</p>
    </div>
</a>
