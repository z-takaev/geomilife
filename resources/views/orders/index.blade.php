@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Мои заказы')" :items="[['label' => __('Профиль'), 'url' => route('profile.show')], ['label' => __('Мои заказы')]]" />

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Страница заказов находится в разработке.') }}</p>
        </div>
    </section>
@endsection
