@props([
    'label' => null,
    'name' => null,
    'value' => null,
])

<x-ui.input
    :label="$label"
    type="date"
    :name="$name"
    :value="$value"
    {{ $attributes }}
/>
