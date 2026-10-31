@extends('stsams.layout')
@section('title', 'Admission application')
@section('content')
<div class="page-head"><div><a class="back" href="{{ route('stsams.applicant.index') }}">← Applicant dashboard</a><span class="eyebrow">ADMISSION APPLICATION</span><h1>{{ $application->programme->name }}</h1><p>{{ $application->admissionSession->name }} · {{ $application->reference ?? 'Draft — reference issued on submission' }}</p></div><span class="pill">{{ str_replace('_', ' ', strtoupper($application->status)) }}</span></div>
<div class="two-column"><section class="card"><h2>Applicant details</h2>
@if($application->status === 'draft')
<form method="POST" action="{{ route('stsams.applicant.update', $application) }}">@csrf
<label>Contact phone<input name="phone" maxlength="32" value="{{ old('phone', $application->phone) }}" required></label>
<label>Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', $application->date_of_birth?->format('Y-m-d')) }}" required></label>
<label>Residential address<textarea name="address" rows="3" required>{{ old('address', $application->address) }}</textarea></label>
<label>Previous institution<input name="prior_school" maxlength="200" value="{{ old('prior_school', $application->prior_school) }}" required></label>
<label>Qualification<input name="qualification" maxlength="160" value="{{ old('qualification', $application->qualification) }}" required></label>
<button class="button primary" type="submit">Save application details</button></form>
@else<dl class="details"><dt>Phone</dt><dd>{{ $application->phone }}</dd><dt>Birth date</dt><dd>{{ $application->date_of_birth?->format('d M Y') }}</dd><dt>Address</dt><dd>{{ $application->address }}</dd><dt>Previous institution</dt><dd>{{ $application->prior_school }}</dd><dt>Qualification</dt><dd>{{ $application->qualification }}</dd></dl>@endif
</section><section class="card"><h2>Supporting documents</h2><p>Files are private; only you and authorized admissions staff may download them. PDF, JPEG or PNG, up to 5 MB.</p>
@foreach($application->documents as $document)<div class="file-row"><span>{{ ucfirst($document->type) }}</span><a href="{{ route('stsams.documents.download', [$application, $document]) }}">Download</a></div>@endforeach
@if($application->status === 'draft')<form method="POST" action="{{ route('stsams.documents.upload', $application) }}" enctype="multipart/form-data">@csrf
<label>Document type<select name="type"><option value="certificate">Certificate</option><option value="transcript">Transcript</option><option value="passport">Passport photo</option><option value="other">Other supporting document</option></select></label>
<label>Choose file<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label><button class="button light" type="submit">Upload securely</button></form>@endif
</section></div>
<section class="section card"><h2>Application progress</h2>
@foreach($application->events as $event)<div class="timeline"><div class="timeline-marker"></div><div><strong>{{ str_replace('_', ' ', ucfirst($event->to_status)) }}</strong><div class="small">{{ $event->created_at?->format('d M Y, H:i') }} · {{ $event->actor?->name }}</div>@if($event->note)<p>{{ $event->note }}</p>@endif</div></div>@endforeach
@if($application->status === 'draft')<div class="submission"><p>Check your details and upload at least one document before submitting. Submission locks the application against editing.</p><form method="POST" action="{{ route('stsams.applicant.submit', $application) }}">@csrf<button class="button primary" type="submit">Submit for admission review</button></form></div>@endif
</section>
@endsection
