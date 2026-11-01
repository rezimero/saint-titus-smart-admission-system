<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class StsamsAuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Applies only to Laravel authorization checks; never bypasses authentication.
        // Apply to all session-authenticated web workspaces, including website editors.
        $this->app['router']->pushMiddlewareToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\RequireStaffPasswordChange::class);
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\StsamsPublicLaunchGate::class);
        Gate::before(static function (User $user, string $ability): ?bool {
            return $user->hasRole('superadmin') ? true : null;
        });
    }
}
