<!-- Forgot Password -->
<div class="modal modalCentered fade modal-forgot" id="forgotPassword">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-black">{{ __('Восстановление пароля') }}</h3>
            <p class="desc-pop">
                {{ __('Введите электронную почту, указанную при регистрации. Мы отправим на неё ссылку для восстановления пароля.') }}
            </p>
            <form class="form-forgot">
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email2" class="tf-lable">{{ __('Электронная почта') }}</label>
                        <input type="email" id="email2" placeholder="{{ __('Электронная почта') }}"
                            autocomplete="email" required="">
                    </fieldset>
                    <button type="submit" class="tf-btn animate-btn w-100">
                        {{ __('Отправить ссылку') }}
                    </button>
                </div>
            </form>
            <a href="#login" data-bs-toggle="modal"
                class=" text-decoration-underline text-primary fw-medium link-2 text-center">
                {{ __('Вернуться ко входу') }}
            </a>
        </div>
    </div>
</div>
<!-- /Forgot Password -->
