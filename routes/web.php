<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes - Authorize.Net Payment Gateway Integration
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('payment.form');
});

Route::prefix('payment')->name('payment.')->group(function () {

    // Payment Form & Processing (Supports Credit Card, eCheck, Saved Cards)
    Route::get('/', [PaymentController::class, 'showPaymentForm'])->name('form');
    Route::post('/process', [PaymentController::class, 'processPayment'])->name('process');

    // Success & Receipt Views
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/receipt', [PaymentController::class, 'receipt'])->name('receipt');

    // Printable & Downloadable PDF Invoice
    Route::get('/invoice/{id}', [PaymentController::class, 'invoice'])->name('invoice');

    // Transaction History
    Route::get('/history', [PaymentController::class, 'history'])->name('history');

    // Refund & Void Operations
    Route::post('/refund/{id}', [PaymentController::class, 'refund'])->name('refund');
    Route::post('/void/{id}', [PaymentController::class, 'void'])->name('void');
    Route::get('/refunds', [PaymentController::class, 'refundsHistory'])->name('refunds');

    // Tokenized Saved Cards (CIM)
    Route::get('/saved-cards', [PaymentController::class, 'savedCards'])->name('saved-cards');
    Route::delete('/saved-cards/{id}', [PaymentController::class, 'deleteSavedCard'])->name('saved-cards.delete');
    Route::post('/saved-cards/{id}/default', [PaymentController::class, 'setDefaultSavedCard'])->name('saved-cards.default');

    // Dashboard & Chart.js Analytics API
    Route::get('/dashboard', [PaymentController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/analytics', [PaymentController::class, 'analytics'])->name('analytics');

    // Export CSV
    Route::get('/export', [PaymentController::class, 'export'])->name('export');

    // Manual Status Override (Admin)
    Route::put('/status/success/{payment}', [PaymentController::class, 'markSuccess'])->name('success.status');
    Route::put('/status/failed/{payment}', [PaymentController::class, 'markFailed'])->name('failed');

    // Delete
    Route::delete('/delete/{payment}', [PaymentController::class, 'destroy'])->name('destroy');

    // Test Gateway Connection
    Route::get('/test-connection', [PaymentController::class, 'testConnection'])->name('test.connection');
});
