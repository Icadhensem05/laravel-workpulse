@props([
    'label' => 'Actions',
    'items' => [],
])

<div class="wp-dropdown" data-dropdown>
    <button type="button" class="wp-btn-secondary" data-dropdown-toggle>{{ $label }}</button>

    <div class="wp-dropdown-menu" data-dropdown-menu>
        @foreach ($items as $item)
            <button type="button" class="wp-dropdown-link">{{ $item }}</button>
        @endforeach
    </div>
</div>
