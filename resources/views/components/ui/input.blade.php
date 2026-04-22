@props([
    'label' => null,
    'helper' => null,
    'error' => false,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'type' => 'text',
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @required($required)
        @disabled($disabled)
        @readonly($readonly)
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        aria-readonly="{{ $readonly ? 'true' : 'false' }}"
        {{ $attributes->class([
            'wp-input',
            'wp-input-error' => $error,
            'wp-input-disabled' => $disabled,
            'wp-input-readonly' => $readonly && ! $disabled,
        ]) }}
    >

    @if ($helper)
        <span class="{{ $error ? 'wp-helper wp-helper-error' : 'wp-helper' }}">{{ $helper }}</span>
    @endif
</label>
