<div class="modal modalCentered fade modal-forgot" id="forgotPassword">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-black">{{ __('Восстановление пароля') }}</h3>
            <p class="desc-pop">
                {{ __('Введите email, указанный при регистрации. Мы отправим на него ссылку для восстановления пароля.') }}
            </p>
            @if (session('auth_status') && session('auth_modal') === 'forgotPassword')
                <div class="auth-notice" role="status">
                    {{ session('auth_status') }}
                </div>
            @endif
            <form action="{{ route('password.email') }}" method="POST" class="form-forgot">
                @csrf
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email2" class="tf-lable">{{ __('Email') }}</label>
                        <input type="email" id="email2" name="email"
                            value="{{ $errors->forgotPassword->any() ? old('email') : '' }}"
                            placeholder="{{ __('Email') }}" autocomplete="email" required
                            @class(['is-invalid' => $errors->forgotPassword->has('email')])>
                        @error('email', 'forgotPassword')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
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
