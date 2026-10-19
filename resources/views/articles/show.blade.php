@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[
                ['label' => __('Главная'), 'url' => route('home')],
                ['label' => __('Статьи'), 'url' => route('articles.index')],
                ['label' => $article->title],
            ]" />
            <h1 class="page-title text-white font-sora">{{ $article->title }}</h1>
        </div>
    </section>

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <div class="content-detail-main-image">
                            <img loading="lazy" src="{{ $article->getFirstMediaUrl('image') }}" alt="{{ $article->title }}">
                        </div>
                        <div class="content-detail-heading">
                            <time class="content-detail-date" datetime="{{ $article->published_at->toDateString() }}">
                                {{ $article->published_at->translatedFormat('j F Y') }}
                            </time>
                            <h2 class="content-detail-title cl-text-main font-sora">{{ $article->title }}</h2>
                        </div>
                        <div class="content-detail-body">
                            {{ $article->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
