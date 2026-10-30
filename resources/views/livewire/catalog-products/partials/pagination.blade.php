@php
    $scrollTo = $scrollTo ?? 'body';
@endphp

@if ($paginator->hasPages())
    <nav aria-label="{{ __('Навигация по страницам') }}">
        <ul class="wg-pagination archive-pagination justify-content-center">
            @unless ($paginator->onFirstPage())
                <li>
                    <button type="button" class="pagination-item" wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="document.querySelector(@js($scrollTo))?.scrollIntoView()"
                        wire:loading.attr="disabled" aria-label="{{ __('Предыдущая страница') }}">
                        <i class="icon icon-caret-left" aria-hidden="true"></i>
                    </button>
                </li>
            @endunless

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination-item" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <li wire:key="catalog-page-{{ $page }}">
                                <span class="pagination-item active" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li wire:key="catalog-page-{{ $page }}">
                                <button type="button" class="pagination-item"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="document.querySelector(@js($scrollTo))?.scrollIntoView()"
                                    wire:loading.attr="disabled"
                                    aria-label="{{ __('Страница :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </button>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <button type="button" class="pagination-item"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="document.querySelector(@js($scrollTo))?.scrollIntoView()"
                        wire:loading.attr="disabled" aria-label="{{ __('Следующая страница') }}">
                        <i class="icon icon-caret-right" aria-hidden="true"></i>
                    </button>
                </li>
            @endif
        </ul>
    </nav>
@endif
