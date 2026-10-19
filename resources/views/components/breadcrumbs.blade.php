@props(['items'])

<nav aria-label="{{ __('Хлебные крошки') }}">
    <ol {{ $attributes->class(['breadcrums']) }}>
        @foreach ($items as $item)
            <li>
                @if ($item['url'] ?? null)
                    <a href="{{ $item['url'] }}" class="breadcrums__link text-caption-01 link">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="breadcrums__current text-caption-01" aria-current="page">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>

            @unless ($loop->last)
                <li aria-hidden="true">
                    <i class="icon icon-arrow-right"></i>
                </li>
            @endunless
        @endforeach
    </ol>
</nav>
