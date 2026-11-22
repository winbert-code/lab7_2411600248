@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Add Supplier</h1>
        <p class="text-muted mb-0">Record a new vendor and its replenishment terms.</p>
    </div>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card app-card">
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            @include('suppliers._form', ['supplier' => $supplier])
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save supplier</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
