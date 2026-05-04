<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| SADAD Payment Gateway Routes
|--------------------------------------------------------------------------
*/
Route::prefix('payment')->name('payment.')->group(function () {
    // Checkout form (customer-facing)
    Route::get('/checkout', [PaymentController::class, 'checkout'])->name('checkout');

    // Initiate payment — validates input, creates transaction, returns auto-submit form
    Route::post('/initiate', [PaymentController::class, 'initiate'])->name('initiate');

    // Browser-based callback from SADAD (POST, form-urlencoded)
    Route::post('/callback', [PaymentController::class, 'callback'])->name('callback');

    // Result pages
    Route::get('/success/{orderId}', [PaymentController::class, 'success'])->name('success');
    Route::get('/failed/{orderId}', [PaymentController::class, 'failed'])->name('failed');
    Route::get('/pending/{orderId}', [PaymentController::class, 'pending'])->name('pending');

    // AJAX status polling
    Route::get('/status/{orderId}', [PaymentController::class, 'status'])->name('status');
});

// SADAD server-to-server webhook (JSON POST, CSRF excluded via bootstrap/app.php)
Route::post('/payment/webhook', [WebhookController::class, 'handle'])->name('payment.webhook');
