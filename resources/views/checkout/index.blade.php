@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Оформление заказа')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Оформление заказа') }}</h1>
        </div>
    </section>

    <section class="s-check-out flat-spacing">
        <div class="container">
            <form class="checkout-page">
                <div class="checkout-page__main">
                    @guest
                        <div class="checkout-login-note" role="status">
                            <span class="checkout-login-note__icon" aria-hidden="true">
                                <i class="icon icon-SealPercent"></i>
                            </span>
                            <div>
                                <h5 class="checkout-login-note__title">{{ __('Получайте больше выгоды') }}</h5>
                                <p class="checkout-login-note__text">
                                    <a href="#login" class="checkout-login-note__link" data-bs-toggle="modal">
                                        {{ __('Войдите в аккаунт') }}
                                    </a>{{ __(', чтобы использовать персональные скидки и получать бонусы за заказ.') }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="checkout-customer-note" role="status">
                            <i class="icon icon-CheckCircle" aria-hidden="true"></i>
                            <span>
                                {{ __('Заказ оформляется для :name. Контактные данные заполнены из профиля.', ['name' => trim($customer->first_name . ' ' . $customer->last_name)]) }}
                            </span>
                        </div>
                    @endguest

                    <section class="checkout-section" aria-labelledby="checkout-products-title">
                        <div class="checkout-section__heading checkout-section__heading--products">
                            <span class="checkout-section__number">{{ __('1') }}</span>
                            <div>
                                <h4 id="checkout-products-title" class="checkout-section__title">
                                    {{ __('Товары в заказе') }}</h4>
                                <p class="checkout-section__description">{{ __('3 товара') }}</p>
                            </div>
                        </div>

                        <ul class="checkout-products">
                            <li class="checkout-product">
                                <a href="{{ route('catalog.index') }}" class="checkout-product__image">
                                    <img src="{{ asset('assets/images/product/oil-product-card.png') }}"
                                        alt="{{ __('Масло чёрного тмина') }}" loading="lazy">
                                </a>
                                <div class="checkout-product__details">
                                    <a href="{{ route('catalog.index') }}"
                                        class="checkout-product__name">{{ __('Масло чёрного тмина') }}</a>
                                    <span class="checkout-product__option mini-product-weight">{{ __('250 мл') }}</span>
                                    <span class="checkout-product__quantity">{{ __('1 шт.') }}</span>
                                </div>
                                <strong class="checkout-product__price">{{ __('890 ₽') }}</strong>
                            </li>
                            <li class="checkout-product">
                                <a href="{{ route('catalog.index') }}" class="checkout-product__image">
                                    <img src="{{ asset('assets/images/product/walnut-urbech-card.png') }}"
                                        alt="{{ __('Урбеч из грецкого ореха') }}" loading="lazy">
                                </a>
                                <div class="checkout-product__details">
                                    <a href="{{ route('catalog.index') }}"
                                        class="checkout-product__name">{{ __('Урбеч из грецкого ореха') }}</a>
                                    <span class="checkout-product__option mini-product-weight">{{ __('400 г') }}</span>
                                    <span class="checkout-product__quantity">{{ __('1 шт.') }}</span>
                                </div>
                                <strong class="checkout-product__price">{{ __('1 250 ₽') }}</strong>
                            </li>
                            <li class="checkout-product">
                                <a href="{{ route('catalog.index') }}" class="checkout-product__image">
                                    <img src="{{ asset('assets/images/product/mountain-honey-card.png') }}"
                                        alt="{{ __('Горный мёд') }}" loading="lazy">
                                </a>
                                <div class="checkout-product__details">
                                    <a href="{{ route('catalog.index') }}"
                                        class="checkout-product__name">{{ __('Горный мёд') }}</a>
                                    <span class="checkout-product__option mini-product-weight">{{ __('500 г') }}</span>
                                    <span class="checkout-product__quantity">{{ __('1 шт.') }}</span>
                                </div>
                                <strong class="checkout-product__price">{{ __('470 ₽') }}</strong>
                            </li>
                        </ul>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-contact-title">
                        <div class="checkout-section__heading">
                            <span class="checkout-section__number">{{ __('2') }}</span>
                            <div>
                                <h4 id="checkout-contact-title" class="checkout-section__title">{{ __('Получатель') }}</h4>
                                <p class="checkout-section__description">{{ __('Укажите, с кем связаться по заказу') }}</p>
                            </div>
                        </div>

                        <div class="checkout-form-grid">
                            <fieldset class="checkout-field">
                                <label for="checkout-first-name">{{ __('Имя') }} <span class="checkout-field__required"
                                        aria-hidden="true"></span></label>
                                <input id="checkout-first-name" type="text" name="first_name"
                                    value="{{ old('first_name', $customer?->first_name) }}"
                                    placeholder="{{ __('Введите имя') }}" autocomplete="given-name" required>
                            </fieldset>
                            <fieldset class="checkout-field">
                                <label for="checkout-last-name">{{ __('Фамилия') }} <span class="checkout-field__required"
                                        aria-hidden="true"></span></label>
                                <input id="checkout-last-name" type="text" name="last_name"
                                    value="{{ old('last_name', $customer?->last_name) }}"
                                    placeholder="{{ __('Введите фамилию') }}" autocomplete="family-name" required>
                            </fieldset>
                            <fieldset class="checkout-field">
                                <label for="checkout-phone">{{ __('Телефон') }} <span class="checkout-field__required"
                                        aria-hidden="true"></span></label>
                                <input id="checkout-phone" type="tel" name="phone"
                                    value="{{ old('phone', $customer?->phone) }}"
                                    placeholder="{{ __('+7 (___) ___-__-__') }}" autocomplete="tel" inputmode="tel"
                                    maxlength="18" data-phone-mask required>
                            </fieldset>
                            <fieldset class="checkout-field">
                                <label for="checkout-email">{{ __('Электронная почта') }} <span
                                        class="checkout-field__required" aria-hidden="true"></span></label>
                                <input id="checkout-email" type="email" name="email"
                                    value="{{ old('email', $customer?->email) }}"
                                    placeholder="{{ __('mail@example.ru') }}" autocomplete="email" required>
                            </fieldset>
                        </div>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-delivery-title">
                        <div class="checkout-section__heading">
                            <span class="checkout-section__number">{{ __('3') }}</span>
                            <div>
                                <h4 id="checkout-delivery-title" class="checkout-section__title">
                                    {{ __('Способ доставки') }}</h4>
                                <p class="checkout-section__description">{{ __('Выберите удобный вариант получения') }}
                                </p>
                            </div>
                        </div>

                        <div class="checkout-delivery-options">
                            <label class="checkout-delivery-option">
                                <input type="radio" name="delivery_method" value="pickup" checked>
                                <span class="checkout-delivery-option__content">
                                    <span class="checkout-delivery-option__icon" aria-hidden="true">
                                        <i class="icon icon-Storefront"></i>
                                    </span>
                                    <span class="checkout-delivery-option__body">
                                        <strong>{{ __('Самовывоз') }}</strong>
                                        <small>{{ __('Сегодня, бесплатно') }}</small>
                                    </span>
                                    <span class="checkout-delivery-option__check" aria-hidden="true"></span>
                                </span>
                            </label>
                            <label class="checkout-delivery-option">
                                <input type="radio" name="delivery_method" value="courier">
                                <span class="checkout-delivery-option__content">
                                    <span class="checkout-delivery-option__icon" aria-hidden="true">
                                        <i class="icon icon-Truck"></i>
                                    </span>
                                    <span class="checkout-delivery-option__body">
                                        <strong>{{ __('Курьер') }}</strong>
                                        <small>{{ __('От 1 дня, от 350 ₽') }}</small>
                                    </span>
                                    <span class="checkout-delivery-option__check" aria-hidden="true"></span>
                                </span>
                            </label>
                            <label class="checkout-delivery-option">
                                <input type="radio" name="delivery_method" value="cdek">
                                <span class="checkout-delivery-option__content">
                                    <span class="checkout-delivery-option__icon" aria-hidden="true">
                                        <i class="icon icon-package"></i>
                                    </span>
                                    <span class="checkout-delivery-option__body">
                                        <strong>{{ __('СДЭК') }}</strong>
                                        <small>{{ __('От 2 дней, рассчитывается отдельно') }}</small>
                                    </span>
                                    <span class="checkout-delivery-option__check" aria-hidden="true"></span>
                                </span>
                            </label>
                        </div>

                        <div class="checkout-address">
                            <div class="checkout-address__heading">
                                <i class="icon icon-MapPin" aria-hidden="true"></i>
                                <h5>{{ __('Адрес получения') }}</h5>
                            </div>
                            <div class="checkout-form-grid">
                                <fieldset class="checkout-field checkout-field--wide">
                                    <label for="checkout-city">{{ __('Город') }} <span class="checkout-field__required"
                                            aria-hidden="true"></span></label>
                                    <input id="checkout-city" type="text" name="city"
                                        value="{{ old('city', $deliveryAddress?->city?->name ?? $customer?->city?->name) }}"
                                        placeholder="{{ __('Введите город') }}" autocomplete="address-level2" required>
                                </fieldset>
                                <fieldset class="checkout-field checkout-field--wide">
                                    <label for="checkout-street">{{ __('Улица') }} <span
                                            class="checkout-field__required" aria-hidden="true"></span></label>
                                    <input id="checkout-street" type="text" name="street"
                                        value="{{ old('street', $deliveryAddress?->street) }}"
                                        placeholder="{{ __('Введите улицу') }}" autocomplete="street-address" required>
                                </fieldset>
                                <fieldset class="checkout-field">
                                    <label for="checkout-house">{{ __('Дом') }} <span
                                            class="checkout-field__required" aria-hidden="true"></span></label>
                                    <input id="checkout-house" type="text" name="house"
                                        value="{{ old('house', $deliveryAddress?->house) }}"
                                        placeholder="{{ __('Дом') }}" required>
                                </fieldset>
                                <fieldset class="checkout-field">
                                    <label for="checkout-apartment">{{ __('Квартира / офис') }}</label>
                                    <input id="checkout-apartment" type="text" name="apartment"
                                        value="{{ old('apartment', $deliveryAddress?->apartment) }}"
                                        placeholder="{{ __('Квартира или офис') }}">
                                </fieldset>
                                <fieldset class="checkout-field">
                                    <label for="checkout-entrance">{{ __('Подъезд') }}</label>
                                    <input id="checkout-entrance" type="text" name="entrance"
                                        value="{{ old('entrance', $deliveryAddress?->entrance) }}"
                                        placeholder="{{ __('Подъезд') }}">
                                </fieldset>
                                <fieldset class="checkout-field">
                                    <label for="checkout-floor">{{ __('Этаж') }}</label>
                                    <input id="checkout-floor" type="text" name="floor"
                                        value="{{ old('floor', $deliveryAddress?->floor) }}"
                                        placeholder="{{ __('Этаж') }}">
                                </fieldset>
                            </div>
                        </div>

                        <fieldset class="checkout-field checkout-field--comment">
                            <label for="checkout-comment">{{ __('Комментарий к заказу') }}</label>
                            <textarea id="checkout-comment" name="comment" placeholder="{{ __('Например, позвонить за час до доставки') }}">{{ old('comment') }}</textarea>
                        </fieldset>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-payment-title">
                        <div class="checkout-section__heading">
                            <span class="checkout-section__number">{{ __('4') }}</span>
                            <div>
                                <h4 id="checkout-payment-title" class="checkout-section__title">{{ __('Оплата') }}</h4>
                                <p class="checkout-section__description">{{ __('Выберите способ оплаты заказа') }}</p>
                            </div>
                        </div>

                        <div class="checkout-payment-options">
                            <label class="checkout-payment-option">
                                <input type="radio" name="payment_method" value="online" checked>
                                <span class="checkout-payment-option__check" aria-hidden="true"></span>
                                <span>
                                    <strong>{{ __('Картой онлайн') }}</strong>
                                    <small>{{ __('Безопасная оплата после подтверждения заказа') }}</small>
                                </span>
                            </label>
                            <label class="checkout-payment-option">
                                <input type="radio" name="payment_method" value="on_receipt">
                                <span class="checkout-payment-option__check" aria-hidden="true"></span>
                                <span>
                                    <strong>{{ __('При получении') }}</strong>
                                    <small>{{ __('Картой или наличными') }}</small>
                                </span>
                            </label>
                        </div>
                    </section>
                </div>

                <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                    <h4 id="checkout-summary-title" class="checkout-summary__title">{{ __('Ваш заказ') }}</h4>

                    <dl class="checkout-summary__rows">
                        <div class="checkout-summary__row">
                            <dt>{{ __('Товары, 3 шт.') }}</dt>
                            <dd>{{ __('2 610 ₽') }}</dd>
                        </div>
                        <div class="checkout-summary__row">
                            <dt>{{ __('Скидка') }}</dt>
                            <dd class="checkout-summary__discount">{{ __('−200 ₽') }}</dd>
                        </div>
                        <div class="checkout-summary__row">
                            <dt>{{ __('Доставка') }}</dt>
                            <dd>{{ __('Бесплатно') }}</dd>
                        </div>
                    </dl>

                    <div class="checkout-summary__total">
                        <span>{{ __('Итого') }}</span>
                        <strong>{{ __('2 410 ₽') }}</strong>
                    </div>

                    <label class="checkout-agreement">
                        <input type="checkbox" class="tf-check style-4 radius-3" required>
                        <span>
                            {{ __('Я соглашаюсь с условиями обработки персональных данных и правилами оформления заказа') }}
                        </span>
                    </label>

                    <button type="button" class="tf-btn animate-btn w-100 checkout-summary__submit">
                        {{ __('Оформить заказ') }}
                    </button>
                </aside>
            </form>
        </div>
    </section>
@endsection
