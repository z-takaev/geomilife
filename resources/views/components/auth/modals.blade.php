@include('auth.partials.login')
@include('auth.partials.register')
@include('auth.partials.forgot-password')

@php
    $authModal = match (true) {
        $errors->signIn->any() => 'login',
        $errors->signUp->any() => 'register',
        $errors->forgotPassword->any() => 'forgotPassword',
        session('auth_modal') !== null => session('auth_modal'),
        request()->string('auth')->toString() === 'login' => 'login',
        default => null,
    };
@endphp

@push('scripts')
    @if ($authModal !== null)
        <script>
            bootstrap.Modal.getOrCreateInstance(document.getElementById(@js($authModal))).show();
        </script>
    @endif
@endpush
