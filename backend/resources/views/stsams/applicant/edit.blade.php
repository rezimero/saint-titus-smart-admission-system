@extends('stsams.layout')
@section('title', 'Application documents and payment')
@section('content')
<div class="page-head"><div><a class="back" href="{{ route('stsams.applicant.index') }}">← Applicant dashboard</a>
<span class="eyebrow">APPLICATION WORKSPACE</span><h1>{{ $application->programme->name }}</h1>
<p>{{ $application->admissionSession->name }} · {{ $application->reference ?? 'Draft — acknowledgement generated on submission' }}</p></div>
<span class="pill">{{ str_replace('_', ' ', strtoupper($application->status)) }}</span></div>
<div class="two-column"><section class="card"><h2>1. Applicant details</h2>
@if($application->status === 'draft')
<form method="POST" action="{{ route('stsams.applicant.update', $application) }}">@csrf
<label>Contact phone<input name="phone" maxlength="32" value="{{ old('phone', $application->phone) }}" required></label>
<label>Date of birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth', $application->date_of_birth?->format('Y-m-d')) }}" required></label>
<label>Residential address<textarea name="address" rows="3" required>{{ old('address', $application->address) }}</textarea></label>
<label>Previous institution<input name="prior_school" maxlength="200" value="{{ old('prior_school', $application->prior_school) }}" required></label>
<label>Qualification<input name="qualification" maxlength="160" value="{{ old('qualification', $application->qualification) }}" required></label>
<button class="button primary" type="submit">Save application details</button></form>
@else
<dl class="details"><dt>Phone</dt><dd>{{ $application->phone }}</dd><dt>Date of birth</dt><dd>{{ $application->date_of_birth?->format('d M Y') }}</dd><dt>Address</dt><dd>{{ $application->address }}</dd><dt>Previous school</dt><dd>{{ $application->prior_school }}</dd><dt>Qualification</dt><dd>{{ $application->qualification }}</dd></dl>
@endif
</section><section class="card"><h2>2. Application fee</h2>
@php($fee = $application->admissionSession->application_fee_kobo)
@php($paid = $application->payments->firstWhere('status', 'paid'))
@if($fee === null)
<p class="notice">The admissions office has not yet published the approved application fee. Online payment and final submission are unavailable.</p>
@elseif($fee === 0)
<p><strong>Application fee waived by the institution.</strong> No payment required.</p>
@elseif($paid)
<p class="payment-good">✓ Payment verified by Paystack.</p><p><strong>₦{{ number_format($paid->amount_kobo / 100, 2) }}</strong></p>
<a class="button light" href="{{ route('stsams.payments.receipt', $application) }}" target="_blank" rel="noopener">Print payment receipt</a>
@else
<p>Approved application fee: <strong>₦{{ number_format($fee / 100, 2) }}</strong></p>
<p>Payment is processed securely by Paystack. Your application is not marked paid until the server verifies it.</p>
@if($application->status === 'draft')
@if(config('stsams_payments.secret_key'))
<form method="POST" action="{{ route('stsams.payments.initialize', $application) }}">@csrf<button class="button primary" type="submit">Pay securely with Paystack</button></form>
@else<p class="notice">Paystack has not been configured. Please contact the admissions office.</p>@endif
@endif
@endif
</section></div>
<section class="card section"><div class="section-head"><div><span class="eyebrow">SUPPORTING DOCUMENTS</span><h2>3. Upload your documents</h2></div><span class="pill">Private storage</span></div>
<p>PDF, JPG, JPEG or PNG · Maximum 5MB per document. Passport photographs must be JPG, JPEG or PNG. Upload each document in its own slot.</p>
<p class="small">Required documents must be present before submission. Conditional documents apply when requested by the college. You may replace or remove files while this application is a draft.</p>
@php($filesByType = $application->documents->sortByDesc('id')->keyBy('type'))
@foreach(collect($documentRequirements)->groupBy('group') as $group => $slots)
<h3 class="document-group-title">{{ $group }}</h3>
<div class="document-grid">
@foreach($slots as $slot)
@if($slot['requirement'] !== 'not_applicable')
@php($document = $filesByType->get($slot['key']))
<article class="document-slot">
<div class="document-slot-top"><strong>{{ $slot['label'] }}</strong>
<span class="requirement-pill requirement-{{ $slot['requirement'] }}">{{ str_replace('_', ' ', ucfirst($slot['requirement'])) }}</span></div>
<div class="small">{{ ($slot['images_only'] ?? false) ? 'JPG / JPEG / PNG' : 'PDF / JPG / JPEG / PNG' }} · Max 5MB</div>
@if($document)
<div class="upload-state">✓ Uploaded: {{ $document->original_name }}</div>
<div class="small">Security scan: {{ $document->scan_status === 'clean' ? 'Passed' : 'Not yet scanned (development)' }}</div>
<div class="document-actions"><a target="_blank" rel="noopener" href="{{ route('stsams.documents.preview', [$application, $document]) }}">Preview</a>
<a href="{{ route('stsams.documents.download', [$application, $document]) }}">Download</a>
@if($application->status === 'draft')<form method="POST" action="{{ route('stsams.documents.remove', [$application, $document]) }}">@csrf @method('DELETE')<button class="link-button" type="submit">Remove</button></form>@endif
</div>
@else<div class="upload-missing">No document uploaded yet</div>@endif
@if($application->status === 'draft')
<form method="POST" enctype="multipart/form-data" action="{{ route('stsams.documents.upload', $application) }}">@csrf
<input type="hidden" name="type" value="{{ $slot['key'] }}">
<label for="document-{{ $slot['key'] }}">{{ $document ? 'Replace this document' : 'Choose file to upload' }}
<input id="document-{{ $slot['key'] }}" name="document" type="file" accept="{{ ($slot['images_only'] ?? false) ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' }}" required></label>
<button type="submit" class="button light">{{ $document ? 'Replace securely' : 'Upload securely' }}</button></form>
@endif
</article>
@endif
@endforeach
</div>
@endforeach
</section>
<section class="card section"><h2>4. Final submission and acknowledgement</h2>
@if($application->status === 'draft')
<p>Review the details above. All required documents and a verified payment (if a fee applies) are required before submission. The application becomes read-only after submission.</p>
<form method="POST" action="{{ route('stsams.applicant.submit', $application) }}">@csrf
<button class="button primary" type="submit">Submit completed application</button></form>
@else<p class="payment-good">Your application has been submitted.</p><a class="button light" href="{{ route('stsams.applicant.acknowledgement', $application) }}" target="_blank" rel="noopener">Print acknowledgement slip</a>@endif
</section>
<section class="card section"><h2>Application progress</h2>
@foreach($application->events as $event)<div class="timeline"><div class="timeline-marker"></div><div><strong>{{ str_replace('_', ' ', ucfirst($event->to_status)) }}</strong><div class="small">{{ $event->created_at?->format('d M Y, H:i') }} · {{ $event->actor?->name }}</div>@if($event->note)<p>{{ $event->note }}</p>@endif</div></div>@endforeach
</section>
@endsection
