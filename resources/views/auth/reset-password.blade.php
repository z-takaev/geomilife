@extends('layouts.base')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="flat-spacing modal-forgot">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="modal-content">
                        <h3 class="title-pop text-black text-center">{{ __('Новый пароль') }}</h3>
                        <p class="desc-pop text-center">
                            {{ __('Укажите email и новый пароль для аккаунта.') }}
                        </p>
                        <form action="{{ route('password.update') }}" method="POST" class="form-log">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="form-content">
                                <x-auth.form.input id="reset-email" name="email" type="email" :label="__('Email')"
                                    :value="old('email', $email)" :placeholder="__('Email')" autocomplete="email" error-bag="resetPassword"
                                    required />
                                <x-auth.form.password id="reset-password" name="password" :label="__('Новый пароль')"
                                    :placeholder="__('Новый пароль')" autocomplete="new-password" error-bag="resetPassword" required />
                                <x-auth.form.password id="reset-password-confirmation" name="password_confirmation"
                                    :label="__('Подтвердите новый пароль')" :placeholder="__('Подтвердите новый пароль')" autocomplete="new-password"
                                    error-bag="resetPassword" required />
                                <button type="submit" class="tf-btn animate-btn w-100">
                                    {{ __('Сохранить новый пароль') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
