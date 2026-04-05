@props([
    'value'   => 0,      // 0-100
    'max'     => 100,
    'label'   => null,
    'color'   => null,   // success | warning | danger | null (accent)
    'size'    => 'md',   // sm | md | lg
    'showPct' => false,
])
@php
$pct = $max > 0 ? min(100, round(($value / $max) * 100)) : 0;
$heights = ['sm' => '4px', 'md' => '8px', 'lg' => '12px'];
$h = $heights[$size] ?? $heights['md'];
$colorClass = $color ? ' ' . $color : '';
@endphp

@if($label)
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
        <span style="font-size:var(--font-xs);font-weight:600;color:var(--color-muted)">{{ $label }}</span>
        @if($showPct)
            <span style="font-size:var(--font-xs);font-weight:700;color:var(--color-text)">{{ $pct }}%</span>
        @endif
    </div>
@endif

<div class="ds-progress" style="height:{{ $h }}">
    <div
        class="ds-progress-bar{{ $colorClass }}"
        style="width:{{ $pct }}%"
        role="progressbar"
        aria-valuenow="{{ $value }}"
        aria-valuemin="0"
        aria-valuemax="{{ $max }}"
    ></div>
</div>
