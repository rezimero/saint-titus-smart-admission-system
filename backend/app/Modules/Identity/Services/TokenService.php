<?php

namespace App\Modules\Identity\Services;

use App\Models\User;

class TokenService
{
    public function create(User $user): string
    {
        return $user->createToken('stsams')->plainTextToken;
    }

    public function revoke(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
