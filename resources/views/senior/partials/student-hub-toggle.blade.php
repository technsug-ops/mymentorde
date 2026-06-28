{{-- Öğrenci Takibi hub — iki görünüm arası geçiş (#14).
     "Öğrencilerim" (liste) ↔ "Süreç Panosu" (process-tracking) tek hub gibi.
     Her iki sayfanın en üstüne include edilir; menüde tek "Öğrencilerim" var. --}}
@php
    $hubIsList = request()->is('senior/students*');
@endphp
<div style="display:inline-flex;gap:4px;background:var(--u-bg,#f1f5f9);border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:4px;margin-bottom:16px;">
    <a href="/senior/students"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;font-size:var(--tx-sm);font-weight:700;text-decoration:none;transition:background .15s,color .15s;{{ $hubIsList ? 'background:#7c3aed;color:#fff;' : 'color:var(--u-muted,#64748b);' }}">
        👥 Öğrenci Listesi
    </a>
    <a href="/senior/process-tracking"
       style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;font-size:var(--tx-sm);font-weight:700;text-decoration:none;transition:background .15s,color .15s;{{ !$hubIsList ? 'background:#7c3aed;color:#fff;' : 'color:var(--u-muted,#64748b);' }}">
        🔄 Süreç Panosu
    </a>
</div>
