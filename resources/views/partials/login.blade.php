<!-- Login -->
<div class="modal modalCentered fade modal-log" id="login">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-primary text-center">{{ __('Log In') }}</h3>
            <form class="form-log">
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email3" class="tf-lable">{{ __('Email') }}<span
                                class="text-secondary">*</span></label>
                        <input type="email" id="email3" placeholder="{{ __('Email') }}" autocomplete="email"
                            required="">
                    </fieldset>
                    <fieldset class="tf-field ">
                        <label for="password-log" class="tf-lable ">{{ __('Password') }}<span
                                class="text-secondary">*</span></label>
                        <div class="password-wrapper">
                            <input class="password-field" type="password" id="password-log"
                                placeholder="{{ __('Password') }}" autocomplete="current-password" required="">
                            <span class="toggle-pass icon-eye-open"></span>
                        </div>
                    </fieldset>
                    <div class="check-bottom">
                        <div class="checkbox-wrap">
                            <input id="save" type="checkbox" class="tf-check style-4 radius-3">
                            <label for="save" class="text-primary text-caption-01">
                                {{ __('Remember me') }}
                            </label>
                        </div>
                        <a href="#forgotPassword" data-bs-toggle="modal"
                            class="text-caption-01 text-decoration-underline text-primary fw-medium link-2">
                            {{ __('Forgot Your Password?') }}
                        </a>
                    </div>
                    <button type="submit" class="tf-btn animate-btn w-100">
                        {{ __('Login') }}
                    </button>
                    <p class="dont-have text-caption-01 text-center">
                        {{ __('Not registered yet?') }}
                        <a href="#register" data-bs-toggle="modal"
                            class=" text-decoration-underline text-primary fw-medium link-2">
                            {{ __('Sign Up') }}
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Login -->
