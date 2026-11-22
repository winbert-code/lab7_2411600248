@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <h2 class="h5 fw-bold mb-1">Create an account</h2>
    <p class="text-muted small mb-4">Join ByteStock to manage your inventory.</p>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" autofocus
                   value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email"
                   value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                   autocomplete="new-password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-plus"></i> Create account
        </button>
    </form>

    <p class="text-center small text-muted mt-3 mb-0">
        Already registered? <a href="{{ route('login') }}">Log in</a>
    </p>
@endsection
