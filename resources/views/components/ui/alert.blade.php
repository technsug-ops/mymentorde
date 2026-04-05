@props(['type' => 'info', 'dismissable' => false])
@php
$styles = [
    'info'   => 'background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;',
    'danger' => 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;',
    'ok'     => 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;',
    'warn'   => 'background:#fffbeb;color:#92400e;border:1px solid #fde68a;',
];
@endphp
<div style="{{ $styles[$type] ?? $styles['info'] }}padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;position:relative;margin-bottom:10px;"
     @if($dismissable) x-data="{show:true}" x-show="show" x-transition @endif>
    {{ $slot }}
    @if($dismissable)
        <button @click="show=false" style="position:absolute;top:8px;right:12px;background:none;border:none;cursor:pointer;font-size:16px;opacity:.5;">✕</button>
    @endif
</div>