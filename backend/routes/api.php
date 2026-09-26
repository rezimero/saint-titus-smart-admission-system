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
