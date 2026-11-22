@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h2 class="h5 fw-bold mb-1">Welcome back</h2>
    <p class="text-muted small mb-4">Sign in to manage your inventory.</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" autofocus
                   value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Log in
        </button>
    </form>

    <p class="text-center small text-muted mt-3 mb-0">
        No account yet? <a href="{{ route('register') }}">Create one</a>
    </p>
    <p class="text-center small text-muted mt-2 mb-0">
        Demo: winbert@gmail.com / winbert906
    </p>
@endsection
