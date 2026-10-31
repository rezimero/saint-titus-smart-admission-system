@extends('stsams.layout')
@section('title', 'Applicant workspace')
@section('content')
<div class="page-head"><div><span class="eyebrow">APPLICANT WORKSPACE</span><h1>Hello, {{ auth()->user()->name }}</h1><p>Create and monitor your admission applications.</p></div><span class="pill">{{ $applications->count() }} application(s)</span></div>
<div class="two-column"><section class="card"><h2>Start a new application</h2><p>Choose your intended programme and current admission session.</p>
@if($sessions->isEmpty() || $programmes->isEmpty())<p class="notice">Applications are currently unavailable. The institution must open a session and publish a programme.</p>
@else<form method="POST" action="{{ route('stsams.applicant.create') }}">@csrf
<label>Admission session<select name="admission_session_id" required>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->name }}</option>@endforeach</select></label>
<label>Programme<select name="programme_id" required>@foreach($programmes as $programme)<option value="{{ $programme->id }}">{{ $programme->name }}</option>@endforeach</select></label>
<button class="button primary" type="submit">Start application</button></form>@endif
</section>
<section class="card"><h2>How it works</h2><ol class="ordered"><li>Select your programme</li><li>Save your personal and academic details</li><li>Upload supporting documentation</li><li>Submit and track decisions</li></ol><p class="small">You may only edit applications while they are drafts.</p></section></div>
<section class="section"><div class="section-head"><h2>My applications</h2></div>
@forelse($applications as $application)<a class="card application-row" href="{{ route('stsams.applicant.edit', $application) }}"><div><strong>{{ $application->programme->name }}</strong><small>{{ $application->admissionSession->name }} · {{ $application->reference ?? 'Draft / no reference yet' }}</small></div><span class="pill">{{ str_replace('_', ' ', ucfirst($application->status)) }}</span></a>
@empty<div class="card empty">You have not started an application yet.</div>@endforelse</section>
@endsection
