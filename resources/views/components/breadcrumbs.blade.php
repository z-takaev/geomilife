@props(['title', 'items' => []])

<section {{ $attributes->class(['s-page-title', 'inner-page-title']) }}>
    <div class="container">
        <nav aria-label="{{ __('Хлебные крошки') }}">
            <ol class="breadcrums">
                <li>
                    <a href="{{ route('home') }}" class="breadcrums__link text-caption-01 link">
                        {{ __('Главная') }}
                    </a>
                </li>

                @foreach ($items as $item)
                    <li aria-hidden="true">
                        <i class="icon icon-arrow-right"></i>
                    </li>
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
                @endforeach
            </ol>
        </nav>

        <h1 class="page-title text-white font-sora">{{ $title }}</h1>
    </div>
</section>
