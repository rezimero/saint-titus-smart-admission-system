@extends('institutional.layout')
@section('content')
<section class="page-hero"><div class="shell"><span class="eyebrow light">SAINT TITUS COLLEGE</span><h1>{{ $title }}</h1><p>{{ $subtitle }}</p></div></section>
<div class="shell breadcrumb"><a href="{{ route('institution.home') }}">Home</a><span> / </span><strong>{{ $title }}</strong></div>
@if($section === 'admissions')
<section class="shell priority-cta"><div><span class="chip">OFFICIAL ADMISSIONS INFORMATION</span>
 @if(config('stsams_launch.admissions_enabled', false))
 <h2>Explore admission opportunities.</h2><p>Use the official admissions portal for current application sessions, eligibility and instructions.</p>
 @else
 <h2>Application intake is not yet open online.</h2><p>Our institutional website is available for programme information. New application registration, submissions and payments remain closed until an official announcement.</p>
 @endif
 </div>@if(config('stsams_launch.admissions_enabled', false))<a class="btn btn-gold" href="{{ url('/admissions') }}">Admissions portal ↗</a>@else<a class="btn btn-gold" href="{{ route('institution.contact') }}">Contact the College ↗</a>@endif</section>
@endif
@if($section === 'academics')
<section class="shell compact-section"><div class="heading-pair"><div><span class="eyebrow">SCHOOL OF HEALTH SCIENCES</span><h2>Current community health pathways</h2></div></div>
 <div class="cards-three">
  <article class="feature-card static"><span class="feature-no">JCHEW</span><h3>Junior Community Health Extension Worker</h3><p>A current community health programme under the regulatory supervision of CHPRBN.</p></article>
  <article class="feature-card static"><span class="feature-no">CHEW</span><h3>Community Health Extension Worker</h3><p>A current professional community health programme under the regulatory supervision of CHPRBN.</p></article>
  <article class="feature-card static editorial-feature"><span class="feature-no">NEW ACCREDITATION · NBTE</span><h3>National Diploma in CHEW</h3><p>Saint Titus College has received NBTE accreditation to run an ND programme in Community Health Extension Worker (CHEW).</p></article>
 </div>
 <p class="subtle-note">Accreditation does not by itself announce an application window. Programme admission dates, entry requirements and fees will be announced separately.</p>
 </section>
 <section class="section pale"><div class="shell"><div class="heading-pair"><div><span class="eyebrow">SCHOOL OF TECHNOLOGY</span><h2>Practical and vocational training areas</h2></div></div>
  <div class="editorial-tags"><span>Information &amp; Communication Technology (ICT)</span><span>Solar &amp; Renewable Energy</span><span>Creative Arts</span><span>Industrial Crafts</span><span>Mechanical Services</span><span>Beauty &amp; Fashion Technology</span></div>
  <p class="subtle-note">Technology training areas are presented separately from NBTE-accredited ND CHEW; no other ND accreditation is implied.</p>
 </div></section>
 <section class="section shell"><div class="heading-pair"><div><span class="eyebrow">PLANNED EXPANSION</span><h2>Future health sciences programmes</h2></div></div>
  <div class="editorial-tags"><span>Pharmacy Technician</span><span>Nursing Science</span><span>Medical Laboratory Science</span><span>Health Informatics</span></div>
  <p class="subtle-note">These are proposed future offerings, subject to all applicable regulatory approvals. They are not advertised as currently available for admission.</p>
 </section>
@endif
@if(in_array($section, ['about', 'academics', 'news'], true))
<section class="shell editorial-milestone" aria-label="NBTE accreditation announcement">
 <span class="eyebrow">INSTITUTIONAL MILESTONE</span><h2>NBTE Accreditation for ND in Community Health Extension Worker (CHEW)</h2>
 <p>Saint Titus College of Health Sciences and Technology, Benin City, has received accreditation from the National Board for Technical Education (NBTE) to run a National Diploma programme in CHEW.</p>
 <p class="subtle-note">This announcement concerns programme accreditation; it is not a notice that applications are currently open.</p>
</section>
@endif
<section class="shell section"><div class="heading-pair"><div><span class="eyebrow">INSTITUTIONAL INFORMATION</span><h2>Information & services</h2></div></div>
<div class="topic-grid">@foreach($topics as $key => $label)<article class="topic-card" id="{{ $key }}"><span class="topic-mark">✦</span><h3>{{ $label }}</h3>@if(!empty($content[$key]))<p class="body-text">{{ $content[$key] }}</p>@else<p class="pending-text">Official information for this section will be published once confirmed by the institution.</p>@endif</article>@endforeach</div>
</section>
@if($section === 'news')
<section class="section pale"><div class="shell"><div class="heading-pair"><div><span class="eyebrow">LATEST UPDATES</span><h2>Official publications and events</h2></div></div><div class="publication-list">@forelse($posts as $post)<a class="publication" href="{{ route('institution.post', $post->slug) }}"><span class="chip">{{ ucfirst($post->category) }}</span><div><h3>{{ $post->title }}</h3><p>{{ $post->summary }}</p>@if($post->event_starts_at)<small>Event: {{ $post->event_starts_at->format('d M Y, g:i A') }} · {{ $post->event_location }}</small>@endif</div><span aria-hidden="true">↗</span></a>@empty<div class="empty-state">No official news or events have been published. Check back for verified announcements.</div>@endforelse</div>
 <div class="heading-pair spacing-top"><div><span class="eyebrow">THE CAMPUS IN PICTURES</span><h2>Photo gallery</h2></div></div><div class="gallery-grid">@forelse($gallery as $photo)<figure><img src="{{ Storage::disk('public')->url($photo->file_path) }}" alt="{{ $photo->alt_text }}"><figcaption>{{ $photo->title }}</figcaption></figure>@empty<div class="empty-state">The official photo gallery is awaiting approved campus images.</div>@endforelse</div></div></section>
@endif
@if($section === 'admissions')
<section class="closing-cta"><div class="shell closing-inner"><div><span class="eyebrow light">ADMISSIONS AT SAINT TITUS</span><h2>Follow our official admissions announcements.</h2><p>Information on approved sessions, admission requirements and application dates will be published by the College.</p></div><a class="btn btn-gold" href="{{ route('institution.contact') }}">Contact admissions ↗</a></div></section>
@endif
@endsection
