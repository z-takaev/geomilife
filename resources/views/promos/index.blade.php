@extends('layouts.base')

@section('title', __('Акции'))
@section('description', __('Акции и специальные предложения на натуральные продукты GeoMiLife.'))

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Акции')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Акции') }}</h1>
        </div>
    </section>

    <section class="promo-archive-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                @foreach ($promos as $promo)
                    <div class="col-md-6 col-lg-4">
                        <x-promotion-card archive :date="__('До :date', ['date' => $promo->end_at->translatedFormat('j F Y')])" :description="$promo->excerpt ?? ''" :image="$promo->getFirstMediaUrl('image', 'card')" :image-alt="$promo->title"
                            :title="$promo->title" :url="route('promos.show', $promo)" />
                    </div>
                @endforeach
            </div>

            {{ $promos->links() }}
        </div>
    </section>
@endsection
