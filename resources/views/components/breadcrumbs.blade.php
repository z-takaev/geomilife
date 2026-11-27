@props(['title' => null, 'breadcrumbs'])

<section {{ $attributes->class(['s-page-title', 'inner-page-title']) }}>
    <div class="container">
        <nav class="breadcrumbs-scroll" aria-label="{{ __('Хлебные крошки') }}">
            <ol class="breadcrums">
                @foreach ($breadcrumbs->items() as $item)
                    @if (!$loop->first)
                        <li class="breadcrums__separator" aria-hidden="true">
                            <span></span>
                        </li>
                    @endif

                    <li>
                        @if (!$loop->last)
                            <a href="{{ $item->url }}" class="breadcrums__link text-caption-01 link">
                                {{ $item->label }}
                            </a>
                        @else
                            <span class="breadcrums__current text-caption-01" aria-current="page">
                                {{ $item->label }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        @if ($title)
            <h1 class="page-title font-sora">{{ $title }}</h1>
        @endif
    </div>
</section>
