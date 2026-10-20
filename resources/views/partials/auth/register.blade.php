<div class="modal modalCentered fade modal-log register" id="register">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-black text-center">{{ __('Регистрация') }}</h3>
            <form class="form-log">
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email" class="tf-lable">{{ __('Электронная почта') }}<span
                                class="text-secondary">*</span></label>
                        <input type="email" id="email" placeholder="{{ __('Электронная почта') }}"
                            autocomplete="email" required="">
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password" class="tf-lable ">{{ __('Пароль') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password"
                                placeholder="{{ __('Пароль') }}" autocomplete="new-password" required="">
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password2" class="tf-lable ">{{ __('Подтвердите пароль') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password2"
                                placeholder="{{ __('Подтвердите пароль') }}" autocomplete="new-password" required="">
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                    </fieldset>
                    <div class="check-bottom">
                        <div class="checkbox-wrap">
                            <input id="agree" type="checkbox" class="tf-check style-4 radius-3">
                            <label for="agree" class="text-black text-caption-01">
                                {{ __('Я принимаю') }}
                                <a href="#"
                                    class="text-decoration-underline fw-medium text-primary link-2">{{ __('условия использования') }}</a>
                            </label>
                        </div>
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
