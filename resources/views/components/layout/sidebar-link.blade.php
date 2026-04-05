@props([
    'href'    => '#',
    'icon'    => null,
    'active'  => false,
    'count'   => null,
    'target'  => null,
])
<a
    class="ds-sidebar-link {{ $active ? 'active' : '' }}"
    href="{{ $href }}"
    @if($target) target="{{ $target }}" @endif
>
    @if($icon)
        <span class="sl-icon">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
    @if($count !== null && (int)$count > 0)
        <span class="sl-count">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</a>
