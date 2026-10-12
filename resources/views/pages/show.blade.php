@extends('layouts.base')

@section('title', __('Доставка и оплата'))
@section('description', __('Условия доставки и оплаты заказов GeoMiLife.'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/magnific-popup.min.js') }}"></script>
@endpush

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Доставка и оплата')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Доставка и оплата') }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ asset('assets/images/content/page-example.webp') }}"
                                alt="{{ __('Посылка с натуральными продуктами GeoMiLife') }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date">{{ __('Обновлено 8 октября 2026') }}</time>
                            <h2 class="content-detail-title text-primary font-sora">{{ __('Доставка и оплата') }}</h2>
                        </div>
                        <div class="content-detail-body">
                            <p>
                                {{ __('Мы отправляем натуральные продукты GeoMiLife по всей России. Стоимость и срок доставки зависят от региона, состава заказа и выбранного способа получения.') }}
                            </p>
                            <h3>{{ __('Оформление доставки') }}</h3>
                            <p>
                                {{ __('Укажите адрес и контактные данные при оформлении заказа. После подтверждения мы аккуратно упакуем продукты и передадим отправление в службу доставки.') }}
                            </p>
                            <h3>{{ __('Оплата заказа') }}</h3>
                            <p>
                                {{ __('Доступные способы оплаты будут показаны на этапе оформления. После оплаты вы получите подтверждение, а после отправки — информацию для отслеживания посылки.') }}
                            </p>
                        </div>
                    </div>
                </article>

                <aside class="col-lg-3">
                    <div class="content-detail-gallery content-gallery sticky-top">
                        @foreach (range(1, 3) as $imageNumber)
                            <a href="{{ asset('assets/images/content/page-example.webp') }}"
                                class="gallery-item style-2 img-style4"
                                aria-label="{{ __('Открыть изображение страницы :number', ['number' => $imageNumber]) }}">
                                <img loading="lazy" src="{{ asset('assets/images/content/page-example.webp') }}"
                                    alt="{{ __('Доставка натуральных продуктов') }}">
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
