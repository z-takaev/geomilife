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

    <section class="s-page-title inner-page-title product-page-title">
        <div class="container">
            <x-breadcrumbs :items="[
                ['label' => __('Главная'), 'url' => route('home')],
                ['label' => __('Каталог'), 'url' => route('catalog.index')],
                ['label' => $product->name],
            ]" />
            <h1 class="page-title text-white font-sora">{{ $product->name }}</h1>
        </div>
    </section>

    <section class="section-product-single flat-spacing product-detail">
        <div class="container">
            <div class="row g-4 g-xl-5">
                <div class="col-lg-6">
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

                <div class="col-lg-6">
                    <div class="tf-product-info-wrap product-detail__info">
                        <div class="tf-product-info-heading">
                            <div class="product-info-meta">
                                <p class="infor_cate fw-semibold text-uppercase">
                                    {{ $product->category?->name ?? __('Натуральные продукты') }}
                                </p>
                                @if ($product->is_new)
                                    <span class="product-detail__badge">{{ __('Новинка') }}</span>
                                @endif
                                @if ($product->is_hit)
                                    <span
                                        class="product-detail__badge product-detail__badge--accent">{{ __('Хит') }}</span>
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
                                        <span class="product-detail__discount">
                                            {{ __('−:percent%', ['percent' => $selectedVariant->discount_percent]) }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <p class="product-infor-desc">
                                {{ $product->description ?: __('Натуральный продукт из отборного сырья. Бережное производство сохраняет природный вкус, аромат и полезные свойства.') }}
                            </p>
                        </div>

                        @if ($product->variants->isNotEmpty())
                            <div class="tf-product-variant">
                                <div class="variant-picker-item variant-unit">
                                    <div class="variant-picker-label">
                                        <span>{{ __('Объём / вес:') }}</span>
                                        <span class="variant-picker-label-value value-currentUnit fw-medium">
                                            {{ $selectedVariant->label() }}
                                        </span>
                                    </div>
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

                                <div class="variant-quantity">
                                    <div class="variant-picker-label">
                                        <span>{{ __('Количество:') }}</span>
                                    </div>
                                    <div class="wg-quantity product-detail__quantity">
                                        <button type="button" class="btn-quantity btn-decrease"
                                            aria-label="{{ __('Уменьшить количество') }}">
                                            <i class="icon icon-minus" aria-hidden="true"></i>
                                        </button>
                                        <input class="quantity-product" type="number" name="quantity" value="1"
                                            min="1" inputmode="numeric" aria-label="{{ __('Количество') }}">
                                        <button type="button" class="btn-quantity btn-increase"
                                            aria-label="{{ __('Увеличить количество') }}">
                                            <i class="icon icon-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="tf-product-total-btn">
                            <div class="group-btn">
                                <button type="button" class="product-card-buy product-detail__buy w-100"
                                    data-bs-toggle="offcanvas" data-bs-target="#shoppingCart">
                                    {{ __('Добавить в корзину') }}
                                </button>
                                <button type="button" class="tf-btn-icon hover-tooltip btn-add-wishlist"
                                    aria-label="{{ __('Добавить в избранное') }}">
                                    <i class="icon icon-heart-stroke fs-24" aria-hidden="true"></i>
                                    <span class="tooltip">{{ __('В избранное') }}</span>
                                </button>
                            </div>
                        </div>

                        <ul class="product-detail__benefits">
                            <li>
                                <i class="icon icon-Storefront" aria-hidden="true"></i>
                                <span>{{ __('Самовывоз из магазина — бесплатно') }}</span>
                            </li>
                            <li>
                                <i class="icon icon-Truck" aria-hidden="true"></i>
                                <span>{{ __('Доставка курьером и транспортной компанией') }}</span>
                            </li>
                            <li>
                                <i class="icon icon-arrow-clockwise" aria-hidden="true"></i>
                                <span>{{ __('Возврат товара надлежащего качества в течение 14 дней') }}</span>
                            </li>
                        </ul>

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
                                <dd>{{ $product->category?->name ?? __('Не указана') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="product-description-section">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-6">
                    <div class="product-description-section__content">
                        <p class="product-description-section__eyebrow">{{ __('О продукте') }}</p>
                        <h2>{{ __('Подробное описание') }}</h2>
                        <p>
                            {{ $product->description ?: __('Продукт изготовлен из натурального сырья без искусственных ароматизаторов и усилителей вкуса. Подходит для ежедневного рациона и бережно упакован для сохранения свежести.') }}
                        </p>
                        <p>
                            {{ __('Храните продукт в сухом прохладном месте, защищённом от прямых солнечных лучей. После вскрытия упаковки соблюдайте рекомендации на этикетке.') }}
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="product-characteristics">
                        <p class="product-description-section__eyebrow">{{ __('Информация') }}</p>
                        <h2>{{ __('Характеристики') }}</h2>
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

    @include('partials.home.special-offers', [
        'sectionKey' => 'recommended',
        'title' => __('Рекомендуемые товары'),
        'products' => $recommendedProducts,
    ])
@endsection
