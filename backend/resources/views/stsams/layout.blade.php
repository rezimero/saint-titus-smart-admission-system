<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Smart Admissions') | {{ config('stsams_institution.name') }}</title>
    <meta name="theme-color" content="#083856">
    <link rel="icon" href="{{ asset('institutional/icons/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('institutional/icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('institutional/icons/favicon-180.png') }}">
    <link rel="stylesheet" href="{{ asset('stsams/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('stsams/documents.css') }}">
</head>
<body>
{{-- Shared institutional header identity; portal navigation remains admissions-specific. --}}
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="topline"><div class="shell topline-inner">
    <span><span class="tiny-dot" aria-hidden="true"></span> Benin City, Edo State, Nigeria</span>
    <div><a href="tel:+2348034418715">08034418715</a><span class="separator" aria-hidden="true">/</span><a href="mailto:{{ config('stsams_institution.email') }}">{{ config('stsams_institution.email') }}</a></div>
</div></div>
<header class="site-header">
    <div class="shell topbar">
        @include('institutional.partials.brand')
        <nav aria-label="Primary navigation">
            <a href="{{ route('stsams.home') }}">Home</a>
            @auth
                <span class="stsams-user-badge" title="{{ auth()->user()->email }}">Signed in: <strong>{{ auth()->user()->name }}</strong> <small>({{ auth()->user()->getRoleNames()->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->join(', ') }})</small></span>
                @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'admission_officer', 'staff', 'website_editor']))
                  <a href="{{ route('stsams.account.show') }}">My profile</a>
                @endif
                <a href="{{ route('stsams.dashboard') }}">Dashboard</a>
                @if(auth()->user()->hasRole('superadmin'))<a href="{{ route('stsams.staff-management.index') }}">Staff access</a>@endif
                @if(auth()->user()->can('settings.manage'))<a href="{{ route('stsams.admin.catalogue') }}">Setup</a>@endif
                <form method="POST" action="{{ route('stsams.logout') }}" class="inline">@csrf<button class="nav-button" type="submit">Sign out</button></form>
            @else
                <a href="{{ route('stsams.login') }}">Sign in</a>
                <a class="nav-cta" href="{{ route('stsams.register') }}">Apply now</a>
            @endauth
        </nav>
    </div>
</header>
<main id="main-content" class="shell content">
    @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
<footer class="footer"><div class="shell">© {{ now()->year }} Saint Titus · Secure Admissions Services <span>Protect your account. Never share your password.</span></div></footer>
</body>
</html>
