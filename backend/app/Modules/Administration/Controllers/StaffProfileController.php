<?php

declare(strict_types=1);

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class StaffProfileController extends Controller
{
    private function staffUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasAnyRole([
            'superadmin', 'admin', 'admission_officer', 'staff', 'website_editor',
        ]), 403);

        return $user;
    }

    public function show(Request $request): View
    {
        $user = $this->staffUser($request);
        $profile = DB::table('staff_profiles')->where('user_id', $user->id)->first();

        return view('stsams.admin.my-profile', compact('user', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->staffUser($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'string', 'min:7', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);
        DB::transaction(function () use ($user, $data): void {
            $user->update(['name' => $data['name']]);
            $attributes = [
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'updated_at' => now(),
            ];
            if (DB::table('staff_profiles')->where('user_id', $user->id)->exists()) {
                DB::table('staff_profiles')->where('user_id', $user->id)->update($attributes);
            } else {
                DB::table('staff_profiles')->insert($attributes + [
                    'user_id' => $user->id,
                    'staff_number' => 'STS-STAFF-'.str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Your profile details have been updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = $this->staffUser($request);
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => [
                'required', 'confirmed', Password::min(16)->mixedCase()->numbers()->symbols(),
            ],
        ]);
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Choose a password that is different from your current password.',
            ]);
        }

        // AuthenticateSession is installed in the web group: older sessions are
        // rejected when their stored password hash no longer matches.
        Auth::logoutOtherDevices($data['current_password']);
        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();
            // Existing personal access tokens must not survive a credential rotation.
            $user->tokens()->delete();
        });
        $request->session()->regenerate();

        return redirect()->route('stsams.account.show')
            ->with('success', 'Password changed successfully. Other sessions have been invalidated.');
    }
}
