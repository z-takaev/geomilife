@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Новости')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Новости') }}</h1>
        </div>
    </section>

    <section class="s-blog-list news-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="news-archive-list">
                        @foreach ($newsItems as $news)
                            <x-news-card :date="$news->published_at->translatedFormat('j F Y')" :description="$news->excerpt ?? ''" :image="$news->getFirstMediaUrl('image', 'card')" :image-alt="$news->title"
                                :title="$news->title" :url="route('news.show', $news)" />
                        @endforeach
                    </div>

                    {{ $newsItems->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
