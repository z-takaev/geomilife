@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[
                ['label' => __('Главная'), 'url' => route('home')],
                ['label' => __('Каталог'), 'url' => route('catalog.index')],
                ['label' => $product->name],
            ]" />
            <h1 class="page-title text-white font-sora">{{ $product->name }}</h1>
        </div>
    </section>

    <section class="flat-spacing">
        <div class="container">
            <p>{{ __('Страница товара находится в разработке.') }}</p>
        </div>
    </section>
@endsection
