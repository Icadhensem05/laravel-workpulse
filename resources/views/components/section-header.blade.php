@props([
    'eyebrow' => null,
    'title',
    'copy' => null,
])

<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="max-w-3xl">
        @if ($eyebrow)
            <p class="wp-label">{{ $eyebrow }}</p>
        @endif

        <h2 class="wp-page-title mt-2">{{ $title }}</h2>

        @if ($copy)
            <p class="wp-section-copy mt-3">{{ $copy }}</p>
        @endif
    </div>

    @if (trim($slot))
        <div class="flex items-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
