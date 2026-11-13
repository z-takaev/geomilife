<div class="profile-page">
    <div class="profile-grid">
        <section class="profile-card">
            <div class="profile-card__heading">
                <div><span class="profile-eyebrow">{{ __('Личный кабинет') }}</span>
                    <h2>{{ __('Личные данные') }}</h2>
                </div>
            </div>
            <form wire:submit="saveProfile" class="profile-form">
                <label>{{ __('Имя') }}<input type="text" wire:model="form.first_name" autocomplete="given-name">
                    @error('form.first_name')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Фамилия') }}<input type="text" wire:model="form.last_name" autocomplete="family-name">
                    @error('form.last_name')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Дата рождения') }}<input type="date" wire:model="form.birth_date">
                    @error('form.birth_date')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Телефон') }}<input type="tel" wire:model="form.phone" autocomplete="tel"
                        placeholder="{{ __('+7 (___) ___-__-__') }}" inputmode="tel" maxlength="18" data-phone-mask>
                    @error('form.phone')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <fieldset class="profile-form__wide checkout-suggestions" x-data="checkoutSuggestions()"
                    x-on:click.outside="open = false" x-on:keydown.escape.stop.prevent="close()">
                    <label for="profile-city">{{ __('Город по умолчанию') }}</label>
                    <div class="checkout-suggestions__input">
                        <input id="profile-city" type="search" wire:model.live.debounce.300ms="city_search"
                            placeholder="{{ __('Начните вводить город') }}" autocomplete="off" maxlength="255"
                            x-ref="searchInput" x-on:focus="show()" x-on:input="show()">
                        @if (mb_strlen(trim($city_search)) >= 3)
                            <ul class="city-selection__results" x-show="open">
                                @forelse ($cities as $city)
                                    <li wire:key="profile-city-{{ $city->id }}">
                                        <button type="button" wire:click="selectCity({{ $city->id }})"
                                            x-on:click="open = false">
                                            <span>{{ $city->name }}</span>
                                            @if ($city->region)
                                                <small>{{ $city->region }}</small>
                                            @endif
                                        </button>
                                    </li>
                                @empty
                                    <li class="city-selection__status">{{ __('Города не найдены') }}</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                    @error('form.city_id')
                        <small>{{ $message }}</small>
                    @enderror
                </fieldset>
                <label class="profile-form__wide">{{ __('Email') }}<input type="email" value="{{ $user->email }}"
                        disabled></label>
                <div class="profile-form__wide profile-form__actions"><button class="profile-button"
                        type="submit">{{ __('Сохранить данные') }}</button></div>
            </form>
        </section>
        <section class="profile-card profile-addresses">
            <div class="profile-card__heading">
                <div><span class="profile-eyebrow">{{ __('Доставка') }}</span>
                    <h2>{{ __('Адреса') }}</h2>
                </div>
            </div>
            <p class="profile-muted">
                {{ __('Адреса сохраняются при оформлении заказа. Можно сохранить до 10 адресов.') }}</p>
            @forelse ($addresses as $address)
                <article wire:key="address-{{ $address->id }}"
                    class="profile-address {{ $address->is_default ? 'profile-address--active' : '' }}">
                    <div>
                        <strong>{{ $address->city->name }}{{ $address->street ? ', ' . $address->street : '' }}{{ $address->house ? ', ' . __('дом') . ' ' . $address->house : '' }}{{ $address->building ? ', ' . __('корпус') . ' ' . $address->building : '' }}</strong>
                        <p>{{ collect([$address->apartment ? __('кв.') . ' ' . $address->apartment : null, $address->entrance ? __('подъезд') . ' ' . $address->entrance : null, $address->floor ? __('этаж') . ' ' . $address->floor : null, $address->intercom ? __('домофон') . ' ' . $address->intercom : null])->filter()->join(', ') }}
                        </p>
                        @if ($address->is_default)
                            <span class="profile-badge">{{ __('Активный адрес') }}</span>
                        @endif
                    </div>
                    <div class="profile-address__actions">
                        @unless ($address->is_default)
                            <button type="button"
                                wire:click="setDefaultAddress({{ $address->id }})">{{ __('Выбрать') }}</button>
                        @endunless
                        <button type="button" wire:click="deleteAddress({{ $address->id }})"
                            wire:confirm="{{ __('Удалить этот адрес?') }}">{{ __('Удалить') }}</button>
                    </div>
                </article>
            @empty <p class="profile-muted">{{ __('Сохранённых адресов пока нет.') }}</p>
            @endforelse

        </section>
        <section class="profile-card profile-addresses">
            <div class="profile-card__heading">
                <div><span class="profile-eyebrow">{{ __('СДЭК') }}</span>
                    <h2>{{ __('Пункты получения') }}</h2>
                </div>
            </div>
            <p class="profile-muted">
                {{ __('Пункты сохраняются при оформлении заказа. Можно сохранить до 10 пунктов.') }}</p>
            @forelse ($deliveryPoints as $point)
                <article wire:key="point-{{ $point->id }}"
                    class="profile-address {{ $point->is_default ? 'profile-address--active' : '' }}">
                    <div><strong>{{ $point->city?->name }}, {{ $point->address }}</strong>
                        <p>{{ $point->code }} · {{ $point->work_time }}</p>
                        @if ($point->is_default)
                            <span class="profile-badge">{{ __('Активный пункт') }}</span>
                        @endif
                    </div>
                    <div class="profile-address__actions">
                        @unless ($point->is_default)
                            <button type="button"
                                wire:click="setDefaultDeliveryPoint({{ $point->id }})">{{ __('Выбрать') }}</button>
                        @endunless
                        <button type="button" wire:click="deleteDeliveryPoint({{ $point->id }})"
                            wire:confirm="{{ __('Удалить этот пункт?') }}">{{ __('Удалить') }}</button>
                    </div>
                </article>
            @empty
                <p class="profile-muted">{{ __('Сохранённых пунктов пока нет.') }}</p>
            @endforelse
        </section>
        <section class="profile-card profile-telegram">
            <div class="profile-telegram__content">
                <span class="profile-telegram__logo" role="img" aria-label="{{ __('Логотип Telegram') }}"></span>
                <div class="profile-telegram__copy">
                    <h2>{{ __('Уведомления в Telegram') }}</h2>
                    <p>{{ __('Получайте сообщения о заказах в Telegram.') }}</p>
                </div>
            </div>
            @if ($user->telegram_chat_id && $user->telegram_notifications_enabled)
                <div class="profile-telegram__controls">
                    <span
                        class="profile-telegram__status profile-telegram__status--connected">{{ __('Подключен') }}</span>
                    <button type="button" class="profile-text-button profile-telegram__disconnect"
                        wire:click="disconnectTelegram">{{ __('Отключить') }}</button>
                </div>
            @elseif ($telegramBotConfigured)
                <div class="profile-telegram__controls">
                    <span class="profile-telegram__status">{{ __('Не подключен') }}</span>
                    <button type="button" class="profile-button" x-data="telegramConnect()" x-bind:disabled="connecting"
                        x-on:click="connect()">{{ __('Подключить') }}</button>
                </div>
            @else
                <div class="profile-telegram__controls">
                    <span class="profile-telegram__status">{{ __('Пока недоступно') }}</span>
                </div>
            @endif
            @error('telegram')
                <small>{{ $message }}</small>
            @enderror
        </section>
    </div>
</div>
