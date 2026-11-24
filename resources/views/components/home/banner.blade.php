@props(['image', 'mobileImage' => null, 'url' => null])

@if ($url)
    <a href="{{ $url }}" {{ $attributes }}>
        <picture>
            @if ($mobileImage)
                <source media="(max-width: 991px)" srcset="{{ $mobileImage }}">
            @endif
            <img loading="lazy" src="{{ $image }}" alt="">
        </picture>
    </a>
@else
    <div {{ $attributes }}>
        <picture>
            @if ($mobileImage)
                <source media="(max-width: 991px)" srcset="{{ $mobileImage }}">
            @endif
            <img loading="lazy" src="{{ $image }}" alt="">
        </picture>
    </div>
@endif
