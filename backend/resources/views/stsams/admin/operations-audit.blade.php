@extends('stsams.layout')
@section('title', 'Operations audit register')
@section('content')
<div class="page-head"><div><span class="eyebrow">SUPERADMIN OVERSIGHT</span><h1>Operations audit register</h1><p>Assignment, document-verification and institutional publication activities.</p></div></div>
<div class="card section"><form method="GET" class="filter"><label>Activity type<select name="subject">
<option value="">All activities</option>
@foreach(['application','document','website_content','website_post','website_media','website_inquiry'] as $type)
<option value="{{ $type }}" @selected(request('subject') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
@endforeach</select></label><button class="button light">Filter</button></form></div>
<div class="card table-wrap"><table><thead><tr><th>When</th><th>Actor</th><th>Activity</th><th>Record</th><th>Change metadata</th></tr></thead><tbody>
@forelse($audits as $audit)<tr><td>{{ $audit->created_at?->format('d M Y, H:i') }}</td><td>{{ $audit->actor?->name ?? 'Account deleted' }}</td><td>{{ $audit->action }}</td><td>{{ $audit->subject_type }} #{{ $audit->subject_id }}</td><td><code>{{ json_encode($audit->metadata, JSON_UNESCAPED_SLASHES) }}</code></td></tr>
@empty<tr><td colspan="5">No audit entries recorded.</td></tr>@endforelse</tbody></table></div>
<div class="pagination">{{ $audits->links() }}</div>
@endsection
