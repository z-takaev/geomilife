@if ($paginator->hasPages())
    <nav aria-label="{{ __('Навигация по страницам') }}">
        <ul class="wg-pagination archive-pagination justify-content-center">
            @unless ($paginator->onFirstPage())
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" class="pagination-item" rel="prev"
                        aria-label="{{ __('Предыдущая страница') }}">
                        <i class="icon icon-caret-left" aria-hidden="true"></i>
                    </a>
                </li>
            @endunless

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination-item" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <li><span class="pagination-item active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="pagination-item"
                                    aria-label="{{ __('Страница :page', ['page' => $page]) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" class="pagination-item" rel="next"
                        aria-label="{{ __('Следующая страница') }}">
                        <i class="icon icon-caret-right" aria-hidden="true"></i>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
@endif
