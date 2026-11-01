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

/** Run ONLY against an isolated test database (stsams_test), never the live STSAMS DB. */
final class AdmissionSessionFeeTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(?int $feeKobo = null, string $status = 'draft'): array
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        $applicant = User::factory()->create();
        $programme = StsamsProgramme::create(['code' => 'TEST-CHEW', 'name' => 'Test Community Health']);
        $session = StsamsAdmissionSession::create([
            'name' => 'Testing session', 'is_open' => true, 'application_fee_kobo' => $feeKobo,
        ]);
        $application = StsamsAdmissionApplication::create([
            'user_id' => $applicant->id,
            'programme_id' => $programme->id,
            'admission_session_id' => $session->id,
            'status' => $status,
        ]);
        return [$admin, $applicant, $session, $application];
    }

    public function test_first_time_configuration_succeeds_when_only_drafts_exist(): void
    {
        [$admin, , $session] = $this->scenario();
        $this->actingAs($admin)->put(route('stsams.admin.session.fee', $session), [
            'application_fee_naira' => '5000.00',
        ])->assertSessionHasNoErrors();
        $this->assertSame(500000, $session->fresh()->application_fee_kobo);
    }

    public function test_first_time_configuration_rejects_submitted_applications(): void
    {
        [$admin, , $session] = $this->scenario(status: 'submitted');
        $this->actingAs($admin)->put(route('stsams.admin.session.fee', $session), [
            'application_fee_naira' => '5000',
        ])->assertSessionHasErrors('application_fee_naira');
        $this->assertNull($session->fresh()->application_fee_kobo);
    }

    public function test_first_time_configuration_rejects_any_payment_record(): void
    {
        [$admin, $applicant, $session, $application] = $this->scenario();
        $application->payments()->create([
            'reference' => 'STSAMS-TEST-PREVIOUS-PAYMENT',
            'gateway' => 'paystack', 'status' => 'initiated',
            'amount_kobo' => 100000, 'currency' => 'NGN',
            'payer_email' => $applicant->email,
        ]);
        $this->actingAs($admin)->put(route('stsams.admin.session.fee', $session), [
            'application_fee_naira' => '5000',
        ])->assertSessionHasErrors('application_fee_naira');
        $this->assertNull($session->fresh()->application_fee_kobo);
    }

    public function test_an_existing_fee_cannot_change_after_drafts_started(): void
    {
        [$admin, , $session] = $this->scenario(feeKobo: 500000);
        $this->actingAs($admin)->put(route('stsams.admin.session.fee', $session), [
            'application_fee_naira' => '6000',
        ])->assertSessionHasErrors('application_fee_naira');
        $this->assertSame(500000, $session->fresh()->application_fee_kobo);
    }

    public function test_an_existing_fee_can_be_unchanged_idempotently(): void
    {
        [$admin, , $session] = $this->scenario(feeKobo: 500000, status: 'submitted');
        $this->actingAs($admin)->put(route('stsams.admin.session.fee', $session), [
            'application_fee_naira' => '5000',
        ])->assertSessionHasNoErrors();
        $this->assertSame(500000, $session->fresh()->application_fee_kobo);
    }
}
