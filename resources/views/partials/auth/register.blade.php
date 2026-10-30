<x-auth.modal id="register" class="modal-log register">
    <h3 class="title-pop text-black text-center">{{ __('Регистрация') }}</h3>
    <form action="{{ route('register') }}" method="POST" class="form-log">
        @csrf
        <div class="form-content">
            <x-auth.form.input id="first-name" name="first_name" :label="__('Имя')" :value="$errors->signUp->any() ? old('first_name') : ''" :placeholder="__('Имя')"
                autocomplete="given-name" error-bag="signUp" required />
            <x-auth.form.input id="last-name" name="last_name" :label="__('Фамилия')" :value="$errors->signUp->any() ? old('last_name') : ''" :placeholder="__('Фамилия')"
                autocomplete="family-name" error-bag="signUp" required />
            <x-auth.form.input id="email" name="email" type="email" :label="__('Email')" :value="$errors->signUp->any() ? old('email') : ''"
                :placeholder="__('Email')" autocomplete="email" error-bag="signUp" required />
            <x-auth.form.password id="password" name="password" :label="__('Пароль')" :placeholder="__('Пароль')"
                autocomplete="new-password" error-bag="signUp" required />
            <x-auth.form.password id="password2" name="password_confirmation" :label="__('Подтвердите пароль')" :placeholder="__('Подтвердите пароль')"
                autocomplete="new-password" error-bag="signUp" required />
            <div class="check-bottom">
                <x-auth.form.checkbox id="agree" name="terms" error-bag="signUp" :checked="$errors->signUp->any() && old('terms')" required>
                    {{ __('Я принимаю') }}
                    <a href="{{ route('page', ['page' => 'terms']) }}" target="_blank" rel="noopener noreferrer"
                        class="text-decoration-underline fw-medium text-primary link-2">{{ __('пользовательское соглашение') }}</a>
                </x-auth.form.checkbox>
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
</x-auth.modal>
