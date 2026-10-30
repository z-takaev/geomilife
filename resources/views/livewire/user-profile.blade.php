<div class="profile-page">
    <div class="profile-grid">
        <section class="profile-card">
            <div class="profile-card__heading">
                <div><span class="profile-eyebrow">{{ __('Личный кабинет') }}</span>
                    <h2>{{ __('Личные данные') }}</h2>
                </div>
            </div>
            <form wire:submit="saveProfile" class="profile-form">
                <label>{{ __('Имя') }}<input type="text" wire:model="first_name" autocomplete="given-name">
                    @error('first_name')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Фамилия') }}<input type="text" wire:model="last_name" autocomplete="family-name">
                    @error('last_name')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Дата рождения') }}<input type="date" wire:model="birth_date">
                    @error('birth_date')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
                <label>{{ __('Телефон') }}<input type="tel" wire:model="phone" autocomplete="tel"
                        placeholder="{{ __('+7 (___) ___-__-__') }}" inputmode="tel" maxlength="18" data-phone-mask>
                    @error('phone')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
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
                <button type="button" class="profile-button" wire:click="addAddress" data-bs-toggle="modal"
                    data-bs-target="#profileAddressModal">{{ __('Добавить адрес') }}</button>
            </div>
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
                        <button type="button" wire:click="editAddress({{ $address->id }})" data-bs-toggle="modal"
                            data-bs-target="#profileAddressModal">{{ __('Изменить') }}</button>
                        <button type="button" wire:click="deleteAddress({{ $address->id }})"
                            wire:confirm="{{ __('Удалить этот адрес?') }}">{{ __('Удалить') }}</button>
                    </div>
                </article>
            @empty <p class="profile-muted">{{ __('Сохранённых адресов пока нет.') }}</p>
            @endforelse

            <div class="modal modalCentered fade profile-address-modal" id="profileAddressModal" wire:ignore.self
                tabindex="-1" aria-labelledby="profileAddressModalTitle" aria-hidden="true" x-data
                x-on:address-saved.window="bootstrap.Modal.getOrCreateInstance($el).hide()">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="profileAddressModalTitle">
                                {{ $address_id ? __('Изменить адрес') : __('Новый адрес') }}</h2><button type="button"
                                class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Закрыть') }}"></button>
                        </div>
                        <div class="modal-body">
                            <form wire:submit="saveAddress" class="profile-form profile-form--address">
                                <label class="profile-form__wide">{{ __('Город') }}<select wire:model="city_id">
                                        <option value="">{{ __('Выберите город') }}</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}">
                                                {{ $city->name }}{{ $city->region ? ', ' . $city->region : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('city_id')
                                        <small>{{ $message }}</small>
                                    @enderror
                                </label>
                                <label>{{ __('Улица') }}<input type="text" wire:model="street">
                                    @error('street')
                                        <small>{{ $message }}</small>
                                    @enderror
                                </label>
                                <label>{{ __('Дом') }}<input type="text" wire:model="house">
                                    @error('house')
                                        <small>{{ $message }}</small>
                                    @enderror
                                </label>
                                <label>{{ __('Корпус') }}<input type="text"
                                        wire:model="building"></label><label>{{ __('Квартира') }}<input type="text"
                                        wire:model="apartment"></label>
                                <label>{{ __('Подъезд') }}<input type="text"
                                        wire:model="entrance"></label><label>{{ __('Этаж') }}<input type="text"
                                        wire:model="floor"></label><label>{{ __('Домофон') }}<input type="text"
                                        wire:model="intercom"></label>
                                <label class="profile-check profile-form__wide"><input type="checkbox"
                                        wire:model="is_default">{{ __('Сделать активным адресом и городом') }}</label>
                                <div class="profile-form__wide profile-form__actions"><button class="profile-button"
                                        type="submit">{{ __('Сохранить адрес') }}</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
                    <button type="button" class="profile-text-button"
                        wire:click="disconnectTelegram">{{ __('Отключить') }}</button>
                </div>
            @elseif ($telegramBotConfigured)
                <div class="profile-telegram__controls">
                    <span class="profile-telegram__status">{{ __('Не подключен') }}</span>
                    <button type="button" class="profile-button" x-data="telegramConnect()"
                        x-bind:disabled="connecting" x-on:click="connect()">{{ __('Подключить') }}</button>
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
