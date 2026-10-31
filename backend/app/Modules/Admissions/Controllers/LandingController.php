<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use Illuminate\View\View;

final class LandingController extends Controller
{
    public function index(): View
    {
        return view('stsams.home', [
            'programmes' => StsamsProgramme::query()->where('is_active', true)->orderBy('name')->get(),
            'sessions' => StsamsAdmissionSession::query()->where('is_open', true)->orderByDesc('id')->get(),
        ]);
    }
}
