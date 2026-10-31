@extends('stsams.layout')
@section('title', 'Review application')
@section('content')
<div class="page-head"><div><a class="back" href="{{ route('stsams.staff.index') }}">← Review queue</a><span class="eyebrow">APPLICATION REVIEW</span><h1>{{ $application->applicant->name }}</h1><p>{{ $application->programme->name }} · {{ $application->reference ?? 'Draft' }}</p></div><span class="pill">{{ str_replace('_', ' ', strtoupper($application->status)) }}</span></div>
<div class="two-column"><section class="card"><h2>Application information</h2><dl class="details"><dt>Email</dt><dd>{{ $application->applicant->email }}</dd><dt>Phone</dt><dd>{{ $application->phone }}</dd><dt>Birth date</dt><dd>{{ $application->date_of_birth?->format('d M Y') }}</dd><dt>Address</dt><dd>{{ $application->address }}</dd><dt>Previous school</dt><dd>{{ $application->prior_school }}</dd><dt>Qualification</dt><dd>{{ $application->qualification }}</dd></dl></section>
<section class="card"><h2>Documents</h2>@forelse($application->documents as $document)<div class="file-row"><span>{{ ucfirst($document->type) }}</span><a href="{{ route('stsams.documents.download', [$application, $document]) }}">Download</a></div>@empty<p>No documents uploaded.</p>@endforelse</section></div>
@if(($application->status === 'submitted' && auth()->user()->can('applications.review')) || ($application->status === 'under_review' && (auth()->user()->can('applications.approve') || auth()->user()->can('applications.reject'))))
<section class="card section"><h2>Take action</h2><form method="POST" action="{{ route('stsams.staff.transition', $application) }}">@csrf
<label>Decision<select name="status" required>
@if($application->status === 'submitted' && auth()->user()->can('applications.review'))<option value="under_review">Begin review</option>@endif
@if($application->status === 'under_review' && auth()->user()->can('applications.approve'))<option value="approved">Approve</option>@endif
@if($application->status === 'under_review' && auth()->user()->can('applications.reject'))<option value="rejected">Reject</option>@endif
</select></label><label>Review note <small>(required when rejecting)</small><textarea name="note" rows="3" maxlength="2000"></textarea></label><button class="button primary" type="submit">Record decision</button></form></section>@endif
<section class="card section"><h2>Audit trail</h2>@foreach($application->events as $event)<div class="timeline"><div class="timeline-marker"></div><div><strong>{{ ucfirst(str_replace('_', ' ', $event->to_status)) }}</strong><div class="small">{{ $event->created_at?->format('d M Y, H:i') }} · {{ $event->actor?->name }}</div>@if($event->note)<p>{{ $event->note }}</p>@endif</div></div>@endforeach</section>
@endsection
