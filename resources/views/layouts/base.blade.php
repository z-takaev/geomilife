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
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.css') }}">
    @stack('styles')

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('favicon.ico') }}">
</head>

<body>
    @include('layouts.partials.back-to-top')
    @include('layouts.partials.page-loader')

    <main id="wrapper">
        @include('layouts.partials.header')

        @yield('content')

        @include('layouts.partials.footer')
    </main>

    @include('layouts.partials.navigation.mobile')
    <livewire:favorites />
    <livewire:shopping-cart />
    <livewire:product-quick-view />
    <livewire:search-suggestions />
    <livewire:city-selector />
    @guest
        @include('auth.partials.login')
        @include('auth.partials.register')
        @include('auth.partials.forgot-password')
    @endguest
    @include('layouts.partials.cookie-consent')
    @include('layouts.partials.notifications')

    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-select.min.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/alpine.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @guest
        @php
            $authModal = match (true) {
                $errors->signIn->any() => 'login',
                $errors->signUp->any() => 'register',
                $errors->forgotPassword->any() => 'forgotPassword',
                session('auth_modal') !== null => session('auth_modal'),
                request()->string('auth')->toString() === 'login' => 'login',
                default => null,
            };
        @endphp
        @if ($authModal !== null)
            <script>
                bootstrap.Modal.getOrCreateInstance(document.getElementById(@js($authModal))).show();
            </script>
        @endif
    @endguest
</body>

</html>
