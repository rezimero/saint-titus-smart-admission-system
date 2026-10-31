<?php

use App\Modules\Institutional\Controllers\PublicWebsiteController;
use App\Modules\Institutional\Controllers\WebsiteAdminController;
use Illuminate\Support\Facades\Route;

// Institutional site. Existing /admissions routes remain owned by the admission module.
Route::get('/', [PublicWebsiteController::class, 'home'])->name('institution.home');
Route::get('/about', fn (PublicWebsiteController $site) => $site->page('about'))->name('institution.about');
Route::get('/academics', fn (PublicWebsiteController $site) => $site->page('academics'))->name('institution.academics');
Route::get('/admissions-info', fn (PublicWebsiteController $site) => $site->page('admissions'))->name('institution.admissions');
Route::get('/campus-life', fn (PublicWebsiteController $site) => $site->page('campus'))->name('institution.campus');
Route::get('/news-events', fn (PublicWebsiteController $site) => $site->page('news'))->name('institution.news');
Route::get('/news-events/{slug}', [PublicWebsiteController::class, 'post'])->name('institution.post');
Route::get('/administration', fn (PublicWebsiteController $site) => $site->page('administration'))->name('institution.administration');
Route::get('/contact', [PublicWebsiteController::class, 'contact'])->name('institution.contact');
Route::post('/contact', [PublicWebsiteController::class, 'sendContact'])
    ->middleware('throttle:3,1')->name('institution.contact.send');

Route::prefix('institution-admin')->name('institution.admin.')->group(function () {
    Route::get('/', [WebsiteAdminController::class, 'dashboard'])->name('index');
    Route::post('/content', [WebsiteAdminController::class, 'saveContent'])->name('content');
    Route::post('/posts', [WebsiteAdminController::class, 'savePost'])->name('posts.create');
    Route::put('/posts/{post}', [WebsiteAdminController::class, 'savePost'])->name('posts.update');
    Route::delete('/posts/{post}', [WebsiteAdminController::class, 'deletePost'])->name('posts.delete');
    Route::post('/media', [WebsiteAdminController::class, 'uploadMedia'])->name('media.create');
    Route::delete('/media/{media}', [WebsiteAdminController::class, 'deleteMedia'])->name('media.delete');
    Route::patch('/inquiries/{inquiry}', [WebsiteAdminController::class, 'resolveInquiry'])->name('inquiries.update');
});
