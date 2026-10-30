@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Акции')" :breadcrumbs="$breadcrumbs" />

    <section class="promo-archive-section flat-spacing">
        <div class="container">
            <div class="row gy-30">
                @forelse ($promos as $promo)
                    <div class="col-md-6 col-lg-4">
                        <x-promos.card archive :date="__('До :date', ['date' => $promo->end_at->translatedFormat('j F Y')])" :description="$promo->excerpt ?? ''" :image="$promo->getFirstMediaUrl('image', 'card')" :title="$promo->title"
                            :url="route('promos.show', $promo)" />
                    </div>
                @empty
                    <div class="col-12">
                        <x-empty-state :title="__('Акции пока не добавлены.')" />
                    </div>
                @endforelse
            </div>

            {{ $promos->links() }}
        </div>
    </section>
@endsection
