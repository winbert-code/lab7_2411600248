@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
    <div class="mb-4">
        <a href="{{ route('categories.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to categories
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Edit Category</h1>
        <p class="text-muted mb-0">Update {{ $category->name }}'s details.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('categories.update', $category) }}" novalidate>
                @csrf
                @method('PUT')
                @include('categories._form', ['category' => $category])
            </form>
        </div>
    </div>
@endsection
