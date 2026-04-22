@props([
    'label' => 'Upload files',
    'copy' => 'Drag supporting files here or browse from your device.',
])

<div class="wp-file-upload">
    <p class="wp-label">{{ $label }}</p>
    <p class="wp-section-copy mt-3">{{ $copy }}</p>
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <x-ui.button variant="secondary">Choose Files</x-ui.button>
        <x-ui.badge variant="neutral">PDF, JPG, PNG up to 10 MB</x-ui.badge>
    </div>
</div>
