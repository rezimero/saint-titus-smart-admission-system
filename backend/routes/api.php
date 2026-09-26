<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Identity\Controllers\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});
