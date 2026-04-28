{{-- GDPR / Uyumluluk üst sekme barı — 3 alt modül arasında geçiş --}}
@php
    $gdprActive = $gdprActive ?? 'policies'; // policies | ropa | avv
@endphp

<style>
.gdpr-tabs { display:flex; gap:4px; margin-bottom:18px; padding:6px; background:#fff;
             border:1px solid #e2e8f0; border-radius:12px; overflow-x:auto; }
.gdpr-tabs a { flex:1; min-width:200px; text-align:center; padding:11px 16px;
               font-size:13px; font-weight:700; color:#475569; text-decoration:none;
               border-radius:8px; transition:all .15s; white-space:nowrap; }
.gdpr-tabs a:hover { background:#f1f5f9; color:#1e40af; }
.gdpr-tabs a.active { background:linear-gradient(135deg,#1e40af,#3b5fcc); color:#fff;
                      box-shadow:0 2px 4px rgba(30,64,175,.18); }
.gdpr-tabs .gdpr-tab-emoji { margin-right:6px; font-size:14px; }
.gdpr-tabs .gdpr-tab-art { font-size:11px; opacity:.75; font-weight:600; margin-left:4px; }
@media (max-width:760px) { .gdpr-tabs a { min-width:auto; padding:10px 12px; font-size:12px; } }
</style>

<div class="gdpr-tabs">
    <a href="{{ route('manager.gdpr-dashboard') }}"
       class="{{ $gdprActive === 'policies' ? 'active' : '' }}">
        <span class="gdpr-tab-emoji">🔒</span>GDPR Politikalar
        <span class="gdpr-tab-art">6 belge × 3 dil</span>
    </a>
    <a href="{{ route('manager.ropa.index') }}"
       class="{{ $gdprActive === 'ropa' ? 'active' : '' }}">
        <span class="gdpr-tab-emoji">📋</span>ROPA<span class="gdpr-tab-art">Art. 30</span>
    </a>
    <a href="{{ route('manager.avv.index') }}"
       class="{{ $gdprActive === 'avv' ? 'active' : '' }}">
        <span class="gdpr-tab-emoji">📑</span>AVV Registry<span class="gdpr-tab-art">Art. 28</span>
    </a>
</div>
