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
    <!-- <button id="goTop">
        <span class="border-progress"></span>
        <span class="icon icon-caret-up"></span>
    </button> -->

    <!-- Preload -->
    <div class="preload preload-container" id="preload">
        <div class="preload-logo">
            <div class="spinner"></div>
        </div>
    </div>
    <!-- /Preload -->

    <main id="wrapper">
        <!-- Top Bar-->
        <div class="tf-topbar style-2 d-none d-md-block">
            <div class="container">
                <div class="topbar-inner">
                    <div class="topbar-left ">
                        <p class="text-caption-01">
                            Wellcome to Fresin Farm
                        </p>
                    </div>
                    <div class="topbar-right d-none d-sm-flex">
                        <ul class="tf-list">
                            <li class="d-none d-md-flex">
                                <a href="#" class="info link-primary">
                                    <i class="icon icon-EnvelopeSimple fs-24"></i>
                                    themesflat@gmail.com
                                </a>
                            </li>
                            <li class="br-line type-vertical d-none d-md-flex"></li>
                            <li>
                                <p class="info">
                                    <i class="icon icon-EnvelopeSimple fs-24"></i>
                                    Mon-Sat: 7.00-19.00
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Top Bar -->
        <!-- Header -->
        <header class="tf-header style-2">
            <div class="container">
                <div class="header-inner">
                    <div class="box-btn-menu d-xl-none">
                        <a href="#mobileMenu" data-bs-toggle="offcanvas" class="btn-mobile-menu">
                            <span></span>
                        </a>
                    </div>
                    <div class="header-left">
                        <a href="{{ url('/') }}" class="logo-site">
                            <img loading="lazy" width="207" height="48" src="{{ asset('assets/images/logo/logo-default.svg') }}"
                                alt="Logo">
                        </a>
                        <nav class="box-navigation d-none d-xl-block">
                            <ul class="box-nav-menu">
                                <li class="menu-item">
                                    <a href="#" class="item-link lh-28">
                                        <span class="text">
                                            Home
                                            <i class="icon icon-caret-down"></i>
                                        </span>
                                    </a>
                                    <div class="sub-menu">
                                        <ul class="sub-menu_list">
                                            <li><a href="{{ url('/') }}" class="sub-menu_link">Homepage 1</a></li>
                                            <li><a href="homepage-2.html" class="sub-menu_link">Homepage 2</a></li>
                                            <li><a href="homepage-3.html" class="sub-menu_link">Homepage 3</a></li>
                                            <li><a href="{{ url('/') }}" class="sub-menu_link">Homepage 4</a></li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="menu-item">
                                    <a href="#" class="item-link lh-28">
                                        <span class="text">
                                            Shop
                                            <i class="icon icon-caret-down"></i>
                                        </span>
                                    </a>
                                    <div class="sub-menu">
                                        <ul class="sub-menu_list">
                                            <li><a href="shop-left-sidebar.html" class="sub-menu_link">Shop Left
                                                    Sidebar</a>
                                            </li>
                                            <li><a href="shop-right-sidebar.html" class="sub-menu_link">Shop Right
                                                    Sidebar</a></li>
                                            <li><a href="shop-filter-sidebar.html" class="sub-menu_link">Shop Filter
                                                    Canvas</a></li>
                                            <li><a href="shopping-cart.html" class="sub-menu_link">Shopping Cart</a>
                                            </li>
                                            <li><a href="check-out.html" class="sub-menu_link">Check Out</a></li>

                                        </ul>
                                    </div>
                                </li>
                                <li class="menu-item">
                                    <a href="#" class="item-link lh-28">
                                        <span class="text">
                                            Products
                                            <i class="icon icon-caret-down"></i>
                                        </span>
                                    </a>
                                    <div class="sub-menu">
                                        <ul class="sub-menu_list">
                                            <li><a href="product-single-1.html" class="sub-menu_link">Product Single
                                                    1</a>
                                            </li>
                                            <li><a href="product-single-2.html" class="sub-menu_link">Product Single
                                                    2</a>
                                            </li>
                                            <li><a href="product-single-3.html" class="sub-menu_link">Product Single
                                                    3</a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="menu-item">
                                    <a href="#" class="item-link lh-28">
                                        <span class="text">
                                            Pages
                                            <i class="icon icon-caret-down"></i>
                                        </span>
                                    </a>
                                    <div class="sub-menu">
                                        <ul class="sub-menu_list">
                                            <li><a href="about-us.html" class="sub-menu_link">About Us</a></li>
                                            <li><a href="pricing-plan.html" class="sub-menu_link">Pricing Plans</a></li>
                                            <li><a href="contact.html" class="sub-menu_link">Contact Us</a></li>
                                            <li class="has-menu_lv2">
                                                <a href="#" class="sub-menu_link">
                                                    <span class="text">
                                                        Our Services
                                                    </span>
                                                    <i class="icon icon-caret-right"></i>
                                                </a>
                                                <ul class="sub-menu-lv2">
                                                    <li><a href="our-service-1.html" class="sub-menu_link">Our Services
                                                            1</a></li>
                                                    <li><a href="our-service-2.html" class="sub-menu_link">Our Services
                                                            2</a></li>
                                                    <li><a href="our-service-3.html" class="sub-menu_link">Our Services
                                                            3</a></li>
                                                    <li><a href="service-detail.html" class="sub-menu_link">Services
                                                            Details</a></li>
                                                </ul>
                                            </li>
                                            <li><a href="faq.html" class="sub-menu_link">FAQs</a></li>
                                            <li><a href="privacy-policy.html" class="sub-menu_link">Privacy Policy</a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <div class="header-right">
                        <div class="box-nav-icon">
                            {{-- <div class="br-line d-none d-md-inline-flex"></div> --}}
                            <ul class="nav-icon-list">
                                <li>
                                    <a href="#canvasSearch" data-bs-toggle="offcanvas"
                                        class="nav-icon-item text-primary link d-none d-md-flex">
                                        <i class="icon icon-magnifying-glass"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#login" data-bs-toggle="modal" class="nav-icon-item text-primary link">
                                        <i class="icon icon-user"></i>
                                    </a>
                                </li>
                                <li class="d-none d-sm-block">
                                    <a href="#wishList" data-bs-toggle="offcanvas"
                                        class="nav-icon-item text-primary link">
                                        <i class="icon icon-heart-stroke"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#shoppingCart" data-bs-toggle="offcanvas"
                                        class="nav-icon-item text-primary link has-num">
                                        <i class="icon icon-ShoppingCartSimple"></i>
                                        <span class="number-count text-caption-02">
                                            3
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="box-nav-support d-none d-xxl-flex">
                            <div class="ic-wrap">
                                <i class="icon icon-phone-call"></i>
                            </div>
                            <div class="info">
                                <p class="title text-caption-01">
                                    Have any Question?
                                </p>
                                <a href="tel:3156666688" class="h6 fw-medium">
                                    315-666-6688
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- /Header -->
        <!-- Slide Show -->
        <div class="container home-hero-slider-wrap">
            <div class="tf-slideshow home-hero-slider tf-btn-swiper-main hover-sw-nav">
                <div dir="ltr" class="swiper tf-swiper sw-slide-show slider_effect_fade" data-auto="true"
                    data-loop="true" data-effect="fade" data-delay="3000">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="slider-wrap">
                                <div class="sld_image">
                                    <img loading="lazy" width="1920" height="860"
                                        src="{{ asset('assets/images/slider/slider-10.jpg') }}" alt="Slider">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider-wrap">
                                <div class="sld_image">
                                    <img loading="lazy" width="1920" height="860"
                                        src="{{ asset('assets/images/slider/slider-11.jpg') }}" alt="Slider">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="slider-wrap">
                                <div class="sld_image">
                                    <img loading="lazy" width="1920" height="860"
                                        src="{{ asset('assets/images/slider/slider-12.jpg') }}" alt="Slider">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="sw-dot-default style-white tf-sw-pagination"></div>
                </div>

                <div class="group-btn">
                    <div class="tf-sw-nav style-2 nav-prev-swiper">
                        <i class="icon icon-caret-left"></i>
                    </div>
                    <div class="tf-sw-nav style-2 nav-next-swiper">
                        <i class="icon icon-caret-right"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Slide Show -->
        <!-- Footer -->
        <footer class="tf-footer">
            <div class="footer-inner-wrap">
                <div class="container">
                    <div class="footer-inner">
                        <div class="inner-left">
                            <a href="{{ url('/') }}" class="logo-site">
                                <img loading="lazy" width="207" height="48" src="{{ asset('assets/images/logo/logo-white.svg') }}"
                                    alt="">
                            </a>
                            <h5 class="title text-white font-main">
                                Subscribe For All The Top News!
                            </h5>
                            <p class="sub-title text-caption-01 text-white">
                                Sign up for updates on our latest news and events.
                            </p>
                            <form class="form-subcribe">
                                <input class="style-2" name="email" type="email" placeholder="Enter your email address"
                                    required>
                                <button type="submit" class="btn-submit">
                                    <i class="icon icon-attached"></i>
                                </button>
                            </form>
                        </div>
                        <div class="inner-center">
                            <div class="footer-col-block">
                                <p class="footer-heading footer-heading-mobile text-caption-02">
                                    QUICK LINK
                                </p>
                                <div class="tf-collapse-content">
                                    <ul class="footer-menu-list">
                                        <li><a href="shop-left-sidebar.html" class="link">Vegetables</a></li>
                                        <li><a href="shop-left-sidebar.html" class="link">Fruits</a></li>
                                        <li><a href="shop-left-sidebar.html" class="link">Dried Goods</a></li>
                                        <li><a href="shop-left-sidebar.html" class="link">Leafy Greens</a></li>
                                        <li><a href="shop-left-sidebar.html" class="link">Organic Herbs</a></li>
                                        <li><a href="shop-left-sidebar.html" class="link">Weekly Veggie Box</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="footer-col-block">
                                <p class="footer-heading footer-heading-mobile text-caption-02">
                                    COMPANY
                                </p>
                                <div class="tf-collapse-content">
                                    <ul class="footer-menu-list">
                                        <li><a href="about-us.html" class="link">About us</a></li>
                                        <li><a href="privacy-policy.html" class="link">How to Order</a></li>
                                        <li><a href="about-us.html" class="link">Our Team</a></li>
                                        <li><a href="our-service-1.html" class="link">Services</a></li>
                                        <li><a href="contact.html" class="link">Contact Us</a></li>
                                        <li><a href="faq.html" class="link">FAQs</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="inner-right">
                            <div class="footer-col-block">
                                <p class="footer-heading footer-heading-mobile text-caption-02">
                                    CONTACT
                                </p>
                                <div class="tf-collapse-content">
                                    <div class="footer-info">
                                        <a href="#" class="link infor-address font-main">101 E 129th St, Chicago, New
                                            York</a>
                                        <a href="#" class="link infor-phone font-main">1-555-678-8888</a>
                                        <a href="#"
                                            class="link infor-email font-main text-decoration-underline">themesflat@gmail.com</a>
                                        <ul class="social-list">
                                            <li><a href="#" class="link"><i class="icon icon-FacebookLogo"></i></a></li>
                                            <li><a href="#" class="link"><i class="icon icon-XLogo"></i></a></li>
                                            <li><a href="#" class="link"><i class="icon icon-TiktokLogo"></i></a></li>
                                            <li><a href="#" class="link"><i class="icon icon-InstagramLogo"></i></a>
                                            </li>
                                            <li><a href="#" class="link"><i class="icon icon-YoutubeLogo"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="container">
                    <div class="br-line ver-abs top-0 bg-white_10"></div>
                    <p class="text-copy text-caption-01 fw-medium text-white">
                        ©2026 Orvana. All Rights Reserved.
                    </p>
                </div>
            </div>
        </footer>
        <!-- /Footer -->
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
                            <input type="email" id="email2" placeholder="Email" autocomplete="email" required="">
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
                    <a href="shop-left-sidebar.html" class="fw-semibold text-primary link-2 text-decoration-underline">
                        Or continue shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- /Shoping Cart -->
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
                            <img loading="lazy" width="100" height="100" src="{{ asset('assets/images/product/product-12.jpg') }}"
                                alt="Image">
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
                            <img loading="lazy" width="100" height="100" src="{{ asset('assets/images/product/product-11.jpg') }}"
                                alt="Image">
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
                            <img loading="lazy" width="100" height="100" src="{{ asset('assets/images/product/product-17.jpg') }}"
                                alt="Image">
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
                            <input type="email" id="email" placeholder="Email" autocomplete="email" required="">
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
                                    <a href="#" class="text-decoration-underline fw-medium text-primary link-2">Terms of
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
                                    <img loading="lazy" width="24" height="24" src="{{ asset('assets/images/logo/face.svg') }}"
                                        alt="Image">
                                </span>
                                Facebook
                            </a>
                            <a href="#" class="tf-btn style-stroke w-100">
                                <span class="icon">
                                    <img loading="lazy" width="24" height="24" src="{{ asset('assets/images/logo/google.svg') }}"
                                        alt="Image">
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
                            <input type="email" id="email3" placeholder="Email" autocomplete="email" required="">
                        </fieldset>
                        <fieldset class="tf-field ">
                            <label for="password-log" class="tf-lable ">Password<span
                                    class="text-secondary">*</span></label>
                            <div class="password-wrapper">
                                <input class="password-field" type="password" id="password-log" placeholder="Password"
                                    autocomplete="current-password" required="">
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
                                <input type="text" placeholder="Search for anything" name="text" required="">
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
