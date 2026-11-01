<?php

declare(strict_types=1);

use App\Modules\Administration\Controllers\StaffProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->prefix('account')->name('stsams.account.')->group(function (): void {
    Route::get('/profile', [StaffProfileController::class, 'show'])->name('show');
    Route::patch('/profile', [StaffProfileController::class, 'update'])->name('update');
    Route::put('/password', [StaffProfileController::class, 'password'])
        ->middleware('throttle:5,1')->name('password');
});
