<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    /** Canonical role matrix. Only these role grants are synchronized. */
    private const ROLE_PERMISSIONS = [
        'superadmin' => '*',
        'admin' => [
            'users.view', 'users.create', 'users.update',
            'applications.view', 'applications.create',
            'applications.review', 'applications.approve', 'applications.reject',
            'reports.view',
        ],
        'admission_officer' => [
            'applications.view', 'applications.review', 'reports.view',
        ],
        'staff' => ['applications.view'],
        'applicant' => ['applications.view', 'applications.create'],
    ];

    private const PERMISSIONS = [
        'users.view', 'users.create', 'users.update', 'users.delete',
        'applications.view', 'applications.create', 'applications.review',
        'applications.approve', 'applications.reject',
        'roles.manage', 'settings.manage', 'reports.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions(
                $permissions === '*'
                    ? Permission::query()->where('guard_name', 'web')->get()
                    : $permissions
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
