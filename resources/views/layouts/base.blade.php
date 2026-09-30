<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ config('app.name', 'Geomilife') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="description"
        content="Orvana is a responsive HTML template for organic shops and eco-friendly stores, featuring 4 homepage layouts, modern design, smooth navigation, and essential eCommerce features for a professional online storefront.">

    <!-- font -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icon/icomoon/style.css') }}">
    <!-- css -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">

    <!-- Favicon and Touch Icons  -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.svg') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('assets/images/logo/favicon.svg') }}">
</head>

<body>
    <!-- Scroll Top -->
    <button id="goTop">
        <span class="border-progress"></span>
        <span class="icon icon-caret-up"></span>
    </button>

    <!-- Preload -->
    <div class="preload preload-container" id="preload">
        <div class="preload-logo">
            <div class="spinner"></div>
        </div>
    </div>
    <!-- /Preload -->

    <main id="wrapper">
        @include('partials.header')

        @yield('content')

        @include('partials.footer')
    </main>

    <!-- Mobile Menu -->
    <div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu">
        <div class="canvas-header">
            <span class="icon-close-popup" data-bs-dismiss="offcanvas">
                <i class="icon icon-close"></i>
            </span>
            <form class="form-search-nav">
                <fieldset>
                    <input type="text" placeholder="What are you looking for?" required>
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
                <h6 class="nd-title text-caption-02 text-primary mb-12">CONTACT</h6>
                <a href="#" class="text-caption-01 text-primary mb-8">
                    101 E 129th St, Chicago, New York
                </a>
                <a href="#" class="h5 fw-medium text-primary mb-12">
                    1-555-678-8888
                </a>
                <a href="mailto:themesflat@gmail.com" class="text-caption-01 text-primary mb-20">
                    themesflat@gmail.com
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
                        <option selected>(USD $)</option>
                        <option>(VND ₫)</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <div class="tf-languages">
                    <select class="tf-dropdown-select style-default type-languages">
                        <option>English</option>
                        <option>العربية</option>
                        <option>简体中文</option>
                        <option>اردو</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <!-- /Mobile Menu -->

    <!-- Forgot Password -->
    <div class="modal modalCentered fade modal-forgot" id="forgotPassword">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="icon-close-popup" data-bs-dismiss="modal">
                    <i class="icon icon-close"></i>
                </div>
                <h3 class="title-pop text-primary">Forgot Your Password?</h3>
                <p class="desc-pop">
                    Enter the e-mail address associated with your account. Click submit to have a password reset link
                    e-mailed to you.
                </p>
                <form class="form-forgot">
                    <div class="form-content">
                        <fieldset class="tf-field">
                            <label for="email2" class="tf-lable">Your E-Mail Address</label>
                            <input type="email" id="email2" placeholder="Email" autocomplete="email"
                                required="">
                        </fieldset>
                        <button type="submit" class="tf-btn animate-btn w-100">
                            Login
                        </button>
                    </div>
                </form>
                <a href="#login" data-bs-toggle="modal"
                    class=" text-decoration-underline text-primary fw-medium link-2 text-center">
                    Back To Login
                </a>
            </div>
        </div>
    </div>
    <!-- /Forgot Password -->
    <!-- Shoping Cart -->
    <div class="offcanvas offcanvas-end canvas-shop-cart" id="shoppingCart">
        <div class="canvas-wrapper">
            <div class="canvas-header">
                <h5 class="title-pop text-primary">Shopping Cart</h5>
                <span class="icon-close-popup" data-bs-dismiss="offcanvas">
                    <i class="icon icon-close"></i>
                </span>
            </div>
            <div class="wrap list-file-delete">
                <div class="tf-mini-cart-threshold">
                    <p class="text text-primary">
                        Buy
                        <span class="fw-medium">
                            $70.00
                        </span>
                        more to get
                        <span class="fw-medium">
                            freeship
                        </span>
                    </p>
                    <div class="tf-progress-bar tf-progress-ship">
                        <div class="value" style="width: 75%;" data-progress="75">
                            <i class="icon icon-Truck"></i>
                        </div>
                    </div>
                </div>
                <div class="tf-mini-cart-wrap">
                    <div class="tf-mini-cart-sroll">
                        <ul class="tf-mini-cart-items list-mini-product">
                            <li class="mini-product-cart file-delete">
                                <div class="prd-wrap">
                                    <div class="prd_image">
                                        <img loading="lazy" width="100" height="100"
                                            src="{{ asset('assets/images/product/product-12.jpg') }}" alt="Image">
                                    </div>
                                    <a href="product-single-1.html"
                                        class="prd_name link-underline text-primary fw-medium">
                                        Smackn’ Grapes Tomates - 1KG
                                    </a>
                                </div>
                                <div class="prd-action">
                                    <a href="#" class="tf-btn-line style-min p-0 remove">
                                        <span class="text-caption-01">
                                            Remove
                                        </span>
                                    </a>
                                    <div class="quantity-price text-primary fw-medium">
                                        <span>1</span>
                                        <span>x</span>
                                        <span class="tf-mini-card-price">$3.99</span>
                                    </div>
                                </div>
                            </li>
                            <li class="mini-product-cart file-delete">
                                <div class="prd-wrap">
                                    <div class="prd_image">
                                        <img loading="lazy" width="100" height="100"
                                            src="{{ asset('assets/images/product/product-11.jpg') }}" alt="Image">
                                    </div>
                                    <a href="product-single-1.html"
                                        class="prd_name link-underline text-primary fw-medium">
                                        Range Large Brown Eggs, 18 Count
                                    </a>
                                </div>
                                <div class="prd-action">
                                    <a href="#" class="tf-btn-line style-min p-0 remove">
                                        <span class="text-caption-01">
                                            Remove
                                        </span>
                                    </a>
                                    <div class="quantity-price text-primary fw-medium">
                                        <span>1</span>
                                        <span>x</span>
                                        <span class="tf-mini-card-price">$3.99</span>
                                    </div>
                                </div>
                            </li>
                            <li class="mini-product-cart file-delete">
                                <div class="prd-wrap">
                                    <div class="prd_image">
                                        <img loading="lazy" width="100" height="100"
                                            src="{{ asset('assets/images/product/product-17.jpg') }}" alt="Image">
                                    </div>
                                    <a href="product-single-1.html"
                                        class="prd_name link-underline text-primary fw-medium">
                                        Earthbound Farm Organic Baby Spinach - 250GR
                                    </a>
                                </div>
                                <div class="prd-action">
                                    <a href="#" class="tf-btn-line style-min p-0 remove">
                                        <span class="text-caption-01">
                                            Remove
                                        </span>
                                    </a>
                                    <div class="quantity-price text-primary fw-medium">
                                        <span>1</span>
                                        <span>x</span>
                                        <span class="tf-mini-card-price">$3.99</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="canvas-footer">
                <div class="tf-cart-totals-discounts fw-medium text-primary">
                    <h4 class="tf-cart-total-text">Subtotal</h4>
                    <h4 class="tf-totals-total-value">$186,99</h4>
                </div>
                <div class="checkbox-wrap">
                    <input id="total" type="checkbox" class="tf-check style-4 radius-3">
                    <label for="total" class="text-primary">
                        I agree with
                        <a href="#" class="text-decoration-underline fw-medium text-primary link-2">
                            Terms & Conditions
                        </a>
                    </label>
                </div>
                <div class="group-btn">
                    <a href="shopping-cart.html" class="tf-btn animate-btn w-100">
                        View Cart
                    </a>
                    <a href="check-out.html" class="tf-btn style-stroke w-100">
                        Check Out
                    </a>
                </div>
                <div class="text-center">
                    <a href="shop-left-sidebar.html"
                        class="fw-semibold text-primary link-2 text-decoration-underline">
                        Or continue shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Shoping Cart -->

    @include('partials.quick-view')

    <!-- Wishlist -->
    <div class="offcanvas offcanvas-end canvas-wishlist" id="wishList">
        <div class="canvas-header">
            <h5 class="title-pop text-primary">Wish List</h5>
            <span class="icon-close-popup" data-bs-dismiss="offcanvas">
                <i class="icon icon-close"></i>
            </span>
        </div>
        <div class="canvas-body">
            <ul class="list-mini-product">
                <li class="mini-product-cart file-delete">
                    <div class="prd-wrap">
                        <div class="prd_image">
                            <img loading="lazy" width="100" height="100"
                                src="{{ asset('assets/images/product/product-12.jpg') }}" alt="Image">
                        </div>
                        <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                            Smackn’ Grapes Tomates - 1KG
                        </a>
                    </div>
                    <div class="prd-action">
                        <a href="#" class="tf-btn-line style-min p-0 remove">
                            <span class="text-caption-01">
                                Remove
                            </span>
                        </a>
                        <div class="quantity-price text-primary fw-medium">
                            <span>1</span>
                            <span>x</span>
                            <span>$3.99</span>
                        </div>
                    </div>
                </li>
                <li class="mini-product-cart file-delete">
                    <div class="prd-wrap">
                        <div class="prd_image">
                            <img loading="lazy" width="100" height="100"
                                src="{{ asset('assets/images/product/product-11.jpg') }}" alt="Image">
                        </div>
                        <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                            Range Large Brown Eggs, 18 Count
                        </a>
                    </div>
                    <div class="prd-action">
                        <a href="#" class="tf-btn-line style-min p-0 remove">
                            <span class="text-caption-01">
                                Remove
                            </span>
                        </a>
                        <div class="quantity-price text-primary fw-medium">
                            <span>1</span>
                            <span>x</span>
                            <span>$3.99</span>
                        </div>
                    </div>
                </li>
                <li class="mini-product-cart file-delete">
                    <div class="prd-wrap">
                        <div class="prd_image">
                            <img loading="lazy" width="100" height="100"
                                src="{{ asset('assets/images/product/product-17.jpg') }}" alt="Image">
                        </div>
                        <a href="product-single-1.html" class="prd_name link-underline text-primary fw-medium">
                            Earthbound Farm Organic Baby Spinach - 250GR
                        </a>
                    </div>
                    <div class="prd-action">
                        <a href="#" class="tf-btn-line style-min p-0 remove">
                            <span class="text-caption-01">
                                Remove
                            </span>
                        </a>
                        <div class="quantity-price text-primary fw-medium">
                            <span>1</span>
                            <span>x</span>
                            <span>$3.99</span>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="canvas-footer">
            <div class="d-flex flex-column align-items-center gap-16 text-center">
                <a href="https://tforvana.vercel.app/wishlist.html" class="tf-btn animate-btn w-100">
                    View All Wish List
                </a>
                <a href="{{ url('/') }}" class="tf-btn-line p-0">
                    Or continue shopping
                </a>
            </div>
        </div>
    </div>
    <!-- /Wishlist -->
    <!-- Register -->
    <div class="modal modalCentered fade modal-log register" id="register">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="icon-close-popup" data-bs-dismiss="modal">
                    <i class="icon icon-close"></i>
                </div>
                <h3 class="title-pop text-primary text-center">Register</h3>
                <form class="form-log">
                    <div class="form-content">
                        <fieldset class="tf-field">
                            <label for="email" class="tf-lable">Email<span class="text-secondary">*</span></label>
                            <input type="email" id="email" placeholder="Email" autocomplete="email"
                                required="">
                        </fieldset>
                        <fieldset class="tf-field ">
                            <label for="password" class="tf-lable ">Password<span
                                    class="text-secondary">*</span></label>
                            <div class="password-wrapper">
                                <input class="password-field" type="password" id="password" placeholder="Password"
                                    autocomplete="new-password" required="">
                                <span class="toggle-pass icon-eye-open"></span>
                            </div>
                        </fieldset>
                        <fieldset class="tf-field ">
                            <label for="password2" class="tf-lable ">Confirm password<span
                                    class="text-secondary">*</span></label>
                            <div class="password-wrapper">
                                <input class="password-field" type="password" id="password2"
                                    placeholder="Confirm password" autocomplete="new-password" required="">
                                <span class="toggle-pass icon-eye-open"></span>
                            </div>
                        </fieldset>
                        <div class="check-bottom">
                            <div class="checkbox-wrap">
                                <input id="agree" type="checkbox" class="tf-check style-4 radius-3">
                                <label for="agree" class="text-primary text-caption-01">
                                    I agree to the
                                    <a href="#"
                                        class="text-decoration-underline fw-medium text-primary link-2">Terms of
                                        User</a>
                                </label>
                            </div>

                        </div>
                        <button type="submit" class="tf-btn animate-btn w-100">
                            Create A New Account
                        </button>
                        <p class="dont-have text-caption-01 text-center">
                            Already have an account?
                            <a href="#login" data-bs-toggle="modal"
                                class=" text-decoration-underline text-primary fw-medium link-2">
                                Login Here
                            </a>
                        </p>
                        <p class="log-orther text-caption-01">
                            <span></span>
                            or sign up with
                            <span></span>
                        </p>
                        <div class="group-btn">
                            <a href="#" class="tf-btn style-stroke w-100">
                                <span class="icon">
                                    <img loading="lazy" width="24" height="24"
                                        src="{{ asset('assets/images/logo/face.svg') }}" alt="Image">
                                </span>
                                Facebook
                            </a>
                            <a href="#" class="tf-btn style-stroke w-100">
                                <span class="icon">
                                    <img loading="lazy" width="24" height="24"
                                        src="{{ asset('assets/images/logo/google.svg') }}" alt="Image">
                                </span>
                                Google
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Register -->
    <!-- Login -->
    <div class="modal modalCentered fade modal-log" id="login">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="icon-close-popup" data-bs-dismiss="modal">
                    <i class="icon icon-close"></i>
                </div>
                <h3 class="title-pop text-primary text-center">Log In</h3>
                <form class="form-log">
                    <div class="form-content">
                        <fieldset class="tf-field">
                            <label for="email3" class="tf-lable">Email<span class="text-secondary">*</span></label>
                            <input type="email" id="email3" placeholder="Email" autocomplete="email"
                                required="">
                        </fieldset>
                        <fieldset class="tf-field ">
                            <label for="password-log" class="tf-lable ">Password<span
                                    class="text-secondary">*</span></label>
                            <div class="password-wrapper">
                                <input class="password-field" type="password" id="password-log"
                                    placeholder="Password" autocomplete="current-password" required="">
                                <span class="toggle-pass icon-eye-open"></span>
                            </div>
                        </fieldset>
                        <div class="check-bottom">
                            <div class="checkbox-wrap">
                                <input id="save" type="checkbox" class="tf-check style-4 radius-3">
                                <label for="save" class="text-primary text-caption-01">
                                    Remember me
                                </label>
                            </div>
                            <a href="#forgotPassword" data-bs-toggle="modal"
                                class="text-caption-01 text-decoration-underline text-primary fw-medium link-2">
                                Forgot Your Password?
                            </a>
                        </div>
                        <button type="submit" class="tf-btn animate-btn w-100">
                            Login
                        </button>
                        <p class="dont-have text-caption-01 text-center">
                            Not registered yet?
                            <a href="#register" data-bs-toggle="modal"
                                class=" text-decoration-underline text-primary fw-medium link-2">
                                Sign Up
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Login -->
    <!-- Open Search -->
    <div class="offcanvas offcanvas-top offcanvas-search" id="canvasSearch">
        <div class="container">
            <div class="row">
                <div class="col-11 col-sm-8 offset-sm-2">
                    <div class="offcanvas-body">
                        <form action="#" class="form-search">
                            <fieldset>
                                <input type="text" placeholder="Search for anything" name="text"
                                    required="">
                            </fieldset>
                            <button type="submit" class="link-2">
                                <i class="icon icon-magnifying-glass"></i>
                            </button>
                        </form>
                    </div>
                    <span class="icon-close-popup icon-close" data-bs-dismiss="offcanvas">
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!-- End Open Search -->

    <!-- Javascript -->
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/carousel.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>
