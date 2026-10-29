<div class="modal modalCentered fade modal-log register" id="register">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-black text-center">{{ __('Регистрация') }}</h3>
            <form action="{{ route('register') }}" method="POST" class="form-log">
                @csrf
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="first-name" class="tf-lable">{{ __('Имя') }}<span
                                class="text-secondary">*</span></label>
                        <input type="text" id="first-name" name="first_name"
                            value="{{ $errors->signUp->any() ? old('first_name') : '' }}"
                            placeholder="{{ __('Имя') }}" autocomplete="given-name" required
                            @class(['is-invalid' => $errors->signUp->has('first_name')])>
                        @error('first_name', 'signUp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <fieldset class="tf-field">
                        <label for="last-name" class="tf-lable">{{ __('Фамилия') }}<span
                                class="text-secondary">*</span></label>
                        <input type="text" id="last-name" name="last_name"
                            value="{{ $errors->signUp->any() ? old('last_name') : '' }}"
                            placeholder="{{ __('Фамилия') }}" autocomplete="family-name" required
                            @class(['is-invalid' => $errors->signUp->has('last_name')])>
                        @error('last_name', 'signUp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <fieldset class="tf-field">
                        <label for="email" class="tf-lable">{{ __('Электронная почта') }}<span
                                class="text-secondary">*</span></label>
                        <input type="email" id="email" name="email"
                            value="{{ $errors->signUp->any() ? old('email') : '' }}"
                            placeholder="{{ __('Электронная почта') }}" autocomplete="email" required
                            @class(['is-invalid' => $errors->signUp->has('email')])>
                        @error('email', 'signUp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password" class="tf-lable ">{{ __('Пароль') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password" name="password"
                                placeholder="{{ __('Пароль') }}" autocomplete="new-password" required
                                @class(['is-invalid' => $errors->signUp->has('password')])>
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                        @error('password', 'signUp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password2" class="tf-lable ">{{ __('Подтвердите пароль') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password2" name="password_confirmation"
                                placeholder="{{ __('Подтвердите пароль') }}" autocomplete="new-password" required>
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                    </fieldset>
                    <div class="check-bottom">
                        <div class="checkbox-wrap">
                            <input id="agree" name="terms" value="1" type="checkbox"
                                class="tf-check style-4 radius-3" required @checked($errors->signUp->any() && old('terms'))>
                            <label for="agree" class="text-black text-caption-01">
                                {{ __('Я принимаю') }}
                                <a href="{{ route('pages.show', ['page' => 'terms']) }}" target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-decoration-underline fw-medium text-primary link-2">{{ __('пользовательское соглашение') }}</a>
                            </label>
                        </div>
                        @error('terms', 'signUp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="tf-btn animate-btn w-100">
                        {{ __('Создать аккаунт') }}
                    </button>
                    <p class="dont-have text-caption-01 text-center">
                        {{ __('Уже есть аккаунт?') }}
                        <a href="#login" data-bs-toggle="modal"
                            class=" text-decoration-underline text-primary fw-medium link-2">
                            {{ __('Войти') }}
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
