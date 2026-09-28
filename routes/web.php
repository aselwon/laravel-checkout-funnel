<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('variant')->group(function () {
    Route::get('/', [FunnelController::class, 'landing'])->name('home');
    Route::post('/signup', [FunnelController::class, 'signup'])->middleware('throttle:20,1')->name('signup');
});
Route::middleware('lead')->group(function () {
    Route::get('/onboarding', [FunnelController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [FunnelController::class, 'checklist'])->name('checklist');
    Route::get('/growth-kit', [FunnelController::class, 'growth'])->middleware('paid')->name('growth');
    Route::get('/checkout/{purchase}/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::middleware('paid.enabled')->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
        Route::get('/checkout/{purchase}/mock', [CheckoutController::class, 'mock'])->name('checkout.mock');
        Route::post('/checkout/{purchase}/mock', [CheckoutController::class, 'completeMock'])->name('checkout.complete');
    });
});
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::get('/admin/login', [AdminController::class, 'login'])->name('login');
Route::post('/admin/login', [AdminController::class, 'authenticate'])->middleware('throttle:20,1')->name('admin.authenticate');
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
});
