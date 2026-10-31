<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_identity_endpoints_reject_guests(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_public_registration_never_grants_an_administrator_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Applicant',
            'email' => 'applicant@stsams.example',
            'password' => 'Applicant!Password2026',
            'role' => 'superadmin',
        ]);

        $response->assertCreated();
        $user = \App\Models\User::query()->where('email', 'applicant@stsams.example')->firstOrFail();
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertFalse($user->hasRole('superadmin'));
        $this->assertNotSame('Applicant!Password2026', $user->password);
    }
}
