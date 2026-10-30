<x-auth.modal id="forgotPassword" class="modal-forgot">
    <h3 class="title-pop text-black">{{ __('Восстановление пароля') }}</h3>
    <p class="desc-pop">
        {{ __('Введите email, указанный при регистрации. Мы отправим на него ссылку для восстановления пароля.') }}
    </p>
    @if (session('auth_status') && session('auth_modal') === 'forgotPassword')
        <x-auth.notice>
            {{ session('auth_status') }}
        </x-auth.notice>
    @endif
    <form action="{{ route('password.email') }}" method="POST" class="form-forgot">
        @csrf
        <div class="form-content">
            <x-auth.form.input id="email2" name="email" type="email" :label="__('Email')" :value="$errors->forgotPassword->any() ? old('email') : ''"
                :placeholder="__('Email')" autocomplete="email" error-bag="forgotPassword" :required-marker="false" required />
            <button type="submit" class="tf-btn animate-btn w-100">
                {{ __('Отправить ссылку') }}
            </button>
        </div>
    </form>
    <a href="#login" data-bs-toggle="modal"
        class=" text-decoration-underline text-primary fw-medium link-2 text-center">
        {{ __('Вернуться ко входу') }}
    </a>
</x-auth.modal>
