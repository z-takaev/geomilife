@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Контакты')" :items="[['label' => __('Контакты')]]" />

    <section class="contacts-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <div class="col-lg-8 contacts-info-column">
                    <div class="contacts-heading">
                        <h2 class="h3 text-primary font-sora">{{ __('Свяжитесь с нами') }}</h2>
                        <p>{{ __('Будем рады ответить на вопросы о продукции, заказах и сотрудничестве.') }}</p>
                    </div>

                    <div class="contacts-info-grid">
                        <div class="contacts-info-card">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-phone-call"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Телефон') }}</p>
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', settings()?->phone) }}"
                                    class="contacts-info-card__value link-primary">
                                    {{ __(settings()?->phone) }}
                                </a>
                            </div>
                        </div>

                        <div class="contacts-info-card">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-EnvelopeSimple"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Электронная почта') }}</p>
                                <a href="mailto:{{ settings()?->email }}" class="contacts-info-card__value link-primary">
                                    {{ __(settings()?->email) }}
                                </a>
                            </div>
                        </div>

                        @if (settings()?->instagram_url ||
                                settings()?->youtube_url ||
                                settings()?->vk_url ||
                                settings()?->telegram_url ||
                                settings()?->whatsapp_url)
                            <div class="contacts-info-card contacts-info-card--social">
                                <div>
                                    <p class="contacts-info-card__label">{{ __('Социальные сети') }}</p>
                                    <ul class="social-links">
                                        @if (settings()?->instagram_url)
                                            <li>
                                                <a href="{{ settings()?->instagram_url }}" class="social-links__link"
                                                    aria-label="{{ __('Instagram') }}" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                                                </a>
                                            </li>
                                        @endif
                                        @if (settings()?->youtube_url)
                                            <li>
                                                <a href="{{ settings()?->youtube_url }}" class="social-links__link"
                                                    aria-label="{{ __('YouTube') }}" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                                                </a>
                                            </li>
                                        @endif
                                        @if (settings()?->vk_url)
                                            <li>
                                                <a href="{{ settings()?->vk_url }}" class="social-links__link"
                                                    aria-label="{{ __('VK') }}" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                                                </a>
                                            </li>
                                        @endif
                                        @if (settings()?->telegram_url)
                                            <li>
                                                <a href="{{ settings()?->telegram_url }}" class="social-links__link"
                                                    aria-label="{{ __('Telegram') }}" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                                                </a>
                                            </li>
                                        @endif
                                        @if (settings()?->whatsapp_url)
                                            <li>
                                                <a href="{{ settings()?->whatsapp_url }}" class="social-links__link"
                                                    aria-label="{{ __('WhatsApp') }}" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <i class="social-icon social-icon--whatsapp" aria-hidden="true"></i>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="contacts-info-card contacts-info-card--wide">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-MapPin"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Адрес') }}</p>
                                <p class="contacts-info-card__value">{{ __(settings()?->address) }}</p>
                            </div>
                        </div>

                        <div class="contacts-info-card contacts-info-card--wide">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-Clock"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Режим работы') }}</p>
                                <p class="contacts-info-card__value">{!! nl2br(e(__(settings()?->working_hours))) !!}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="col-right_sidebar sticky-top">
                        <livewire:question-form />
                    </div>
                </div>

                @if (filled(settings()?->yandex_map_script))
                    <div class="col-12">
                        <div class="contacts-map">
                            {!! settings()?->yandex_map_script !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
