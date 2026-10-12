@extends('layouts.base')

@section('title', __('Акции'))
@section('description', __('Акции и специальные предложения на натуральные продукты GeoMiLife.'))

@section('content')
    @php
        $promos = [
            [
                'date' => __('До 31 октября'),
                'title' => __('Соберите полезный набор со скидкой 15%'),
                'description' => __('Выберите любимые масла, урбеч и мёд — скидка применится к набору автоматически.'),
            ],
            [
                'date' => __('До 5 ноября'),
                'title' => __('Второе сыродавленное масло дешевле на 20%'),
                'description' => __('Добавьте в корзину любые две бутылки масла и получите скидку на вторую позицию.'),
            ],
            [
                'date' => __('До 10 ноября'),
                'title' => __('Подарок к заказу от 3 000 ₽'),
                'description' => __(
                    'Оформите заказ на сумму от 3 000 ₽ и получите натуральный комплимент от GeoMiLife.',
                ),
            ],
            [
                'date' => __('До 15 ноября'),
                'title' => __('Семейный набор натуральных продуктов'),
                'description' => __('Готовый набор для всей семьи по специальной цене с выгодой на каждую позицию.'),
            ],
            [
                'date' => __('До 20 ноября'),
                'title' => __('Скидка на коллекцию горного мёда'),
                'description' => __(
                    'Попробуйте несколько сортов натурального мёда из Северной Осетии по выгодной цене.',
                ),
            ],
            [
                'date' => __('До 30 ноября'),
                'title' => __('Бесплатная доставка большого заказа'),
                'description' => __('Мы оплатим доставку при заказе продукции на сумму от 5 000 ₽.'),
            ],
        ];
    @endphp

    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Акции')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Акции') }}</h1>
        </div>
    </section>

    <section class="promo-archive-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                @foreach ($promos as $promo)
                    <div class="col-md-6 col-lg-4">
                        <x-promotion-card archive :date="$promo['date']" :description="$promo['description']" :image="asset('assets/images/content/promo-example.webp')" :image-alt="__('Подарочный набор натуральных продуктов GeoMiLife')"
                            :title="$promo['title']" />
                    </div>
                @endforeach
            </div>

            <x-archive-pagination class="archive-pagination" />
        </div>
    </section>
@endsection
