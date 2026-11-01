@extends('stsams.layout')
@section('title', 'Admissions operations dashboard')
@section('content')
<div class="page-head"><div><span class="eyebrow">ADMISSIONS OPERATIONS</span><h1>Welcome, {{ auth()->user()->name }}</h1><p>Assigned review work, application health and delegated decisions.</p></div><a class="button light" href="{{ route('stsams.account.show') }}">My profile</a></div>
<section class="ops-grid" aria-label="Admissions statistics">
@foreach(['submitted'=>'Awaiting review','under_review'=>'Under review','mine'=>'Assigned to me','unassigned'=>'Unassigned','offered'=>'Offers issued','rejected'=>'Rejected'] as $key=>$label)
<div class="card ops-stat"><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div>
@endforeach
</section>
<form method="GET" class="card ops-filters"><label>My workload<select name="scope"><option value="">All accessible</option><option value="mine" @selected(request('scope')==='mine')>Assigned to me</option><option value="unassigned" @selected(request('scope')==='unassigned')>Unassigned</option></select></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(['submitted','under_review','offered','rejected'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></label><button class="button primary">Filter applications</button></form>
<div class="card table-wrap"><table><thead><tr><th>Applicant</th><th>Programme</th><th>Reference</th><th>Assigned reviewer</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($applications as $application)
<tr><td>{{ $application->applicant?->name }}<small>{{ $application->applicant?->email }}</small></td><td>{{ $application->programme?->name }}</td><td>{{ $application->reference ?? '—' }}</td><td>{{ $application->reviewer?->name ?? 'Unassigned' }}</td><td><span class="pill">{{ str_replace('_',' ',$application->status) }}</span></td><td><a href="{{ route('stsams.staff.show', $application) }}">Open case</a></td></tr>
@empty<tr><td colspan="6">No applications match these filters.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $applications->links() }}</div>
<style>.ops-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin:14px 0 24px}.ops-stat{display:flex;flex-direction:column;gap:10px;padding:18px}.ops-stat small{color:#496275}.ops-stat strong{font-size:2rem;color:#102f49}.ops-filters{display:flex;align-items:end;flex-wrap:wrap;gap:14px;padding:18px;margin:0 0 18px}.ops-filters label{flex:1;min-width:180px}</style>
@endsection
