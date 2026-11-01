<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use App\Models\StsamsApplicationDocument;
use App\Models\User;
use App\Modules\Operations\Services\AssignmentService;
use App\Modules\Operations\Services\DocumentReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class OperationsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_reviewers_cannot_assign_another_person_without_delegation(): void
    {
        $reviewer = User::factory()->create()->assignRole('admission_officer');
        $applicant = User::factory()->create();
        $programme = \App\Models\StsamsProgramme::create(['code' => 'OPS', 'name' => 'Operations Test']);
        $session = \App\Models\StsamsAdmissionSession::create(['name' => 'Operations Session']);
        $application = StsamsAdmissionApplication::create([
            'user_id' => $applicant->id, 'programme_id' => $programme->id,
            'admission_session_id' => $session->id, 'status' => 'submitted',
        ]);
        $this->actingAs($reviewer)->post(route('stsams.ops.assign', $application), ['reviewer_id' => $reviewer->id])
            ->assertForbidden();
    }

    private function submittedCase(): StsamsAdmissionApplication
    {
        $applicant = User::factory()->create();
        $programme = StsamsProgramme::create(['code' => 'OPS-CASES', 'name' => 'Community Health']);
        $session = StsamsAdmissionSession::create(['name' => 'Operations Cases Session']);
        return StsamsAdmissionApplication::create([
            'user_id' => $applicant->id, 'programme_id' => $programme->id,
            'admission_session_id' => $session->id, 'status' => 'submitted',
        ]);
    }

    public function test_assigned_reviewer_can_review_documents_and_actions_are_audited(): void
    {
        $staff = User::factory()->create()->assignRole('admission_officer');
        $case = $this->submittedCase();
        $this->actingAs($staff)->post(route('stsams.ops.claim', $case))->assertRedirect();
        $this->assertSame($staff->id, (int) $case->fresh()->assigned_reviewer_id);
        $this->actingAs($staff)->post(route('stsams.staff.transition', $case), [
            'status' => 'under_review', 'note' => 'Starting credential examination.',
        ])->assertRedirect();
        $document = $case->documents()->create([
            'uploaded_by' => $case->user_id, 'type' => 'passport',
            'path' => 'fixtures/passport.jpg', 'original_name' => 'passport.jpg',
            'mime_type' => 'image/jpeg', 'size_bytes' => 100, 'scan_status' => 'clean',
        ]);
        $this->actingAs($staff)->post(route('stsams.ops.document.review', [$case, $document]), [
            'decision' => 'verified', 'note' => 'Photograph legible.',
        ])->assertRedirect();
        $this->assertSame('verified', $document->fresh()->verification_status);
        $this->assertDatabaseHas('stsams_operations_audits', [
            'actor_id' => $staff->id, 'subject_type' => 'document',
            'subject_id' => $document->id, 'action' => 'document.verified',
        ]);
    }

    public function test_applicant_document_creation_cannot_preapprove_credentials(): void
    {
        $case = $this->submittedCase();
        $document = $case->documents()->create([
            'uploaded_by' => $case->user_id, 'type' => 'passport',
            'path' => 'fixtures/passport.jpg', 'original_name' => 'passport.jpg',
            'mime_type' => 'image/jpeg', 'size_bytes' => 100,
            'scan_status' => 'clean',
        ]);
        $this->assertSame('pending', $document->fresh()->verification_status);
        $this->assertNull($document->fresh()->verified_by);
        $this->assertNotContains('verification_status', $document->getFillable());
        $this->assertNotContains('verified_by', $document->getFillable());
        $this->assertNotContains('verified_at', $document->getFillable());
    }

    public function test_unverified_documents_block_even_a_superadmin_offer(): void
    {
        $root = User::factory()->create()->assignRole('superadmin');
        $case = $this->submittedCase();
        $case->forceFill(['status' => 'under_review'])->save();
        $this->actingAs($root)->post(route('stsams.staff.transition', $case), [
            'status' => 'offered', 'note' => 'Admission authorized pending verification.',
        ])->assertSessionHasErrors('document');
        $this->assertSame('under_review', $case->fresh()->status);
    }

    public function test_same_reviewer_cannot_offer_even_with_offer_permission(): void
    {
        $reviewer = User::factory()->create()->assignRole('admission_officer');
        $reviewer->givePermissionTo('applications.offer_admission');
        $case = $this->submittedCase();
        $case->forceFill(['status' => 'under_review', 'assigned_reviewer_id' => $reviewer->id])->save();
        $case->events()->create([
            'actor_id' => $reviewer->id, 'from_status' => 'submitted', 'to_status' => 'under_review',
        ]);
        // A verified fixture must be set via a privileged write, not via
        // ordinary mass assignment on an applicant-uploaded document.
        $verifiedDocument = $case->documents()->create([
            'uploaded_by' => $case->user_id, 'type' => 'passport',
            'path' => 'fixtures/passport.jpg', 'original_name' => 'passport.jpg',
            'mime_type' => 'image/jpeg', 'size_bytes' => 100,
            'scan_status' => 'clean',
        ]);
        $verifiedDocument->forceFill(['verification_status' => 'verified'])->save();
        $this->assertSame('verified', $verifiedDocument->fresh()->verification_status);
        $this->actingAs($reviewer)->post(route('stsams.staff.transition', $case), [
            'status' => 'offered', 'note' => 'Candidate meets requirements.',
        ])->assertSessionHasErrors('status');
    }

    public function test_website_editor_can_save_but_superadmin_publishes_and_audits(): void
    {
        $editor = User::factory()->create()->assignRole('website_editor');
        $super = User::factory()->create()->assignRole('superadmin');
        $topics = config('stsams_institution.topics');
        $section = array_key_first($topics);
        $field = array_key_first($topics[$section]);
        $this->actingAs($editor)->post(route('institution.admin.content'), [
            'section' => $section, 'content_key' => $field, 'body' => 'Official approved wording.',
        ])->assertRedirect();
        $content = \App\Models\InstitutionContent::firstOrFail();
        $this->actingAs($super)->post(route('institution.admin.content.publish', $content))->assertRedirect();
        $this->assertSame('Official approved wording.', $content->fresh()->body);
        $this->assertDatabaseHas('stsams_operations_audits', [
            'subject_type' => 'website_content', 'subject_id' => $content->id,
            'action' => 'website.content.published',
        ]);
    }

    public function test_website_editors_cannot_publish_without_individual_grant(): void
    {
        $editor = User::factory()->create()->assignRole('website_editor');
        $this->actingAs($editor)->post(route('institution.admin.posts.create'), [
            'category' => 'news', 'title' => 'Private draft', 'summary' => 'Editorial draft summary',
            'body' => 'Not yet approved', 'publish' => 1,
        ])->assertForbidden();
    }

    public function test_applicants_cannot_see_central_audit_register(): void
    {
        $applicant = User::factory()->create()->assignRole('applicant');
        $this->actingAs($applicant)->get(route('stsams.ops.audit'))->assertForbidden();
    }

    public function test_website_editor_can_save_a_private_content_draft(): void
    {
        $editor = User::factory()->create()->assignRole('website_editor');
        $topics = config('stsams_institution.topics');
        $section = array_key_first($topics);
        $field = array_key_first($topics[$section]);
        $this->actingAs($editor)->post(route('institution.admin.content'), [
            'section' => $section, 'content_key' => $field, 'body' => 'Draft editorial material',
        ])->assertRedirect();
        $this->assertDatabaseHas('stsams_web_contents', [
            'section' => $section, 'content_key' => $field, 'pending_body' => 'Draft editorial material', 'body' => null,
        ]);
    }
}
