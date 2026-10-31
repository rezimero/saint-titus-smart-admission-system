@extends('institutional.layout')
@section('content')
<section class="page-hero"><div class="shell"><span class="eyebrow light">{{ strtoupper($post->category) }} • {{ $post->published_at?->format('d M Y') ?? 'OFFICIAL UPDATE' }}</span><h1>{{ $post->title }}</h1><p>{{ $post->summary }}</p></div></section>
<div class="shell breadcrumb"><a href="{{ route('institution.home') }}">Home</a> / <a href="{{ route('institution.news') }}">News & Events</a> / {{ $post->title }}</div>
<article class="shell article-body"><p class="body-text">{{ $post->body }}</p>@if($post->event_starts_at)<div class="event-meta"><strong>Event date:</strong> {{ $post->event_starts_at->format('l, d M Y, g:i A') }}@if($post->event_location) <br><strong>Location:</strong> {{ $post->event_location }}@endif</div>@endif<a class="text-link" href="{{ route('institution.news') }}">← All news and events</a></article>
@endsection
