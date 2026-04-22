@props([
    'tabs' => [],
    'active' => null,
])

<div class="wp-tabs" role="tablist">
    @foreach ($tabs as $tab)
        <button
            type="button"
            class="wp-tab {{ $active === ($tab['key'] ?? null) ? 'wp-tab-active' : '' }}"
            data-tab-trigger="{{ $tab['key'] ?? '' }}"
            role="tab"
            aria-selected="{{ $active === ($tab['key'] ?? null) ? 'true' : 'false' }}"
        >
            {{ $tab['label'] ?? '' }}
        </button>
    @endforeach
</div>
