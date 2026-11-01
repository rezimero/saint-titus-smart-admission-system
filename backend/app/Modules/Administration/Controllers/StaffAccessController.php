<?php

declare(strict_types=1);

namespace App\Modules\Administration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsStaffAccessAudit;
use App\Models\User;
use App\Modules\Administration\Services\StaffAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class StaffAccessController extends Controller
{
    private function authorizeSuperadmin(Request $request): void
    {
        Gate::authorize('staff.manage');
        abort_unless($request->user()?->hasRole('superadmin'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeSuperadmin($request);
        $staff = User::role(StaffAccessService::ROLES)
            ->with(['roles', 'permissions'])->orderBy('name')->paginate(25);
        return view('stsams.admin.staff-access', [
            'staff' => $staff,
            'roles' => StaffAccessService::ROLES,
            'grantable' => StaffAccessService::GRANTS,
            'audit' => StsamsStaffAccessAudit::with(['actor', 'staffUser'])->latest()->limit(30)->get(),
        ]);
    }

    public function store(Request $request, StaffAccessService $service): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:16', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'],
            'role' => ['required', Rule::in(StaffAccessService::ROLES)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', Rule::in(StaffAccessService::GRANTS)],
        ]);
        $service->create($request->user(), $data);
        return back()->with('success', 'Staff account created. Send initial credentials through a secure channel.');
    }

    public function update(Request $request, User $user, StaffAccessService $service): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->hasRole('superadmin'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'role' => ['required', Rule::in(StaffAccessService::ROLES)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', Rule::in(StaffAccessService::GRANTS)],
        ]);
        $service->update($request->user(), $user, $data);
        return back()->with('success', 'Staff authorization updated and audited.');
    }
}
