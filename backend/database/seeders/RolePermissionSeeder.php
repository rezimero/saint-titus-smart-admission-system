<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'staff.manage', 'website.manage', 'website.publish', 'settings.manage', 'reports.audit',
        'applications.view', 'applications.view_all', 'applications.create', 'applications.review',
        'applications.offer_admission', 'applications.reject', 'applications.assign',
        'reports.view', 'roles.manage',
    ];

    // Crucial: ordinary admins and reviewers never inherit the offer permission.
    private const ROLE_MATRIX = [
        'admin' => ['applications.view_all', 'applications.review', 'reports.view'],
        'admission_officer' => ['applications.view_all', 'applications.review'],
        'staff' => [],
        'website_editor' => ['website.manage'],
        'applicant' => ['applications.view', 'applications.create'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (self::ROLE_MATRIX as $name => $permissions) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
                ->syncPermissions($permissions);
        }
        // Gate::before grants the superadmin access to future abilities too.
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::where('guard_name', 'web')->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
