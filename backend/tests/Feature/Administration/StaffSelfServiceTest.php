<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Models\User;
use App\Modules\Administration\Services\StaffAccessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class StaffSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_members_can_view_and_update_only_their_own_profile(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user)->get(route('stsams.account.show'))
            ->assertOk()->assertSee($user->name)->assertSee($user->email);
        $this->actingAs($user)->patch(route('stsams.account.update'), [
            'name' => 'Updated Administrator', 'phone' => '08034418715',
            'job_title' => 'Admissions Reviewer',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Administrator']);
        $this->assertDatabaseHas('staff_profiles', ['user_id' => $user->id, 'job_title' => 'Admissions Reviewer']);
    }

    public function test_staff_header_shows_signed_in_name_and_profile_link(): void
    {
        $staff = User::factory()->create(['name' => 'Review Officer'])->assignRole('admin');
        $this->actingAs($staff)->get(route('stsams.home'))
            ->assertOk()->assertSee('Review Officer')->assertSee('My profile');
    }

    public function test_superadmin_staff_creation_requires_password_rotation(): void
    {
        $superadmin = User::factory()->create()->assignRole('superadmin');
        $created = app(StaffAccessService::class)->create($superadmin, [
            'name' => 'New Staff Officer',
            'email' => 'new-staff@stcollegeofhealthsciences.edu.ng',
            'password' => 'NewStaffPassword123!',
            'role' => 'admin',
            'permissions' => [],
        ]);
        $this->assertTrue((bool) $created->fresh()->must_change_password);
    }

    public function test_applicants_cannot_use_staff_profile_endpoints(): void
    {
        $applicant = User::factory()->create()->assignRole('applicant');
        $this->actingAs($applicant)->get(route('stsams.account.show'))->assertForbidden();
        $this->actingAs($applicant)->put(route('stsams.account.password'), [
            'current_password' => 'password', 'password' => 'SomethingNew123456!@#',
            'password_confirmation' => 'SomethingNew123456!@#',
        ])->assertForbidden();
    }

    public function test_initial_staff_password_must_be_changed_before_dashboard_access(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $user->forceFill(['must_change_password' => true])->save();
        $this->actingAs($user)->get(route('stsams.dashboard'))
            ->assertRedirect(route('stsams.account.show'));
        $this->actingAs($user)->get(route('stsams.account.show'))->assertOk();
    }

    public function test_wrong_current_password_is_rejected_without_clearing_flag(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldStaffPassword123!')])->assignRole('staff');
        $user->forceFill(['must_change_password' => true])->save();
        $this->actingAs($user)->put(route('stsams.account.password'), [
            'current_password' => 'wrong-password',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue((bool) $user->fresh()->must_change_password);
    }

    public function test_staff_can_rotate_password_and_unlock_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldStaffPassword123!')])->assignRole('admin');
        $user->forceFill(['must_change_password' => true])->save();
        $this->actingAs($user)->put(route('stsams.account.password'), [
            'current_password' => 'OldStaffPassword123!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertRedirect(route('stsams.account.show'));
        $user->refresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->password));
    }
}
