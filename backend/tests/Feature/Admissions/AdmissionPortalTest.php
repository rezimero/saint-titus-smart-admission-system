<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdmissionPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_renders_without_catalogue_entries(): void
    {
        $this->get('/admissions')->assertOk()->assertSee('Saint Titus');
        $this->get('/admissions/dashboard')->assertRedirect('/login');
    }

    public function test_public_registration_automatically_assigns_applicant_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->post('/admissions/register', [
            'name' => 'Sample Applicant',
            'email' => 'sample-applicant@stsams.example',
            'password' => 'LongSecurePass!2026',
            'password_confirmation' => 'LongSecurePass!2026',
            'role' => 'superadmin',
        ])->assertRedirect(route('stsams.applicant.index'));
        $user = User::query()->where('email', 'sample-applicant@stsams.example')->firstOrFail();
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertFalse($user->hasRole('superadmin'));
    }

    public function test_applicant_can_create_and_submit_a_complete_application(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('applicant');
        $programme = StsamsProgramme::create(['code' => 'MIDW', 'name' => 'Midwifery', 'is_active' => true]);
        $session = StsamsAdmissionSession::create(['name' => '2027-2028', 'is_open' => true]);
        $this->actingAs($user)->post(route('stsams.applicant.create'), [
            'programme_id' => $programme->id, 'admission_session_id' => $session->id,
        ])->assertRedirect();
        $application = StsamsAdmissionApplication::query()->firstOrFail();
        $this->actingAs($user)->post(route('stsams.applicant.update', $application), [
            'phone' => '08012345678', 'date_of_birth' => '2001-05-05',
            'address' => '12 Sample Street', 'prior_school' => 'Example Secondary School',
            'qualification' => 'SSCE',
        ])->assertRedirect();
        $application->documents()->create([
            'uploaded_by' => $user->id, 'type' => 'certificate', 'path' => 'stsams/sample.pdf',
            'original_name' => 'sample.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 100,
        ]);
        $this->actingAs($user)->post(route('stsams.applicant.submit', $application))->assertRedirect();
        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->reference);
        $this->assertSame(2, $application->events()->count());
        $this->actingAs($user)->post(route('stsams.applicant.update', $application), [])->assertForbidden();
    }

    public function test_applicant_cannot_see_another_applicants_record(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $visitor->assignRole('applicant');
        $programme = StsamsProgramme::create(['code' => 'HLTH', 'name' => 'Health Studies']);
        $session = StsamsAdmissionSession::create(['name' => '2027-2028']);
        $application = StsamsAdmissionApplication::create([
            'user_id' => $owner->id, 'programme_id' => $programme->id,
            'admission_session_id' => $session->id, 'status' => 'draft',
        ]);
        $this->actingAs($visitor)->get(route('stsams.applicant.edit', $application))->assertForbidden();
    }

    public function test_review_and_decision_require_separate_permissions_and_record_events(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $applicant = User::factory()->create();
        $applicant->assignRole('applicant');
        $officer = User::factory()->create();
        $officer->assignRole('admission_officer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $programme = StsamsProgramme::create(['code' => 'NURS', 'name' => 'Nursing']);
        $session = StsamsAdmissionSession::create(['name' => '2027-2028']);
        $application = StsamsAdmissionApplication::create([
            'user_id' => $applicant->id, 'programme_id' => $programme->id,
            'admission_session_id' => $session->id, 'status' => 'submitted',
        ]);
        $this->actingAs($applicant)->post(route('stsams.staff.transition', $application), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($officer)->post(route('stsams.staff.transition', $application), ['status' => 'under_review'])->assertRedirect();
        $this->actingAs($officer)->post(route('stsams.staff.transition', $application), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($admin)->post(route('stsams.staff.transition', $application), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame(2, $application->events()->count());
    }
}
