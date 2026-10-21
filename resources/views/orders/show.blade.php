@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Заказ №:number', ['number' => $order->number])" :breadcrumbs="$breadcrumbs" />

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Детальная страница заказа находится в разработке.') }}</p>
        </div>
    </section>
@endsection
