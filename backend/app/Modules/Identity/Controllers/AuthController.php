<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Requests\LoginRequest;
use App\Modules\Identity\Requests\RegisterRequest;
use App\Modules\Identity\Services\AuthenticationService;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\TokenService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegistrationService $service): JsonResponse
    {
        $user = $service->register($request->validated());

        return response()->json([
            'user' => $user,
        ], 201);
    }

    public function login(
        LoginRequest $request,
        AuthenticationService $service,
        TokenService $tokens
    ): JsonResponse {
        $user = $service->authenticate(
            $request->email,
            $request->password
        );

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'token' => $tokens->create($user)
        ]);
    }

    public function logout(TokenService $tokens): JsonResponse
    {
        $tokens->revoke(auth()->user());

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => auth()->user()
        ]);
    }
}
