@props(['background', 'image', 'name', 'productCount' => __('12 товаров'), 'url' => '#'])

<a href="{{ $url }}" {{ $attributes->class(['category-v01', 'hover-img', 'style-2', $background]) }}>
    <div class="cate-image img-style">
        <img loading="lazy" width="120" height="120" src="{{ asset('assets/images/category/' . $image) }}"
            alt="{{ $name }}">
    </div>
    <div class="cate-info">
        <h5 class="info_name text-primary link-underline">{{ $name }}</h5>
        <p class="info_quanity text-caption-01">{{ $productCount }}</p>
    </div>
</a>
