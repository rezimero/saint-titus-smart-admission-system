<?php

declare(strict_types=1);

use App\Modules\Operations\Controllers\AssignmentController;
use App\Modules\Operations\Controllers\DocumentReviewController;
use App\Modules\Operations\Controllers\OperationsAuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->group(function (): void {
    Route::post('admissions/staff/applications/{application}/claim', [AssignmentController::class, 'claim'])->name('stsams.ops.claim');
    Route::post('admissions/staff/applications/{application}/assign', [AssignmentController::class, 'assign'])->name('stsams.ops.assign');
    Route::post('admissions/staff/applications/{application}/documents/{document}/review', [DocumentReviewController::class, 'decide'])->name('stsams.ops.document.review');
    Route::get('system-admin/audit', [OperationsAuditController::class, 'index'])->name('stsams.ops.audit');
});
