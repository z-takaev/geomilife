@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Новости')" :breadcrumbs="$breadcrumbs" />

    <section class="s-blog-list news-archive-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="news-archive-list">
                        @forelse ($newsItems as $news)
                            {{-- TODO: поправить формать в конфиге --}}
                            <x-news.card :date="$news->published_at->translatedFormat('j F Y')" :description="$news->excerpt ?? ''" :image="$news->getFirstMediaUrl('image', 'card')" :title="$news->title"
                                :url="route('news.show', $news)" />
                        @empty
                            <x-empty-state :title="__('Новости пока не добавлены.')" icon="icon-package" />
                        @endforelse
                    </div>

                    {{ $newsItems->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
