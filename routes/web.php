<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\CategoryController;
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

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');
