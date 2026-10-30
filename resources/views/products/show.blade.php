@extends('layouts.base')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
@endpush

@section('content')
    <x-breadcrumbs class="product-page-title" :title="$product->category?->name" :breadcrumbs="$breadcrumbs" />

    <section class="section-product-single flat-spacing product-detail">
        <div class="container">
            <div class="row g-4 g-xl-5">
                <div class="col-lg-4">
                    @if ($images->isNotEmpty())
                        <div id="productGallery" class="carousel slide product-gallery" data-bs-interval="false">
                            <div class="carousel-inner product-gallery__main">
                                @foreach ($images as $image)
                                    <div @class(['carousel-item', 'active' => $loop->first])>
                                        <img loading="lazy" src="{{ $image->getUrl() }}" alt="{{ $product->name }}">
                                    </div>
                                @endforeach
                            </div>

                            @if ($images->count() > 1)
                                <button class="carousel-control-prev product-gallery__control" type="button"
                                    data-bs-target="#productGallery" data-bs-slide="prev"
                                    aria-label="{{ __('Предыдущее изображение') }}">
                                    <span class="icon icon-caret-left" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next product-gallery__control" type="button"
                                    data-bs-target="#productGallery" data-bs-slide="next"
                                    aria-label="{{ __('Следующее изображение') }}">
                                    <span class="icon icon-caret-right" aria-hidden="true"></span>
                                </button>

                                <div class="carousel-indicators product-gallery__thumbnails">
                                    @foreach ($images as $image)
                                        <button type="button" @class(['active' => $loop->first]) data-bs-target="#productGallery"
                                            data-bs-slide-to="{{ $loop->index }}"
                                            @if ($loop->first) aria-current="true" @endif
                                            aria-label="{{ __('Показать изображение :number', ['number' => $loop->iteration]) }}">
                                            <img loading="lazy" src="{{ $image->getUrl() }}"
                                                alt="{{ __('Превью товара :number', ['number' => $loop->iteration]) }}">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <span class="home-product-card__image-placeholder">{{ __('Изображение отсутствует') }}</span>
                    @endif
                </div>

                <div class="col-lg-8">
                    <div class="tf-product-info-wrap product-detail__info" x-data="productVariantSelector(@js($productViewModel->variantOptions), @js($productViewModel->selectedVariant['id']))">
                        <div class="tf-product-info-heading">
                            <div class="product-info-meta">
                                <a href="{{ route('catalog.category', $product->category) }}"
                                    class="infor_cate product-detail__category fw-semibold text-uppercase">
                                    {{ $product->category->name }}
                                </a>
                                @if ($product->is_new)
                                    <span class="product-card-badge product-card-badge--new">{{ __('Новинка') }}</span>
                                @endif
                                @if ($product->is_hit)
                                    <span class="product-card-badge product-card-badge--hit">{{ __('Хит') }}</span>
                                @endif
                            </div>

                            <h2 class="product-infor-name">{{ $product->name }}</h2>

                            <x-products.price :variant="$productViewModel->selectedVariant" />
                        </div>
                        <div class="quickview-product-variant product-detail__variant">
                            <div class="variant-picker-item variant-unit">
                                <x-products.variant-selector :variants="$productViewModel->variantOptions" :selected-id="$productViewModel->selectedVariant['id']" />
                            </div>
                        </div>

                        <div class="quickview-product-total-btn product-detail__actions">
                            <div class="group-btn">
                                <x-products.quantity-selector />
                                <x-products.add-to-cart-button :variant="$productViewModel->selectedVariant" />
                                <x-products.favorite-button :product="$product" />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-description-section">
        <div class="container">
            <x-products.details :product="$product" prefix="product" />
        </div>
    </section>

    @if ($recommendedProducts->isNotEmpty())
        <x-products.slider :title="__('Рекомендуемые товары')" :all-products-url="route('catalog.index')">
            @foreach ($recommendedProducts as $recommendedProduct)
                <x-products.card :product="$recommendedProduct" :wire:key="'product-card-recommended-'.$recommendedProduct->getKey()" />
            @endforeach
        </x-products.slider>
    @endif
@endsection
