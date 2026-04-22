@props([
    'variant' => 'neutral',
])

@php
    $classes = match ($variant) {
        'info' => 'wp-badge wp-badge-info',
        'success' => 'wp-badge wp-badge-success',
        'warning' => 'wp-badge wp-badge-warning',
        'danger' => 'wp-badge wp-badge-danger',
        default => 'wp-badge wp-badge-neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
