@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Мои заказы')" :breadcrumbs="$breadcrumbs" />

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Страница заказов находится в разработке.') }}</p>
        </div>
    </section>
@endsection
