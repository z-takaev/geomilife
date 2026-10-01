<!-- Mobile Menu -->
<div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu">
    <div class="canvas-header">
        <span class="icon-close-popup" data-bs-dismiss="offcanvas">
            <i class="icon icon-close"></i>
        </span>
        <form class="form-search-nav">
            <fieldset>
                <input type="text" placeholder="{{ __('What are you looking for?') }}" required>
            </fieldset>
            <button type="submit" class="btn-action">
                <i class="icon icon-magnifying-glass"></i>
            </button>
        </form>
    </div>
    <div class="canvas-body">
        <div class="mb-content-top">
            <ul class="nav-ul-mb" id="wrapper-menu-navigation"></ul>
        </div>
        <div class="need-help-wrap">
            <h6 class="nd-title text-caption-02 text-primary mb-12">{{ __('CONTACT') }}</h6>
            <a href="#" class="text-caption-01 text-primary mb-8">
                {{ __('101 E 129th St, Chicago, New York') }}
            </a>
            <a href="tel:+79888731020" class="h5 fw-medium text-primary mb-12">
                {{ __('+7-9888-731-020') }}
            </a>
            <a href="mailto:geomilife@bk.ru" class="text-caption-01 text-primary mb-20">
                {{ __('geomilife@bk.ru') }}
            </a>
            <ul class="social-list d-flex align-items-center gap-24">
                <li>
                    <a href="#" class="d-flex text-primary"><i class="icon fs-24 icon-FacebookLogo"></i></a>
                </li>
                <li>
                    <a href="#" class="d-flex text-primary"><i class="icon fs-24 icon-XLogo"></i></a>
                </li>
                <li>
                    <a href="#" class="d-flex text-primary"><i class="icon fs-24 icon-TiktokLogo"></i></a>
                </li>
                <li>
                    <a href="#" class="d-flex text-primary"><i class="icon fs-24 icon-InstagramLogo"></i></a>
                </li>
                <li>
                    <a href="#" class="d-flex text-primary"><i class="icon fs-24 icon-YoutubeLogo"></i></a>
                </li>
            </ul>
        </div>
    </div>
    <div class="canvas-footer">
        <div class="d-flex justify-content-center border-end">
            <div class="tf-currencies">
                <select class="tf-dropdown-select style-default type-currencies">
                    <option selected>{{ __('(USD $)') }}</option>
                    <option>{{ __('(VND ₫)') }}</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-center">
            <div class="tf-languages">
                <select class="tf-dropdown-select style-default type-languages">
                    <option>{{ __('English') }}</option>
                    <option>{{ __('العربية') }}</option>
                    <option>{{ __('简体中文') }}</option>
                    <option>{{ __('اردو') }}</option>
                </select>
            </div>
        </div>
    </div>
</div>
<!-- /Mobile Menu -->
