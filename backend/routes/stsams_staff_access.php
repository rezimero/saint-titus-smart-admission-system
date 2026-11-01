<?php

declare(strict_types=1);

use App\Modules\Administration\Controllers\StaffAccessController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('system-admin')->name('stsams.staff-management.')->group(function (): void {
    Route::get('/staff', [StaffAccessController::class, 'index'])->name('index');
    Route::post('/staff', [StaffAccessController::class, 'store'])->name('store');
    Route::patch('/staff/{user}', [StaffAccessController::class, 'update'])->name('update');
});
