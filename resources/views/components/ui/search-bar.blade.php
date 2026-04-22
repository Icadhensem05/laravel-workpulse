@props([
    'placeholder' => 'Search',
])

<label class="wp-search">
    <span class="text-ink-400">⌕</span>
    <input type="text" placeholder="{{ $placeholder }}" class="w-full bg-transparent text-sm text-white placeholder:text-ink-400 focus:outline-none" {{ $attributes }}>
</label>
