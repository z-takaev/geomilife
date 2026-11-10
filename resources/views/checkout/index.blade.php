@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-breadcrumbs :title="__('Оформление заказа')" :breadcrumbs="$breadcrumbs" />
    <livewire:checkout />
@endsection
