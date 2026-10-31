<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class RegistrationService
{
    /** Public registration never accepts a client-selected role. */
    public function register(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            // Resolve first: misconfigured authorization must not create orphan users.
            $role = Role::findByName('applicant', 'web');

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($role);

            return $user;
        });
    }
}
