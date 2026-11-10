<section class="s-check-out flat-spacing">
    <div class="container">
        @if (session('order_message'))
            <div class="checkout-info" role="status">{{ session('order_message') }}</div>
        @endif
        <form wire:submit="placeOrder" class="checkout-page" novalidate>
            <div class="checkout-page__main">
                @guest
                    <div class="checkout-login-note" role="status">
                        <span class="checkout-login-note__icon" aria-hidden="true"><i
                                class="icon icon-SealPercent"></i></span>
                        <div>
                            <h5 class="checkout-login-note__title">{{ __('Уже покупали у нас?') }}</h5>
                            <p class="checkout-login-note__text"><a href="#login" class="checkout-login-note__link"
                                    data-bs-toggle="modal">{{ __('Войдите в аккаунт') }}</a>{{ __(', чтобы использовать сохранённые адреса и видеть заказ в личном кабинете.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="checkout-customer-note" role="status"><i class="icon icon-CheckCircle"
                            aria-hidden="true"></i><span>{{ __('Контакты заполнены из профиля. Вы можете указать другого получателя.') }}</span>
                    </div>
                @endguest

                @if ($isPreorder)
                    <div class="checkout-info" role="status">
                        {{ __('Товары в вашем заказе поставляются под заказ. Точные сроки доставки менеджер согласует с вами отдельно.') }}
                    </div>
                @endif
                @if ($checkout_attempted && $errors->any())
                    <div class="checkout-errors" role="alert">
                        <strong>{{ __('Проверьте данные заказа') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if ($errors->has('quoted_total_minor') || $errors->has('quote'))
                            <button type="button" class="order-text-button"
                                wire:click="recalculate">{{ __('Пересчитать и подтвердить новый итог') }}</button>
                        @endif
                    </div>
                @endif

                <section class="checkout-section" aria-labelledby="checkout-products-title">
                    <div class="checkout-section__heading checkout-section__heading--products"><span
                            class="checkout-section__number">{{ __('1') }}</span>
                        <div>
                            <h4 id="checkout-products-title" class="checkout-section__title">
                                {{ __('Товары в заказе') }}</h4>
                        </div>
                    </div>
                    <ul class="checkout-products">
                        @foreach ($items as $item)
                            <li class="checkout-product" wire:key="checkout-product-{{ $item['product_variant_id'] }}">
                                <a href="{{ $item['url'] }}" class="checkout-product__image"><img
                                        src="{{ $item['image'] ?: asset('assets/images/product/oil-product-card.png') }}"
                                        alt="{{ __($item['product_name']) }}" loading="lazy"></a>
                                <div class="checkout-product__details"><a href="{{ $item['url'] }}"
                                        class="checkout-product__name">{{ $item['product_name'] }}</a><span
                                        class="checkout-product__option mini-product-weight">{{ $item['variant_name'] }}</span>
                                </div>
                                <div class="checkout-product__prices"><strong
                                        class="checkout-product__price">{{ __(':count × :price', ['count' => $item['quantity'], 'price' => \App\Support\Values\Money::fromMinor($item['total_minor'])->format()]) }}</strong>
                                    <span
                                        class="checkout-product__unit-price">{{ __(':price за шт.', ['price' => \App\Support\Values\Money::fromMinor($item['price_minor'])->format()]) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="checkout-section" aria-labelledby="checkout-contact-title">
                    <div class="checkout-section__heading"><span
                            class="checkout-section__number">{{ __('2') }}</span>
                        <div>
                            <h4 id="checkout-contact-title" class="checkout-section__title">{{ __('Получатель') }}</h4>
                            <p class="checkout-section__description">{{ __('Укажите, с кем связаться по заказу') }}</p>
                        </div>
                    </div>
                    @auth
                        <div class="checkout-recipient-options" role="group" aria-label="{{ __('Получатель заказа') }}">
                            <label><input type="radio" wire:model.live.boolean="other_recipient" value="0">
                                {{ __('Я получатель') }}</label>
                            <label><input type="radio" wire:model.live.boolean="other_recipient" value="1">
                                {{ __('Другой получатель') }}</label>
                        </div>
                    @endauth
                    <div class="checkout-form-grid">
                        @foreach ($contactFields as $field)
                            <fieldset class="checkout-field"><label
                                    for="checkout-{{ $field['name'] }}">{{ $field['label'] }} <span
                                        class="checkout-field__required" aria-hidden="true"></span></label>
                                <input id="checkout-{{ $field['name'] }}" type="{{ $field['type'] }}"
                                    wire:model.blur="{{ $field['name'] }}" autocomplete="{{ $field['autocomplete'] }}"
                                    placeholder="{{ $field['placeholder'] }}"
                                    @if ($field['name'] === 'phone') data-phone-mask inputmode="tel" maxlength="18" @endif
                                    required>
                                @error($field['name'])
                                    <small class="checkout-field-error">{{ $message }}</small>
                                @enderror
                            </fieldset>
                        @endforeach
                    </div>
                </section>

                <section class="checkout-section" aria-labelledby="checkout-delivery-title">
                    <div class="checkout-section__heading"><span
                            class="checkout-section__number">{{ __('3') }}</span>
                        <div>
                            <h4 id="checkout-delivery-title" class="checkout-section__title">
                                {{ __('Способ доставки') }}</h4>
                            <p class="checkout-section__description">{{ __('Доступные способы зависят от города') }}
                            </p>
                        </div>
                    </div>
                    <fieldset class="checkout-field checkout-city checkout-suggestions" x-data="checkoutSuggestions()"
                        x-on:click.outside="open = false" x-on:keydown.escape.stop.prevent="close()"
                        x-on:keydown.arrow-down.prevent="move($event, 1)"
                        x-on:keydown.arrow-up.prevent="move($event, -1)">
                        <label for="checkout-city">{{ __('Город') }} <span class="checkout-field__required"
                                aria-hidden="true"></span></label>
                        <div class="checkout-suggestions__input">
                            <input id="checkout-city" type="text" role="combobox" aria-autocomplete="list"
                                aria-controls="checkout-city-results" x-bind:aria-expanded="open" x-ref="searchInput"
                                wire:model="city_search"
                                x-on:input.debounce.300ms="if (Array.from($event.target.value.trim()).length >= 3) $wire.$commit()"
                                x-on:focus="show()" x-on:input="show()" x-on:click="show()"
                                x-on:keydown.enter.prevent placeholder="{{ __('Выберите город (от 3 символов)') }}"
                                autocomplete="off" maxlength="255" required>
                            <ul id="checkout-city-results" class="city-selection__results" role="listbox"
                                aria-label="{{ __('Города') }}" x-ref="searchResults" x-cloak
                                x-show="open && Array.from($wire.city_search.trim()).length >= 3">
                                @forelse ($cities as $option)
                                    <li wire:key="checkout-city-{{ $option->id }}">
                                        <button type="button" role="option"
                                            aria-selected="{{ $city_id === $option->id ? 'true' : 'false' }}"
                                            wire:click="selectCity({{ $option->id }})" x-on:click="open = false"
                                            wire:loading.attr="disabled" wire:target="city_search, selectCity">
                                            <span>{{ $option->name }}</span>
                                            @if ($option->region)
                                                <small>{{ $option->region }}</small>
                                            @endif
                                        </button>
                                    </li>
                                @empty
                                    <li class="checkout-suggestions__empty">
                                        {{ __('Город не найден. Попробуйте изменить запрос.') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        @error('city_id')
                            <small class="checkout-field-error">{{ $message }}</small>
                        @enderror
                    </fieldset>
                    <div class="checkout-delivery-options">
                        @foreach ($availableDeliveryMethods as $value => $label)
                            <label class="checkout-delivery-option"><input type="radio"
                                    wire:model.live="delivery_method" value="{{ $value }}"><span
                                    class="checkout-delivery-option__content"><span
                                        class="checkout-delivery-option__icon" aria-hidden="true"><i
                                            class="icon {{ $value === 'PICKUP' ? 'icon-Storefront' : ($value === 'COURIER' ? 'icon-Truck' : 'icon-package') }}"></i></span><span
                                        class="checkout-delivery-option__body"><strong>{{ $label }}</strong><small>{{ $value === 'PICKUP' ? __('Бесплатно') : ($value === 'COURIER' ? __('Время согласует менеджер') : __('В пункт выдачи')) }}</small></span><span
                                        class="checkout-delivery-option__check"
                                        aria-hidden="true"></span></span></label>
                        @endforeach
                    </div>

                    @if ($city && $delivery_method !== '')
                        <div class="checkout-address checkout-delivery-details"
                            wire:key="delivery-details-{{ $city_id }}-{{ $delivery_method }}">
                            @if ($delivery_method === \App\Enums\Orders\DeliveryMethodEnum::PICKUP->value)
                                <div class="checkout-info"><strong>{{ $city->pickup_name }}</strong>
                                    <p>{{ $city->pickup_address }}</p>
                                    <p>{{ $city->pickup_working_hours }}</p>
                                    <p>{{ $city->pickup_phone }}</p>
                                    <small>{{ __('Приходите после уведомления о готовности. Время получения согласует менеджер.') }}</small>
                                </div>
                            @elseif ($delivery_method === \App\Enums\Orders\DeliveryMethodEnum::COURIER->value)
                                <div class="checkout-address__heading">
                                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                                    <h5>{{ __('Адрес доставки') }}</h5>
                                </div>
                                @if ($addresses->isNotEmpty())
                                    <div class="checkout-saved-options">
                                        @foreach ($addresses as $address)
                                            <button type="button"
                                                class="checkout-saved-option {{ $address_id === $address->id ? 'is-selected' : '' }}"
                                                wire:click="selectAddress({{ $address->id }})">{{ $address->city?->name }},
                                                {{ $address->street }},
                                                {{ $address->house }}</button>
                                        @endforeach
                                        <button type="button" class="checkout-saved-option"
                                            wire:click="clearAddress">{{ __('Новый адрес') }}</button>
                                    </div>
                                @endif
                                <div class="checkout-form-grid">
                                    @foreach ($addressFields as $field)
                                        <fieldset
                                            class="checkout-field {{ $field['name'] === 'street' ? 'checkout-field--wide' : '' }}">
                                            <label for="checkout-{{ $field['name'] }}">{{ $field['label'] }}
                                                @if ($field['required'])
                                                    <span class="checkout-field__required" aria-hidden="true"></span>
                                                @endif
                                            </label>
                                            <input id="checkout-{{ $field['name'] }}" type="text"
                                                wire:model.blur="{{ $field['name'] }}" @required($field['required'])>
                                            @error($field['name'])
                                                <small class="checkout-field-error">{{ $message }}</small>
                                            @enderror
                                        </fieldset>
                                    @endforeach
                                </div>
                                <label class="checkout-door-option"><input type="checkbox"
                                        wire:model.live="delivery_to_door"><span>{{ __('Доставка до двери') }}</span></label>
                                <p class="checkout-field-hint">
                                    {{ __('Доставка до двери: :price. Подъём выше первого этажа оплачивается дополнительно.', ['price' => $doorDeliveryPrice]) }}
                                </p>
                                @if ($delivery_to_door)
                                    <div class="checkout-door-fields">
                                        <div class="checkout-form-grid">
                                            @foreach ($doorFields as $field)
                                                <fieldset class="checkout-field"><label
                                                        for="checkout-{{ $field['name'] }}">{{ $field['label'] }}
                                                        @if ($field['required'])
                                                            <span class="checkout-field__required"
                                                                aria-hidden="true"></span>
                                                        @endif
                                                    </label><input id="checkout-{{ $field['name'] }}"
                                                        type="{{ $field['name'] === 'floor' ? 'number' : 'text' }}"
                                                        wire:model.live.blur="{{ $field['name'] }}"
                                                        @if ($field['name'] === 'floor') min="1" max="200" step="1" inputmode="numeric" @endif
                                                        @required($field['required'])>
                                                    @if ($field['name'] === 'floor')
                                                        <small
                                                            class="checkout-field-hint">{{ __(':price за этаж, начиная со второго. Первый этаж — бесплатно.', ['price' => $floorLiftingPrice]) }}</small>
                                                    @endif
                                                    @error($field['name'])
                                                        <small class="checkout-field-error">{{ $message }}</small>
                                                    @enderror
                                                </fieldset>
                                            @endforeach
                                        </div>
                                        <p class="checkout-section__description">
                                            {{ __('Наличие лифта не влияет на стоимость подъёма.') }}
                                        </p>
                                    </div>
                                @endif
                            @elseif ($delivery_method === \App\Enums\Orders\DeliveryMethodEnum::CDEK->value)
                                <div class="checkout-address__heading">
                                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                                    <h5>{{ __('Пункт получения СДЭК') }}</h5>
                                </div>
                                @if ($savedPoints->isNotEmpty())
                                    <div class="checkout-saved-options">
                                        <strong>{{ __('Ваши пункты получения') }}</strong>
                                        @foreach ($savedPoints as $point)
                                            <button type="button"
                                                class="checkout-saved-option {{ $saved_delivery_point_id === $point->id ? 'is-selected' : '' }}"
                                                wire:click="selectSavedDeliveryPoint({{ $point->id }})"
                                                aria-pressed="{{ $saved_delivery_point_id === $point->id ? 'true' : 'false' }}"><i
                                                    class="icon icon-MapPin"
                                                    aria-hidden="true"></i><span><strong>{{ $point->code }}</strong><small>{{ $point->address }}</small></span></button>
                                        @endforeach
                                        <button type="button"
                                            class="checkout-saved-option checkout-saved-option--new"
                                            wire:click="chooseAnotherDeliveryPoint"><i class="icon icon-MapPin"
                                                aria-hidden="true"></i><span><strong>{{ __('Другой пункт выдачи') }}</strong><small>{{ __('Выбрать город и пункт выдачи') }}</small></span></button>
                                    </div>
                                @endif
                                @if (!$selectedDeliveryPoint)
                                    <fieldset class="checkout-field checkout-suggestions" x-data="checkoutSuggestions()"
                                        x-on:click.outside="open = false" x-on:keydown.escape.stop.prevent="close()"
                                        x-on:keydown.arrow-down.prevent="move($event, 1)"
                                        x-on:keydown.arrow-up.prevent="move($event, -1)">
                                        <label for="checkout-point-search">{{ __('Поиск пункта СДЭК') }}</label>
                                        <div class="checkout-suggestions__input">
                                            <input id="checkout-point-search" type="text" role="combobox"
                                                aria-autocomplete="list" aria-controls="checkout-point-results"
                                                x-bind:aria-expanded="open" x-ref="searchInput"
                                                wire:model.live.debounce.300ms="delivery_point_search"
                                                autocomplete="off" maxlength="255" x-on:input="show()"
                                                x-on:focus="show()" x-on:click="show()" x-on:keydown.enter.prevent
                                                placeholder="{{ __('Адрес или код пункта') }}">
                                            <ul id="checkout-point-results" class="city-selection__results"
                                                role="listbox" aria-label="{{ __('Доступные пункты СДЭК') }}"
                                                x-ref="searchResults" x-cloak
                                                x-show="open && $wire.delivery_point_search.trim() !== ''">
                                                @forelse ($deliveryPoints as $point)
                                                    <li wire:key="point-{{ $point['code'] }}">
                                                        <button type="button" role="option"
                                                            aria-selected="{{ $delivery_point_code === $point['code'] ? 'true' : 'false' }}"
                                                            wire:click="selectDeliveryPoint(@js($point['code']))"
                                                            x-on:click="open = false" wire:loading.attr="disabled"
                                                            wire:target="delivery_point_search, selectDeliveryPoint">
                                                            <strong>{{ $point['code'] }}</strong><span>{{ $point['address'] }}</span></button>
                                                    </li>
                                                @empty
                                                    <li class="checkout-suggestions__empty">
                                                        {{ __('Подходящих пунктов не найдено. Измените поиск или повторите расчёт.') }}
                                                    </li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </fieldset>
                                @endif
                                @if ($selectedDeliveryPoint)
                                    <div class="checkout-info checkout-selected-point" role="status">
                                        <button type="button"
                                            class="order-text-button checkout-selected-point__change"
                                            wire:click="clearDeliveryPoint">{{ __('Изменить') }}</button>
                                        <strong>{{ $selectedDeliveryPoint['code'] }}</strong>
                                        <p>{{ $selectedDeliveryPoint['address'] }}</p>
                                    </div>
                                @endif
                                <button type="button" class="order-text-button" wire:click="recalculate"
                                    wire:loading.attr="disabled">{{ __('Повторить расчёт') }}</button>
                            @endif
                        </div>
                    @endif
                    <fieldset class="checkout-field checkout-field--comment"><label
                            for="checkout-comment">{{ __('Комментарий к заказу') }}</label>
                        <textarea id="checkout-comment" wire:model.blur="comment"
                            placeholder="{{ __('Например, позвонить перед доставкой') }}"></textarea>
                    </fieldset>
                </section>

                <section class="checkout-section" aria-labelledby="checkout-payment-title">
                    <div class="checkout-section__heading"><span
                            class="checkout-section__number">{{ __('4') }}</span>
                        <div>
                            <h4 id="checkout-payment-title" class="checkout-section__title">{{ __('Оплата') }}</h4>
                            <p class="checkout-section__description">{{ __('Выберите способ оплаты заказа') }}</p>
                        </div>
                    </div>
                    <div class="checkout-payment-options">
                        <label class="checkout-payment-option"><input type="radio" wire:model.live="payment_method"
                                value="{{ \App\Enums\Orders\PaymentMethodEnum::ONLINE->value }}"><span
                                class="checkout-payment-option__check"
                                aria-hidden="true"></span><span><strong>{{ \App\Enums\Orders\PaymentMethodEnum::ONLINE->getLabel() }}</strong><small>{{ __('На защищённой странице Т-Банка. Оплата сразу при оформлении.') }}</small></span></label>
                        @if ($delivery_method === \App\Enums\Orders\DeliveryMethodEnum::PICKUP->value)
                            <label class="checkout-payment-option"><input type="radio"
                                    wire:model.live="payment_method"
                                    value="{{ \App\Enums\Orders\PaymentMethodEnum::PICKUP->value }}"><span
                                    class="checkout-payment-option__check"
                                    aria-hidden="true"></span><span><strong>{{ \App\Enums\Orders\PaymentMethodEnum::PICKUP->getLabel() }}</strong><small>{{ __('Оплата в точке самовывоза') }}</small></span></label>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                <h4 id="checkout-summary-title" class="checkout-summary__title">{{ __('Ваш заказ') }}</h4>
                <dl class="checkout-summary__rows">
                    <div class="checkout-summary__row">
                        <dt>{{ __('Товары') }}</dt>
                        <dd>{{ $subtotal->format() }}</dd>
                    </div>
                    <div class="checkout-summary__row">
                        <dt>{{ __('Доставка') }}</dt>
                        <dd>{{ $deliveryReady ? $deliveryPrice->format() : __('Требуется расчёт') }}</dd>
                    </div>
                </dl>
                <div class="checkout-summary__total">
                    <span>{{ __('Итого') }}</span>
                    @if ($deliveryReady)
                        <strong>{{ $total->format() }}</strong>
                    @else
                        <span class="checkout-summary__pending">{{ __('После расчёта') }}</span>
                    @endif
                </div>
                @if ($privacyPolicyUrl)
                    <label class="checkout-agreement"><input type="checkbox" wire:model="agreement"
                            class="tf-check style-4 radius-3" required><span>{{ __('Соглашаюсь с') }} <a
                                href="{{ $privacyPolicyUrl }}" target="_blank"
                                rel="noopener">{{ __('политикой обработки персональных данных') }}</a></span></label>
                @endif
                <button type="submit" class="tf-btn animate-btn w-100 checkout-summary__submit"
                    wire:loading.attr="disabled"><span wire:loading.remove
                        wire:target="placeOrder">{{ __('Оформить заказ') }}</span><span wire:loading
                        wire:target="placeOrder">{{ __('Сохраняем заказ…') }}</span></button>
                <p class="checkout-section__description" wire:loading
                    wire:target="recalculate,selectDeliveryPoint,selectSavedDeliveryPoint">
                    {{ __('Рассчитываем доставку…') }}</p>
            </aside>
        </form>
    </div>
</section>
