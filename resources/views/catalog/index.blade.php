@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="$category?->name ?? __('Каталог')" :breadcrumbs="$breadcrumbs" />

    <livewire:catalog-products :category="$category" />
@endsection
