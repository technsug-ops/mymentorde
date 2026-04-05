@props(['message' => 'Kayıt bulunamadı.', 'icon' => '📭', 'action' => null, 'actionLabel' => null])
<div style="text-align:center;padding:40px 20px;color:var(--u-muted);">
    <div style="font-size:40px;margin-bottom:8px;">{{ $icon }}</div>
    <div style="font-size:14px;">{{ $message }}</div>
    @if($action)<a href="{{ $action }}" class="btn alt" style="margin-top:12px;">{{ $actionLabel ?? 'Ekle' }}</a>@endif
</div>
