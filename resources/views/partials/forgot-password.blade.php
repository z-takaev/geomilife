<!-- Forgot Password -->
<div class="modal modalCentered fade modal-forgot" id="forgotPassword">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            <h3 class="title-pop text-primary">{{ __('Forgot Your Password?') }}</h3>
            <p class="desc-pop">
                {{ __('Enter the e-mail address associated with your account. Click submit to have a password reset link e-mailed to you.') }}
            </p>
            <form class="form-forgot">
                <div class="form-content">
                    <fieldset class="tf-field">
                        <label for="email2" class="tf-lable">{{ __('Your E-Mail Address') }}</label>
                        <input type="email" id="email2" placeholder="{{ __('Email') }}" autocomplete="email"
                            required="">
                    </fieldset>
                    <button type="submit" class="tf-btn animate-btn w-100">
                        {{ __('Login') }}
                    </button>
                </div>
            </form>
            <a href="#login" data-bs-toggle="modal"
                class=" text-decoration-underline text-primary fw-medium link-2 text-center">
                {{ __('Back To Login') }}
            </a>
        </div>
    </div>
</div>
<!-- /Forgot Password -->
