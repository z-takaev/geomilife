@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Профиль')" :items="[['label' => __('Профиль')]]" />

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Страница профиля находится в разработке.') }}</p>
        </div>
    </section>
@endsection
