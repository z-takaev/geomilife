<!-- Top Bar-->
<div class="tf-topbar style-2 d-none d-md-block">
    <div class="container">
        <div class="topbar-inner">
            <div class="topbar-left ">
                <div class="city-selector">
                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                    <label class="visually-hidden" for="city">{{ __('Выберите город') }}</label>
                    <select id="city" class="tf-dropdown-select style-default type-cities"
                        aria-label="{{ __('Выберите город') }}">
                        <option value="" selected disabled>{{ __('Выберите город') }}</option>
                        <option value="moscow">{{ __('Москва') }}</option>
                        <option value="saint-petersburg">{{ __('Санкт-Петербург') }}</option>
                        <option value="kazan">{{ __('Казань') }}</option>
                    </select>
                </div>
            </div>
            <div class="topbar-right d-none d-sm-flex">
                <ul class="tf-list">
                    <li class="d-none d-md-flex">
                        <a href="tel:+79888731020" class="info link-primary">
                            <i class="icon icon-phone-call fs-24"></i>
                            {{ __('+7-9888-731-020') }}
                        </a>
                    </li>
                    <li class="br-line type-vertical d-none d-md-flex"></li>
                    <li>
                        <a href="mailto:geomilife@bk.ru" class="info link-primary">
                            <i class="icon icon-EnvelopeSimple fs-24"></i>
                            {{ __('geomilife@bk.ru') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- /Top Bar -->
<!-- Header -->
<header class="tf-header style-2">
    <div class="container">
        <div class="header-inner">
            <div class="box-btn-menu d-xl-none">
                <a href="#mobileMenu" data-bs-toggle="offcanvas" class="btn-mobile-menu">
                    <span></span>
                </a>
            </div>
            <div class="header-left">
                <a href="{{ url('/') }}" class="logo-site">
                    <img loading="lazy" width="207" height="48"
                        src="{{ asset('assets/images/logo/logo-default.svg') }}" alt="{{ __('Логотип GeoMiLife') }}">
                </a>
                @include('partials.navigation')
            </div>
            <div class="header-right">
                <div class="box-nav-icon">
                    <ul class="nav-icon-list">
                        <li>
                            <a href="#canvasSearch" data-bs-toggle="offcanvas"
                                class="nav-icon-item text-primary link d-none d-md-flex">
                                <i class="icon icon-magnifying-glass"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#login" data-bs-toggle="modal" class="nav-icon-item text-primary link">
                                <i class="icon icon-user"></i>
                            </a>
                        </li>
                        <li class="d-none d-sm-block">
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
<!-- /Header -->
