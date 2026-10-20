@extends('layouts.base')

@section('content')
    <section class="s-page-title inner-page-title">
        <div class="container">
            <x-breadcrumbs :items="[['label' => __('Главная'), 'url' => route('home')], ['label' => __('Отзывы')]]" />
            <h1 class="page-title text-white font-sora">{{ __('Отзывы') }}</h1>
        </div>
    </section>

    <section class="flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="reviews-archive-list">
                        @forelse ($reviews as $review)
                            <x-review-card :review="$review" :expandable="true" />
                        @empty
                            <x-empty-state :title="__('Отзывы пока не добавлены.')" />
                        @endforelse
                    </div>

                    {{ $reviews->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
