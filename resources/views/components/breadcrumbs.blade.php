@props(['items'])

<nav aria-label="{{ __('Хлебные крошки') }}">
    <ol {{ $attributes->class(['breadcrums']) }}>
        @foreach ($items as $item)
            <li>
                @if ($item['url'] ?? null)
                    <a href="{{ $item['url'] }}" class="text-caption-01 link text-white">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-caption-01 text-white-50" aria-current="page">
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
