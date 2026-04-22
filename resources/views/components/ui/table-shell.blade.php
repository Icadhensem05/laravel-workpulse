@props([
    'title' => null,
    'copy' => null,
])

<section class="wp-table-shell">
    @if ($title || $copy || trim($toolbar ?? ''))
        <div class="wp-table-toolbar">
            <div>
                @if ($title)
                    <p class="wp-section-title">{{ $title }}</p>
                @endif
                @if ($copy)
                    <p class="wp-section-copy mt-2">{{ $copy }}</p>
                @endif
            </div>

            @isset($toolbar)
                <div class="flex flex-wrap items-center gap-3">
                    {{ $toolbar }}
                </div>
            @endisset
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="wp-table wp-table-stack">
            {{ $slot }}
        </table>
    </div>
</section>
