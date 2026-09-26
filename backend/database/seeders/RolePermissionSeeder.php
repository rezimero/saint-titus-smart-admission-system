<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach([
            'manage_users',
            'manage_applications',
            'review_applications',
            'approve_applications',
            'reject_applications',
            'view_reports'
        ] as $permission){
            Permission::firstOrCreate(['name'=>$permission]);
        }

        foreach([
            'superadmin',
            'admin',
            'admission_officer',
            'staff',
            'applicant'
        ] as $role){
            Role::firstOrCreate(['name'=>$role]);
        }
    }
}
