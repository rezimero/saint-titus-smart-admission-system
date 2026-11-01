@extends('stsams.layout')
@section('title', 'My Profile')
@section('content')
<div class="page-head"><div><span class="eyebrow">STAFF SELF-SERVICE</span>
<h1>My profile & account security</h1><p>Review your identity, update your details and protect your account.</p></div></div>
@if($user->must_change_password)
<div class="alert error" role="alert"><strong>Password change required.</strong>
You are using an initial staff credential. Change your password below to unlock your assigned workspaces.</div>
@endif
<div class="two-column stsams-profile-columns">
<section class="card section"><h2>Account details</h2>
<dl class="stsams-identity-list">
<dt>Signed in as</dt><dd><strong>{{ $user->name }}</strong></dd>
<dt>Institutional email</dt><dd>{{ $user->email }}</dd>
<dt>Staff number</dt><dd>{{ $profile?->staff_number ?? 'Assigned on first profile save' }}</dd>
<dt>Assigned roles</dt><dd>{{ $user->getRoleNames()->map(fn ($role) => ucwords(str_replace('_', ' ', $role)))->join(', ') ?: 'None' }}</dd>
<dt>Direct permission grants</dt><dd>{{ $user->getDirectPermissions()->pluck('name')->join(', ') ?: 'None' }}</dd>
</dl>
<p><small>Your email address, roles and permissions are managed by the superadmin and cannot be changed here.</small></p>
<form method="POST" action="{{ route('stsams.account.update') }}">@csrf @method('PATCH')
<label>Full name <input name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></label>
<label>Phone number <input type="tel" name="phone" maxlength="30" value="{{ old('phone', $profile?->phone) }}" autocomplete="tel"></label>
<label>Job title / designation <input name="job_title" maxlength="120" value="{{ old('job_title', $profile?->job_title) }}"></label>
<button class="button primary" type="submit">Save profile</button>
</form></section>
<section class="card section" id="security"><h2>Change password</h2>
<p>Use a unique password of at least 16 characters, including uppercase, lowercase, a number and a symbol.</p>
<form method="POST" action="{{ route('stsams.account.password') }}">@csrf @method('PUT')
<label>Current / temporary password <input type="password" name="current_password" autocomplete="current-password" required></label>
<label>New password <input type="password" name="password" minlength="16" autocomplete="new-password" required></label>
<label>Confirm new password <input type="password" name="password_confirmation" minlength="16" autocomplete="new-password" required></label>
<button class="button primary" type="submit">Update password</button>
</form>
<p><small>Your new password will replace the temporary credential. Other signed-in sessions and existing API tokens will be invalidated.</small></p>
</section></div>
<style>
.stsams-profile-columns{align-items:start}.stsams-identity-list{display:grid;grid-template-columns:minmax(110px,145px) 1fr;gap:10px 12px;margin-bottom:22px;overflow-wrap:anywhere}
.stsams-identity-list dt{color:#607c8e;font-size:.88rem}.stsams-identity-list dd{margin:0}.stsams-profile-columns form label{display:block;margin:12px 0}.stsams-profile-columns form input{display:block;width:100%;margin-top:6px}
@media(max-width:760px){.stsams-identity-list{grid-template-columns:1fr;gap:4px}.stsams-identity-list dd{margin-bottom:8px}}
</style>
@endsection
