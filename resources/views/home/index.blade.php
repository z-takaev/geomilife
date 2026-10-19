@extends('layouts.base')

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
        'sectionKey' => 'hits',
        'title' => __('Хиты продаж'),
        'products' => $hitProducts,
    ])
    @include('partials.banner-double')
    @include('partials.home.special-offers', [
        'sectionKey' => 'new',
        'title' => __('Новинки'),
        'products' => $newProducts,
    ])
    @include('partials.banner-triple')
    @include('partials.home.special-offers', [
        'sectionKey' => 'discounted',
        'title' => __('Товары со скидкой'),
        'products' => $discountedProducts,
    ])
    @include('partials.home.latest-promos')
    @include('partials.home.company-story')
    @include('partials.home.reviews')
@endsection
