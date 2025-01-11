<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('payment', PaymentController::class)->names('payment');
});

Route::prefix('payment')->name('payment.')->controller(PaymentController::class)->group(function () {
    Route::post('initiate', 'initiatePayment')->name('initiate');
    Route::post('verify/{transactionId}', 'verifyPayment')->name('verify');
    Route::post('refund', 'refundPayment')->name('refund');
    Route::post('ipn/{provider}', 'handleIPN')->name('ipn');
});
