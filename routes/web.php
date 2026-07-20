<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return redirect()->route('payment.form');
});

Route::prefix('payment')->name('payment.')->group(function () {

    // Payment Form
    Route::get('/', [PaymentController::class, 'showPaymentForm'])->name('form');

    // Process Payment
    Route::post('/process', [PaymentController::class, 'processPayment'])->name('process');

    // Success
    Route::get('/success', [PaymentController::class, 'success'])->name('success');

    // Receipt
    Route::get('/receipt', [PaymentController::class, 'receipt'])->name('receipt');

    // History
    Route::get('/history', [PaymentController::class, 'history'])->name('history');

    // Dashboard
    Route::get('/dashboard', [PaymentController::class, 'dashboard'])->name('dashboard');

    // Export CSV
    Route::get('/export', [PaymentController::class, 'export'])->name('export');

    // Change Status
    Route::put('/status/success/{payment}', [PaymentController::class, 'markSuccess'])
        ->name('success.status');

    Route::put('/status/failed/{payment}', [PaymentController::class, 'markFailed'])
        ->name('failed');

    // Delete
    Route::delete('/delete/{payment}', [PaymentController::class, 'destroy'])
        ->name('destroy');

    // Test Connection
    Route::get('/test-connection', [PaymentController::class, 'testConnection'])
        ->name('test.connection');
});
