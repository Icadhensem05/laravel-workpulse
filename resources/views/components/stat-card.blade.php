@props([
    'label',
    'value',
    'meta' => null,
    'icon' => null,
])

<section class="wp-panel p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="wp-label">{{ $label }}</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-white">{{ $value }}</p>

            @if ($meta)
                <p class="wp-helper mt-3">{{ $meta }}</p>
            @endif
        </div>

        @if ($icon)
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-sm font-semibold text-brand-100">
                {{ $icon }}
            </span>
        @endif
    </div>
</section>
