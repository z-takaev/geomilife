@extends('layouts.base')

@section('title', __('Вопросы и ответы'))
@section('description', __('Ответы на частые вопросы о продукции GeoMiLife, оформлении заказа, оплате и доставке.'))

@section('content')
    @php
        $faqGroups = [
            [
                'id' => 'orders',
                'title' => __('Заказ и оплата'),
                'items' => [
                    [
                        'question' => __('Как оформить заказ?'),
                        'answer' => __(
                            'Добавьте нужные товары в корзину, перейдите к оформлению заказа и заполните контактные данные. После подтверждения мы сообщим вам детали доставки.',
                        ),
                    ],
                    [
                        'question' => __('Какие способы оплаты доступны?'),
                        'answer' => __(
                            'Вы сможете выбрать удобный способ оплаты при оформлении заказа. Доступные варианты будут показаны на соответствующем шаге.',
                        ),
                    ],
                    [
                        'question' => __('Можно ли изменить заказ после оформления?'),
                        'answer' => __(
                            'Если заказ ещё не передан в доставку, свяжитесь с нами — мы постараемся изменить состав или контактные данные.',
                        ),
                    ],
                    [
                        'question' => __('Как узнать статус заказа?'),
                        'answer' => __(
                            'После оформления мы отправим подтверждение и будем сообщать об изменениях статуса по указанным контактным данным.',
                        ),
                    ],
                ],
            ],
            [
                'id' => 'delivery',
                'title' => __('Доставка'),
                'items' => [
                    [
                        'question' => __('В какие города вы доставляете?'),
                        'answer' => __(
                            'Мы доставляем заказы по России. Точная доступность и сроки зависят от населённого пункта и выбранного способа доставки.',
                        ),
                    ],
                    [
                        'question' => __('Сколько времени занимает доставка?'),
                        'answer' => __(
                            'Срок доставки рассчитывается при оформлении заказа и зависит от региона. После отправки заказа вы получите информацию для отслеживания.',
                        ),
                    ],
                    [
                        'question' => __('Как упаковывается продукция?'),
                        'answer' => __(
                            'Мы подбираем упаковку с учётом особенностей каждого продукта, чтобы сохранить качество товара во время перевозки.',
                        ),
                    ],
                    [
                        'question' => __('Что делать, если товар повредился при доставке?'),
                        'answer' => __(
                            'Сфотографируйте упаковку и товар сразу после получения, затем свяжитесь с нами. Мы рассмотрим обращение и предложим решение.',
                        ),
                    ],
                ],
            ],
            [
                'id' => 'products',
                'title' => __('Продукция'),
                'items' => [
                    [
                        'question' => __('Где производится продукция GeoMiLife?'),
                        'answer' => __(
                            'Основу ассортимента составляют натуральные продукты из Северной Осетии, изготовленные с вниманием к качеству сырья и рецептуре.',
                        ),
                    ],
                    [
                        'question' => __('Где посмотреть состав продукта?'),
                        'answer' => __(
                            'Состав, пищевая ценность и рекомендации по хранению будут указаны в карточке каждого товара.',
                        ),
                    ],
                    [
                        'question' => __('Как правильно хранить продукцию?'),
                        'answer' => __(
                            'Условия и сроки хранения зависят от конкретного товара. Следуйте информации на упаковке и в карточке продукта.',
                        ),
                    ],
                    [
                        'question' => __('Как выбрать подходящий продукт?'),
                        'answer' => __(
                            'Изучите описание и состав в карточке товара. Если останутся вопросы, отправьте их через форму на этой странице.',
                        ),
                    ],
                ],
            ],
        ];
    @endphp

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
                        @foreach ($faqGroups as $group)
                            <li class="faq-item">
                                <h2 class="faq_title h4 text-primary">{{ $group['title'] }}</h2>

                                <div class="faq_wrap" id="faq-{{ $group['id'] }}">
                                    @foreach ($group['items'] as $item)
                                        @php
                                            $collapseId = "faq-{$group['id']}-{$loop->iteration}";
                                            $isOpen = $loop->parent->first && $loop->first;
                                        @endphp

                                        <div class="accordion-faq">
                                            <button type="button"
                                                class="accordion-action{{ $isOpen ? '' : ' collapsed' }} w-100 text-start"
                                                data-bs-target="#{{ $collapseId }}" data-bs-toggle="collapse"
                                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                                aria-controls="{{ $collapseId }}">
                                                <span class="text lh-28">{{ $item['question'] }}</span>
                                                <span class="ic-ar" aria-hidden="true">
                                                    <i class="icon-caret-down"></i>
                                                </span>
                                            </button>

                                            <div id="{{ $collapseId }}" class="collapse{{ $isOpen ? ' show' : '' }}"
                                                data-bs-parent="#faq-{{ $group['id'] }}">
                                                <div class="accordion-body">
                                                    <p>{{ $item['answer'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
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
