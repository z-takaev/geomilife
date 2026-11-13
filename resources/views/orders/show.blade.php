@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
@endpush

@section('content')
    <x-breadcrumbs :title="__('Детали заказа')" :breadcrumbs="$breadcrumbs" />
    <section class="flat-spacing orders-page">
        <div class="container">
            <div class="orders-page__heading">
                <div><span class="profile-eyebrow">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                    <h2>{{ __('Заказ №:number', ['number' => $order->number]) }}</h2>
                    <p>{{ __('Спасибо за ваш заказ') }}</p>
                </div><span
                    class="order-badge order-badge--{{ $order->status->getColor() }}">{{ $order->status->getLabel() }}</span>
            </div>
            @if (session('order_message'))
                <div class="checkout-info" role="status">{{ session('order_message') }}</div>
            @endif
            @if ($errors->any())
                <div class="checkout-errors" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @if ($presentation['isCancelling'])
                <div class="checkout-info" role="status">
                    {{ __('Отмена выполняется. Мы сообщим о завершении и результате возврата оплаты.') }}</div>
            @endif
            @if ($order->is_preorder)
                <div class="checkout-info">{{ __('Точные сроки доставки согласовываются отдельно с менеджером') }}</div>
            @endif
            <ol class="order-stages">
                @foreach ($presentation['stages'] as $stage)
                    <li class="{{ $stage['done'] ? 'is-done' : '' }}"><span aria-hidden="true"></span>{{ $stage['label'] }}
                    </li>
                @endforeach
            </ol>
            <div class="order-detail-grid">
                <div class="order-detail-main">
                    <section class="order-panel">
                        <h3>{{ __('Товары в заказе') }}</h3>
                        <div class="order-items">
                            @foreach ($order->items as $item)
                                <article class="order-item">
                                    <div><strong>{{ $item->product_name }}</strong>
                                        <p>{{ $item->variant_name }}</p>
                                        <small>{{ __('Артикул: :sku', ['sku' => $item->sku]) }}</small>
                                    </div>
                                    <div class="order-item__price"><span>{{ $item->price->format() }}
                                            {{ __('за шт.') }}</span>
                                        <strong>{{ __(':count × :price', ['count' => $item->quantity, 'price' => $item->total->format()]) }}</strong>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                    <section class="order-panel">
                        <h3>{{ __('Получатель') }}</h3>
                        <dl class="order-information">
                            <div>
                                <dt>{{ __('Имя') }}</dt>
                                <dd>{{ $order->customer_name }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Телефон') }}</dt>
                                <dd>{{ $order->customer_phone }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Email') }}</dt>
                                <dd>{{ $order->customer_email }}</dd>
                            </div>
                        </dl>
                    </section>
                    <section class="order-panel">
                        <h3>{{ $presentation['deliveryLabel'] }}</h3>
                        <p class="order-address">{{ $presentation['addressLabel'] }}</p>
                        @if ($presentation['workingHours'])
                            <p>{{ __('График: :hours', ['hours' => $presentation['workingHours']]) }}</p>
                        @endif
                        @if ($order->delivery_method->isPickup())
                            <p class="order-muted">
                                {{ __('Приходите после уведомления о готовности. Время получения согласует менеджер.') }}
                            </p>
                        @endif
                        @if ($order->delivery_method->isCourier())
                            <p>{{ !empty($order->delivery_details['delivery_to_door']) ? __('С доставкой до двери и подъёмом на этаж') : __('Без услуги доставки до двери') }}
                            </p>
                            <p class="order-muted">{{ __('Время доставки согласует менеджер.') }}</p>
                        @endif
                        @if ($order->delivery_method->isCdek())
                            @if ($order->delivery_address['code'] ?? null)
                                <p>{{ __('Пункт: :code', ['code' => $order->delivery_address['code']]) }}</p>
                            @endif
                            @if ($order->shipment?->cdek_number)
                                <div class="order-tracking">
                                    <span>{{ __('Трек-номер СДЭК') }}</span><strong>{{ $order->shipment->cdek_number }}</strong>
                            </div>@else<p class="order-muted">
                                    {{ __('Трек-номер появится после подготовки отправления.') }}</p>
                            @endif
                            @if (isset($order->delivery_details['period_min']))
                                <p>{{ __('Ориентировочный срок: :min–:max рабочих дней.', ['min' => $order->delivery_details['period_min'], 'max' => $order->delivery_details['period_max']]) }}
                                </p>
                            @endif
                        @endif
                    </section>
                    @if ($order->comment)
                        <section class="order-panel">
                            <h3>{{ __('Комментарий к заказу') }}</h3>
                            <p class="order-comment">{{ $order->comment }}</p>
                        </section>
                    @endif
                </div>
                <aside class="order-detail-summary order-panel">
                    <h3>{{ __('Оплата и итог') }}</h3><span
                        class="order-badge order-badge--{{ $order->payment_status->getColor() }}">{{ $order->payment_status->getLabel() }}</span>
                    <p>{{ $presentation['paymentLabel'] }}</p>
                    <dl class="checkout-summary__rows">
                        <div class="checkout-summary__row">
                            <dt>{{ __('Товары') }}</dt>
                            <dd>{{ ($order->subtotal ?? $order->total)->format() }}</dd>
                        </div>
                        <div class="checkout-summary__row">
                            <dt>{{ __('Доставка') }}</dt>
                            <dd>{{ $presentation['deliveryPriceLabel'] }}</dd>
                        </div>
                    </dl>
                    <div class="checkout-summary__total">
                        <span>{{ __('Итого') }}</span><strong>{{ $order->total->format() }}</strong>
                    </div>
                    @if ($order->status->isCancelled())
                        <p class="order-muted">{{ __('Заказ отменён.') }}</p>
                    @endif
                    @if ($order->payment_status->isRefundPending())
                        <p class="order-muted">
                            {{ __('Полный возврат оплаты выполняется. Срок зачисления зависит от банка.') }}</p>
                    @endif
                    @if ($order->payment_status->isRefunded())
                        <p class="order-muted">{{ __('Банк подтвердил полный возврат оплаты.') }}</p>
                    @endif
                    <form method="post" action="{{ route('orders.repeat', $order) }}" x-data
                        @submit="if (!confirm(@js(__('Текущая корзина будет заменена товарами из этого заказа. Продолжить?')))) $event.preventDefault()">
                        @csrf
                        <button type="submit" class="profile-button w-100">{{ __('Повторить заказ') }}</button>
                    </form>
                    @if ($presentation['canCancel'])
                        <form method="post" action="{{ $presentation['cancelUrl'] }}" class="order-cancel-form" x-data
                            @submit="if (!confirm(@js(__('Отменить заказ? Онлайн-оплата будет возвращена полностью.')))) $event.preventDefault()">@csrf<input
                                type="hidden" name="reason" value="{{ __('Отмена покупателем') }}"><button type="submit"
                                class="order-text-button">{{ __('Отменить заказ') }}</button></form>
                    @endif
                </aside>
            </div>
            @auth<a href="{{ route('orders.index') }}" class="order-back-link">{{ __('Все мои заказы') }}</a>@endauth
        </div>
    </section>
@endsection
