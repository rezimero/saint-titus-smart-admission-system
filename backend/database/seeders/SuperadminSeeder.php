<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

/** Explicit opt-in bootstrap. Not invoked by DatabaseSeeder. */
final class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) (getenv('STSAMS_SUPERADMIN_NAME') ?: ''));
        $email = mb_strtolower(trim((string) (getenv('STSAMS_SUPERADMIN_EMAIL') ?: '')));
        $password = (string) (getenv('STSAMS_SUPERADMIN_PASSWORD') ?: '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            throw new RuntimeException('A valid STSAMS_SUPERADMIN_NAME is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            throw new RuntimeException('A valid STSAMS_SUPERADMIN_EMAIL is required.');
        }

        if (
            strlen($password) < 16 ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^a-zA-Z0-9]/', $password)
        ) {
            throw new RuntimeException('Superadmin password must be at least 16 characters with upper/lowercase letters, a number and a symbol.');
        }

        // Fail closed if authorization was not initialized before provisioning.
        $role = Role::findByName('superadmin', 'web');

        DB::transaction(function () use ($name, $email, $password, $role): void {
            $existing = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($existing !== null) {
                if (!$existing->hasRole($role)) {
                    throw new RuntimeException('The requested email belongs to an existing non-superadmin account. Refusing elevation.');
                }

                // Idempotent: never reset an existing administrator password.
                return;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $user->assignRole($role);
        });

        $this->command?->info('Superadmin bootstrap completed. No password was displayed.');
    }
}
