@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => $page->title]]" />
            <h1 class="page-title text-white font-sora">{{ $page->title }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row">
                <article class="col-12 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-body">
                            {{ $page->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
