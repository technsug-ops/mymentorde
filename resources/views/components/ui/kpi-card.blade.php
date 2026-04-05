@props(['value', 'label', 'suffix' => '', 'prefix' => '', 'icon' => null, 'trend' => null, 'trendUp' => null, 'color' => null])
@php
$iconColors = ['🎓'=>'#2563eb','💰'=>'#16a34a','⏳'=>'#d97706','⭐'=>'#eab308','📄'=>'#6366f1','📊'=>'#8b5cf6','🎯'=>'#ec4899','🔔'=>'#f97316','👥'=>'#0891b2','📋'=>'#7c3aed','🏛️'=>'#059669','✈️'=>'#dc2626'];
$iconBg = $iconColors[$icon] ?? ($color ?? '#2563eb');
@endphp
<div class="panel" style="text-align:center;padding:20px 14px;">
    @if($icon)
    <div style="width:44px;height:44px;border-radius:12px;background:{{ $iconBg }}15;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
        <span style="font-size:22px;line-height:1;">{{ $icon }}</span>
    </div>
    @endif
    <div style="font-size:28px;font-weight:800;color:#111827;line-height:1;">{{ $prefix }}{{ $value }}{{ $suffix }}</div>
    <div style="font-size:12px;color:#6b7280;margin-top:6px;font-weight:500;">{{ $label }}</div>
    @if($trend)
    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;{{ $trendUp === true ? 'background:#dcfce7;color:#166534;' : ($trendUp === false ? 'background:#fef2f2;color:#991b1b;' : 'background:#f3f4f6;color:#6b7280;') }}">
        @if($trendUp === true)↑ @elseif($trendUp === false)↓ @endif{{ $trend }}
    </div>
    @endif
</div>