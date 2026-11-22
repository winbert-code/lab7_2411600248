@extends('layouts.app')

@section('title', 'Add Brand')

@section('content')
    <div class="mb-4">
        <a href="{{ route('brands.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to brands
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Add Brand</h1>
        <p class="text-muted mb-0">Create a new manufacturer brand for your products.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('brands.store') }}" novalidate>
                @csrf
                @include('brands._form', ['brand' => $brand])
            </form>
        </div>
    </div>
@endsection
