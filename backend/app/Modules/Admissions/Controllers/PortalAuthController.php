<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PortalAuthController extends Controller
{
    public function loginForm(): View { return view('stsams.auth.login'); }
    public function registerForm(): View { return view('stsams.auth.register'); }

    public function register(Request $request, RegistrationService $registration): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $user = $registration->register($data);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('stsams.applicant.index')->with('success', 'Account created. Start your admission application.');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $key = 'stsams:login:' . hash('sha256', mb_strtolower($data['email']) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again shortly.']);
        }
        if (!Auth::attempt($data, false)) {
            RateLimiter::hit($key, 120);
            throw ValidationException::withMessages(['email' => 'These credentials are invalid.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->intended(route('stsams.dashboard'));
    }

    public function dashboard(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasRole('superadmin')) {
            return redirect()->route('stsams.staff-management.index');
        }
        if ($user->canAny(['applications.view_all', 'applications.review', 'applications.offer_admission', 'applications.reject'])) {
            return redirect()->route('stsams.staff.index');
        }
        if ($user->can('settings.manage')) {
            return redirect()->route('stsams.admin.catalogue');
        }
        if ($user->can('website.manage')) {
            return redirect()->route('institution.admin.index');
        }
        if ($user->hasRole('applicant')) {
            return redirect()->route('stsams.applicant.index');
        }
        abort(403, 'No portal workspace has been assigned to this account.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('stsams.home');
    }
}
