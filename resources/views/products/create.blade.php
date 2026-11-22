@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="mb-4">
        <a href="{{ route('products.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to inventory
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Add Product</h1>
        <p class="text-muted mb-0">Enter the details of the new computer part.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('products.store') }}" novalidate>
                @csrf
                @include('products._form', [
                    'product' => $product,
                    'brands' => $brands,
                    'categories' => $categories,
                ])
            </form>
        </div>
    </div>
@endsection
