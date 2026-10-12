@extends('layouts.base')

@section('title', __('Новости'))
@section('description', __('Новости производства, новые продукты и важные события GeoMiLife.'))

@section('content')
    @php
        $newsItems = [
            [
                'date' => __('8 октября 2026'),
                'title' => __('GeoMiLife расширяет производство сыродавленных масел'),
                'description' => __(
                    'Рассказываем о новом оборудовании, которое помогает бережно сохранять природные свойства семян и орехов.',
                ),
            ],
            [
                'date' => __('3 октября 2026'),
                'title' => __('Новая партия продуктов из Северной Осетии уже в каталоге'),
                'description' => __('В ассортимент вернулись любимые масла, урбечи и натуральный мёд свежего сезона.'),
            ],
            [
                'date' => __('25 сентября 2026'),
                'title' => __('Как мы проверяем качество сырья перед производством'),
                'description' => __('Показываем основные этапы отбора семян, орехов и трав для продукции GeoMiLife.'),
            ],
            [
                'date' => __('18 сентября 2026'),
                'title' => __('GeoMiLife принял участие в фестивале локальных производителей'),
                'description' => __(
                    'Делимся впечатлениями от встречи с покупателями и рассказываем о самых популярных продуктах фестиваля.',
                ),
            ],
        ];
    @endphp

    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Новости')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Новости') }}</h1>
        </div>
    </section>

    <section class="s-blog-list news-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="news-archive-list">
                        @foreach ($newsItems as $item)
                            <x-news-card :date="$item['date']" :description="$item['description']" :image="asset('assets/images/content/news-example.webp')" :image-alt="__('Производство натуральных масел GeoMiLife')"
                                :title="$item['title']" />
                        @endforeach
                    </div>

                    <x-archive-pagination class="archive-pagination" />
                </div>
            </div>
        </div>
    </section>
@endsection
