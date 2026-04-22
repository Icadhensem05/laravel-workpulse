@props([
    'label' => null,
    'helper' => null,
    'error' => false,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'name' => null,
    'value' => null,
])

<label class="wp-field">
    @if ($label)
        <span class="wp-field-label wp-label">
            {{ $label }}
            @if ($required)
                <span class="wp-label-required">*</span>
            @endif
        </span>
    @endif

    <textarea
        name="{{ $name }}"
        @required($required)
        @disabled($disabled)
        @readonly($readonly)
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        aria-readonly="{{ $readonly ? 'true' : 'false' }}"
        {{ $attributes->class([
            'wp-textarea',
            'wp-input-error' => $error,
            'wp-input-disabled' => $disabled,
            'wp-input-readonly' => $readonly && ! $disabled,
        ]) }}
    >{{ $value ?? $slot }}</textarea>

    @if ($helper)
        <span class="{{ $error ? 'wp-helper wp-helper-error' : 'wp-helper' }}">{{ $helper }}</span>
    @endif
</label>
