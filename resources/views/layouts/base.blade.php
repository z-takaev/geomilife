<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <x-seo />

    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icon/icomoon/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.css') }}">
    @stack('styles')

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('favicon.ico') }}">
</head>

<body>
    @include('partials.go-top')
    @include('partials.preload')

    <main id="wrapper">
        @include('partials.header')

        @yield('content')

        @include('partials.footer')
    </main>

    @include('partials.mobile-menu')
    @include('partials.wishlist')
    @include('partials.shopping-cart')
    @include('partials.quick-view')
    @include('partials.search')
    @include('partials.city-selection')
    @include('partials.auth.login')
    @include('partials.auth.register')
    @include('partials.auth.forgot-password')

    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-select.min.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>
