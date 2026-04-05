@props([
    'src'     => null,
    'name'    => '',
    'size'    => 'md',    // xs | sm | md | lg | xl
    'color'   => null,    // override background
    'rounded' => 'full',  // full | lg
])
@php
$initials = collect(explode(' ', trim($name)))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
if (!$initials) $initials = '?';
$style = $color ? "background:{$color}22;color:{$color};" : '';
@endphp

<span
    class="ds-avatar {{ $size }}"
    style="{{ $style }}{{ $rounded !== 'full' ? 'border-radius:var(--r-lg);' : '' }}"
    title="{{ $name }}"
>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" loading="lazy">
    @else
        {{ $initials }}
    @endif
</span>
