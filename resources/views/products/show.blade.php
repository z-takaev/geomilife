@extends('layouts.base')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
@endpush

@section('content')
    @php
        $selectedVariant = $product->variants->first();
        $mainImage = $product->getFirstMediaUrl('image');
        $galleryImages = collect([
            $mainImage !== '' ? $mainImage : asset('assets/images/product/detail/detail-7.jpg'),
            asset('assets/images/product/detail/detail-4.jpg'),
            asset('assets/images/product/detail/detail-6.jpg'),
            asset('assets/images/product/detail/detail-5.jpg'),
        ])
            ->unique()
            ->values();
    @endphp

    <x-breadcrumbs class="product-page-title" :title="$product->category?->name" :items="[['label' => __('Каталог'), 'url' => route('catalog.index')], ['label' => $product->name]]" />

    <section class="section-product-single flat-spacing product-detail">
        <div class="container">
            <div class="row g-4 g-xl-5">
                <div class="col-lg-4">
                    <div id="productGallery" class="carousel slide product-gallery" data-bs-interval="false">
                        <div class="carousel-inner product-gallery__main">
                            @foreach ($galleryImages as $image)
                                <div @class(['carousel-item', 'active' => $loop->first])>
                                    <img loading="lazy" src="{{ $image }}" alt="{{ $product->name }}">
                                </div>
                            @endforeach
                        </div>

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
                            @foreach ($galleryImages as $image)
                                <button type="button" @class(['active' => $loop->first]) data-bs-target="#productGallery"
                                    data-bs-slide-to="{{ $loop->index }}"
                                    @if ($loop->first) aria-current="true" @endif
                                    aria-label="{{ __('Показать изображение :number', ['number' => $loop->iteration]) }}">
                                    <img loading="lazy" src="{{ $image }}"
                                        alt="{{ __('Превью товара :number', ['number' => $loop->iteration]) }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="tf-product-info-wrap product-detail__info">
                        <div class="tf-product-info-heading">
                            <div class="product-info-meta">
                                <a href="{{ route('catalog.index') }}"
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

                            @if ($selectedVariant !== null)
                                <div class="product-infor-price">
                                    <span
                                        class="product-detail__price">{{ $selectedVariant->currentPrice()->format() }}</span>
                                    @if ($selectedVariant->oldPrice() !== null)
                                        <span
                                            class="product-detail__old-price">{{ $selectedVariant->oldPrice()->format() }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($product->variants->isNotEmpty())
                            <div class="quickview-product-variant product-detail__variant">
                                <div class="variant-picker-item variant-unit">
                                    <div class="variant-picker-values" role="group"
                                        aria-label="{{ __('Выберите объём или вес') }}">
                                        @foreach ($product->variants as $variant)
                                            <button type="button" class="unit-btn{{ $loop->first ? ' active' : '' }}"
                                                data-unit="{{ $variant->label() }}"
                                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                                {{ $variant->label() }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="quickview-product-total-btn product-detail__actions">
                            <div class="group-btn">
                                <div class="wg-quantity quick-view-quantity">
                                    <button type="button" class="btn-quantity minus-prd quick-view-quantity__button"
                                        aria-label="{{ __('Уменьшить количество') }}">
                                        <i class="icon icon-minus" aria-hidden="true"></i>
                                    </button>
                                    <input class="quantity-product quick-view-quantity__input" type="text" name="number"
                                        value="1" inputmode="numeric" aria-label="{{ __('Количество') }}">
                                    <button type="button" class="btn-quantity plus-prd quick-view-quantity__button"
                                        aria-label="{{ __('Увеличить количество') }}">
                                        <i class="icon icon-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="button" class="product-card-buy quick-view-buy w-100"
                                    data-bs-toggle="offcanvas" data-bs-target="#shoppingCart">
                                    {{ __('Купить') }}
                                </button>
                                <button type="button" class="tf-btn-icon hover-tooltip btn-add-wishlist"
                                    aria-label="{{ __('Добавить в избранное') }}">
                                    <i class="icon icon-heart-stroke" aria-hidden="true"></i>
                                    <span class="tooltip">{{ __('В избранное') }}</span>
                                </button>
                            </div>
                        </div>

                        <dl class="product-detail__meta">
                            <div>
                                <dt>{{ __('Артикул') }}</dt>
                                <dd>{{ $selectedVariant?->sku ?? __('Не указан') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Наличие') }}</dt>
                                <dd>{{ $selectedVariant !== null ? __($selectedVariant->status->getLabel()) : __('Нет в наличии') }}
                                </dd>
                            </div>
                            <div>
                                <dt>{{ __('Категория') }}</dt>
                                <dd>
                                    <a href="{{ route('catalog.index') }}">{{ $product->category->name }}</a>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-description-section">
        <div class="container">
            <div class="product-detail-tabs">
                <nav class="product-detail-tabs__navigation" role="tablist" aria-label="{{ __('Информация о товаре') }}">
                    <button class="active" id="product-description-tab" type="button" role="tab" data-bs-toggle="tab"
                        data-bs-target="#product-description" aria-controls="product-description" aria-selected="true">
                        {{ __('Описание') }}
                    </button>
                    <button id="product-characteristics-tab" type="button" role="tab" data-bs-toggle="tab"
                        data-bs-target="#product-characteristics" aria-controls="product-characteristics"
                        aria-selected="false">
                        {{ __('Характеристики') }}
                    </button>
                </nav>

                <div class="tab-content product-detail-tabs__content">
                    <div class="tab-pane fade show active" id="product-description" role="tabpanel"
                        aria-labelledby="product-description-tab" tabindex="0">
                        <p>
                            {{ $product->description ?: __('Продукт изготовлен из натурального сырья без искусственных ароматизаторов и усилителей вкуса. Подходит для ежедневного рациона и бережно упакован для сохранения свежести.') }}
                        </p>
                        <p>
                            {{ __('Храните продукт в сухом прохладном месте, защищённом от прямых солнечных лучей. После вскрытия упаковки соблюдайте рекомендации на этикетке.') }}
                        </p>
                    </div>

                    <div class="tab-pane fade" id="product-characteristics" role="tabpanel"
                        aria-labelledby="product-characteristics-tab" tabindex="0">
                        <dl class="product-characteristics__list">
                            @forelse ($product->attributeValues as $attributeValue)
                                <div class="product-characteristics__item">
                                    <dt>{{ $attributeValue->productAttribute->name }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ $attributeValue->value }}</dd>
                                </div>
                            @empty
                                <div class="product-characteristics__item">
                                    <dt>{{ __('Состав') }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ __('100% натуральный продукт') }}</dd>
                                </div>
                                <div class="product-characteristics__item">
                                    <dt>{{ __('Страна производства') }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ __('Россия') }}</dd>
                                </div>
                                <div class="product-characteristics__item">
                                    <dt>{{ __('Срок годности') }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ __('12 месяцев') }}</dd>
                                </div>
                                <div class="product-characteristics__item">
                                    <dt>{{ __('Условия хранения') }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ __('При температуре от +5 до +25 °C') }}</dd>
                                </div>
                                <div class="product-characteristics__item">
                                    <dt>{{ __('Упаковка') }}</dt>
                                    <span aria-hidden="true"></span>
                                    <dd>{{ __('Стеклянная бутылка') }}</dd>
                                </div>
                            @endforelse
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($recommendedProducts->isNotEmpty())
        <x-product-selection :title="__('Рекомендуемые товары')" :all-products-url="route('catalog.index')">
            @foreach ($recommendedProducts as $recommendedProduct)
                <livewire:product-card :product="$recommendedProduct"
                    :wire:key="'product-card-recommended-'.$recommendedProduct->getKey()" />
            @endforeach
        </x-product-selection>
    @endif
@endsection
