@extends('stsams.layout')
@section('title', 'Create account')
@section('content')
<div class="auth-wrap"><div class="card auth-card"><span class="eyebrow">BEGIN YOUR JOURNEY</span><h1>Create your applicant account</h1><p>Registration is for applicants only. Staff access is provisioned by the institution.</p>
<form method="POST" action="{{ route('stsams.register') }}">@csrf
<label>Full name<input name="name" value="{{ old('name') }}" maxlength="120" required autocomplete="name"></label>
<label>Email address<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
<label>Password <small>(12 characters minimum)</small><input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
<label>Confirm password<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label>
<button class="button primary full" type="submit">Create account</button>
</form><p class="small">Already registered? <a href="{{ route('stsams.login') }}">Sign in</a></p></div></div>
@endsection
