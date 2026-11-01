<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireStaffPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if ($user && (bool) $user->must_change_password
            && ! $request->routeIs('stsams.account.*', 'stsams.logout')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Change your temporary password before continuing.',
                ], 403);
            }

            return redirect()->route('stsams.account.show')
                ->with('warning', 'You must change your temporary password before accessing staff functions.');
        }

        return $next($request);
    }
}
