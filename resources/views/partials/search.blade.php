<!-- Open Search -->
<div class="offcanvas offcanvas-top offcanvas-search" id="canvasSearch">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-8 offset-sm-2">
                <div class="offcanvas-body">
                    <form action="#" class="form-search">
                        <fieldset>
                            <input type="text" placeholder="{{ __('Найти товар') }}" name="text" required="">
                        </fieldset>
                        <button type="submit" class="link-2">
                            <i class="icon icon-magnifying-glass"></i>
                        </button>
                    </form>
                    <div class="search-suggestions">
                        <div class="search-suggestions__group">
                            <p class="search-suggestions__title">{{ __('Категории') }}</p>
                            <ul class="search-suggestions__categories">
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/category/oils.png') }}"
                                            alt="{{ __('Масла') }}">
                                        <span>{{ __('Масла') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/category/urbech.png') }}"
                                            alt="{{ __('Урбеч') }}">
                                        <span>{{ __('Урбеч') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/category/honey-products.png') }}"
                                            alt="{{ __('Мёд') }}">
                                        <span>{{ __('Мёд') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/category/healthy-sweets.png') }}"
                                            alt="{{ __('Полезные сладости') }}">
                                        <span>{{ __('Полезные сладости') }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="search-suggestions__group">
                            <p class="search-suggestions__title">{{ __('Товары') }}</p>
                            <ul class="search-suggestions__products">
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/product/oil-product-card.png') }}"
                                            alt="{{ __('Сыродавленное масло чёрного тмина') }}">
                                        <span>{{ __('Сыродавленное масло чёрного тмина') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/product/walnut-urbech-card.png') }}"
                                            alt="{{ __('Урбеч из грецкого ореха') }}">
                                        <span>{{ __('Урбеч из грецкого ореха') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/product/mountain-honey-card.png') }}"
                                            alt="{{ __('Горный мёд') }}">
                                        <span>{{ __('Горный мёд') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/product/herbal-tea-card.png') }}"
                                            alt="{{ __('Травяной чай') }}">
                                        <span>{{ __('Травяной чай') }}</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="search-suggestion">
                                        <img src="{{ asset('assets/images/product/product-19.jpg') }}"
                                            alt="{{ __('Натуральная ореховая паста') }}">
                                        <span>{{ __('Натуральная ореховая паста') }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <span class="icon-close-popup icon-close" data-bs-dismiss="offcanvas">
                </span>
            </div>
        </div>
    </div>
</div>
<!-- End Open Search -->
