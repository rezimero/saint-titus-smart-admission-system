@extends('stsams.layout')
@section('title', 'Admissions review queue')
@section('content')
<div class="page-head"><div><span class="eyebrow">ADMISSIONS OPERATIONS</span><h1>Application review queue</h1><p>Applications, decisions and audit trails.</p></div><span class="pill">{{ $applications->total() }} record(s)</span></div>
<form method="GET" class="filter"><label>Filter status<select name="status"><option value="">All statuses</option>@foreach(['draft','submitted','under_review','approved','rejected'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></label><button class="button light" type="submit">Filter</button></form>
<div class="card table-wrap"><table><thead><tr><th>Applicant</th><th>Programme</th><th>Reference</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($applications as $application)<tr><td>{{ $application->applicant->name }}<small>{{ $application->applicant->email }}</small></td><td>{{ $application->programme->name }}<small>{{ $application->admissionSession->name }}</small></td><td>{{ $application->reference ?? 'Draft' }}</td><td><span class="pill">{{ str_replace('_',' ', $application->status) }}</span></td><td><a href="{{ route('stsams.staff.show', $application) }}">Review →</a></td></tr>
@empty<tr><td colspan="5">No applications match this filter.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $applications->links() }}</div>
@endsection
