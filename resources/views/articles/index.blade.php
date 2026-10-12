@extends('layouts.base')

@section('title', __('Статьи'))
@section('description', __('Полезные статьи о натуральных продуктах, питании и здоровых привычках.'))

@section('content')
    @php
        $articles = [
            __('Как выбрать сыродавленное масло для ежедневного рациона'),
            __('Польза урбеча: состав, свойства и способы употребления'),
            __('Льняное масло: как хранить и с чем сочетать'),
            __('Натуральный мёд: на что обратить внимание при выборе'),
            __('Тыквенные семечки как источник ценных микроэлементов'),
            __('Пять простых способов добавить полезные масла в рацион'),
            __('Как читать состав натурального продукта'),
            __('Здоровый завтрак из простых и понятных ингредиентов'),
        ];
    @endphp

    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Статьи')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Статьи') }}</h1>
        </div>
    </section>

    <section class="article-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="article-archive-list">
                        @foreach ($articles as $articleTitle)
                            <x-article-list-item :image="asset('assets/images/content/article-example.webp')" :image-alt="__('Натуральные масла, семена и орехи')" :title="$articleTitle" />
                        @endforeach
                    </div>

                    <x-archive-pagination class="archive-pagination" />
                </div>
            </div>
        </div>
    </section>
@endsection
