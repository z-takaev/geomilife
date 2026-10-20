@extends('layouts.base')

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
                @forelse ($promos as $promo)
                    <div class="col-md-6 col-lg-4">
                        <x-promotion-card archive :date="__('До :date', ['date' => $promo->end_at->translatedFormat('j F Y')])" :description="$promo->excerpt ?? ''" :image="$promo->getFirstMediaUrl('image', 'card')" :image-alt="$promo->title"
                            :title="$promo->title" :url="route('promos.show', $promo)" />
                    </div>
                @empty
                    <div class="col-12">
                        <x-empty-state :title="__('Акции пока не добавлены.')" />
                    </div>
                @endforelse
            </div>

            {{ $promos->links() }}
        </div>
    </section>
@endsection
