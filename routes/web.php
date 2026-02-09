<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\RegisterUserController;

Route::get('/', function () {
    return view('home');
})->name('home');


Route::get('/category/{slug}', [CategoryController::class, 'show']);

Route::get('/login', fn () => view('auth.login'))->name('login');
Route::get('/register', fn () => view('auth.register'))->name('register');


Route::get('/register', [RegisterUserController::class, 'create']);
Route::post('/register', [RegisterUserController::class, 'store']);

// login
Route::get('/login', [SessionController::class, 'create'])->name('login');
Route::post('/login', [SessionController::class, 'store']);
Route::post('/logout', [SessionController::class, 'destroy']);

Route::get('/cart', function () {
    $cart = session()->get('cart', []);

    return view('cart', compact('cart'));
});



// USER ROUTES
Route::middleware(['auth'])->group(function () {
    Route::post('/cart/add/{product}', [CartController::class, 'add']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/checkout', [OrderController::class, 'store']);
});

// ADMIN ROUTES
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index']);
    Route::resource('/products', AdminProductController::class);
    Route::get('/orders', [AdminOrderController::class, 'index']);
});


Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');
