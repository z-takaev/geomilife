<!-- Footer -->
<footer class="tf-footer">
    <div class="footer-inner-wrap">
        <div class="container">
            <div class="footer-inner">
                <div id="about" class="inner-left">
                    <a href="{{ url('/') }}" class="logo-site">
                        <img loading="lazy" width="207" height="48"
                            src="{{ asset('assets/images/logo/logo-white.svg') }}" alt="">
                    </a>
                    <h5 class="title text-white font-main">
                        {{ __('Натуральные продукты GeoMiLife') }}
                    </h5>
                    <p class="sub-title text-caption-01 text-white">
                        {{ __('Сыродавленные масла, урбеч и полезные продукты собственного производства с доставкой по России.') }}
                    </p>
                </div>
                <div class="inner-center">
                    <div class="footer-col-block">
                        <p class="footer-heading footer-heading-mobile text-caption-02">
                            {{ __('Разделы') }}
                        </p>
                        <div class="tf-collapse-content">
                            <ul class="footer-menu-list">
                                <li><a href="{{ url('/') }}" class="link">{{ __('Главная') }}</a></li>
                                <li><a href="{{ url('/#catalog') }}" class="link">{{ __('Каталог') }}</a></li>
                                <li><a href="{{ url('/#advantages') }}" class="link">{{ __('Преимущества') }}</a>
                                </li>
                                <li><a href="{{ url('/#promotions') }}" class="link">{{ __('Акции') }}</a></li>
                                <li><a href="{{ url('/#blog') }}" class="link">{{ __('Полезные статьи') }}</a></li>
                                <li><a href="{{ url('/#contacts') }}" class="link">{{ __('Контакты') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="footer-col-block">
                        <p class="footer-heading footer-heading-mobile text-caption-02">
                            {{ __('Информация') }}
                        </p>
                        <div class="tf-collapse-content">
                            <ul class="footer-menu-list">
                                <li><a href="{{ url('/#about') }}" class="link">{{ __('О компании') }}</a></li>
                                <li><a href="{{ url('/#advantages') }}"
                                        class="link">{{ __('Доставка и оплата') }}</a></li>
                                <li><a href="{{ url('/#advantages') }}"
                                        class="link">{{ __('Гарантия качества') }}</a></li>
                                <li><a href="{{ url('/#contacts') }}" class="link">{{ __('Связаться с нами') }}</a>
                                </li>
                                <li><a href="tel:+79888731020" class="link">{{ __('Позвонить нам') }}</a></li>
                                <li><a href="mailto:geomilife@bk.ru" class="link">{{ __('Написать на почту') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="inner-right">
                    <div id="contacts" class="footer-col-block">
                        <p class="footer-heading footer-heading-mobile text-caption-02">
                            {{ __('Контакты') }}
                        </p>
                        <div class="tf-collapse-content">
                            <div class="footer-info">
                                <span class="infor-address font-main text-white">
                                    {{ __('ул. Тамаева 35, Владикавказ') }}
                                </span>
                                <a href="tel:+79888731020"
                                    class="link infor-phone font-main">{{ __('+7-9888-731-020') }}</a>
                                <a href="mailto:geomilife@bk.ru"
                                    class="link infor-email font-main text-decoration-underline">{{ __('geomilife@bk.ru') }}</a>
                                <ul class="social-list">
                                    <li>
                                        <a href="#" class="link" aria-label="{{ __('Instagram') }}">
                                            <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="link" aria-label="{{ __('YouTube') }}">
                                            <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="link" aria-label="{{ __('VK') }}">
                                            <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="link" aria-label="{{ __('Telegram') }}">
                                            <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="link" aria-label="{{ __('WhatsApp') }}">
                                            <i class="social-icon social-icon--whatsapp" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="br-line ver-abs top-0 bg-white_10"></div>
            <div class="footer-copyright">
                <p class="text-caption-01 fw-medium text-white">
                    {{ __('«Geomilife» 2026 - производитель масла') }}
                </p>
                <a class="footer-developer" href="#" aria-label="{{ __('Разработано в: Web-Chip') }}">
                    <img loading="lazy" width="20" height="20"
                        src="{{ asset('assets/images/logo/web-chip.webp') }}" alt="{{ __('Логотип Web-Chip') }}">
                    <span class="footer-developer-tooltip" role="tooltip">{{ __('Разработано в: Web-Chip') }}</span>
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- /Footer -->
