<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return redirect()->route('payment.form');
});

// Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/', [PaymentController::class, 'showPaymentForm'])->name('form');
    Route::post('/process', [PaymentController::class, 'processPayment'])->name('process');
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/history', [PaymentController::class, 'history'])->name('history');
});
