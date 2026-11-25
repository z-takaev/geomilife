@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="$news->title" :breadcrumbs="$breadcrumbs" />

    <section class="content-detail-section flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-9 content-detail-main-column">
                    <div class="content-detail-main">
                        <x-content-detail-cover :date="$news->published_at" :image="$news->getFirstMediaUrl('image')" :title="$news->title" />
                        <div class="content-detail-body">
                            {{ $news->getRichContentAttribute('content') }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
