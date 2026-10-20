@extends('layouts.base')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/magnific-popup.min.js') }}"></script>
@endpush

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => $page->title]]" />
            <h1 class="page-title text-white font-sora">{{ $page->title }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            @php
                $images = $page->getMedia('images');
            @endphp

            <div class="row gy-30">
                <article @class([
                    'content-detail-main-column',
                    'col-lg-9' => $images->isNotEmpty(),
                    'col-12' => $images->isEmpty(),
                ])>
                    <div class="content-detail-main content-detail-main--surface">
                        @if (filled($page->content))
                            <div class="content-detail-body">
                                {{ $page->getRichContentAttribute('content') }}
                            </div>
                        @else
                            <x-empty-state :title="__('Информация пока не добавлена.')" />
                        @endif
                    </div>
                </article>

                @if ($images->isNotEmpty())
                    <aside class="col-lg-3">
                        <div class="content-detail-gallery content-gallery sticky-top">
                            @foreach ($images as $image)
                                <a href="{{ $image->getUrl() }}" class="gallery-item">
                                    <img loading="lazy" src="{{ $image->getUrl() }}"
                                        alt="{{ __('Изображение страницы «:title»', ['title' => $page->title]) }}">
                                </a>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </section>
@endsection
