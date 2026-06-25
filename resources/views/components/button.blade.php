@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'type' => 'button'
])

@php
    $classes = 'btn btn-' . $variant;
    if ($size === 'sm') $classes .= ' btn-sm';
    if ($size === 'lg') $classes .= ' btn-lg';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="fas fa-{{ $icon }}"></i>
    @endif
    {{ $slot }}
</button>
