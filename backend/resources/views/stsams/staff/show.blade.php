@extends('stsams.layout')
@section('title', 'Admissions case review')
@section('content')
<div class="page-head"><div><a class="back" href="{{ route('stsams.staff.index') }}">← Operations dashboard</a><span class="eyebrow">CASE REVIEW</span><h1>{{ $application->applicant?->name }}</h1><p>{{ $application->programme?->name }} · {{ $application->reference ?? 'Draft' }}</p></div><span class="pill">{{ ucwords(str_replace('_',' ',$application->status)) }}</span></div>
<section class="card section"><h2>Review ownership</h2><p>Assigned reviewer: <strong>{{ $application->reviewer?->name ?? 'Unassigned' }}</strong></p>
@if(!$application->assigned_reviewer_id && auth()->user()->can('applications.review') && in_array($application->status,['submitted','under_review']))
<form method="POST" action="{{ route('stsams.ops.claim',$application) }}">@csrf<button class="button primary">Claim this application</button></form>
@endif
@can('applications.assign')
@if(in_array($application->status,['submitted','under_review']))
<form method="POST" action="{{ route('stsams.ops.assign',$application) }}">@csrf<label>Assign to authorized reviewer<select name="reviewer_id"><option value="">Unassigned</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected($application->assigned_reviewer_id == $reviewer->id)>{{ $reviewer->name }} ({{ $reviewer->email }})</option>@endforeach</select></label><button class="button light">Save reviewer assignment</button></form>
@endif
@endcan
</section>
<div class="two-column"><section class="card"><h2>Applicant details</h2><dl class="details"><dt>Email</dt><dd>{{ $application->applicant?->email }}</dd><dt>Telephone</dt><dd>{{ $application->phone }}</dd><dt>Date of birth</dt><dd>{{ $application->date_of_birth?->format('d M Y') }}</dd><dt>Address</dt><dd>{{ $application->address }}</dd><dt>Previous school</dt><dd>{{ $application->prior_school }}</dd><dt>Qualification</dt><dd>{{ $application->qualification }}</dd></dl></section>
<section class="card"><h2>Credential verification</h2><p>Each uploaded document must be individually verified before an admission offer can be issued. Human verification is not malware scanning.</p>
@forelse($application->documents as $document)
<div class="ops-document"><strong>{{ ucwords(str_replace('_',' ',$document->type)) }}</strong><small>Status: {{ $document->verification_status }} · Malware scan: {{ $document->scan_status }}</small>
<a href="{{ route('stsams.documents.preview',[$application,$document]) }}" target="_blank" rel="noopener noreferrer">Secure preview</a> · <a href="{{ route('stsams.documents.download',[$application,$document]) }}">Download</a>
@if($application->status==='under_review' && (auth()->id()===$application->assigned_reviewer_id || auth()->user()->hasRole('superadmin')) && auth()->user()->can('applications.review'))
<form method="POST" action="{{ route('stsams.ops.document.review',[$application,$document]) }}">@csrf<label>Decision<select name="decision"><option value="verified">Verify credential</option><option value="rejected">Reject credential</option></select></label><label>Review note<textarea name="note" rows="2" maxlength="2000">{{ $document->verification_note }}</textarea></label><button class="button light">Save verification</button></form>
@endif
</div>
@empty<p>No documents uploaded.</p>@endforelse</section></div>
<section class="card section"><h2>Application payment</h2>@forelse($application->payments->where('status','paid') as $payment)<p>Verified Paystack receipt: ₦{{ number_format($payment->amount_kobo/100,2) }} · {{ $payment->reference }}</p>@empty<p>No electronically verified payment (the fee may be waived or payments suspended).</p>@endforelse</section>
@if(($application->status==='submitted' && auth()->user()->can('applications.review') && ($application->assigned_reviewer_id===auth()->id() || auth()->user()->hasRole('superadmin'))) || ($application->status==='under_review' && (auth()->user()->can('applications.offer_admission') || auth()->user()->can('applications.reject'))))
<section class="card section"><h2>Admission decision</h2><form method="POST" action="{{ route('stsams.staff.transition',$application) }}">@csrf
<label>Action<select name="status" required>
@if($application->status==='submitted' && auth()->user()->can('applications.review') && ($application->assigned_reviewer_id===auth()->id() || auth()->user()->hasRole('superadmin')))<option value="under_review">Start review</option>@endif
@if($application->status==='under_review' && auth()->user()->can('applications.offer_admission'))<option value="offered">Issue admission offer</option>@endif
@if($application->status==='under_review' && auth()->user()->can('applications.reject'))<option value="rejected">Reject application</option>@endif
</select></label><label>Decision justification <small>(mandatory for offers and rejection)</small><textarea name="note" rows="4" maxlength="2000"></textarea></label><button class="button primary">Record audited decision</button></form></section>
@endif
<section class="card section"><h2>Application history</h2>@forelse($application->events as $event)<div class="timeline"><div class="timeline-marker"></div><div><strong>{{ ucwords(str_replace('_',' ',$event->to_status)) }}</strong><p>{{ $event->created_at?->format('d M Y, H:i') }} · {{ $event->actor?->name }}</p>@if($event->note)<p>{{ $event->note }}</p>@endif</div></div>@empty<p>No events recorded.</p>@endforelse</section>
<style>.ops-document{border:1px solid #d8e3ed;border-radius:12px;padding:14px;margin:12px 0}.ops-document small{display:block;color:#476578;margin:6px 0}.ops-document form{margin-top:12px}</style>
@endsection
