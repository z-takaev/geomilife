@extends('layouts.base')

@section('content')
    <x-breadcrumbs :title="__('Профиль')" :breadcrumbs="$breadcrumbs" />

    <section class="flat-spacing">
        <div class="container">
            <livewire:profile.manage-profile />
        </div>
    </section>
@endsection
