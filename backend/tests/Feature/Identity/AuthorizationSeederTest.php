<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AuthorizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeding_is_idempotent_and_assigns_least_privilege(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(5, Role::query()->where('guard_name', 'web')->count());
        $this->assertSame(12, Permission::query()->where('guard_name', 'web')->count());

        $applicant = Role::findByName('applicant', 'web');
        $this->assertTrue($applicant->hasPermissionTo('applications.create'));
        $this->assertFalse($applicant->hasPermissionTo('applications.approve'));
        $this->assertFalse($applicant->hasPermissionTo('users.delete'));

        $officer = Role::findByName('admission_officer', 'web');
        $this->assertTrue($officer->hasPermissionTo('applications.review'));
        $this->assertFalse($officer->hasPermissionTo('applications.approve'));

        $superadmin = Role::findByName('superadmin', 'web');
        $this->assertSame(12, $superadmin->permissions()->count());
        $this->assertTrue($superadmin->hasPermissionTo('roles.manage'));
    }

    public function test_roles_use_the_web_guard_compatible_with_sanctum_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['superadmin', 'admin', 'admission_officer', 'staff', 'applicant'] as $name) {
            $this->assertDatabaseHas('roles', ['name' => $name, 'guard_name' => 'web']);
        }
    }
}
