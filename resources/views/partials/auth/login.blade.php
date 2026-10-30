<div class="modal modalCentered fade modal-log" id="login">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-black text-center">{{ __('Вход') }}</h3>
            @if (session('auth_status') && session('auth_modal') === 'login')
                <div class="auth-notice" role="status">
                    {{ session('auth_status') }}
                </div>
            @endif
            <form action="{{ route('login') }}" method="POST" class="form-log">
                @csrf
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email3" class="tf-lable">{{ __('Email') }}<span
                                class="text-secondary">*</span></label>
                        <input type="email" id="email3" name="email"
                            value="{{ $errors->signIn->any() ? old('email') : '' }}" placeholder="{{ __('Email') }}"
                            autocomplete="email" required @class(['is-invalid' => $errors->signIn->has('email')])>
                        @error('email', 'signIn')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password-log" class="tf-lable ">{{ __('Пароль') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password-log" name="password"
                                placeholder="{{ __('Пароль') }}" autocomplete="current-password" required
                                @class(['is-invalid' => $errors->signIn->has('password')])>
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                        @error('password', 'signIn')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <div class="check-bottom">
                        <div class="checkbox-wrap">
                            <input id="save" name="remember" value="1" type="checkbox"
                                class="tf-check style-4 radius-3" @checked($errors->signIn->any() && old('remember'))>
                            <label for="save" class="text-black text-caption-01">
                                {{ __('Запомнить меня') }}
                            </label>
                        </div>
                        <a href="#forgotPassword" data-bs-toggle="modal"
                            class="text-caption-01 text-decoration-underline text-primary fw-medium link-2">
                            {{ __('Забыли пароль?') }}
                        </a>
                    </div>
                    <button type="submit" class="tf-btn animate-btn w-100">
                        {{ __('Войти') }}
                    </button>
                    <p class="dont-have text-caption-01 text-center">
                        {{ __('Ещё не зарегистрированы?') }}
                        <a href="#register" data-bs-toggle="modal"
                            class=" text-decoration-underline text-primary fw-medium link-2">
                            {{ __('Зарегистрироваться') }}
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
