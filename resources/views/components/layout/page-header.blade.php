@props(['title', 'subtitle' => null])
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <div>
        <h2 style="margin:0;font-size:18px;font-weight:700;">{{ $title }}</h2>
        @if($subtitle)<div style="font-size:12px;color:var(--u-muted);margin-top:2px;">{{ $subtitle }}</div>@endif
    </div>
    @if(isset($actions))<div style="display:flex;gap:8px;">{{ $actions }}</div>@endif
</div>
