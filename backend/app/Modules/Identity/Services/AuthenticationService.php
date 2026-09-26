<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function authenticate(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
