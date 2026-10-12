@extends('layouts.base')

@section('title', __('GeoMiLife расширяет производство сыродавленных масел'))
@section('description', __('Новости производства натуральных продуктов GeoMiLife.'))

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
                ['label' => __('Новости'), 'url' => route('news.index')],
                ['label' => __('Новая производственная линия')],
            ]" />
            <h1 class="page-title text-white font-sora">{{ __('Новая производственная линия') }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ asset('assets/images/content/news-example.webp') }}"
                                alt="{{ __('Производство сыродавленного масла GeoMiLife') }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date">{{ __('8 октября 2026') }}</time>
                            <h2 class="content-detail-title text-primary font-sora">
                                {{ __('GeoMiLife расширяет производство сыродавленных масел') }}
                            </h2>
                        </div>
                        <div class="content-detail-body">
                            <p>
                                {{ __('Мы запустили новую производственную линию, которая помогает ещё бережнее работать с семенами и орехами. Оборудование поддерживает низкую температуру отжима и позволяет сохранить естественный вкус, аромат и свойства сырья.') }}
                            </p>
                            <h3>{{ __('Больше контроля на каждом этапе') }}</h3>
                            <p>
                                {{ __('Каждая партия проходит несколько этапов проверки: от отбора сырья до оценки готового масла. Обновлённый процесс делает производство стабильнее, сохраняя ремесленный подход и внимание к деталям.') }}
                            </p>
                            <p>
                                {{ __('В ближайшее время продукты новой партии появятся в каталоге. Мы продолжим рассказывать о производстве и знакомить вас с людьми, которые создают продукцию GeoMiLife.') }}
                            </p>
                        </div>
                    </div>
                </article>

                <aside class="col-lg-3">
                    <div class="content-detail-gallery content-gallery sticky-top">
                        @foreach (range(1, 3) as $imageNumber)
                            <a href="{{ asset('assets/images/content/news-example.webp') }}"
                                class="gallery-item style-2 img-style4"
                                aria-label="{{ __('Открыть изображение новости :number', ['number' => $imageNumber]) }}">
                                <img loading="lazy" src="{{ asset('assets/images/content/news-example.webp') }}"
                                    alt="{{ __('Производство натуральных масел') }}">
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
