@props([
    'title'  => '',
    'meta'   => null,
    'status' => null,  // active | success | danger | null
    'icon'   => null,
])
<div class="ds-timeline-item">
    <div class="ds-timeline-dot {{ $status ?? '' }}">
        {{ $icon ?? ($status === 'success' ? '✓' : ($status === 'danger' ? '✕' : '●')) }}
    </div>
    <div class="ds-timeline-title">{{ $title }}</div>
    @if($meta)
        <div class="ds-timeline-meta">{{ $meta }}</div>
    @endif
    @if($slot->isNotEmpty())
        <div class="ds-timeline-body">{{ $slot }}</div>
    @endif
</div>
