@props([
    'label',
    'checked' => false,
    'name',
    'value',
])

<label class="wp-choice wp-field-choice">
    <span class="relative">
        <input type="radio" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked($checked) {{ $attributes }}>
        <span class="wp-choice-circle peer-checked:border-brand-400">
            <span class="h-2.5 w-2.5 rounded-full bg-brand-400 opacity-0 peer-checked:opacity-100"></span>
        </span>
    </span>
    <span>{{ $label }}</span>
</label>
