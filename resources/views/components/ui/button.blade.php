@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'secondary' => 'wp-btn-secondary',
        'ghost' => 'wp-btn-ghost',
        'danger' => 'wp-btn-danger',
        default => 'wp-btn',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
