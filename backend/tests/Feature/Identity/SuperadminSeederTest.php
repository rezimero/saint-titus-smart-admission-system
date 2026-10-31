<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class SuperadminSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'bootstrap-admin@stsams.example';
    private const PASSWORD = 'Secure!BootstrapPass2026';

    public function test_explicit_provisioning_is_idempotent_and_does_not_reset_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->withBootstrapEnvironment(self::PASSWORD, function (): void {
            $this->seed(SuperadminSeeder::class);
            $this->seed(SuperadminSeeder::class);
        });

        $this->assertSame(1, User::query()->where('email', self::EMAIL)->count());
        $user = User::query()->where('email', self::EMAIL)->firstOrFail();
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
    }

    public function test_existing_non_admin_user_is_never_promoted(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['email' => self::EMAIL]);

        try {
            $this->withBootstrapEnvironment(self::PASSWORD, function (): void {
                $this->seed(SuperadminSeeder::class);
            });
            $this->fail('Expected a conflicting-account exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Refusing elevation', $exception->getMessage());
        }

        $this->assertFalse($user->fresh()->hasRole('superadmin'));
    }

    public function test_weak_password_is_rejected_before_creation(): void
    {
        $this->seed(RolePermissionSeeder::class);

        try {
            $this->withBootstrapEnvironment('ChangeMe@123', function (): void {
                $this->seed(SuperadminSeeder::class);
            });
            $this->fail('Expected invalid credentials to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('at least 16 characters', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => self::EMAIL]);
    }

    private function withBootstrapEnvironment(string $password, callable $action): void
    {
        $new = [
            'STSAMS_SUPERADMIN_NAME' => 'Bootstrap Administrator',
            'STSAMS_SUPERADMIN_EMAIL' => self::EMAIL,
            'STSAMS_SUPERADMIN_PASSWORD' => $password,
        ];
        $old = [];

        foreach ($new as $key => $value) {
            $old[$key] = getenv($key);
            putenv("{$key}={$value}");
        }

        try {
            $action();
        } finally {
            foreach ($old as $key => $value) {
                putenv($value === false ? $key : "{$key}={$value}");
            }
        }
    }
}
