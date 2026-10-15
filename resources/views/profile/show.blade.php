@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Профиль')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Профиль') }}</h1>
        </div>
    </section>

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Страница профиля находится в разработке.') }}</p>
        </div>
    </section>
@endsection
