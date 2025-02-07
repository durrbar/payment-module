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
    Route::prefix('payment')->name('payment.')->controller(PaymentController::class)->group(function () {
        Route::post('make', 'makePayment')->name('make');
        Route::post('verify/{transactionId}', 'verifyPayment')->name('verify');
        Route::post('refund', 'refundPayment')->name('refund');
        Route::post('discount', 'applyDiscount')->name('discount');
        Route::withoutMiddleware(['auth:sanctum'])->group(function () {
            Route::post('ipn/{provider}', 'ipn')->name('ipn');
            Route::post('success/{provider}', 'success')->name('success');
            Route::post('fail/{provider}', 'fail')->name('fail');
            Route::post('cancel/{provider}', 'cancel')->name('cancel');
        });
    });
});
