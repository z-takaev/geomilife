@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Статьи')" :breadcrumbs="$breadcrumbs" />

    <section class="article-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="article-archive-list">
                        @forelse ($articles as $article)
                            <x-article-card :image="$article->getFirstMediaUrl('image', 'card')" :title="$article->title" :url="route('articles.show', $article)" />
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
