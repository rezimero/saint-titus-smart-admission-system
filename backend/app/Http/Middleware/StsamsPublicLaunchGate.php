<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StsamsPublicLaunchGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $admissionsClosed = !config('stsams_launch.admissions_enabled', false);
        if ($admissionsClosed && (
            $path === 'admissions/register' ||
            ($request->isMethod('POST') && (str_starts_with($path, 'admissions/applicant/') || $path === 'admissions/applicant/applications'))
        )) {
            abort(503, 'Online applications are temporarily unavailable. Please consult the admissions office.');
        }
        if (!config('stsams_launch.payments_enabled', false)
            && $request->isMethod('POST') && str_ends_with($path, '/pay')
            && str_starts_with($path, 'admissions/applicant/applications/')) {
            abort(503, 'Online payments are temporarily unavailable.');
        }
        return $next($request);
    }
}
