@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="mb-4">
        <a href="{{ route('products.show', $product) }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left"></i> Back to product
        </a>
        <h1 class="h3 fw-bold mb-1 mt-2">Edit Product</h1>
        <p class="text-muted mb-0">Update the details for <strong>{{ $product->name }}</strong>.</p>
    </div>

    <div class="card app-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('products.update', $product) }}" novalidate>
                @csrf
                @method('PUT')
                @include('products._form', [
                    'product' => $product,
                    'brands' => $brands,
                    'categories' => $categories,
                ])
            </form>
        </div>
    </div>
@endsection
