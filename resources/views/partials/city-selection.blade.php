<div class="modal modalCentered fade modal-city-selection" id="citySelection" tabindex="-1"
    aria-labelledby="citySelectionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="icon-close-popup city-selection__close" data-bs-dismiss="modal"
                aria-label="{{ __('Закрыть окно выбора города') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>

            <div class="modal-body city-selection">
                <h2 id="citySelectionTitle" class="city-selection__title">{{ __('Выберите город') }}</h2>
                <p class="city-selection__description">
                    {{ __('Начните вводить свой город и выберите его из выпадающего списка.') }}
                </p>

                <form class="city-selection__search" role="search">
                    <label class="visually-hidden" for="citySearch">{{ __('Ваш город') }}</label>
                    <input id="citySearch" type="search" placeholder="{{ __('Ваш город') }}" autocomplete="off">
                </form>

                <p class="city-selection__hint">
                    {{ __('Ниже указаны крупные города для быстрого перехода.') }}
                </p>

                <div class="city-selection__cities">
                    <div class="city-selection__column">
                        <ul class="city-selection__featured">
                            <li><button type="button">{{ __('Владикавказ') }}</button></li>
                            <li><button type="button">{{ __('Санкт-Петербург') }}</button></li>
                            <li><button type="button">{{ __('Москва') }}</button></li>
                        </ul>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('А') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Абакан') }}</button></li>
                                <li><button type="button">{{ __('Архангельск') }}</button></li>
                                <li><button type="button">{{ __('Астрахань') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('Б') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Барнаул') }}</button></li>
                                <li><button type="button">{{ __('Белгород') }}</button></li>
                                <li><button type="button">{{ __('Брянск') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('В') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Владикавказ') }}</button></li>
                                <li><button type="button">{{ __('Волгоград') }}</button></li>
                                <li><button type="button">{{ __('Воронеж') }}</button></li>
                            </ul>
                        </div>
                    </div>

                    <div class="city-selection__column">
                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('К') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Казань') }}</button></li>
                                <li><button type="button">{{ __('Калуга') }}</button></li>
                                <li><button type="button">{{ __('Краснодар') }}</button></li>
                                <li><button type="button">{{ __('Красноярск') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('М') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Махачкала') }}</button></li>
                                <li><button type="button">{{ __('Москва') }}</button></li>
                                <li><button type="button">{{ __('Мурманск') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('Н') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Нальчик') }}</button></li>
                                <li><button type="button">{{ __('Нижний Новгород') }}</button></li>
                                <li><button type="button">{{ __('Новосибирск') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('О') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Омск') }}</button></li>
                                <li><button type="button">{{ __('Оренбург') }}</button></li>
                            </ul>
                        </div>
                    </div>

                    <div class="city-selection__column">
                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('Р') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Ростов-на-Дону') }}</button></li>
                                <li><button type="button">{{ __('Рязань') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('С') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Самара') }}</button></li>
                                <li><button type="button">{{ __('Саратов') }}</button></li>
                                <li><button type="button">{{ __('Сочи') }}</button></li>
                                <li><button type="button">{{ __('Ставрополь') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('Т') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Тверь') }}</button></li>
                                <li><button type="button">{{ __('Тула') }}</button></li>
                                <li><button type="button">{{ __('Тюмень') }}</button></li>
                            </ul>
                        </div>

                        <div class="city-selection__group">
                            <span class="city-selection__letter">{{ __('У') }}</span>
                            <ul>
                                <li><button type="button">{{ __('Уфа') }}</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
