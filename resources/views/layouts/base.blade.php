<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <x-seo />
    @stack('meta')

    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icon/icomoon/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">
    @stack('styles')

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('favicon.ico') }}">
    @if (settings()?->snow_enabled)
        <link rel="stylesheet" href="{{ asset('assets/css/snow.min.css') }}">
    @endif
</head>

<body>
    <main id="wrapper">
        @include('layouts.partials.header')

        @yield('content')

        @include('layouts.partials.footer')
    </main>

    @include('layouts.partials.navigation.mobile')

    <livewire:favorites />
    <livewire:shopping-cart />
    <livewire:city-selector />
    <livewire:search-suggestions />
    <livewire:product-quick-view />

    @guest
        <x-auth.modals />
    @endguest

    @include('layouts.partials.back-to-top')
    @include('layouts.partials.page-loader')
    @include('layouts.partials.cookie-consent')
    @include('layouts.partials.notifications')

    @if (settings()?->snow_enabled)
        <script src="{{ asset('assets/js/snow.min.js') }}"></script>
        <script>
            new Snow();
        </script>
    @endif

    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-select.min.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/alpine.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>
