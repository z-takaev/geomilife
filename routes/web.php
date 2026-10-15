<?php

declare(strict_types=1);

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/contacts', ContactController::class)->name('contacts.index');

Route::get('/catalog', CatalogController::class)->name('catalog.index');

Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/checkout', CheckoutController::class)->name('checkout.index');

Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');

Route::get('/reviews', ReviewController::class)->name('reviews.index');

Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');

Route::controller(NewsController::class)->prefix('news')->name('news.')->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/{news}', 'show')->name('show');
});

Route::controller(ArticleController::class)->prefix('articles')->name('articles.')->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/{article}', 'show')->name('show');
});

Route::controller(PromoController::class)->prefix('promos')->name('promos.')->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/{promo}', 'show')->name('show');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', ProfileController::class)->name('profile.show');

    Route::controller(OrderController::class)->prefix('profile/orders')->name('orders.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{order}', 'show')->name('show');
    });
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
