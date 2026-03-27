<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('payment/return', [PaymentController::class, 'handleReturn'])
    ->name('payment.return');
