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
                            {{ __('Укажите электронную почту и новый пароль для аккаунта.') }}
                        </p>
                        <form action="{{ route('password.update') }}" method="POST" class="form-log">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="form-content">
                                <fieldset class="tf-field">
                                    <label for="reset-email" class="tf-lable">{{ __('Электронная почта') }}<span
                                            class="text-secondary">*</span></label>
                                    <input type="email" id="reset-email" name="email" value="{{ old('email', $email) }}"
                                        placeholder="{{ __('Электронная почта') }}" autocomplete="email" required
                                        @class(['is-invalid' => $errors->resetPassword->has('email')])>
                                    @error('email', 'resetPassword')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </fieldset>
                                <fieldset class="tf-field">
                                    <label for="reset-password" class="tf-lable">{{ __('Новый пароль') }}<span
                                            class="text-secondary">*</span></label>
                                    <div class="password-wrapper">
                                        <input class="password-field" type="password" id="reset-password" name="password"
                                            placeholder="{{ __('Новый пароль') }}" autocomplete="new-password" required
                                            @class(['is-invalid' => $errors->resetPassword->has('password')])>
                                        <span class="toggle-pass icon-eye-open"></span>
                                    </div>
                                    @error('password', 'resetPassword')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </fieldset>
                                <fieldset class="tf-field">
                                    <label for="reset-password-confirmation"
                                        class="tf-lable">{{ __('Подтвердите новый пароль') }}<span
                                            class="text-secondary">*</span></label>
                                    <div class="password-wrapper">
                                        <input class="password-field" type="password" id="reset-password-confirmation"
                                            name="password_confirmation" placeholder="{{ __('Подтвердите новый пароль') }}"
                                            autocomplete="new-password" required>
                                        <span class="toggle-pass icon-eye-open"></span>
                                    </div>
                                </fieldset>
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
