@extends('stsams.layout')
@section('title', 'Central staff authorization')
@section('content')
<div class="page-head"><div><span class="eyebrow">SUPERADMIN CONTROL</span>
<h1>Staff accounts & permissions</h1><p>Create administrators and delegate only the authority they need.</p></div></div>
<section class="card section"><h2>Create staff account</h2>
<p>Administrators may review applications, but <strong>cannot offer admission</strong> without an explicit grant.</p>
<form method="POST" action="{{ route('stsams.staff-management.store') }}">@csrf
<div class="two-column">
<label>Full name <input name="name" value="{{ old('name') }}" required maxlength="120"></label>
<label>Institutional email <input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Temporary password <input type="password" name="password" minlength="16" autocomplete="new-password" required></label>
<label>Confirm password <input type="password" name="password_confirmation" minlength="16" autocomplete="new-password" required></label>
<label>Role <select name="role" required>@foreach($roles as $role)<option value="{{ $role }}" @selected(old('role') === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>@endforeach</select></label>
</div>
<fieldset><legend>Additional individually granted capabilities</legend>
<div class="stsams-grants">@foreach($grantable as $permission)
<label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', []), true))> {{ ucwords(str_replace(['.','_'], ' ', $permission)) }}</label>
@endforeach</div></fieldset>
<button type="submit" class="button primary">Create staff administrator</button></form></section>
<section class="card section"><h2>Manage existing staff</h2>
@forelse($staff as $member)
<form method="POST" action="{{ route('stsams.staff-management.update', $member) }}" class="stsams-staff-row">@csrf @method('PATCH')
<div><strong>{{ $member->email }}</strong><small>Staff ID: {{ $member->id }} · Direct grants are shown below</small></div>
<div class="two-column"><label>Name<input name="name" value="{{ $member->name }}" required></label>
<label>Role<select name="role">@foreach($roles as $role)<option value="{{ $role }}" @selected($member->hasRole($role))>{{ ucwords(str_replace('_', ' ', $role)) }}</option>@endforeach</select></label></div>
<div class="stsams-grants">@foreach($grantable as $permission)
<label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($member->hasDirectPermission($permission))> {{ ucwords(str_replace(['.','_'], ' ', $permission)) }}</label>
@endforeach</div><button class="button light" type="submit">Save access</button></form>
@empty<p>No staff accounts yet.</p>@endforelse
{{ $staff->links() }}</section>
<section class="card section"><h2>Recent access changes</h2>
@forelse($audit as $item)<p><strong>{{ $item->action }}</strong> · {{ $item->staffUser?->email }} · by {{ $item->actor?->email }} · {{ $item->created_at?->format('d M Y H:i') }}</p>
@empty<p>No changes recorded yet.</p>@endforelse</section>
<style>.stsams-grants{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;padding:12px 0}.stsams-grants label{display:flex;align-items:center;gap:8px}.stsams-staff-row{padding:20px 0;border-bottom:1px solid #dce6ee}.stsams-staff-row small{display:block;color:#607c8e}</style>
@endsection
