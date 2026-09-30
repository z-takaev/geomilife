<!-- Top Bar-->
<div class="tf-topbar style-2 d-none d-md-block">
    <div class="container">
        <div class="topbar-inner">
            <div class="topbar-left ">
                <div class="city-selector">
                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                    <label class="visually-hidden" for="city">Выберите город</label>
                    <select id="city" class="tf-dropdown-select style-default type-cities"
                        aria-label="Выберите город">
                        <option value="" selected disabled>Выберите город</option>
                        <option value="moscow">Москва</option>
                        <option value="saint-petersburg">Санкт-Петербург</option>
                        <option value="kazan">Казань</option>
                    </select>
                </div>
            </div>
            <div class="topbar-right d-none d-sm-flex">
                <ul class="tf-list">
                    <li class="d-none d-md-flex">
                        <a href="tel:+79888731020" class="info link-primary">
                            <i class="icon icon-phone-call fs-24"></i>
                            +7-9888-731-020
                        </a>
                    </li>
                    <li class="br-line type-vertical d-none d-md-flex"></li>
                    <li>
                        <a href="mailto:geomilife@bk.ru" class="info link-primary">
                            <i class="icon icon-EnvelopeSimple fs-24"></i>
                            geomilife@bk.ru
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
                        src="{{ asset('assets/images/logo/logo-default.svg') }}" alt="Logo">
                </a>
                <nav class="box-navigation d-none d-xl-block">
                    <ul class="box-nav-menu">
                        <li class="menu-item menu-item-catalog">
                            <a href="#" class="item-link catalog-link lh-28">
                                <span class="text">
                                    <span class="catalog-menu-icon" aria-hidden="true">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </span>
                                    Каталог
                                </span>
                            </a>
                            <div class="sub-menu">
                                <ul class="sub-menu_list">
                                    <li><a href="{{ url('/') }}" class="sub-menu_link">Homepage 1</a></li>
                                    <li><a href="homepage-2.html" class="sub-menu_link">Homepage 2</a></li>
                                    <li><a href="homepage-3.html" class="sub-menu_link">Homepage 3</a></li>
                                    <li><a href="{{ url('/') }}" class="sub-menu_link">Homepage 4</a></li>
                                    <li class="has-menu_lv2">
                                        <a href="#" class="sub-menu_link">
                                            <span class="text">
                                                Our Services
                                            </span>
                                            <i class="icon icon-caret-right"></i>
                                        </a>
                                        <ul class="sub-menu-lv2">
                                            <li><a href="our-service-1.html" class="sub-menu_link">Our Services
                                                    1</a></li>
                                            <li><a href="our-service-2.html" class="sub-menu_link">Our Services
                                                    2</a></li>
                                            <li><a href="our-service-3.html" class="sub-menu_link">Our Services
                                                    3</a></li>
                                            <li><a href="service-detail.html" class="sub-menu_link">Services
                                                    Details</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="item-link lh-28">
                                <span class="text">
                                    Акции
                                </span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="item-link lh-28">
                                <span class="text">
                                    Доставка и оплата
                                </span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="item-link lh-28">
                                <span class="text">
                                    О компании
                                </span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="item-link lh-28">
                                <span class="text">
                                    Контакты
                                </span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="item-link lh-28">
                                <span class="text">
                                    Еще
                                    <i class="icon icon-caret-down"></i>
                                </span>
                            </a>
                            <div class="sub-menu">
                                <ul class="sub-menu_list">
                                    <li><a href="about-us.html" class="sub-menu_link">About Us</a></li>
                                    <li><a href="pricing-plan.html" class="sub-menu_link">Pricing Plans</a></li>
                                    <li><a href="contact.html" class="sub-menu_link">Contact Us</a></li>
                                    <li class="has-menu_lv2">
                                        <a href="#" class="sub-menu_link">
                                            <span class="text">
                                                Our Services
                                            </span>
                                            <i class="icon icon-caret-right"></i>
                                        </a>
                                        <ul class="sub-menu-lv2">
                                            <li><a href="our-service-1.html" class="sub-menu_link">Our Services
                                                    1</a></li>
                                            <li><a href="our-service-2.html" class="sub-menu_link">Our Services
                                                    2</a></li>
                                            <li><a href="our-service-3.html" class="sub-menu_link">Our Services
                                                    3</a></li>
                                            <li><a href="service-detail.html" class="sub-menu_link">Services
                                                    Details</a></li>
                                        </ul>
                                    </li>
                                    <li><a href="faq.html" class="sub-menu_link">FAQs</a></li>
                                    <li><a href="privacy-policy.html" class="sub-menu_link">Privacy Policy</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </nav>
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
