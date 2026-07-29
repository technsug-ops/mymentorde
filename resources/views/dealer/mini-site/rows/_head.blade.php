{{-- Kart başlığı: sıra numarası + ↑ ↓ + sil. $label = kart türü adı --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
    <span style="font-size:12px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.06em;">
        {{ $label }} <span data-num>{{ is_int($i) ? $i + 1 : 1 }}</span>
    </span>
    <span style="display:flex;gap:6px;">
        <button type="button" data-act="up"   title="Yukarı taşı"  style="{{ $btn }}">↑</button>
        <button type="button" data-act="down" title="Aşağı taşı"   style="{{ $btn }}">↓</button>
        <button type="button" data-act="del"  title="Bu kartı sil" style="{{ $btn }}color:#b91c1c;">✕</button>
    </span>
</div>
