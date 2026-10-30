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
    @include('home.partials.main-slider')
    @include('home.partials.benefits')
    @include('home.partials.featured-categories')
    @include('home.partials.wide-banner')
    @if ($hitProducts->isNotEmpty())
        <x-products.slider :title="__('Хиты продаж')" :all-products-url="route('catalog.index')">
            @foreach ($hitProducts as $product)
                <x-products.card :product="$product" :wire:key="'product-card-hits-'.$product->getKey()" />
            @endforeach
        </x-products.slider>
    @endif
    @include('home.partials.two-banners')
    @if ($newProducts->isNotEmpty())
        <x-products.slider :title="__('Новинки')" :all-products-url="route('catalog.index')">
            @foreach ($newProducts as $product)
                <x-products.card :product="$product" :wire:key="'product-card-new-'.$product->getKey()" />
            @endforeach
        </x-products.slider>
    @endif
    @include('home.partials.three-banners')
    @if ($discountedProducts->isNotEmpty())
        <x-products.slider :title="__('Товары со скидкой')" :all-products-url="route('catalog.index')">
            @foreach ($discountedProducts as $product)
                <x-products.card :product="$product" :wire:key="'product-card-discounted-'.$product->getKey()" />
            @endforeach
        </x-products.slider>
    @endif
    @include('home.partials.latest-promos')
    @include('home.partials.company-story')
    @include('home.partials.reviews')
@endsection
