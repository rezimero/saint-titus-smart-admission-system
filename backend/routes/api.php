<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Identity\Controllers\AuthController;

Route::prefix('v1')->group(function(){

    Route::post('/auth/register',[AuthController::class,'register']);
    Route::post('/auth/login',[AuthController::class,'login']);

    Route::middleware('auth:sanctum')->group(function(){
        Route::post('/auth/logout',[AuthController::class,'logout']);
        Route::get('/auth/me',[AuthController::class,'me']);
    });

});


// STSAMS Paystack signed webhook; API routes are CSRF-exempt. Verified against Paystack API before payment approval.
\Illuminate\Support\Facades\Route::post('/v1/payments/paystack/webhook', \App\Modules\Admissions\Controllers\PaystackWebhookController::class)->middleware('throttle:60,1')->name('stsams.payments.webhook');
