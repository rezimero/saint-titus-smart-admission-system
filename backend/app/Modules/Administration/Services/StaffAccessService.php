<?php

declare(strict_types=1);

namespace App\Modules\Administration\Services;

use App\Models\StsamsStaffAccessAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class StaffAccessService
{
    public const ROLES = ['admin', 'admission_officer', 'staff', 'website_editor'];
    public const GRANTS = [
        'applications.view_all', 'applications.review', 'applications.offer_admission',
        'applications.reject', 'settings.manage', 'website.manage', 'website.publish', 'reports.view',
        'applications.assign', 'reports.audit',
    ];

    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->forceFill(['must_change_password' => true])->save();
            DB::table('staff_profiles')->insert([
                'user_id' => $user->id,
                'staff_number' => 'STS-STAFF-'.str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->setAccess($user, $data['role'], $data['permissions'] ?? []);
            $this->audit($actor, $user, 'staff_created', null);
            return $user;
        });
    }

    public function update(User $actor, User $user, array $data): void
    {
        DB::transaction(function () use ($actor, $user, $data): void {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($target->hasRole('superadmin') || $target->hasRole('applicant')) {
                throw ValidationException::withMessages(['staff' => 'Protected accounts cannot be modified here.']);
            }
            $before = $this->access($target);
            $target->update(['name' => $data['name']]);
            $this->setAccess($target, $data['role'], $data['permissions'] ?? []);
            $this->audit($actor, $target, 'staff_permissions_updated', $before);
        });
    }

    private function setAccess(User $user, string $role, array $permissions): void
    {
        if (!in_array($role, self::ROLES, true) || array_diff($permissions, self::GRANTS)) {
            throw ValidationException::withMessages(['role' => 'Invalid staff role or permission.']);
        }
        // Only one managed staff role. Never grant superadmin through the web UI.
        $user->syncRoles([$role]);
        $user->syncPermissions(array_values(array_unique($permissions)));
    }

    private function access(User $user): array
    {
        return [
            'role' => $user->getRoleNames()->all(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->all(),
        ];
    }

    private function audit(User $actor, User $user, string $action, ?array $before): void
    {
        StsamsStaffAccessAudit::create([
            'actor_id' => $actor->id, 'staff_user_id' => $user->id,
            'action' => $action, 'before_access' => $before,
            'after_access' => $this->access($user->fresh()),
        ]);
    }
}
