<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Requests\LoginRequest;
use App\Modules\Identity\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        AuthenticationService $service
    ): JsonResponse {
        $user = $service->authenticate(
            $request->email,
            $request->password
        );

        if (!$user) {
            return response()->json([
                "message" => "Invalid credentials"
            ], 401);
        }

        return response()->json([
            "token" => $user->createToken("stsams")->plainTextToken
        ]);
    }
}
