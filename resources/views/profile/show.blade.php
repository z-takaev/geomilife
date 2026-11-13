@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-breadcrumbs :title="__('Профиль')" :breadcrumbs="$breadcrumbs" />

    <section class="flat-spacing">
        <div class="container">
            <livewire:user-profile />
        </div>
    </section>
@endsection
