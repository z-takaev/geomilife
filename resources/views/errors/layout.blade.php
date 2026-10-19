<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>@yield('title') — {{ config('app.name', __('Geomilife')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icon/icomoon/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('favicon.ico') }}">
</head>

<body>
    <main>
        <section class="section-404">
            <div class="banner-404 container">
                <div class="banner-content">
                    <p class="text-display text-primary font-sora">@yield('code')</p>
                    <h1 class="title text-primary font-sora">@yield('heading')</h1>
                    <p class="desc text-secondary">@yield('message')</p>
                    <a href="{{ route('home') }}" class="tf-btn animate-btn">
                        {{ __('Вернуться на главную') }}
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
