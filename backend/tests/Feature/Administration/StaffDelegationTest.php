<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StaffDelegationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_review_but_cannot_offer_admission_by_default(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($admin->can('applications.review'));
        $this->assertFalse($admin->can('applications.offer_admission'));
        $admin->givePermissionTo('applications.offer_admission');
        $this->assertTrue($admin->fresh()->can('applications.offer_admission'));
        $admin->revokePermissionTo('applications.offer_admission');
        $this->assertFalse($admin->fresh()->can('applications.offer_admission'));
    }

    public function test_superadmin_has_every_laravel_gate_ability(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->assertTrue($superadmin->can('applications.offer_admission'));
        $this->assertTrue($superadmin->can('website.manage'));
        $this->assertTrue($superadmin->can('staff.manage'));
    }

    public function test_ordinary_admin_cannot_manage_staff(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('stsams.staff-management.store'), [
            'name' => 'Restricted Member', 'email' => 'restricted@example.test',
            'password' => 'StrongPass#999000A', 'password_confirmation' => 'StrongPass#999000A',
            'role' => 'admin', 'permissions' => ['applications.offer_admission'],
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'restricted@example.test']);
    }

    public function test_superadmin_can_create_review_only_admin_and_change_grants_with_audit(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin)->post(route('stsams.staff-management.store'), [
            'name' => 'Admissions Reviewer', 'email' => 'reviewer@example.test',
            'password' => 'StrongPass#999000A', 'password_confirmation' => 'StrongPass#999000A',
            'role' => 'admin', 'permissions' => [],
        ])->assertRedirect();
        $staff = User::where('email', 'reviewer@example.test')->firstOrFail();
        $this->assertTrue($staff->can('applications.review'));
        $this->assertFalse($staff->can('applications.offer_admission'));
        $this->assertDatabaseHas('staff_profiles', ['user_id' => $staff->id]);
        $this->assertDatabaseHas('stsams_staff_access_audits', ['staff_user_id' => $staff->id, 'action' => 'staff_created']);

        $this->actingAs($superadmin)->patch(route('stsams.staff-management.update', $staff), [
            'name' => 'Admissions Reviewer', 'role' => 'admin',
            'permissions' => ['applications.offer_admission'],
        ])->assertRedirect();
        $this->assertTrue($staff->fresh()->can('applications.offer_admission'));
        $this->assertDatabaseHas('stsams_staff_access_audits', ['staff_user_id' => $staff->id, 'action' => 'staff_permissions_updated']);
    }

    public function test_superadmin_role_cannot_be_assigned_through_staff_form(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin)->post(route('stsams.staff-management.store'), [
            'name' => 'Another Root', 'email' => 'root2@example.test',
            'password' => 'StrongPass#999000A', 'password_confirmation' => 'StrongPass#999000A',
            'role' => 'superadmin',
        ])->assertSessionHasErrors('role');
    }
}
