@extends('layouts.base')

@section('title', __('Соберите полезный набор со скидкой 15%'))
@section('description', __('Специальное предложение на набор натуральных продуктов GeoMiLife.'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/magnific-popup.min.js') }}"></script>
@endpush

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[
                ['label' => __('Главная'), 'url' => route('home')],
                ['label' => __('Акции'), 'url' => route('promos.index')],
                ['label' => __('Полезный набор')],
            ]" />
            <h1 class="page-title text-white font-sora">{{ __('Полезный набор') }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ asset('assets/images/content/promo-example.webp') }}"
                                alt="{{ __('Подарочный набор натуральных продуктов GeoMiLife') }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date">{{ __('Акция действует до 31 октября 2026') }}</time>
                            <h2 class="content-detail-title text-primary font-sora">
                                {{ __('Соберите полезный набор со скидкой 15%') }}
                            </h2>
                        </div>
                        <div class="content-detail-body">
                            <p>
                                {{ __('Выберите любимые масла, урбеч и мёд, чтобы собрать собственный набор натуральных продуктов. При добавлении трёх и более позиций скидка применится автоматически.') }}
                            </p>
                            <h3>{{ __('Что можно добавить в набор') }}</h3>
                            <p>
                                {{ __('В акции участвуют сыродавленные масла, ореховые пасты, урбечи и натуральный горный мёд. Вы можете сочетать разные продукты и выбрать удобный формат упаковки.') }}
                            </p>
                            <p>
                                {{ __('Количество наборов ограничено. Предложение не суммируется с другими скидками и действует до указанной даты.') }}
                            </p>
                        </div>
                    </div>
                </article>

                <aside class="col-lg-3">
                    <div class="content-detail-gallery content-gallery sticky-top">
                        @foreach (range(1, 3) as $imageNumber)
                            <a href="{{ asset('assets/images/content/promo-example.webp') }}"
                                class="gallery-item style-2 img-style4"
                                aria-label="{{ __('Открыть изображение акции :number', ['number' => $imageNumber]) }}">
                                <img loading="lazy" src="{{ asset('assets/images/content/promo-example.webp') }}"
                                    alt="{{ __('Набор натуральных продуктов') }}">
                                <span class="box-icon" aria-hidden="true">
                                    <i class="icon icon-magnifying-glass"></i>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
