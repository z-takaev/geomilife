@extends('layouts.base')

@section('title', __('Вопросы и ответы'))
@section('description', __('Ответы на частые вопросы о продукции GeoMiLife, оформлении заказа, оплате и доставке.'))

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Вопросы и ответы')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Вопросы и ответы') }}</h1>
        </div>
    </section>

    <section class="section-faq flat-spacing">
        <div class="container">
            <div class="row gy-30">
                <div class="col-lg-8">
                    <ul class="faq-list">
                        <li class="faq-item">
                            <div class="faq_wrap" id="faq-accordion">
                                @foreach ($faqs as $faq)
                                    @php
                                        $collapseId = "faq-{$faq->getKey()}";
                                    @endphp

                                    <div class="accordion-faq">
                                        <button type="button"
                                            class="accordion-action{{ $loop->first ? '' : ' collapsed' }} w-100 text-start"
                                            data-bs-target="#{{ $collapseId }}" data-bs-toggle="collapse"
                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                            aria-controls="{{ $collapseId }}">
                                            <span class="text lh-28">{{ $faq->question }}</span>
                                            <span class="ic-ar" aria-hidden="true">
                                                <i class="icon-caret-down"></i>
                                            </span>
                                        </button>

                                        <div id="{{ $collapseId }}" class="collapse{{ $loop->first ? ' show' : '' }}"
                                            data-bs-parent="#faq-accordion">
                                            <div class="accordion-body">
                                                {{ $faq->answer }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <div class="col-right_sidebar sticky-top">
                        <form class="form-get faq-question-form">
                            <h2 class="form-title h5 text-white text-center">
                                {{ __('Задать вопрос') }}
                            </h2>

                            <div class="form-content">
                                <fieldset class="tf-field">
                                    <label for="faq-name" class="tf-lable text-primary">
                                        {{ __('Имя') }}<span class="text-secondary">*</span>
                                    </label>
                                    <input type="text" id="faq-name" placeholder="{{ __('Введите ваше имя') }}"
                                        autocomplete="name">
                                </fieldset>

                                <fieldset class="tf-field">
                                    <label for="faq-email" class="tf-lable text-primary">
                                        {{ __('Электронная почта') }}<span class="text-secondary">*</span>
                                    </label>
                                    <input type="email" id="faq-email"
                                        placeholder="{{ __('Введите электронную почту') }}" autocomplete="email">
                                </fieldset>

                                <fieldset class="tf-field">
                                    <label for="faq-message" class="tf-lable text-primary">
                                        {{ __('Ваш вопрос') }}<span class="text-secondary">*</span>
                                    </label>
                                    <textarea id="faq-message" placeholder="{{ __('Напишите ваш вопрос') }}"></textarea>
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
            </div>
        </div>
    </section>
@endsection
