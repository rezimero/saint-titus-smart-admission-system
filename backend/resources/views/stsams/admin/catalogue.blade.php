@extends('stsams.layout')
@section('title', 'Admission configuration')
@section('content')
<div class="page-head"><div><span class="eyebrow">SYSTEM CONFIGURATION</span><h1>Admission setup</h1><p>Use only programmes, admission requirements, sessions and fees officially approved by the college.</p></div></div>
<div class="two-column"><section class="card"><h2>Create programme</h2>
<form method="POST" action="{{ route('stsams.admin.programme') }}">@csrf
<label>Programme code<input name="code" maxlength="32" placeholder="e.g. NDCH" required></label>
<label>Approved programme name<input name="name" maxlength="160" required></label>
<label>Programme classification<select name="admission_track" required>@foreach($admissionTracks as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></label>
<label>Description<textarea name="description" rows="3"></textarea></label>
<button class="button primary" type="submit">Add programme</button></form>
</section><section class="card"><h2>Create admission session</h2>
<form method="POST" action="{{ route('stsams.admin.session') }}">@csrf
<label>Session name<input name="name" placeholder="e.g. 2027/2028" maxlength="80" required></label>
<label>Opens on<input type="date" name="opens_on"></label><label>Closes on<input type="date" name="closes_on"></label>
<label>Approved application fee (₦)<input inputmode="decimal" name="application_fee_naira" placeholder="Enter approved amount or 0 if waived" pattern="[0-9]{1,7}(\.[0-9]{1,2})?" required></label>
<p class="small">The fee is stored in kobo. Do not enter an estimated amount. This is separate from an eventual acceptance fee.</p>
<button class="button primary" type="submit">Create admission session</button></form></section></div>
<section class="section"><h2>Programmes and document requirements</h2><p>Default: passport, birth certificate/age declaration, origin certificate and first O'Level result are required. JAMB and professional documents are conditional unless explicitly configured as required.</p>
@forelse($programmes as $programme)
<details class="card admin-details"><summary><strong>{{ $programme->name }}</strong> <span class="small">{{ $programme->code }} · {{ $admissionTracks[$programme->admission_track] ?? 'General' }} · {{ $programme->is_active ? 'Active' : 'Inactive' }}</span></summary>
@php($saved = $programme->documentRequirements->pluck('requirement', 'document_type'))
<form method="POST" action="{{ route('stsams.admin.programme.requirements', $programme) }}">@csrf @method('PUT')
<label>Programme classification<select name="admission_track">@foreach($admissionTracks as $code => $label)<option value="{{ $code }}" @selected($programme->admission_track === $code)>{{ $label }}</option>@endforeach</select></label>
@foreach($documentTypes as $type => $spec)
@php($value = $saved->get($type, $spec['default']))
<label>{{ $spec['label'] }}
<select name="requirements[{{ $type }}]">
@foreach(['required' => 'Required', 'conditional' => 'Conditional / if applicable', 'optional' => 'Optional', 'not_applicable' => 'Not applicable'] as $key => $label)
<option value="{{ $key }}" @selected($value === $key)>{{ $label }}</option>
@endforeach
</select></label>
@endforeach
<button class="button primary" type="submit">Save programme requirements</button></form></details>
@empty<div class="card">No programmes have been configured.</div>@endforelse</section>
<section class="section"><h2>Admission sessions and fees</h2><div class="card">
@forelse($sessions as $session)<div class="session-admin-row"><div><strong>{{ $session->name }}</strong><div class="small">{{ $session->is_open ? 'Open' : 'Closed' }} · Fee: {{ $session->application_fee_kobo === null ? 'Not configured' : '₦'.number_format($session->application_fee_kobo / 100, 2) }}</div></div>
<form method="POST" action="{{ route($session->is_open ? 'stsams.admin.session.close' : 'stsams.admin.session.open', $session) }}">@csrf<button class="button light" type="submit">{{ $session->is_open ? 'Close session' : 'Open session' }}</button></form>
@if($session->application_fee_kobo === null)
<form method="POST" action="{{ route('stsams.admin.session.fee', $session) }}">@csrf @method('PUT')<label>Configure approved fee (₦)<input name="application_fee_naira" inputmode="decimal" pattern="[0-9]{1,7}(\.[0-9]{1,2})?" placeholder="Approved amount or 0 if waived" required></label><p class="small">First-time fee setup is permitted with draft applications only. Submitted applications and existing payment records prevent changes.</p><button class="button light" type="submit">Set fee</button></form>
@endif</div>
@empty<p>No admission sessions yet.</p>@endforelse</div></section>
@endsection
