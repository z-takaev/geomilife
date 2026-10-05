<?php

declare(strict_types=1);

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PromoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

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

Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
