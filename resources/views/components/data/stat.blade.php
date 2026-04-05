@props([
    'icon'    => null,
    'value'   => '—',
    'label'   => '',
    'trend'   => null,
    'trendUp' => null,
    'prefix'  => '',
    'suffix'  => '',
    'color'   => null,
])
<div class="ds-stat">
    @if($icon)
        <div class="ds-stat-icon" @if($color) style="color:{{ $color }}" @endif>{{ $icon }}</div>
    @endif
    <div class="ds-stat-value" @if($color) style="color:{{ $color }}" @endif>
        {{ $prefix }}{{ $value }}{{ $suffix }}
    </div>
    <div class="ds-stat-label">{{ $label }}</div>
    @if($trend)
    <div class="ds-stat-trend {{ $trendUp === true ? 'up' : ($trendUp === false ? 'down' : '') }}">
        @if($trendUp === true) ↑ @elseif($trendUp === false) ↓ @endif
        {{ $trend }}
    </div>
    @endif
</div>
