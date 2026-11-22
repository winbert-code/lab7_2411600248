@extends('layouts.app')

@section('title', 'Add Category')

@section('content')
    <div class="mb-4">
        <a href="{{ route('categories.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to categories
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Add Category</h1>
        <p class="text-muted mb-0">Create a new grouping for your products.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('categories.store') }}" novalidate>
                @csrf
                @include('categories._form', ['category' => $category])
            </form>
        </div>
    </div>
@endsection
