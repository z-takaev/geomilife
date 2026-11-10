@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-breadcrumbs :title="__('Мои заказы')" :breadcrumbs="$breadcrumbs" />
    <section class="flat-spacing orders-page">
        <div class="container">
            <div class="orders-page__heading">
                <div><span class="profile-eyebrow">{{ __('Личный кабинет') }}</span>
                    <h2>{{ __('История заказов') }}</h2>
                    <p>{{ __('Состав, получение и состояние ваших покупок') }}</p>
                </div><a href="{{ route('catalog.index') }}" class="tf-btn">{{ __('В каталог') }}</a>
            </div>
            <div class="orders-list">
                @forelse ($orders as $order)
                    <article class="customer-order-card">
                        <div class="customer-order-card__top">
                            <div><a href="{{ route('orders.show', $order) }}"
                                    class="customer-order-card__number">{{ __('Заказ №:number', ['number' => $order->number]) }}</a>
                                <p>{{ $order->created_at->format('d.m.Y H:i') }}</p>
                            </div><span
                                class="order-badge order-badge--{{ $order->status->getColor() }}">{{ $order->status->getLabel() }}</span>
                        </div>
                        <div class="customer-order-card__body">
                            <div><span
                                    class="order-label">{{ __('Получение') }}</span><strong>{{ $presentations[$order->id]['deliveryLabel'] }}</strong>
                                <p>{{ $presentations[$order->id]['addressLabel'] }}</p>
                            </div>
                            <div><span
                                    class="order-label">{{ __('Оплата') }}</span><strong>{{ $order->payment_status->getLabel() }}</strong>
                            </div>
                            <div class="customer-order-card__total"><span
                                    class="order-label">{{ __('Итого') }}</span><strong>{{ $order->total->format() }}</strong>
                            </div>
                        </div>
                        @if ($order->is_preorder)
                            <p class="order-preorder">
                                {{ __('Предзаказ. Точные сроки доставки согласовываются отдельно с менеджером.') }}</p>
                        @endif
                        <div class="customer-order-card__actions"><a href="{{ route('orders.show', $order) }}"
                                class="order-text-button">{{ __('Подробнее о заказе') }} <span
                                    aria-hidden="true">→</span></a>
                            @if ($presentations[$order->id]['canPay'])
                                <form method="post" action="{{ $presentations[$order->id]['payUrl'] }}">@csrf<button
                                        type="submit" class="tf-btn">{{ __('Оплатить') }}</button></form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="orders-empty">
                        <h3>{{ __('У вас пока нет заказов') }}</h3>
                        <p>{{ __('Выберите товары в каталоге — здесь появится история ваших покупок.') }}</p><a
                            href="{{ route('catalog.index') }}" class="tf-btn">{{ __('Перейти в каталог') }}</a>
                    </div>
                @endforelse
            </div>
            <div class="orders-pagination">{{ $orders->links() }}</div>
        </div>
    </section>
@endsection
