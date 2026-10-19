@extends('layouts.base')

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
                        <livewire:question-form />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
