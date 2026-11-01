<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Models\StsamsAdmissionApplication;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdmissionOfferPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function application(): StsamsAdmissionApplication
    {
        $applicant = User::factory()->create();
        $programme = \App\Models\StsamsProgramme::create([
            'code' => 'CHEW-PERM', 'name' => 'Community Health Extension Worker', 'is_active' => true,
        ]);
        $session = \App\Models\StsamsAdmissionSession::create([
            'name' => '2026-2027 permission test', 'is_open' => true,
        ]);
        return StsamsAdmissionApplication::create([
            'user_id' => $applicant->id, 'programme_id' => $programme->id,
            'admission_session_id' => $session->id,
            'status' => 'under_review',
        ]);
    }

    public function test_reviewing_admin_cannot_offer_admission_but_authorized_admin_can(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $application = $this->application();
        $route = route('stsams.staff.transition', $application);
        $this->actingAs($admin)->post($route, ['status' => 'offered'])
            ->assertForbidden();
        $this->assertSame('under_review', $application->fresh()->status);

        $reviewer = User::factory()->create()->assignRole('admission_officer');
        $application->events()->create([
            'actor_id' => $reviewer->id, 'from_status' => 'submitted',
            'to_status' => 'under_review', 'note' => null,
        ]);
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $type) {
            // Verification fields are intentionally NOT mass assignable. Seed the
            // reviewer-approved fixture explicitly, without changing model security.
            $document = $application->documents()->create([
                'uploaded_by' => $application->user_id, 'type' => $type,
                'path' => 'fixtures/'. $type . '.pdf', 'original_name' => $type.'.pdf',
                'mime_type' => 'application/pdf', 'size_bytes' => 10,
                'scan_status' => 'clean',
            ]);
            $document->forceFill([
                'verification_status' => 'verified',
                'verified_by' => $reviewer->id,
                'verified_at' => now(),
            ])->save();
            $this->assertSame('verified', $document->fresh()->verification_status);
        }
        $admin->givePermissionTo('applications.offer_admission');
        $this->actingAs($admin)->post($route, ['status' => 'offered', 'note' => 'Applicant meets admission criteria.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('offered', $application->fresh()->status);
        $this->assertDatabaseHas('stsams_application_events', [
            'application_id' => $application->id, 'actor_id' => $admin->id,
            'from_status' => 'under_review', 'to_status' => 'offered',
        ]);
    }

    public function test_rejection_is_not_automatically_granted_with_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $application = $this->application();
        $this->actingAs($admin)->post(route('stsams.staff.transition', $application), [
            'status' => 'rejected', 'note' => 'Missing qualification',
        ])->assertForbidden();
        $this->assertSame('under_review', $application->fresh()->status);
    }
}
