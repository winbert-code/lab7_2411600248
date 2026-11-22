@props(['status'])

@php
    $class = match ($status) {
        'Inactive' => 'badge-inactive',
        'Out of stock' => 'badge-out',
        'Low stock' => 'badge-low',
        default => 'badge-in',
    };
    $icon = match ($status) {
        'Inactive' => 'bi-archive',
        'Out of stock' => 'bi-x-octagon',
        'Low stock' => 'bi-exclamation-triangle',
        default => 'bi-check-circle',
    };
@endphp

<span class="stock-badge {{ $class }}">
    <i class="bi {{ $icon }}"></i> {{ $status }}
</span>
