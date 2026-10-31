<?php

declare(strict_types=1);

use App\Modules\Admissions\Controllers\AdmissionReviewController;
use App\Modules\Admissions\Controllers\ApplicantController;
use App\Modules\Admissions\Controllers\CatalogueController;
use App\Modules\Admissions\Controllers\DocumentController;
use App\Modules\Admissions\Controllers\LandingController;
use App\Modules\Admissions\Controllers\PortalAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admissions')->name('stsams.')->group(function (): void {
    Route::get('/', [LandingController::class, 'index'])->name('home');
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [PortalAuthController::class, 'loginForm'])->name('login');
        Route::post('/login', [PortalAuthController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/register', [PortalAuthController::class, 'registerForm'])->name('register');
        Route::post('/register', [PortalAuthController::class, 'register'])->middleware('throttle:5,1');
    });
    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [PortalAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('/applicant', [ApplicantController::class, 'index'])->name('applicant.index');
        Route::post('/applicant/applications', [ApplicantController::class, 'create'])->name('applicant.create');
        Route::get('/applicant/applications/{application}', [ApplicantController::class, 'edit'])->name('applicant.edit');
        Route::post('/applicant/applications/{application}', [ApplicantController::class, 'update'])->name('applicant.update');
        Route::post('/applicant/applications/{application}/submit', [ApplicantController::class, 'submit'])->name('applicant.submit');
        Route::post('/applicant/applications/{application}/documents', [DocumentController::class, 'upload'])->name('documents.upload');
        Route::get('/applications/{application}/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('/staff', [AdmissionReviewController::class, 'index'])->name('staff.index');
        Route::get('/staff/applications/{application}', [AdmissionReviewController::class, 'show'])->name('staff.show');
        Route::post('/staff/applications/{application}/transition', [AdmissionReviewController::class, 'transition'])->name('staff.transition');
        Route::get('/admin/catalogue', [CatalogueController::class, 'index'])->name('admin.catalogue');
        Route::post('/admin/programmes', [CatalogueController::class, 'programme'])->name('admin.programme');
        Route::post('/admin/sessions', [CatalogueController::class, 'session'])->name('admin.session');
        Route::post('/admin/sessions/{session}/open', [CatalogueController::class, 'openSession'])->name('admin.session.open');
        Route::post('/admin/sessions/{session}/close', [CatalogueController::class, 'closeSession'])->name('admin.session.close');
    });
});

// Laravel's auth middleware needs a conventional named login route.
if (!Route::has('login')) {
    Route::get('/login', static fn () => redirect()->route('stsams.login'))->name('login');
}
