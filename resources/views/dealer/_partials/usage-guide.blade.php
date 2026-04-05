@props(['title' => 'Kullanım Kılavuzu', 'items' => []])

@if(!empty($items))
<div style="background:var(--bg,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px 20px;margin-top:16px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px;">💡 {{ $title }}</div>
    <ul style="margin:0;padding-left:18px;">
        @foreach($items as $item)
            <li style="font-size:var(--tx-sm);color:var(--muted,#64748b);margin-bottom:6px;">{{ $item }}</li>
        @endforeach
    </ul>
</div>
@endif

