<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RegisterUserController;

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

    // Login / Register option pages
    Route::get('/login-options', fn () => view('auth.login-options'))
        ->name('login-options');

    Route::get('/register-options', fn () => view('auth.register-options'))
        ->name('register-options');

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

    // Dashboard
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    // Categories (Dairy, Vegetables, Fruits, Meat)
    Route::get('/category/{slug}', [CategoryController::class, 'show']);
});

/*
 Cart (optional: keep public or protect later)
*/
Route::get('/cart', function () {
    $cart = session()->get('cart', []);
    return view('cart', compact('cart'));
});

// admin only 
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
