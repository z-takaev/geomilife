@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="$promo->title" :breadcrumbs="$breadcrumbs" />

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <x-content-detail-cover :date="$promo->end_at" :image="$promo->getFirstMediaUrl('image')" :title="$promo->title" expires />
                        <div class="content-detail-body">
                            {{ $promo->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
