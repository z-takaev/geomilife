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
                    {{ __('Каталог') }}
                </span>
            </a>
            <div class="sub-menu">
                <ul class="sub-menu_list">
                    <li><a href="{{ url('/') }}" class="sub-menu_link">{{ __('Homepage 1') }}</a></li>
                    <li><a href="homepage-2.html" class="sub-menu_link">{{ __('Homepage 2') }}</a></li>
                    <li><a href="homepage-3.html" class="sub-menu_link">{{ __('Homepage 3') }}</a></li>
                    <li><a href="{{ url('/') }}" class="sub-menu_link">{{ __('Homepage 4') }}</a></li>
                    <li class="has-menu_lv2">
                        <a href="#" class="sub-menu_link">
                            <span class="text">
                                {{ __('Our Services') }}
                            </span>
                            <i class="icon icon-caret-right"></i>
                        </a>
                        <ul class="sub-menu-lv2">
                            <li><a href="our-service-1.html" class="sub-menu_link">{{ __('Our Services 1') }}</a></li>
                            <li><a href="our-service-2.html" class="sub-menu_link">{{ __('Our Services 2') }}</a></li>
                            <li><a href="our-service-3.html" class="sub-menu_link">{{ __('Our Services 3') }}</a></li>
                            <li><a href="service-detail.html" class="sub-menu_link">{{ __('Services Details') }}</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </li>
        <li class="menu-item">
            <a href="#" class="item-link lh-28">
                <span class="text">
                    {{ __('Акции') }}
                </span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="item-link lh-28">
                <span class="text">
                    {{ __('Доставка и оплата') }}
                </span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="item-link lh-28">
                <span class="text">
                    {{ __('О компании') }}
                </span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="item-link lh-28">
                <span class="text">
                    {{ __('Контакты') }}
                </span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="item-link lh-28">
                <span class="text">
                    {{ __('Еще') }}
                    <i class="icon icon-caret-down"></i>
                </span>
            </a>
            <div class="sub-menu">
                <ul class="sub-menu_list">
                    <li><a href="about-us.html" class="sub-menu_link">{{ __('About Us') }}</a></li>
                    <li><a href="pricing-plan.html" class="sub-menu_link">{{ __('Pricing Plans') }}</a></li>
                    <li><a href="contact.html" class="sub-menu_link">{{ __('Contact Us') }}</a></li>
                    <li class="has-menu_lv2">
                        <a href="#" class="sub-menu_link">
                            <span class="text">
                                {{ __('Our Services') }}
                            </span>
                            <i class="icon icon-caret-right"></i>
                        </a>
                        <ul class="sub-menu-lv2">
                            <li><a href="our-service-1.html" class="sub-menu_link">{{ __('Our Services 1') }}</a></li>
                            <li><a href="our-service-2.html" class="sub-menu_link">{{ __('Our Services 2') }}</a></li>
                            <li><a href="our-service-3.html" class="sub-menu_link">{{ __('Our Services 3') }}</a></li>
                            <li><a href="service-detail.html" class="sub-menu_link">{{ __('Services Details') }}</a>
                            </li>
                        </ul>
                    </li>
                    <li><a href="faq.html" class="sub-menu_link">{{ __('FAQs') }}</a></li>
                    <li><a href="privacy-policy.html" class="sub-menu_link">{{ __('Privacy Policy') }}</a></li>
                </ul>
            </div>
        </li>
    </ul>
</nav>
