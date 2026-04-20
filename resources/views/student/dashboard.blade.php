@extends('student.layouts.app')

@section('title', 'Öğrenci Paneli')
@section('page_title', 'Öğrenci Paneli')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* -- sd-* Öğrenci Paneli v2 -- Funnel based -- */

/* Journey */
.sd-journey { background:var(--u-card); border-radius:14px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); overflow:hidden; margin-bottom:20px; }
.sd-journey-top { padding:18px 22px 14px; display:flex; align-items:center; justify-content:space-between; }
.sd-journey-title { font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; }
.sd-journey-tag { font-size:11px; font-weight:600; padding:2px 10px; border-radius:10px; }
.sd-journey-tag.progress { background:var(--accent-soft,rgba(124,58,237,.08)); color:var(--u-brand); }
.sd-journey-tag.done { background:rgba(22,163,74,.08); color:var(--u-ok); }
.sd-journey-pct { font-size:22px; font-weight:800; color:var(--u-brand); letter-spacing:-1px; }
.sd-journey-pct span { font-size:13px; font-weight:500; color:var(--u-muted); }
.sd-bar-wrap { padding:0 22px; margin-bottom:14px; }
.sd-bar { height:6px; background:var(--u-bg); border-radius:3px; overflow:hidden; }
.sd-bar-fill { height:100%; background:linear-gradient(90deg,var(--u-brand),var(--u-brand-2)); border-radius:3px; transition:width .8s cubic-bezier(.4,0,.2,1); }
.sd-steps { display:grid; grid-template-columns:repeat(6,1fr); border-top:1px solid var(--u-line); }
.sd-step { padding:12px 10px; display:flex; flex-direction:column; align-items:center; gap:5px; cursor:default; border-right:1px solid var(--u-line); text-align:center; position:relative; }
.sd-step:last-child { border-right:none; }
.sd-step-num { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; border:2px solid var(--u-line); background:var(--u-bg); color:var(--u-muted); transition:all .3s; }
.sd-step.done .sd-step-num { background:var(--u-ok); border-color:var(--u-ok); color:#fff; }
.sd-step.active .sd-step-num { background:var(--accent-soft,rgba(124,58,237,.1)); border-color:var(--u-brand); color:var(--u-brand); box-shadow:0 0 0 3px var(--accent-soft,rgba(124,58,237,.08)); }
.sd-step.locked .sd-step-num { opacity:.35; }
.sd-step-name { font-size:10px; font-weight:600; line-height:1.2; }
.sd-step.done .sd-step-name { color:var(--u-ok); }
.sd-step.active .sd-step-name { color:var(--u-brand); }
.sd-step.locked .sd-step-name { color:var(--u-muted); }
.sd-step.active::after { content:''; position:absolute; top:-1px; left:50%; transform:translateX(-50%); border-left:5px solid transparent; border-right:5px solid transparent; border-top:5px solid var(--u-brand); }

/* Hero */
.sd-hero { border-radius:14px; padding:24px 28px; margin-bottom:20px; color:#fff; box-shadow:var(--u-shadow-md); overflow:hidden; }
.sd-hero.purple { background:linear-gradient(135deg,var(--u-brand-2),var(--u-brand)); }
.sd-hero.blue { background:linear-gradient(135deg,#1e3a8a,#3b82f6); }
.sd-hero.teal { background:linear-gradient(135deg,#134e4a,#0d9488); }
.sd-hero.green { background:linear-gradient(135deg,#065f46,#16a34a); }
.sd-hero.amber { background:linear-gradient(135deg,#78350f,#d97706); }
.sd-hero-badge { display:inline-flex; align-items:center; gap:6px; font-size:10px; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,.6); margin-bottom:6px; }
.sd-hero-badge .pulse { width:7px; height:7px; border-radius:50%; background:#34d399; box-shadow:0 0 6px rgba(52,211,153,.6); animation:sdPulse 1.5s infinite; }
@keyframes sdPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.sd-hero-title { font-size:20px; font-weight:700; margin-bottom:5px; line-height:1.3; }
.sd-hero-sub { font-size:13px; color:rgba(255,255,255,.75); line-height:1.5; margin-bottom:14px; max-width:560px; }
.sd-hero-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 22px; border-radius:8px; background:#fff; color:var(--u-brand-2); font-size:13px; font-weight:700; border:none; cursor:pointer; font-family:inherit; transition:all .15s; text-decoration:none; }
.sd-hero-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.15); color:var(--u-brand-2); text-decoration:none; }
.sd-hero-meta { display:flex; gap:14px; margin-top:12px; flex-wrap:wrap; }
.sd-hero-meta span { font-size:11px; color:rgba(255,255,255,.5); display:flex; align-items:center; gap:4px; }

/* Grids */
.sd-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px; }
.sd-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:20px; }
.sd-grid-4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px; margin-bottom:20px; }

/* Stat */
.sd-stat { background:var(--u-card); border-radius:10px; padding:14px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); display:flex; align-items:center; gap:10px; }
.sd-stat-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }

/* Section card */
.sd-card { background:var(--u-card); border-radius:10px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); overflow:hidden; margin-bottom:20px; }
.sd-card-head { padding:12px 16px; border-bottom:1px solid var(--u-line); display:flex; align-items:center; justify-content:space-between; }
.sd-card-head h4 { font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px; }
.sd-card-link { font-size:11px; font-weight:600; color:var(--u-brand); text-decoration:none; }
.sd-card-body { padding:10px 16px; }

/* Checklist item */
.sd-cl { display:flex; align-items:center; gap:8px; padding:7px 0; border-bottom:1px solid var(--u-line); font-size:12px; }
.sd-cl:last-child { border-bottom:none; }
.sd-cl-dot { width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:700; flex-shrink:0; }
.sd-cl-name { flex:1; }
.sd-cl-tag { font-size:9px; font-weight:600; padding:2px 7px; border-radius:5px; }

/* Tip */
.sd-tip { background:var(--accent-soft,rgba(124,58,237,.04)); border:1px solid var(--accent-soft,rgba(124,58,237,.1)); border-radius:10px; padding:14px 16px; display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; }
.sd-tip-icon { width:32px; height:32px; border-radius:8px; background:var(--accent-soft,rgba(124,58,237,.08)); display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.sd-tip h5 { font-size:12px; font-weight:600; color:var(--u-brand-2); margin-bottom:2px; }
.sd-tip p { font-size:11px; color:var(--u-brand-2); line-height:1.5; }

/* Quick links */
.sd-ql { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
.sd-ql a { display:flex; flex-direction:column; align-items:center; gap:6px; padding:12px 8px; background:var(--u-card); border:1px solid var(--u-line); border-radius:8px; text-decoration:none; color:var(--u-text); font-size:11px; font-weight:600; text-align:center; transition:all .15s; }
.sd-ql a:hover { border-color:var(--u-brand); color:var(--u-brand); transform:translateY(-2px); box-shadow:var(--u-shadow-md); text-decoration:none; }
.sd-ql-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; color:#fff; }

/* Celebrate */
.sd-celebrate { background:linear-gradient(135deg,var(--u-brand-2),var(--u-brand)); color:#fff; border-radius:14px; padding:32px 28px; text-align:center; margin-bottom:20px; box-shadow:var(--u-shadow-md); }
.sd-celebrate .emoji { font-size:48px; margin-bottom:12px; }
.sd-celebrate h2 { font-size:22px; font-weight:800; margin-bottom:6px; }
.sd-celebrate p { font-size:13px; opacity:.8; max-width:460px; margin:0 auto; line-height:1.5; }

/* Alert */
.sd-alert { border-radius:8px; padding:10px 14px; border:1px solid; display:flex; gap:8px; align-items:flex-start; margin-bottom:8px; font-size:12px; }
.sd-alert.warn { border-color:#fecaca; background:#fff5f5; color:#b91c1c; }
.sd-alert.info { border-color:#c5dafd; background:#f0f7ff; color:#1a3e8a; }
.sd-alert.danger { border-color:#fecaca; background:#fef2f2; color:#991b1b; }
.sd-alert-icon { font-size:14px; flex-shrink:0; margin-top:1px; }
.sd-alert-body { flex:1; }
.sd-alert-btn { font-size:10px; font-weight:600; padding:3px 8px; border-radius:5px; background:#fff; border:1px solid var(--u-line); color:var(--u-text); text-decoration:none; white-space:nowrap; flex-shrink:0; }

@media(max-width:860px){
    .sd-steps { grid-template-columns:repeat(3,1fr); }
    .sd-grid-2,.sd-grid-3,.sd-grid-4,.sd-ql { grid-template-columns:1fr 1fr; }
    .sd-hero-title { font-size:17px; }
}
@media(max-width:600px){
    .sd-steps { grid-template-columns:repeat(2,1fr); }
    .sd-grid-4 { grid-template-columns:1fr 1fr; gap:8px; }
    .sd-ql { grid-template-columns:1fr; }
    .sd-grid-2,.sd-grid-3 { grid-template-columns:1fr; }
    .sd-stat { padding:10px; gap:8px; }
    .sd-stat-icon { width:34px; height:34px; flex-shrink:0; }
    .sd-stat-icon svg { width:16px; height:16px; }
}
.jm-minimalist .sd-journey,.jm-minimalist .sd-card,.jm-minimalist .sd-stat { box-shadow:none; }
</style>
@endpush

@section('content')
@php
    $steps       = $funnelSteps ?? [];
    $pct         = $funnelPct ?? 0;
    $stage       = request()->query('stage', $currentStage ?? 'documents');
    $firstName   = trim((string) ($guestApplication?->first_name ?? $user->name ?? ''));
    $allDone     = $pct >= 100;

    // Stage-based greeting (mockup'taki gibi)
    $stageGreet = match($stage) {
        'contract'   => "Merhaba {$firstName}! Sozlesme surecin seni bekliyor.",
        'documents'  => "Merhaba {$firstName} 👋",
        'uni_assist' => "Harika gidiyorsun {$firstName}! 🚀",
        'visa'       => "Kabul geldi {$firstName}! 🎉",
        'abroad'     => "Tebrikler {$firstName}! 🇩🇪",
        default      => $greeting ?? 'Merhaba!',
    };
    $stageSub = match($stage) {
        'contract'   => 'Basvurun alindi. Sozlesme surecini tamamla.',
        'documents'  => 'Danismanlik surecin devam ediyor. Siradaki adimini asagida goruyorsun.',
        'uni_assist' => 'Belgeler tamamlandi - Uni-Assist basvurun hazirlaniyor.',
        'visa'       => 'Universiteden kabul aldin - vize sureci basliyor.',
        'abroad'     => 'Tum surec tamamlandi. Almanya\'da yeni hayatin basliyor!',
        default      => $greetingSub ?? '',
    };

    $totalRequired   = ($requiredChecklist ?? collect())->where('is_required', true)->count();
    $missingRequired = ($requiredChecklist ?? collect())->where('is_required', true)->where('done', false)->count();
@endphp

{{-- Greeting --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 style="font-size:20px;font-weight:800;letter-spacing:-.3px;">{{ $stageGreet }}</h2>
        <p style="font-size:13px;color:var(--u-muted);margin-top:2px;">{{ $stageSub }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/student/messages" style="padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid var(--u-line);background:var(--u-card);color:var(--u-text);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">✉ Danismana Mesaj</a>
        <a href="/student/tickets" style="padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid var(--u-line);background:var(--u-card);color:var(--u-text);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">📞 Destek</a>
    </div>
</div>

{{-- Alerts --}}
@foreach(($alerts ?? collect()) as $al)
<div class="sd-alert {{ $al['type'] ?? 'info' }}">
    <span class="sd-alert-icon">{{ $al['icon'] ?? 'ℹ' }}</span>
    <div class="sd-alert-body">{{ $al['message'] ?? '' }}</div>
    @if(!empty($al['action_url']))
    <a href="{{ $al['action_url'] }}" class="sd-alert-btn">{{ $al['action_text'] ?? 'Git' }}</a>
    @endif
</div>
@endforeach

{{-- Journey Funnel --}}
<div class="sd-journey">
    <div class="sd-journey-top">
        <div class="sd-journey-title">
            Danismanlik Sureci
            <span class="sd-journey-tag {{ $allDone ? 'done' : 'progress' }}">{{ $allDone ? 'Tamamlandi' : 'Devam Ediyor' }}</span>
        </div>
        <div class="sd-journey-pct">{{ $pct }}<span>%</span></div>
    </div>
    <div class="sd-bar-wrap">
        <div class="sd-bar"><div class="sd-bar-fill" style="width:{{ $pct }}%"></div></div>
    </div>
    <div class="sd-steps">
        @foreach($steps as $i => $s)
            @php
                $cls = $s['done'] ? 'done' : ($s['key'] === $stage ? 'active' : 'locked');
            @endphp
            <div class="sd-step {{ $cls }}">
                <div class="sd-step-num">{{ $s['done'] ? '✓' : ($i + 1) }}</div>
                <div class="sd-step-name">{{ $s['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ══ 4. HERO KARTI (state'e gore) ══ --}}
@if($stage === 'contract')
<div class="sd-hero purple">
    <div class="sd-hero-badge"><span class="pulse"></span> Siradaki adim</div>
    <div class="sd-hero-title">Sozlesme surecini tamamla</div>
    <div class="sd-hero-sub">Basvurun alindi. Simdi sozlesme surecini tamamlaman gerekiyor.</div>
    <a href="/student/contract" class="sd-hero-btn">📜 Sozlesmeye Git →</a>
</div>
@elseif($stage === 'documents')
<div class="sd-hero blue">
    <div class="sd-hero-badge"><span class="pulse"></span> Siradaki adim</div>
    <div class="sd-hero-title">Belgelerini tamamla</div>
    <div class="sd-hero-sub">Universite basvurusu icin {{ $totalRequired }} belge gerekiyor. Su ana kadar {{ $docSummary['approved'] ?? 0 }} tanesi onaylandi. Eksik belgeleri yukle ve danismaninin onayini bekle.</div>
    <a href="/student/registration/documents" class="sd-hero-btn">📄 Belge Merkezine Git →</a>
    <div class="sd-hero-meta">
        <span>📎 {{ $docSummary['approved'] ?? 0 }}/{{ $totalRequired }} onayli</span>
        @if($missingRequired > 0)<span>⏱️ ~{{ $missingRequired }} belge acil</span>@endif
        <span>📸 Fotograf da yuklenebilir</span>
    </div>
</div>
@elseif($stage === 'uni_assist')
<div class="sd-hero purple">
    <div class="sd-hero-badge"><span class="pulse"></span> Siradaki adim</div>
    <div class="sd-hero-title">Uni-Assist basvurusu</div>
    <div class="sd-hero-sub">Belgelerin tamamlandi! Simdi danismanin Uni-Assist basvurunu hazirliyor.</div>
    <a href="/student/process-tracking" class="sd-hero-btn">📊 Surec Takibine Git →</a>
    <div class="sd-hero-meta">
        <span>⏱️ Tahmini: 2-3 hafta</span>
        <span>📧 Gelismeler bildirilecek</span>
    </div>
</div>
@elseif($stage === 'visa')
<div class="sd-hero teal">
    <div class="sd-hero-badge"><span class="pulse"></span> Siradaki adim</div>
    <div class="sd-hero-title">Vize basvurusu</div>
    <div class="sd-hero-sub">Universiteden kabul geldi! Simdi vize basvurusu icin Sperrkonto acma ve konsolosluk randevusu surecin basliyor.</div>
    <a href="/student/visa" class="sd-hero-btn" style="color:#134e4a;">🛂 Vize Takibine Git →</a>
    <div class="sd-hero-meta">
        <span>🏦 Sperrkonto sureci</span>
        <span>📅 Konsolosluk randevusu</span>
    </div>
</div>
@elseif($stage === 'abroad' || $allDone)
<div class="sd-celebrate">
    <div class="emoji">🇩🇪</div>
    <h2>Almanya'ya hos geldin!</h2>
    <p>Tum surec tamamlandi. Artik Almanya'da yeni hayatina basliyorsun. Danismanin hala yaninda!</p>
</div>
@endif

{{-- ══ 5. STAT KARTLARI (HER ZAMAN) ══ --}}
<div class="sd-grid-4">
    <div class="sd-stat">
        <div class="sd-stat-icon" style="background:#16a34a;border-radius:10px;"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><div style="font-size:11px;color:var(--u-muted);">Onayli</div><div style="font-size:20px;font-weight:800;">{{ $docSummary['approved'] ?? 0 }}</div><div style="font-size:10px;color:var(--u-muted);">belge</div></div>
    </div>
    <div class="sd-stat">
        <div class="sd-stat-icon" style="background:#d97706;border-radius:10px;"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M5 22h14l-1.5-9h-11L5 22z"/><path d="M8.5 13V4a3.5 3.5 0 0 1 7 0v9"/></svg></div>
        <div><div style="font-size:11px;color:var(--u-muted);">Bekleyen</div><div style="font-size:20px;font-weight:800;">{{ $docSummary['uploaded'] ?? 0 }}</div><div style="font-size:10px;color:var(--u-muted);">inceleniyor</div></div>
    </div>
    <div class="sd-stat">
        <div class="sd-stat-icon" style="background:#dc2626;border-radius:10px;"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
        <div><div style="font-size:11px;color:var(--u-muted);">Reddedilen</div><div style="font-size:20px;font-weight:800;">{{ $docSummary['rejected'] ?? 0 }}</div><div style="font-size:10px;color:var(--u-muted);">duzelt</div></div>
    </div>
    <div class="sd-stat">
        <div class="sd-stat-icon" style="background:#6366f1;border-radius:10px;"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="12" y2="16"/></svg></div>
        <div><div style="font-size:11px;color:var(--u-muted);">Eksik</div><div style="font-size:20px;font-weight:800;">{{ $missingRequired }}</div><div style="font-size:10px;color:var(--u-muted);">yuklenmedi</div></div>
    </div>
</div>

{{-- ══ 6. ACIL BELGELER + BU HAFTA (HER ZAMAN) ══ --}}
<div class="sd-grid-2">
    <div class="sd-card" style="margin-bottom:0;">
        <div class="sd-card-head"><h4>📋 Acil Belgeler</h4><a class="sd-card-link" href="/student/registration/documents">Tumunu Gor →</a></div>
        <div class="sd-card-body">
            @forelse(($requiredChecklist ?? collect())->take(5) as $doc)
            <div class="sd-cl">
                @if($doc['done'] ?? false)
                    <div class="sd-cl-dot" style="background:#16a34a;color:#fff;border-radius:50%;">✓</div>
                @else
                    <div class="sd-cl-dot" style="background:#f59e0b;color:#fff;border-radius:50%;font-weight:800;">!</div>
                @endif
                <div class="sd-cl-name">{{ $doc['name'] ?? $doc['code'] ?? 'Belge' }}</div>
                <span class="sd-cl-tag" style="background:{{ ($doc['done'] ?? false) ? 'rgba(22,163,74,.1)' : 'rgba(239,68,68,.1)' }};color:{{ ($doc['done'] ?? false) ? '#16a34a' : '#dc2626' }};font-weight:700;padding:3px 10px;border-radius:6px;">{{ ($doc['done'] ?? false) ? 'Onayli' : 'Acil' }}</span>
            </div>
            @empty
            <div style="padding:12px 0;text-align:center;color:var(--u-muted);font-size:12px;">Zorunlu belge tanimlanmamis</div>
            @endforelse
        </div>
    </div>
    <div class="sd-card" style="margin-bottom:0;">
        <div class="sd-card-head"><h4>📊 Bu Hafta</h4></div>
        <div class="sd-card-body">
            <div class="sd-cl">
                <div class="sd-cl-dot" style="background:#dbeafe;color:#3b82f6;">📄</div>
                <div class="sd-cl-name">{{ $weekActivity['documents_uploaded'] ?? 0 }} belge yuklendi</div>
                <span class="sd-cl-tag" style="background:#dbeafe;color:#2563eb;font-weight:700;padding:3px 10px;border-radius:6px;">Yeni</span>
            </div>
            <div class="sd-cl">
                <div class="sd-cl-dot" style="background:var(--accent-soft,#ede9fe);color:var(--u-brand);">💬</div>
                <div class="sd-cl-name">{{ $weekActivity['messages_received'] ?? 0 }} mesaj geldi</div>
                <span class="sd-cl-tag" style="background:{{ ($dmUnread ?? 0) > 0 ? '#fef3c7' : '#dcfce7' }};color:{{ ($dmUnread ?? 0) > 0 ? '#d97706' : '#16a34a' }};font-weight:700;padding:3px 10px;border-radius:6px;">{{ ($dmUnread ?? 0) > 0 ? 'Okunmadi' : 'Tamam' }}</span>
            </div>
            <div class="sd-cl">
                <div class="sd-cl-dot" style="background:#dcfce7;color:#16a34a;">✅</div>
                <div class="sd-cl-name">{{ $weekActivity['outcomes_added'] ?? 0 }} belge onaylandi</div>
                <span class="sd-cl-tag" style="background:#dcfce7;color:#16a34a;font-weight:700;padding:3px 10px;border-radius:6px;">Tamamlandi</span>
            </div>
            @if(($docSummary['rejected'] ?? 0) > 0)
            <div class="sd-cl">
                <div class="sd-cl-dot" style="background:#fee2e2;color:#dc2626;">❌</div>
                <div class="sd-cl-name">{{ $docSummary['rejected'] }} belge reddedildi</div>
                <span class="sd-cl-tag" style="background:#fee2e2;color:#dc2626;font-weight:700;padding:3px 10px;border-radius:6px;">Duzelt</span>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ 7. STATE-SPECIFIC KARTLAR ══ --}}
@if($stage === 'uni_assist')
<div class="sd-grid-3">
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(22,163,74,.08);">📄</div><div><div style="font-size:10px;color:var(--u-muted);">Belgeler</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Tamam</div><div style="font-size:10px;color:var(--u-muted);">{{ $docSummary['approved'] ?? 0 }}/{{ $totalRequired }} onayli</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(124,58,237,.08);">🎓</div><div><div style="font-size:10px;color:var(--u-muted);">Basvuru</div><div style="font-size:14px;font-weight:700;">{{ ($outcomeByStep ?? collect())->where('step', 'uni_assist')->sum('total') ?: 'Hazirlaniyor' }}</div><div style="font-size:10px;color:var(--u-muted);">universiteye</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(217,119,6,.08);">⏳</div><div><div style="font-size:10px;color:var(--u-muted);">Durum</div><div style="font-size:14px;font-weight:700;">Hazirlaniyor</div></div></div>
</div>
<div class="sd-tip">
    <div class="sd-tip-icon">☕</div>
    <div><h5>Danismanin calisiyor</h5><p>Uni-Assist basvurun hazirlanirken sen rahatlikla bekleyebilirsin.</p></div>
</div>
@endif

@if($stage === 'visa')
<div class="sd-grid-3">
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(22,163,74,.08);">🎓</div><div><div style="font-size:10px;color:var(--u-muted);">Universite</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">Kabul Geldi!</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(8,145,178,.08);">🏦</div><div><div style="font-size:10px;color:var(--u-muted);">Sperrkonto</div><div style="font-size:14px;font-weight:700;">Surecte</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(217,119,6,.08);">📅</div><div><div style="font-size:10px;color:var(--u-muted);">Konsolosluk</div><div style="font-size:14px;font-weight:700;">Bekliyor</div></div></div>
</div>
@endif

@if($stage === 'abroad' || $allDone)
<div class="sd-grid-3" style="margin-bottom:20px;">
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(22,163,74,.08);">🎓</div><div><div style="font-size:10px;color:var(--u-muted);">Universite</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">Kayit Tamamlandi</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(22,163,74,.08);">🛂</div><div><div style="font-size:10px;color:var(--u-muted);">Vize</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Onaylandi</div></div></div>
    <div class="sd-stat"><div class="sd-stat-icon" style="background:rgba(22,163,74,.08);">🏠</div><div><div style="font-size:10px;color:var(--u-muted);">Konaklama</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Ayarlandi</div></div></div>
</div>

<div class="sd-ql">
    <a href="/student/housing"><span class="sd-ql-icon" style="background:#3b82f6;">🏙</span> Sehir Rehberi</a>
    <a href="/student/payments"><span class="sd-ql-icon" style="background:#7c3aed;">🏦</span> Banka & Sigorta</a>
    <a href="/student/cost-calculator"><span class="sd-ql-icon" style="background:#0891b2;">🚌</span> Ulasim</a>
    <a href="/student/materials"><span class="sd-ql-icon" style="background:#16a34a;">🛒</span> Yasam Rehberi</a>
</div>
@endif

{{-- Quick Links (her zaman) --}}
<div class="sd-ql">
    <a href="/student/registration">
        <span class="sd-ql-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);border-radius:14px;width:44px;height:44px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </span> Kayit Formu
    </a>
    <a href="/student/registration/documents">
        <span class="sd-ql-icon" style="background:linear-gradient(135deg,#0891b2,#22d3ee);border-radius:14px;width:44px;height:44px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/></svg>
        </span> Belgelerim
    </a>
    <a href="/student/contract">
        <span class="sd-ql-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);border-radius:14px;width:44px;height:44px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>
        </span> Sozlesme
    </a>
    <a href="/student/messages">
        <span class="sd-ql-icon" style="background:linear-gradient(135deg,#16a34a,#4ade80);border-radius:14px;width:44px;height:44px;">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </span> Danisman
    </a>
</div>

{{-- Countdowns --}}
@if(($countdowns ?? collect())->isNotEmpty())
<div class="sd-card">
    <div class="sd-card-head"><h4>⏳ Yaklasan Tarihler</h4></div>
    <div class="sd-card-body">
        @foreach($countdowns as $cd)
        <div class="sd-cl">
            <div class="sd-cl-dot" style="background:{{ ($cd['urgency'] ?? '') === 'urgent' ? 'rgba(220,38,38,.08)' : 'rgba(217,119,6,.08)' }};color:{{ ($cd['urgency'] ?? '') === 'urgent' ? '#dc2626' : '#d97706' }};">⏰</div>
            <div class="sd-cl-name">{{ $cd['label'] ?? '' }}</div>
            <span class="sd-cl-tag" style="background:{{ ($cd['urgency'] ?? '') === 'urgent' ? 'rgba(220,38,38,.08)' : 'rgba(217,119,6,.08)' }};color:{{ ($cd['urgency'] ?? '') === 'urgent' ? '#dc2626' : '#d97706' }};">{{ $cd['days_left'] ?? '?' }} gun · {{ $cd['deadline'] ?? '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Öğrenci Analitikleri ── --}}
@if(!empty($studentAnalytics))
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;margin-top:16px;">
    <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">📊 Süreç Analitiklerin</div>
    <div class="kpi-row-compact" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
        <div style="text-align:center;padding:10px 6px;background:var(--bg,#f8fafc);border-radius:8px;min-width:0;">
            <div style="font-size:20px;font-weight:800;color:#3b82f6;">{{ $studentAnalytics['daysSinceStart'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Gün (süreç başlangıcı)</div>
        </div>
        <div style="text-align:center;padding:10px 6px;background:var(--bg,#f8fafc);border-radius:8px;min-width:0;">
            <div style="font-size:20px;font-weight:800;color:#16a34a;">%{{ $studentAnalytics['checklistRate'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Checklist Tamamlama</div>
        </div>
        <div style="text-align:center;padding:10px 6px;background:var(--bg,#f8fafc);border-radius:8px;min-width:0;">
            <div style="font-size:20px;font-weight:800;color:#8b5cf6;">{{ $studentAnalytics['achievementCount'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Başarım Rozeti</div>
        </div>
        <div style="text-align:center;padding:10px 6px;background:var(--bg,#f8fafc);border-radius:8px;min-width:0;">
            <div style="font-size:20px;font-weight:800;color:#f59e0b;">{{ $studentAnalytics['achievementPoints'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Toplam Puan</div>
        </div>
    </div>
</div>
@endif

@endsection
