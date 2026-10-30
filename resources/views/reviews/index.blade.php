@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Отзывы')" :breadcrumbs="$breadcrumbs" />

    <section class="flat-spacing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="reviews-archive-list">
                        @forelse ($reviews as $review)
                            <x-reviews.card :review="$review" :expandable="true" />
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
