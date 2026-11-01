<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#083856">
  <meta name="description" content="{{ $description ?? 'Official institutional website of Saint Titus College of Health Sciences and Technology, Benin City, Edo State. Admission information and online applications.' }}">
  <title>{{ $title ?? 'Home' }} | {{ config('stsams_institution.name') }}</title>
  <link rel="icon" href="{{ asset('institutional/icons/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('institutional/icons/favicon-32.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('institutional/icons/favicon-180.png') }}">
  <link rel="stylesheet" href="{{ asset('institutional/website.css') }}">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="topline"><div class="shell topline-inner">
  <span><span class="tiny-dot"></span> Benin City, Edo State, Nigeria</span>
  <div><a href="tel:+2348034418715">08034418715</a><span class="separator">/</span><a href="mailto:{{ config('stsams_institution.email') }}">{{ config('stsams_institution.email') }}</a></div>
</div></div>
<header class="site-header">
  <div class="shell header-inner">
    @include('institutional.partials.brand')
    <details class="mobile-menu"><summary aria-label="Open navigation">☰ Menu</summary>
      <nav class="mobile-links" aria-label="Mobile navigation">
        <a href="{{ route('institution.home') }}">Home</a><a href="{{ route('institution.about') }}">About</a><a href="{{ route('institution.academics') }}">Academics</a>
        <a href="{{ route('institution.admissions') }}">Admissions</a><a href="{{ route('institution.campus') }}">Campus Life</a><a href="{{ route('institution.news') }}">News & Events</a>
        <a href="{{ route('institution.administration') }}">Administration</a><a href="{{ route('institution.contact') }}">Contact</a><a href="{{ url('/admissions') }}">Apply Online</a>
      </nav>
    </details>
    <nav class="desktop-nav" aria-label="Main navigation">
      <a href="{{ route('institution.home') }}" @if(request()->routeIs('institution.home')) aria-current="page" @endif>Home</a>
      <a href="{{ route('institution.about') }}" @if(request()->routeIs('institution.about')) aria-current="page" @endif>About</a>
      <a href="{{ route('institution.academics') }}" @if(request()->routeIs('institution.academics')) aria-current="page" @endif>Academics</a>
      <a href="{{ route('institution.admissions') }}" @if(request()->routeIs('institution.admissions')) aria-current="page" @endif>Admissions</a>
      <a href="{{ route('institution.campus') }}" @if(request()->routeIs('institution.campus')) aria-current="page" @endif>Campus Life</a>
      <a href="{{ route('institution.news') }}" @if(request()->routeIs('institution.news')) aria-current="page" @endif>News & Events</a>
      <a href="{{ route('institution.administration') }}" @if(request()->routeIs('institution.administration')) aria-current="page" @endif>Administration</a>
      <a href="{{ route('institution.contact') }}" @if(request()->routeIs('institution.contact')) aria-current="page" @endif>Contact</a>
    </nav>
    <a class="btn btn-primary header-apply" href="{{ url('/admissions') }}">Apply now <span aria-hidden="true">↗</span></a>
  </div>
  @auth
    @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'admission_officer', 'staff', 'website_editor']))
      <div class="shell" style="display:flex;justify-content:flex-end;align-items:center;gap:16px;padding:8px 0;font-size:.84rem;color:#28485d;flex-wrap:wrap">
        <span>Signed in: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->getRoleNames()->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->join(', ') }})</span>
        <a href="{{ route('stsams.account.show') }}">My profile & security</a>
      </div>
    @endif
  @endauth
</header>
<main id="main-content">
  @if(session('success'))<div class="shell flash" role="status">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="shell error-box" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
<footer class="footer">
  <div class="shell footer-grid">
    <div><a class="footer-logo" href="{{ route('institution.home') }}"><img src="{{ asset('institutional/saint-titus-official-logo.jpg') }}" alt="Saint Titus College logo" width="55" height="63"><strong>SAINT TITUS<br>COLLEGE OF HEALTH SCIENCES<br>AND TECHNOLOGY</strong></a>
      <p>An institutional information website and gateway to the Saint Titus online admission portal.</p></div>
    <div><h3>Explore</h3><a href="{{ route('institution.about') }}">About the College</a><a href="{{ route('institution.academics') }}">Academics</a><a href="{{ route('institution.news') }}">News & Events</a><a href="{{ route('institution.contact') }}">Contact Us</a></div>
    <div><h3>Admissions</h3><a href="{{ route('institution.admissions') }}">Admission Information</a><a href="{{ url('/admissions') }}">Application Portal</a><a href="{{ url('/admissions/register') }}">Applicant Registration</a><a href="{{ url('/admissions/login') }}">Track Your Application</a></div>
    <div><h3>Contact</h3><p>KM 11 Benin–Lagos Expressway, after UNIBEN Main Gate, by Evidence, Benin City, Edo State.</p><a href="tel:+2348034418715">08034418715</a><a href="mailto:{{ config('stsams_institution.email') }}">{{ config('stsams_institution.email') }}</a><a href="https://wa.me/2349091219057" rel="noopener noreferrer">WhatsApp: 09091219057</a></div>
  </div>
  <div class="shell footer-bottom"><span>© {{ now()->year }} Saint Titus College of Health Sciences and Technology. All rights reserved.</span><span>Official information is published by authorized institutional staff.</span></div>
</footer>
</body>
</html>
