@extends('institutional.layout')
@section('content')
<section class="page-hero"><div class="shell"><span class="eyebrow light">SAINT TITUS COLLEGE</span><h1>{{ $title }}</h1><p>{{ $subtitle }}</p></div></section>
<div class="shell breadcrumb"><a href="{{ route('institution.home') }}">Home</a><span> / </span><strong>{{ $title }}</strong></div>
@if($section === 'admissions')
<section class="shell priority-cta"><div><span class="chip">THE ADMISSION PORTAL</span><h2>Apply online with confidence.</h2><p>Create your account, upload documents, submit your application and track decisions in our secure portal.</p><div class="tiny-links"><a href="{{ url('/admissions/register') }}">New applicant? Register ↗</a><a href="{{ url('/admissions/login') }}">Already applied? Sign in ↗</a></div></div><a class="btn btn-gold" href="{{ url('/admissions') }}">Open application portal ↗</a></section>
@endif
@if($section === 'academics')
<section class="shell compact-section"><div class="heading-pair"><div><span class="eyebrow">ACADEMIC PATHWAYS</span><h2>Programme categories</h2></div></div><div class="cards-three"><div class="feature-card static"><span class="feature-no">ND</span><h3>National Diploma</h3><p>Verified programme listings and availability will be published by the institution.</p></div><div class="feature-card static"><span class="feature-no">HND</span><h3>Higher National Diploma</h3><p>Programme offerings are subject to official institutional confirmation.</p></div><div class="feature-card static"><span class="feature-no">CERT</span><h3>Certificate</h3><p>Check the admissions portal for officially published programme options.</p></div></div><p class="subtle-note">These are information categories, not claims of current programme approval or accreditation. <a href="{{ url('/admissions') }}">View the current admissions catalogue.</a></p></section>
@endif
<section class="shell section"><div class="heading-pair"><div><span class="eyebrow">INSTITUTIONAL INFORMATION</span><h2>Information & services</h2></div></div>
<div class="topic-grid">@foreach($topics as $key => $label)<article class="topic-card" id="{{ $key }}"><span class="topic-mark">✦</span><h3>{{ $label }}</h3>@if(!empty($content[$key]))<p class="body-text">{{ $content[$key] }}</p>@else<p class="pending-text">Official information for this section will be published once confirmed by the institution.</p>@endif</article>@endforeach</div>
</section>
@if($section === 'news')
<section class="section pale"><div class="shell"><div class="heading-pair"><div><span class="eyebrow">LATEST UPDATES</span><h2>Official publications and events</h2></div></div><div class="publication-list">@forelse($posts as $post)<a class="publication" href="{{ route('institution.post', $post->slug) }}"><span class="chip">{{ ucfirst($post->category) }}</span><div><h3>{{ $post->title }}</h3><p>{{ $post->summary }}</p>@if($post->event_starts_at)<small>Event: {{ $post->event_starts_at->format('d M Y, g:i A') }} · {{ $post->event_location }}</small>@endif</div><span aria-hidden="true">↗</span></a>@empty<div class="empty-state">No official news or events have been published. Check back for verified announcements.</div>@endforelse</div>
 <div class="heading-pair spacing-top"><div><span class="eyebrow">THE CAMPUS IN PICTURES</span><h2>Photo gallery</h2></div></div><div class="gallery-grid">@forelse($gallery as $photo)<figure><img src="{{ Storage::disk('public')->url($photo->file_path) }}" alt="{{ $photo->alt_text }}"><figcaption>{{ $photo->title }}</figcaption></figure>@empty<div class="empty-state">The official photo gallery is awaiting approved campus images.</div>@endforelse</div></div></section>
@endif
@if($section === 'admissions')
<section class="closing-cta"><div class="shell closing-inner"><div><span class="eyebrow light">YOUR APPLICATION</span><h2>Everything starts with your applicant account.</h2><p>Use the secure admissions system to manage your application.</p></div><a class="btn btn-gold" href="{{ url('/admissions') }}">Go to portal ↗</a></div></section>
@endif
@endsection
