<div class="offcanvas offcanvas-end canvas-quickview" id="quickView" tabindex="-1" aria-labelledby="quickViewTitle">
    <div class="mini-quick-image">
        <div id="quickViewGallery" class="carousel slide quick-view-gallery" data-bs-interval="false">
            <div class="carousel-inner quick-view-gallery__main">
                <div class="carousel-item active">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-7.jpg') }}"
                        alt="{{ __('Масло чёрного тмина') }}">
                </div>
                <div class="carousel-item">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-4.jpg') }}"
                        alt="{{ __('Масло чёрного тмина') }}">
                </div>
                <div class="carousel-item">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-6.jpg') }}"
                        alt="{{ __('Масло чёрного тмина') }}">
                </div>
                <div class="carousel-item">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-5.jpg') }}"
                        alt="{{ __('Масло чёрного тмина') }}">
                </div>
            </div>
            <div class="carousel-indicators quick-view-gallery__thumbnails">
                <button type="button" class="active" data-bs-target="#quickViewGallery" data-bs-slide-to="0"
                    aria-current="true" aria-label="{{ __('Показать первое изображение') }}">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-7.jpg') }}"
                        alt="{{ __('Превью масла чёрного тмина') }}">
                </button>
                <button type="button" data-bs-target="#quickViewGallery" data-bs-slide-to="1"
                    aria-label="{{ __('Показать второе изображение') }}">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-4.jpg') }}"
                        alt="{{ __('Превью масла чёрного тмина') }}">
                </button>
                <button type="button" data-bs-target="#quickViewGallery" data-bs-slide-to="2"
                    aria-label="{{ __('Показать третье изображение') }}">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-6.jpg') }}"
                        alt="{{ __('Превью масла чёрного тмина') }}">
                </button>
                <button type="button" data-bs-target="#quickViewGallery" data-bs-slide-to="3"
                    aria-label="{{ __('Показать четвёртое изображение') }}">
                    <img loading="lazy" src="{{ asset('assets/images/product/detail/detail-5.jpg') }}"
                        alt="{{ __('Превью масла чёрного тмина') }}">
                </button>
            </div>
        </div>
    </div>
    <div class="wrap-canvas">
        <div class="canvas-header ps-md-0">
            <h5 class="title-pop text-primary" id="quickViewTitle">{{ __('Быстрый просмотр') }}</h5>
            <button type="button" class="icon-close-popup" data-bs-dismiss="offcanvas"
                aria-label="{{ __('Закрыть') }}">
                <i class="icon icon-close"></i>
            </button>
        </div>
        <div class="canvas-body ps-md-0 tf-quick-view">
            <div class="quickview-product-info">
                <div class="product-info-meta">
                    <a href="{{ route('catalog.index') }}"
                        class="infor_cate product-detail__category fw-semibold text-uppercase">
                        {{ __('Масла') }}
                    </a>
                    <span class="product-card-badge product-card-badge--hit">{{ __('Хит') }}</span>
                    <span class="product-card-badge product-card-badge--new">{{ __('Новинка') }}</span>
                    <span class="product-card-badge product-card-badge--sale">{{ __('Скидка') }}</span>
                </div>
                <h3 class="product-infor-name text-black">
                    {{ __('Сыродавленное масло чёрного тмина') }}
                </h3>
                <div class="product-infor-price">
                    <h2 class="text-black price-on-sale">{{ __('890 ₽') }}</h2>
                    <p class="text-body-large cl-text-2 text-decoration-line-through">{{ __('1 190 ₽') }}</p>
                </div>
            </div>
            <div class="quickview-product-variant">
                <div class="variant-picker-item variant-unit">
                    <div class="variant-picker-values">
                        <button type="button" class="unit-btn active" data-unit="250 мл"
                            data-price="890">{{ __('250 мл') }}</button>
                        <button type="button" class="unit-btn" data-unit="500 мл"
                            data-price="1490">{{ __('500 мл') }}</button>
                        <button type="button" class="unit-btn" data-unit="1 л"
                            data-price="2690">{{ __('1 л') }}</button>
                    </div>
                </div>
            </div>
            <div class="quickview-product-total-btn">
                <div class="group-btn">
                    <div class="wg-quantity quick-view-quantity">
                        <button type="button" class="btn-quantity minus-prd quick-view-quantity__button"
                            aria-label="{{ __('Уменьшить количество') }}">
                            <i class="icon icon-minus"></i>
                        </button>
                        <input class="quantity-product quick-view-quantity__input" type="text" name="number"
                            value="1" inputmode="numeric" aria-label="{{ __('Количество') }}">
                        <button type="button" class="btn-quantity plus-prd quick-view-quantity__button"
                            aria-label="{{ __('Увеличить количество') }}">
                            <i class="icon icon-plus"></i>
                        </button>
                    </div>
                    <a href="#shoppingCart" data-bs-toggle="offcanvas" class="product-card-buy quick-view-buy w-100">
                        {{ __('Купить') }}
                    </a>
                    <button type="button" class="tf-btn-icon hover-tooltip btn-add-wishlist"
                        aria-label="{{ __('Добавить в избранное') }}">
                        <i class="icon icon-heart-stroke"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="product-detail-tabs quick-view-tabs">
        <nav class="product-detail-tabs__navigation" role="tablist" aria-label="{{ __('Информация о товаре') }}">
            <button class="active" id="quick-view-description-tab" type="button" role="tab"
                data-bs-toggle="tab" data-bs-target="#quick-view-description" aria-controls="quick-view-description"
                aria-selected="true">
                {{ __('Описание') }}
            </button>
            <button id="quick-view-characteristics-tab" type="button" role="tab" data-bs-toggle="tab"
                data-bs-target="#quick-view-characteristics" aria-controls="quick-view-characteristics"
                aria-selected="false">
                {{ __('Характеристики') }}
            </button>
        </nav>

        <div class="tab-content product-detail-tabs__content">
            <div class="tab-pane fade show active" id="quick-view-description" role="tabpanel"
                aria-labelledby="quick-view-description-tab" tabindex="0">
                <p>
                    {{ __('Натуральное сыродавленное масло с насыщенным вкусом и ароматом. Подходит для салатов, каш и холодных блюд.') }}
                </p>
                <p>
                    {{ __('Храните продукт в сухом прохладном месте, защищённом от прямых солнечных лучей. После вскрытия упаковки соблюдайте рекомендации на этикетке.') }}
                </p>
            </div>

            <div class="tab-pane fade" id="quick-view-characteristics" role="tabpanel"
                aria-labelledby="quick-view-characteristics-tab" tabindex="0">
                <dl class="product-characteristics__list">
                    <div class="product-characteristics__item">
                        <dt>{{ __('Состав') }}</dt>
                        <span aria-hidden="true"></span>
                        <dd>{{ __('100% масло чёрного тмина') }}</dd>
                    </div>
                    <div class="product-characteristics__item">
                        <dt>{{ __('Объём') }}</dt>
                        <span aria-hidden="true"></span>
                        <dd>{{ __('250 мл') }}</dd>
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
                </dl>
            </div>
        </div>
    </div>
</div>
