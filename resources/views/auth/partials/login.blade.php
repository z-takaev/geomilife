<x-auth.modal id="login" class="modal-log">
    <h3 class="title-pop text-black text-center">{{ __('Вход') }}</h3>
    @if (session('auth_status') && session('auth_modal') === 'login')
        <x-auth.notice>
            {{ session('auth_status') }}
        </x-auth.notice>
    @endif
    <form action="{{ route('login') }}" method="POST" class="form-log">
        @csrf
        <div class="form-content">
            <x-auth.form.input id="email3" name="email" type="email" :label="__('Email')" :value="$errors->signIn->any() ? old('email') : ''"
                :placeholder="__('Email')" autocomplete="email" error-bag="signIn" required />
            <x-auth.form.password id="password-log" name="password" :label="__('Пароль')" :placeholder="__('Пароль')"
                autocomplete="current-password" error-bag="signIn" required />
            <div class="check-bottom">
                <x-auth.form.checkbox id="save" name="remember" error-bag="signIn" :checked="$errors->signIn->any() && old('remember')">
                    {{ __('Запомнить меня') }}
                </x-auth.form.checkbox>
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
</x-auth.modal>
