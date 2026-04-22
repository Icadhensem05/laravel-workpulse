@props([
    'label',
    'checked' => false,
    'name' => null,
    'value' => '1',
])

<label class="wp-switch wp-field-choice">
    <span class="relative">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked($checked) {{ $attributes }}>
        <span class="wp-switch-track peer-checked:border-brand-400 peer-checked:bg-brand-500/70">
            <span class="wp-switch-thumb peer-checked:translate-x-5"></span>
        </span>
    </span>
    <span>{{ $label }}</span>
</label>
