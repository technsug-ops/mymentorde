@props([
    'count'    => 0,
    'href'     => '#',
    'icon'     => '🔔',
    'title'    => 'Bildirimler',
])
<a class="ds-notif-bell" href="{{ $href }}" title="{{ $title }}">
    {{ $icon }}
    @if((int)$count > 0)
        <span class="notif-count">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</a>
