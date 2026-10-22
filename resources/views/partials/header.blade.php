<div class="tf-topbar style-2">
    <div class="container">
        <div class="topbar-inner">
            <div class="topbar-left ">
                <div class="city-selector">
                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                    <a href="#citySelection" class="city-selector__link" data-bs-toggle="modal" role="button"
                        aria-controls="citySelection" aria-haspopup="dialog">
                        {{ __('Выберите город') }}
                    </a>
                </div>
            </div>
            <div class="topbar-center">
                <ul class="tf-list">
                    <li>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', settings()?->phone) }}" class="info link-primary">
                            <i class="icon icon-phone-call"></i>
                            {{ __(settings()?->phone) }}
                        </a>
                    </li>
                    <li class="br-line type-vertical d-none d-md-block"></li>
                    <li class="d-none d-md-block">
                        <a href="mailto:{{ settings()?->email }}" class="info link-primary">
                            <i class="icon icon-EnvelopeSimple"></i>
                            {{ __(settings()?->email) }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="topbar-right d-none d-md-flex">
                <ul class="topbar-social-list social-links">
                    @if (settings()?->instagram_url)
                        <li>
                            <a href="{{ settings()?->instagram_url }}" class="social-links__link"
                                aria-label="{{ __('Instagram') }}" target="_blank" rel="noopener noreferrer">
                                <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                    @if (settings()?->youtube_url)
                        <li>
                            <a href="{{ settings()?->youtube_url }}" class="social-links__link"
                                aria-label="{{ __('Youtube') }}" target="_blank" rel="noopener noreferrer">
                                <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                    @if (settings()?->vk_url)
                        <li>
                            <a href="{{ settings()?->vk_url }}" class="social-links__link"
                                aria-label="{{ __('ВКонтакте') }}" target="_blank" rel="noopener noreferrer">
                                <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                    @if (settings()?->telegram_url)
                        <li>
                            <a href="{{ settings()?->telegram_url }}" class="social-links__link"
                                aria-label="{{ __('Telegram') }}" target="_blank" rel="noopener noreferrer">
                                <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                    @if (settings()?->whatsapp_url)
                        <li>
                            <a href="{{ settings()?->whatsapp_url }}" class="social-links__link"
                                aria-label="{{ __('Ватсап') }}" target="_blank" rel="noopener noreferrer">
                                <i class="social-icon social-icon--whatsapp" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<header class="tf-header style-2">
    <div class="container">
        <div class="header-inner">
            <div class="box-btn-menu header-mobile-actions d-xl-none">
                <a href="#mobileMenu" data-bs-toggle="offcanvas" class="btn-mobile-menu">
                    <span></span>
                </a>
                <a href="#canvasSearch" data-bs-toggle="offcanvas" class="nav-icon-item text-primary link"
                    aria-label="{{ __('Открыть поиск') }}">
                    <i class="icon icon-magnifying-glass"></i>
                </a>
            </div>
            <div class="header-left">
                <a href="{{ url('/') }}" class="logo-site">
                    <img loading="lazy" src="{{ asset('assets/images/logo/logo-default.svg') }}"
                        alt="{{ __('Логотип GeoMiLife') }}">
                </a>
                @include('partials.navigation')
            </div>
            <div class="header-right">
                <div class="box-nav-icon">
                    <ul class="nav-icon-list">
                        <li class="d-none d-xl-flex">
                            <a href="#canvasSearch" data-bs-toggle="offcanvas" class="nav-icon-item text-primary link"
                                aria-label="{{ __('Открыть поиск') }}">
                                <i class="icon icon-magnifying-glass"></i>
                            </a>
                        </li>
                        @guest
                            <li>
                                <a href="#login" data-bs-toggle="modal" class="nav-icon-item text-primary link"
                                    aria-label="{{ __('Войти') }}">
                                    <i class="icon icon-user"></i>
                                </a>
                            </li>
                        @endguest
                        @auth
                            <li class="dropdown account-menu">
                                <button id="accountMenu" type="button"
                                    class="nav-icon-item text-primary link account-menu__toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false" aria-label="{{ __('Открыть меню пользователя') }}">
                                    <i class="icon icon-user"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end account-menu__dropdown"
                                    aria-labelledby="accountMenu">
                                    <li>
                                        <a href="{{ route('profile.show') }}" class="dropdown-item account-menu__item">
                                            <i class="icon icon-UserCircle" aria-hidden="true"></i>
                                            <span>{{ __('Профиль') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('orders.index') }}" class="dropdown-item account-menu__item">
                                            <i class="icon icon-package" aria-hidden="true"></i>
                                            <span>{{ __('Заказы') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item account-menu__item">
                                            <i class="icon icon-arrow-right" aria-hidden="true"></i>
                                            <span>{{ __('Выход') }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </li>
                        @endauth
                        <li>
                            <a href="#wishList" data-bs-toggle="offcanvas" class="nav-icon-item text-primary link">
                                <i class="icon icon-heart-stroke"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#shoppingCart" data-bs-toggle="offcanvas"
                                class="nav-icon-item text-primary link has-num">
                                <i class="icon icon-ShoppingCartSimple"></i>
                                <span class="number-count text-caption-02">
                                    3
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
