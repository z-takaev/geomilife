<div class="modal modalCentered fade modal-city-selection" id="citySelection" tabindex="-1"
    aria-labelledby="citySelectionTitle" aria-hidden="true" wire:ignore.self x-data
    x-on:city-selected="bootstrap.Modal.getInstance($el)?.hide()">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="icon-close-popup city-selection__close" data-bs-dismiss="modal"
                aria-label="{{ __('Закрыть окно выбора города') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>

            <div class="modal-body city-selection">
                <h2 id="citySelectionTitle" class="city-selection__title">{{ __('Выберите город') }}</h2>
                <p class="city-selection__description">
                    {{ __('Начните вводить свой город и выберите его из списка.') }}
                </p>

                <div class="city-selection__search" role="search" x-data="citySearch()"
                    x-on:click.outside="suggestionsOpen = false" x-on:keydown.escape.stop="suggestionsOpen = false">
                    <label class="visually-hidden" for="citySearch">{{ __('Ваш город') }}</label>
                    <input id="citySearch" type="search" placeholder="{{ __('Ваш город') }}" autocomplete="off"
                        maxlength="255" wire:model.live.debounce.300ms="query" aria-controls="citySearchResults"
                        x-ref="citySearch" x-on:input="suggestionsOpen = true" x-on:focus="suggestionsOpen = true">
                    <span class="city-selection__loading" wire:loading.delay wire:target="query" role="status">
                        {{ __('Поиск…') }}
                    </span>
                    <button type="button" class="city-selection__clear" aria-label="{{ __('Очистить поиск города') }}"
                        x-cloak x-show="$wire.query !== ''" x-on:click="clear()">
                        <i class="icon icon-close" aria-hidden="true"></i>
                    </button>

                    @if ($query !== '')
                        <ul id="citySearchResults" class="city-selection__results" aria-live="polite"
                            x-show="suggestionsOpen && $wire.query.trim() !== ''">
                            @forelse ($searchResults as $city)
                                <li wire:key="city-result-{{ $city->getKey() }}">
                                    <button type="button" wire:click="select({{ $city->getKey() }})"
                                        wire:loading.attr="disabled" wire:target="query, select">
                                        <span>{{ __($city->name) }}</span>
                                    </button>
                                </li>
                            @empty
                                <li class="city-selection__status">{{ __('Города не найдены') }}</li>
                            @endforelse
                        </ul>
                    @endif
                </div>

                <p class="city-selection__hint">
                    {{ __('Выберите город из списка или воспользуйтесь поиском.') }}
                </p>

                @if ($featuredCities->isNotEmpty())
                    <ul class="city-selection__featured">
                        @foreach ($featuredCities as $city)
                            <li wire:key="city-featured-{{ $city->getKey() }}">
                                <button type="button" wire:click="select({{ $city->getKey() }})"
                                    wire:loading.attr="disabled" wire:target="select">
                                    {{ __($city->name) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="city-selection__cities">
                    @foreach ($cityColumns as $column)
                        <div class="city-selection__column" wire:key="city-column-{{ $loop->index }}">
                            @foreach ($column as $letter => $cities)
                                <div class="city-selection__group" wire:key="city-group-{{ $letter }}">
                                    <span class="city-selection__letter">{{ __($letter) }}</span>
                                    <ul>
                                        @foreach ($cities as $city)
                                            <li wire:key="city-suggested-{{ $city->getKey() }}">
                                                <button type="button" wire:click="select({{ $city->getKey() }})"
                                                    wire:loading.attr="disabled" wire:target="select">
                                                    {{ __($city->name) }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
