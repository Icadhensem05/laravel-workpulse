@props([
    'id',
    'title',
    'copy' => null,
    'open' => false,
])

<div
    id="{{ $id }}"
    class="wp-modal-backdrop {{ $open ? 'is-open' : '' }}"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-hidden="{{ $open ? 'false' : 'true' }}"
    aria-labelledby="{{ $id }}-title"
    @if ($copy)
        aria-describedby="{{ $id }}-copy"
    @endif
    tabindex="-1"
>
    <div class="wp-modal-card">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 id="{{ $id }}-title" class="wp-section-title">{{ $title }}</h3>
                @if ($copy)
                    <p id="{{ $id }}-copy" class="wp-section-copy mt-2">{{ $copy }}</p>
                @endif
            </div>

            <button type="button" class="wp-btn-ghost" data-modal-close>Close</button>
        </div>

        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</div>
