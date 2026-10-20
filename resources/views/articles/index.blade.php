@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Статьи')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Статьи') }}</h1>
        </div>
    </section>

    <section class="article-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="article-archive-list">
                        @forelse ($articles as $article)
                            <x-article-list-item :image="$article->getFirstMediaUrl('image', 'card')" :image-alt="$article->title" :title="$article->title" :url="route('articles.show', $article)" />
                        @empty
                            <x-empty-state :title="__('Статьи пока не добавлены.')" />
                        @endforelse
                    </div>

                    {{ $articles->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
