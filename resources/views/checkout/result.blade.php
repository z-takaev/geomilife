@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <section class="flat-spacing">
        <div class="container">
            <x-empty-state :title="$title" :description="$description" />
            <div class="d-flex justify-content-center gap-3 mt-4">
                @auth
                    <a href="{{ route('orders.index') }}" class="tf-btn animate-btn">{{ __('Мои заказы') }}</a>
                @endauth
                <a href="{{ route('catalog.index') }}"
                    class="tf-btn btn-outline animate-btn">{{ __('Перейти в каталог') }}</a>
            </div>
        </div>
    </section>
@endsection
