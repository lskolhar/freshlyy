<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegisterUserController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
 Home
*/
Route::get('/', function () {
    return view('home');
})->name('home');

/*
 Authentication (Guests only)
*/
Route::middleware('guest')->group(function () {

    // Login form
    Route::get('/login', [SessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [SessionController::class, 'store']);

    // Register form
    Route::get('/register', [RegisterUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterUserController::class, 'store']);
});

/*
 Logout (Authenticated users)

*/
Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
 Protected Pages (Authenticated users)
*/
Route::middleware('auth')->group(function () {
    Route::get('/category/{slug}', [CategoryController::class, 'show']);
});

/*
 Cart (optional: keep public or protect later)
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
});

// admin only
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

Route::get('/orders', [OrderController::class, 'index'])
    ->middleware('auth')
    ->name('orders.index');

Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
    ->middleware('auth')
    ->name('orders.updateStatus');

Route::middleware('auth')->post('/payment/create', [PaymentController::class, 'create']);

Route::post('/checkout', [PaymentController::class, 'initiatePayment'])
    ->middleware('auth')
    ->name('checkout.initiate');

Route::post('/payment/return', [PaymentController::class, 'handleReturn'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payment.return');

Route::get('/payment/success/{order}', [PaymentController::class, 'success'])
    ->middleware('auth')
    ->name('payment.success');

Route::get('/payment/return', [PaymentController::class, 'handleRedirect'])
    ->middleware('auth')
    ->name('payment.redirect');
