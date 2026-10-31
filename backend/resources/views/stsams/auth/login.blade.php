@extends('stsams.layout')
@section('title', 'Sign in')
@section('content')
<div class="auth-wrap"><div class="card auth-card"><span class="eyebrow">WELCOME BACK</span><h1>Sign in to your portal</h1><p>Access your application dashboard securely.</p>
<form method="POST" action="{{ route('stsams.login') }}">@csrf
<label>Email address<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button class="button primary full" type="submit">Sign in</button>
</form><p class="small">New applicant? <a href="{{ route('stsams.register') }}">Create your account</a></p></div></div>
@endsection
