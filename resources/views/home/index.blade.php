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
    @if ($hitProducts->isNotEmpty())
        <x-product-selection :title="__('Хиты продаж')" :all-products-url="route('catalog.index')">
            @foreach ($hitProducts as $product)
                <livewire:product-card :product="$product" :wire:key="'product-card-hits-'.$product->getKey()" />
            @endforeach
        </x-product-selection>
    @endif
    @include('partials.banner-double')
    @if ($newProducts->isNotEmpty())
        <x-product-selection :title="__('Новинки')" :all-products-url="route('catalog.index')">
            @foreach ($newProducts as $product)
                <livewire:product-card :product="$product" :wire:key="'product-card-new-'.$product->getKey()" />
            @endforeach
        </x-product-selection>
    @endif
    @include('partials.banner-triple')
    @if ($discountedProducts->isNotEmpty())
        <x-product-selection :title="__('Товары со скидкой')" :all-products-url="route('catalog.index')">
            @foreach ($discountedProducts as $product)
                <livewire:product-card :product="$product" :wire:key="'product-card-discounted-'.$product->getKey()" />
            @endforeach
        </x-product-selection>
    @endif
    @include('partials.home.latest-promos')
    @include('partials.home.company-story')
    @include('partials.home.reviews')
@endsection
