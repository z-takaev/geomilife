@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="$promo->title" :items="[['label' => __('Акции'), 'url' => route('promos.index')], ['label' => $promo->title]]" />

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ $promo->getFirstMediaUrl('image') }}" alt="{{ $promo->title }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date" datetime="{{ $promo->end_at->toDateString() }}">
                                {{ __('Акция действует до :date', ['date' => $promo->end_at->translatedFormat('j F Y')]) }}
                            </time>
                            <h2 class="content-detail-title cl-text-main font-sora">{{ $promo->title }}</h2>
                        </div>
                        <div class="content-detail-body">
                            {{ $promo->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
