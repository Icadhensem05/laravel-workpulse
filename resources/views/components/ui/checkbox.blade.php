@props([
    'label',
    'checked' => false,
    'name' => null,
    'value' => '1',
])

<label class="wp-choice wp-field-choice">
    <span class="relative">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked($checked) {{ $attributes }}>
        <span class="wp-choice-box peer-checked:border-brand-400 peer-checked:bg-brand-500">
            <span class="text-xs opacity-0 peer-checked:opacity-100">✓</span>
        </span>
    </span>
    <span>{{ $label }}</span>
</label>
