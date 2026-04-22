@props([
    'label' => null,
    'helper' => null,
    'error' => false,
    'disabled' => false,
    'required' => false,
    'options' => [],
    'name' => null,
    'selected' => null,
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

    <select
        name="{{ $name }}"
        @required($required)
        @disabled($disabled)
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        {{ $attributes->class([
            'wp-select',
            'wp-input-error' => $error,
            'wp-input-disabled' => $disabled,
        ]) }}
    >
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>

    @if ($helper)
        <span class="{{ $error ? 'wp-helper wp-helper-error' : 'wp-helper' }}">{{ $helper }}</span>
    @endif
</label>
