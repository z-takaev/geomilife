<footer class="tf-footer">
    <div class="footer-inner-wrap">
        <div class="container">
            <div class="footer-inner">
                <div id="about" class="inner-left">
                    <a href="{{ url('/') }}" class="logo-site">
                        <img loading="lazy" src="{{ asset('assets/images/logo/logo-white.svg') }}" alt="">
                    </a>
                    @if (settings()?->footer_title)
                        <h5 class="title text-white font-main">
                            {{ __(settings()?->footer_title) }}
                        </h5>
                    @endif
                    @if (settings()?->footer_description)
                        <p class="sub-title text-caption-01 text-white">
                            {{ __(settings()?->footer_description) }}
                        </p>
                    @endif
                </div>

                @include('layouts.partials.navigation.footer')

                <div class="inner-right">
                    <div id="contacts" class="footer-col-block open">
                        <p class="footer-heading footer-heading-mobile text-caption-02">
                            {{ __('Контакты') }}
                        </p>
                        <div class="tf-collapse-content">
                            <div class="footer-info">
                                <span class="infor-address font-main text-white">
                                    {{ __(settings()?->address) }}
                                </span>
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', settings()?->phone) }}"
                                    class="link infor-phone font-main">{{ __(settings()?->phone) }}</a>
                                <a href="mailto:{{ settings()?->email }}"
                                    class="link infor-email font-main text-decoration-underline">{{ __(settings()?->email) }}</a>
                                <ul class="social-list">
                                    @if (settings()?->instagram_url)
                                        <li>
                                            <a href="{{ settings()?->instagram_url }}" class="link"
                                                aria-label="{{ __('Instagram') }}">
                                                <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (settings()?->youtube_url)
                                        <li>
                                            <a href="{{ settings()?->youtube_url }}" class="link"
                                                aria-label="{{ __('YouTube') }}">
                                                <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (settings()?->vk_url)
                                        <li>
                                            <a href="{{ settings()?->vk_url }}" class="link"
                                                aria-label="{{ __('VK') }}">
                                                <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (settings()?->telegram_url)
                                        <li>
                                            <a href="{{ settings()?->telegram_url }}" class="link"
                                                aria-label="{{ __('Telegram') }}">
                                                <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
                                    @if (settings()?->whatsapp_url)
                                        <li>
                                            <a href="{{ settings()?->whatsapp_url }}" class="link"
                                                aria-label="{{ __('WhatsApp') }}">
                                                <i class="social-icon social-icon--whatsapp" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endif
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
            <div class="footer-bottom-content">
                <div class="footer-copyright">
                    <p class="text-caption-01 fw-medium text-white">
                        {{ __('«Geomilife» 2026 - производитель масла') }}
                    </p>
                    <a class="footer-developer" href="https://web-chip.ru" target="_blank"
                        aria-label="{{ __('Разработано в: Web-Chip') }}">
                        <img loading="lazy" src="{{ asset('assets/images/logo/web-chip.webp') }}"
                            alt="{{ __('Логотип Web-Chip') }}">
                        <span class="footer-developer-tooltip"
                            role="tooltip">{{ __('Разработано в: Web-Chip') }}</span>
                    </a>
                </div>
                <ul class="footer-legal-links">
                    <li>
                        <a href="{{ route('page', 'terms') }}" class="link">
                            {{ __('Пользовательское соглашение') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page', 'policy') }}" class="link">
                            {{ __('Политика конфиденциальности') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
