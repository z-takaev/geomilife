@extends('layouts.base')

@section('title', __('Как выбрать сыродавленное масло для ежедневного рациона'))
@section('description', __('Практическое руководство по выбору и хранению натуральных масел.'))

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
                ['label' => __('Статьи'), 'url' => route('articles.index')],
                ['label' => __('Выбор сыродавленного масла')],
            ]" />
            <h1 class="page-title text-white font-sora">{{ __('Выбор сыродавленного масла') }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ asset('assets/images/content/article-example.webp') }}"
                                alt="{{ __('Натуральные масла, семена и орехи') }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date">{{ __('5 октября 2026') }}</time>
                            <h2 class="content-detail-title text-primary font-sora">
                                {{ __('Как выбрать сыродавленное масло для ежедневного рациона') }}
                            </h2>
                        </div>
                        <div class="content-detail-body">
                            <p>
                                {{ __('Сыродавленные масла отличаются вкусом, ароматом и набором природных компонентов. Выбор зависит от ваших привычек, любимых блюд и того, как именно вы планируете использовать продукт.') }}
                            </p>
                            <h3>{{ __('Обращайте внимание на способ производства') }}</h3>
                            <p>
                                {{ __('При бережном холодном отжиме сырьё не подвергается сильному нагреву. Готовое масло сохраняет характерный аромат семян или орехов и требует правильного хранения после открытия.') }}
                            </p>
                            <h3>{{ __('Начните со знакомых вкусов') }}</h3>
                            <p>
                                {{ __('Для салатов подойдут мягкие масла, а более насыщенные варианты можно добавлять в каши, соусы и готовые блюда. Используйте масло без нагревания и храните его в соответствии с рекомендациями на упаковке.') }}
                            </p>
                        </div>
                    </div>
                </article>

                <aside class="col-lg-3">
                    <div class="content-detail-gallery content-gallery sticky-top">
                        @foreach (range(1, 3) as $imageNumber)
                            <a href="{{ asset('assets/images/content/article-example.webp') }}"
                                class="gallery-item style-2 img-style4"
                                aria-label="{{ __('Открыть изображение статьи :number', ['number' => $imageNumber]) }}">
                                <img loading="lazy" src="{{ asset('assets/images/content/article-example.webp') }}"
                                    alt="{{ __('Ингредиенты для полезного рациона') }}">
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
