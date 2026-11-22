@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="mb-4">
        <a href="{{ route('users.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to users
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Edit User</h1>
        <p class="text-muted mb-0">Update {{ $user->name }}'s role and login details.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
                @csrf
                @method('PUT')
                @include('users._form', ['user' => $user])
            </form>
        </div>
    </div>
@endsection
