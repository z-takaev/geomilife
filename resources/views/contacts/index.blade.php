@extends('layouts.base')

@section('title', __('Контакты'))
@section('description', __('Контактная информация GeoMiLife и форма обратной связи.'))

@section('content')
    @php
        $settings = settings();
        $address = $settings?->address ?: 'ул. Тамаева, 35, Владикавказ';
        $phone = $settings?->phone ?: '+7-9888-731-020';
        $email = $settings?->email ?: 'geomilife@bk.ru';
        $socialLinks = [
            ['label' => __('Instagram'), 'url' => $settings?->instagram_url, 'icon' => 'instagram'],
            ['label' => __('YouTube'), 'url' => $settings?->youtube_url, 'icon' => 'youtube'],
            ['label' => __('VK'), 'url' => $settings?->vk_url, 'icon' => 'vk'],
            ['label' => __('Telegram'), 'url' => $settings?->telegram_url, 'icon' => 'telegram'],
            ['label' => __('WhatsApp'), 'url' => $settings?->whatsapp_url, 'icon' => 'whatsapp'],
        ];
    @endphp

    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Контакты')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Контакты') }}</h1>
        </div>
    </section>

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
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"
                                    class="contacts-info-card__value link">
                                    {{ __($phone) }}
                                </a>
                            </div>
                        </div>

                        <div class="contacts-info-card">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-EnvelopeSimple"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Электронная почта') }}</p>
                                <a href="mailto:{{ $email }}" class="contacts-info-card__value link">
                                    {{ __($email) }}
                                </a>
                            </div>
                        </div>

                        <div class="contacts-info-card contacts-info-card--social">
                            <div>
                                <p class="contacts-info-card__label">{{ __('Социальные сети') }}</p>
                                <ul class="contacts-socials">
                                    @foreach ($socialLinks as $socialLink)
                                        <li>
                                            <a href="{{ $socialLink['url'] ?: '#' }}" class="contacts-socials__link"
                                                aria-label="{{ $socialLink['label'] }}" target="_blank"
                                                rel="noopener noreferrer">
                                                <i class="social-icon social-icon--{{ $socialLink['icon'] }}"
                                                    aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="contacts-info-card contacts-info-card--wide">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-MapPin"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Адрес') }}</p>
                                <p class="contacts-info-card__value">{{ __($address) }}</p>
                            </div>
                        </div>

                        <div class="contacts-info-card contacts-info-card--wide">
                            <span class="contacts-info-card__icon" aria-hidden="true">
                                <i class="icon icon-Clock"></i>
                            </span>
                            <div>
                                <p class="contacts-info-card__label">{{ __('Режим работы') }}</p>
                                <p class="contacts-info-card__value">{{ __('Пн–Пт: 09:00–18:00, Сб–Вс: выходной') }}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="col-right_sidebar sticky-top">
                        <form class="form-get faq-question-form">
                            <h2 class="form-title h5 text-white text-center">
                                {{ __('Задать вопрос') }}
                            </h2>

                            <div class="form-content">
                                <fieldset class="tf-field">
                                    <label for="contact-name" class="tf-lable text-primary">
                                        {{ __('Имя') }}<span class="text-secondary">*</span>
                                    </label>
                                    <input type="text" id="contact-name" placeholder="{{ __('Введите ваше имя') }}"
                                        autocomplete="name">
                                </fieldset>

                                <fieldset class="tf-field">
                                    <label for="contact-email" class="tf-lable text-primary">
                                        {{ __('Электронная почта') }}<span class="text-secondary">*</span>
                                    </label>
                                    <input type="email" id="contact-email"
                                        placeholder="{{ __('Введите электронную почту') }}" autocomplete="email">
                                </fieldset>

                                <fieldset class="tf-field">
                                    <label for="contact-message" class="tf-lable text-primary">
                                        {{ __('Ваш вопрос') }}<span class="text-secondary">*</span>
                                    </label>
                                    <textarea id="contact-message" placeholder="{{ __('Напишите ваш вопрос') }}"></textarea>
                                </fieldset>
                            </div>

                            <div class="btn-wrap">
                                <button type="button" class="tf-btn-text-icon fw-semibold animate-btn animate-dark">
                                    {{ __('Отправить вопрос') }}
                                    <span class="ic-wrap" aria-hidden="true">
                                        <i class="icon icon-ArrowUpRight"></i>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12">
                    <div class="contacts-map">
                        <iframe
                            src="https://yandex.ru/map-widget/v1/?ll=44.681900%2C43.036700&z=14&pt=44.681900%2C43.036700%2Cpm2rdm"
                            title="{{ __('Карта с расположением магазина GeoMiLife') }}" loading="lazy"
                            allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
