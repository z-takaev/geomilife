@extends('layouts.base')

@section('title', __('GeoMiLife — натуральные продукты из Северной Осетии'))
@section('description', __('Натуральные продукты GeoMiLife с доставкой по России.'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
    <script src="{{ asset('assets/js/magnific-popup.min.js') }}"></script>
    <script src="{{ asset('assets/js/vk-video-player.min.js') }}"></script>
@endpush

@section('content')
    @include('partials.home.hero-slider')
    @include('partials.home.benefits')
    @include('partials.home.featured-categories')
    @include('partials.banner-wide')
    @include('partials.home.special-offers', [
        'title' => __('Хиты продаж'),
        'productOrder' => [0, 2, 1, 4, 3, 5],
    ])
    @include('partials.banner-double')
    @include('partials.home.special-offers', [
        'title' => __('Новинки'),
        'productOrder' => [3, 1, 4, 0, 5, 2],
    ])
    @include('partials.banner-triple')
    @include('partials.home.special-offers', [
        'title' => __('Товары со скидкой'),
        'productOrder' => [2, 5, 4, 0, 3, 1],
    ])
    @include('partials.home.latest-promos')
    @include('partials.home.company-story')
@endsection
