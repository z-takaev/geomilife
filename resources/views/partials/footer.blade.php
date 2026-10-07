<!-- Footer -->
@php
    $settings = settings();
    $footerTitle = $settings?->footer_title ?: 'Натуральные продукты GeoMiLife';
    $footerDescription =
        $settings?->footer_description ?:
        'Сыродавленные масла, урбеч и полезные продукты собственного производства с доставкой по России.';
    $address = $settings?->address ?: 'ул. Тамаева 35, Владикавказ';
    $phone = $settings?->phone ?: '+7-9888-731-020';
    $email = $settings?->email ?: 'geomilife@bk.ru';
@endphp

<footer class="tf-footer">
    <div class="footer-inner-wrap">
        <div class="container">
            <div class="footer-inner">
                <div id="about" class="inner-left">
                    <a href="{{ url('/') }}" class="logo-site">
                        <img loading="lazy" src="{{ asset('assets/images/logo/logo-white.svg') }}" alt="">
                    </a>
                    <h5 class="title text-white font-main">
                        {{ __($footerTitle) }}
                    </h5>
                    <p class="sub-title text-caption-01 text-white">
                        {{ __($footerDescription) }}
                    </p>
                </div>

                @include('partials.footer-navigation')

                <div class="inner-right">
                    <div id="contacts" class="footer-col-block open">
                        <p class="footer-heading footer-heading-mobile text-caption-02">
                            {{ __('Контакты') }}
                        </p>
                        <div class="tf-collapse-content">
                            <div class="footer-info">
                                <span class="infor-address font-main text-white">
                                    {{ __($address) }}
                                </span>
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"
                                    class="link infor-phone font-main">{{ __($phone) }}</a>
                                <a href="mailto:{{ $email }}"
                                    class="link infor-email font-main text-decoration-underline">{{ __($email) }}</a>
                                <ul class="social-list">
                                    <li>
                                        <a href="{{ $settings?->instagram_url ?: '#' }}" class="link"
                                            aria-label="{{ __('Instagram') }}">
                                            <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ $settings?->youtube_url ?: '#' }}" class="link"
                                            aria-label="{{ __('YouTube') }}">
                                            <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ $settings?->vk_url ?: '#' }}" class="link"
                                            aria-label="{{ __('VK') }}">
                                            <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ $settings?->telegram_url ?: '#' }}" class="link"
                                            aria-label="{{ __('Telegram') }}">
                                            <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ $settings?->whatsapp_url ?: '#' }}" class="link"
                                            aria-label="{{ __('WhatsApp') }}">
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
                    <img loading="lazy" src="{{ asset('assets/images/logo/web-chip.webp') }}"
                        alt="{{ __('Логотип Web-Chip') }}">
                    <span class="footer-developer-tooltip" role="tooltip">{{ __('Разработано в: Web-Chip') }}</span>
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- /Footer -->
