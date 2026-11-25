@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="$article->title" :breadcrumbs="$breadcrumbs" />

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <x-content-detail-cover :date="$article->published_at" :image="$article->getFirstMediaUrl('image')" :title="$article->title" />
                        <div class="content-detail-body">
                            {{ $article->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
