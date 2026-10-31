@extends('stsams.layout')
@section('title', 'Admissions')
@section('content')
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">YOUR FUTURE STARTS HERE</span>
        <h1>Your next chapter begins at Saint Titus.</h1>
        <p>Discover programmes, apply securely, submit your supporting documents and follow your admission decision in one place.</p>
        <div class="actions"><a class="button primary" href="{{ auth()->check() ? route('stsams.dashboard') : route('stsams.register') }}">Start an application</a><a class="button light" href="#programmes">Explore programmes</a></div>
    </div>
    <div class="hero-panel"><div class="panel-tag">ADMISSIONS PORTAL</div><div class="panel-visual">01 <span>→</span> 02 <span>→</span> 03</div><div class="steps"><div>01 <b>Create an account</b></div><div>02 <b>Apply & upload</b></div><div>03 <b>Track your status</b></div></div></div>
</section>
<section class="section" id="programmes">
    <div class="section-head"><div><span class="eyebrow">ACADEMIC OFFERINGS</span><h2>Available programmes</h2></div><span class="pill">{{ $programmes->count() }} listed</span></div>
    @if($programmes->isEmpty())<div class="card empty">Programme information will appear here when admissions administration publishes it.</div>
    @else <div class="card-grid">@foreach($programmes as $programme)<article class="card"><span class="eyebrow">{{ $programme->code }}</span><h3>{{ $programme->name }}</h3><p>{{ $programme->description ?? 'Apply through the Saint Titus Smart Admissions Portal.' }}</p></article>@endforeach</div>@endif
</section>
<section class="section"><div class="section-head"><div><span class="eyebrow">APPLICATION WINDOW</span><h2>Admission sessions</h2></div></div>
    @forelse($sessions as $session)<div class="card compact"><b>{{ $session->name }}</b><span class="pill {{ $session->acceptsApplications() ? 'green' : '' }}">{{ $session->acceptsApplications() ? 'Accepting applications' : 'Not accepting applications' }}</span></div>
    @empty<div class="card empty">No admission session is open yet. Please check back for an announcement.</div>@endforelse
</section>
@endsection
