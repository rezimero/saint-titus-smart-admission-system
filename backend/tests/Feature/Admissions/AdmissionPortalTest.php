<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AdmissionPortalTest extends TestCase
{
    use RefreshDatabase;

    private function draft(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('applicant');
        $programme = StsamsProgramme::create(['code' => 'CHEW', 'name' => 'Community Health Extension Worker', 'admission_track' => 'chew']);
        $session = StsamsAdmissionSession::create(['name' => 'Test Session 2027', 'is_open' => true, 'application_fee_kobo' => 0]);
        $application = StsamsAdmissionApplication::create([
            'user_id' => $user->id, 'programme_id' => $programme->id, 'admission_session_id' => $session->id,
            'status' => 'draft', 'phone' => '08012345678', 'date_of_birth' => '2002-01-02',
            'address' => 'Example Street', 'prior_school' => 'Example Secondary School', 'qualification' => 'SSCE',
        ]);
        return [$user, $application];
    }

    private function attach(StsamsAdmissionApplication $application, User $user, string $type): void
    {
        $application->documents()->create([
            'uploaded_by' => $user->id, 'type' => $type, 'path' => 'fixtures/example.pdf',
            'original_name' => 'example.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 10, 'scan_status' => 'clean',
        ]);
    }

    public function test_an_applicant_cannot_submit_without_required_documents(): void
    {
        [$user, $app] = $this->draft();
        $this->actingAs($user)->post(route('stsams.applicant.submit', $app))
            ->assertSessionHasErrors('document');
        $this->assertSame('draft', $app->fresh()->status);
    }

    public function test_required_documents_and_explicit_fee_waiver_allow_submission(): void
    {
        [$user, $app] = $this->draft();
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $type) {
            $this->attach($app, $user, $type);
        }
        $this->actingAs($user)->post(route('stsams.applicant.submit', $app))->assertRedirect();
        $this->assertSame('submitted', $app->fresh()->status);
        $this->actingAs($user)->get(route('stsams.applicant.acknowledgement', $app))->assertOk();
    }

    public function test_a_positive_fee_requires_verified_payment(): void
    {
        [$user, $app] = $this->draft();
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $type) {
            $this->attach($app, $user, $type);
        }
        $app->admissionSession->update(['application_fee_kobo' => 500000]);
        $this->actingAs($user)->post(route('stsams.applicant.submit', $app))->assertSessionHasErrors('payment');
        $this->assertSame('draft', $app->fresh()->status);
    }

    public function test_an_applicant_cannot_view_other_users_documents(): void
    {
        [$owner, $app] = $this->draft();
        $this->attach($app, $owner, 'passport');
        $other = User::factory()->create();
        $other->assignRole('applicant');
        $doc = $app->documents()->firstOrFail();
        $this->actingAs($other)->get(route('stsams.documents.preview', [$app, $doc]))->assertForbidden();
    }

    public function test_paystack_payment_is_not_marked_paid_without_server_verification(): void
    {
        [$user, $app] = $this->draft();
        $app->admissionSession->update(['application_fee_kobo' => 500000]);
        config(['stsams_payments.secret_key' => 'sk_test_for_http_fake_only', 'stsams_launch.payments_enabled' => true]);
        Http::fake(['https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/mockcheckout', 'reference' => 'unused']
        ], 200)]);
        $this->actingAs($user)->post(route('stsams.payments.initialize', $app))
            ->assertRedirect('https://checkout.paystack.com/mockcheckout');
        $payment = $app->payments()->firstOrFail();
        $this->assertSame('initiated', $payment->status);
        Http::fake(['https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true, 'data' => [
                'status' => 'success', 'reference' => $payment->reference,
                'amount' => 100, 'currency' => 'NGN',
                'customer' => ['email' => $user->email], 'id' => 8999,
            ]
        ], 200)]);
        $this->get(route('stsams.payments.callback', ['reference' => $payment->reference]))
            ->assertSessionHasErrors('payment');
        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_public_registration_never_assigns_superadmin_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->post('/admissions/register', [
            'name' => 'Sample Applicant', 'email' => 'registration@example.test',
            'password' => 'LongSecurePass!2026', 'password_confirmation' => 'LongSecurePass!2026',
            'role' => 'superadmin',
        ])->assertRedirect(route('stsams.applicant.index'));
        $user = User::where('email', 'registration@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertFalse($user->hasRole('superadmin'));
    }

    public function test_programme_specific_jamb_result_requirement_is_enforced(): void
    {
        [$user, $app] = $this->draft();
        $app->programme->documentRequirements()->create(['document_type' => 'jamb_result', 'requirement' => 'required']);
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $type) {
            $this->attach($app, $user, $type);
        }
        $this->actingAs($user)->post(route('stsams.applicant.submit', $app))->assertSessionHasErrors('document');
        $this->attach($app, $user, 'jamb_result');
        $this->actingAs($user)->post(route('stsams.applicant.submit', $app))->assertRedirect();
        $this->assertSame('submitted', $app->fresh()->status);
    }

    public function test_correct_paystack_response_marks_payment_paid_idempotently(): void
    {
        [$user, $app] = $this->draft();
        $app->admissionSession->update(['application_fee_kobo' => 500000]);
        config(['stsams_payments.secret_key' => 'sk_test_for_http_fake_only', 'stsams_launch.payments_enabled' => true]);
        $payment = $app->payments()->create([
            'reference' => 'STSAMS-ABCDEF123456789', 'gateway' => 'paystack', 'status' => 'initiated',
            'amount_kobo' => 500000, 'currency' => 'NGN', 'payer_email' => $user->email,
        ]);
        Http::fake(['https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true, 'data' => [
                'status' => 'success', 'reference' => $payment->reference,
                'amount' => 500000, 'currency' => 'NGN',
                'customer' => ['email' => $user->email], 'id' => 912345,
            ],
        ], 200)]);
        $this->actingAs($user)->get(route('stsams.payments.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('stsams.applicant.edit', $app));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->actingAs($user)->get(route('stsams.payments.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('stsams.applicant.edit', $app));
        $this->assertSame(1, $app->payments()->where('status', 'paid')->count());
    }

    public function test_staff_review_and_offer_require_separate_permissions(): void
    {
        [$applicant, $app] = $this->draft();
        $app->update(['status' => 'submitted']);
        $officer = User::factory()->create(); $officer->assignRole('admission_officer');
        $admin = User::factory()->create(); $admin->assignRole('admin');
        $this->actingAs($applicant)->get(route('stsams.staff.index'))->assertForbidden();
        $this->actingAs($applicant)->post(route('stsams.staff.transition', $app), ['status' => 'offered'])->assertForbidden();
        // Reviewers now have to claim a case before moving it into review.
        $this->actingAs($officer)->post(route('stsams.ops.claim', $app))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($officer->id, (int) $app->fresh()->assigned_reviewer_id);
        $this->actingAs($officer)->post(route('stsams.staff.transition', $app), ['status' => 'under_review'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('under_review', $app->fresh()->status);
        $this->actingAs($officer)->post(route('stsams.staff.transition', $app), ['status' => 'offered'])->assertForbidden();
        $this->actingAs($admin)->post(route('stsams.staff.transition', $app), ['status' => 'offered'])->assertForbidden();

        // The offer step requires individually verified submitted documents.
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $type) {
            $this->attach($app, $applicant, $type);
        }
        foreach ($app->documents()->get() as $document) {
            $this->actingAs($officer)->post(route('stsams.ops.document.review', [$app, $document]), [
                'decision' => 'verified', 'note' => 'Credential inspected for test case.',
            ])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('verified', $document->fresh()->verification_status);
        }
        $admin->givePermissionTo('applications.offer_admission');
        $this->actingAs($admin)->post(route('stsams.staff.transition', $app), [
            'status' => 'offered', 'note' => 'All required credentials verified by another officer.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('offered', $app->fresh()->status);
        $this->assertDatabaseHas('stsams_application_events', [
            'application_id' => $app->id, 'actor_id' => $admin->id,
            'from_status' => 'under_review', 'to_status' => 'offered',
        ]);
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        $this->postJson('/api/v1/payments/paystack/webhook', [
            'event' => 'charge.success', 'data' => ['reference' => 'arbitrary-ref'],
        ])->assertUnauthorized();
    }
}
