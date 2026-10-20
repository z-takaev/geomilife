@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Заказ №:number', ['number' => $order->number])" :items="[
        ['label' => __('Профиль'), 'url' => route('profile.show')],
        ['label' => __('Мои заказы'), 'url' => route('orders.index')],
        ['label' => __('Заказ №:number', ['number' => $order->number])],
    ]" />

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Детальная страница заказа находится в разработке.') }}</p>
        </div>
    </section>
@endsection
