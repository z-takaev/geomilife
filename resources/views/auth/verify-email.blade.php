@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="flat-spacing modal-forgot">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="modal-content text-center">
                        <h3 class="title-pop text-black">{{ __('Подтвердите email') }}</h3>
                        <p class="desc-pop">
                            {{ __('Мы отправили письмо со ссылкой для подтверждения на :email.', ['email' => auth()->user()->email]) }}
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <div class="auth-notice" role="status">
                                {{ __('Новое письмо с подтверждением отправлено.') }}
                            </div>
                        @endif
                        <form action="{{ route('verification.send') }}" method="POST">
                            @csrf
                            <button type="submit" class="tf-btn animate-btn w-100">
                                {{ __('Отправить письмо повторно') }}
                            </button>
                        </form>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-decoration-underline text-primary fw-medium link-2 mt-3">
                                {{ __('Выйти из аккаунта') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
