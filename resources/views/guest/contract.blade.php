@extends('guest.layouts.app')

@section('title', 'Sözleşme')
@section('page_title', 'Sözleşme')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gc-* Guest Contract v2 — State-based clean design ── */

/* Funnel stepper */
.gc-funnel { display:flex; align-items:center; background:var(--u-card); border-radius:14px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); overflow:hidden; margin-bottom:24px; }
.gc-fs { flex:1; padding:14px 18px; display:flex; align-items:center; gap:10px; border-right:1px solid var(--u-line); }
.gc-fs:last-child { border-right:none; }
.gc-fs-num { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; border:2px solid var(--u-line); background:var(--u-bg); color:var(--u-muted); transition:all .3s; }
.gc-fs.done .gc-fs-num { background:var(--u-ok); border-color:var(--u-ok); color:#fff; }
.gc-fs.active .gc-fs-num { background:rgba(13,148,136,.1); border-color:var(--u-brand); color:var(--u-brand); box-shadow:0 0 0 4px rgba(13,148,136,.08); }
.gc-fs.locked .gc-fs-num { opacity:.4; }
.gc-fs-name { font-size:12px; font-weight:600; }
.gc-fs.done .gc-fs-name { color:var(--u-ok); }
.gc-fs.active .gc-fs-name { color:var(--u-brand); }
.gc-fs.locked .gc-fs-name { color:var(--u-muted); }
.gc-fs-sub { font-size:10px; color:var(--u-muted); margin-top:1px; }

/* Hero action card */
.gc-hero { border-radius:14px; padding:24px 28px; margin-bottom:20px; color:#fff; position:relative; overflow:hidden; }
.gc-hero.teal { background:linear-gradient(135deg,#134e4a,#0d9488); }
.gc-hero.blue { background:linear-gradient(135deg,#1e3a8a,#3b82f6); }
.gc-hero.purple { background:linear-gradient(135deg,#4c1d95,#7c3aed); }
.gc-hero.amber { background:linear-gradient(135deg,#78350f,#f59e0b); }
.gc-hero.green { background:linear-gradient(135deg,#065f46,#10b981); }
.gc-hero.red { background:linear-gradient(135deg,#7f1d1d,#ef4444); }
.gc-hero-badge { display:inline-flex; align-items:center; gap:6px; font-size:10px; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,.6); margin-bottom:6px; }
.gc-hero-badge .pulse { width:7px; height:7px; border-radius:50%; background:#34d399; box-shadow:0 0 6px rgba(52,211,153,.6); animation:gcPulse 1.5s infinite; }
@keyframes gcPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.gc-hero-title { font-size:20px; font-weight:700; margin-bottom:5px; line-height:1.3; }
.gc-hero-sub { font-size:13px; color:rgba(255,255,255,.75); line-height:1.5; margin-bottom:16px; max-width:560px; }
.gc-hero-meta { display:flex; gap:16px; margin-top:12px; flex-wrap:wrap; }
.gc-hero-meta span { font-size:11px; color:rgba(255,255,255,.5); display:flex; align-items:center; gap:4px; }
.gc-hero-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 24px; border-radius:8px; background:#fff; color:#134e4a; font-size:13px; font-weight:700; border:none; cursor:pointer; font-family:inherit; transition:all .15s; text-decoration:none; }
.gc-hero-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.15); }

/* Process timeline */
.gc-proc { background:var(--u-card); border-radius:12px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); overflow:hidden; margin-bottom:20px; }
.gc-proc-head { padding:14px 18px; border-bottom:1px solid var(--u-line); font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px; }
.gc-proc-step { padding:12px 18px 12px 48px; position:relative; display:flex; align-items:center; gap:10px; }
.gc-proc-step::before { content:''; position:absolute; left:27px; top:0; bottom:0; width:2px; background:var(--u-line); }
.gc-proc-step:first-child::before { top:50%; }
.gc-proc-step:last-child::before { bottom:50%; }
.gc-proc-dot { position:absolute; left:18px; top:50%; transform:translateY(-50%); width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:9px; z-index:1; border:2px solid var(--u-card); }
.gc-proc-dot.done { background:var(--u-ok); color:#fff; box-shadow:0 0 0 3px rgba(22,163,74,.1); }
.gc-proc-dot.now { background:var(--u-brand); color:#fff; box-shadow:0 0 0 3px rgba(13,148,136,.1); animation:gcPulse 2s infinite; }
.gc-proc-dot.wait { background:var(--u-line); color:var(--u-muted); }
.gc-proc-name { font-size:12px; font-weight:600; flex:1; }
.gc-proc-step.is-done .gc-proc-name { color:var(--u-ok); }
.gc-proc-step.is-now .gc-proc-name { color:var(--u-brand); }
.gc-proc-step.is-wait .gc-proc-name { color:var(--u-muted); }
.gc-proc-tag { font-size:9px; font-weight:600; padding:2px 8px; border-radius:99px; }
.gc-proc-tag.ok { background:rgba(22,163,74,.08); color:var(--u-ok); }
.gc-proc-tag.now { background:rgba(13,148,136,.08); color:var(--u-brand); }

/* Tip */
.gc-tip { background:rgba(13,148,136,.04); border:1px solid rgba(13,148,136,.1); border-radius:12px; padding:16px 18px; display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; }
.gc-tip-icon { width:34px; height:34px; border-radius:8px; background:rgba(13,148,136,.08); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.gc-tip h5 { font-size:12px; font-weight:600; color:var(--u-text); margin-bottom:2px; }
.gc-tip p { font-size:12px; color:var(--u-muted); line-height:1.5; }

/* Info grid */
.gc-info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.gc-info-card { background:var(--u-card); border-radius:10px; padding:14px; box-shadow:var(--u-shadow); border:1px solid var(--u-line); display:flex; align-items:center; gap:10px; }
.gc-info-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }

/* Contract viewer */
.gc-viewer { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; overflow:hidden; box-shadow:var(--u-shadow); margin-bottom:20px; }
.gc-viewer-top { padding:12px 18px; border-bottom:1px solid var(--u-line); display:flex; align-items:center; justify-content:space-between; background:var(--u-bg); }
.gc-viewer-title { font-size:14px; font-weight:700; }
.gc-viewer-meta { font-size:11px; color:var(--u-muted); margin-top:2px; }
.gc-viewer-btn { padding:5px 10px; border-radius:6px; font-size:11px; font-weight:600; border:1px solid var(--u-line); background:var(--u-card); color:var(--u-text); cursor:pointer; display:inline-flex; align-items:center; gap:4px; text-decoration:none; transition:all .15s; font-family:inherit; }
.gc-viewer-btn:hover { border-color:var(--u-brand); color:var(--u-brand); }
.gc-viewer-body { padding:24px 28px; max-height:400px; overflow-y:auto; font-size:12px; line-height:1.8; white-space:pre-wrap; }
.gc-viewer-body h2 { font-size:15px; font-weight:700; text-align:center; margin:0 0 12px; }
.gc-viewer-body h3 { font-size:13px; font-weight:700; margin:16px 0 6px; color:var(--u-brand); border-bottom:1px solid var(--u-line); padding-bottom:3px; }
.gc-read-bar { height:3px; background:var(--u-line); overflow:hidden; }
.gc-read-fill { height:100%; background:linear-gradient(90deg,var(--u-brand),#7c3aed); width:0%; transition:width .3s; }
.gc-scroll-hint { text-align:center; padding:8px; background:var(--u-bg); font-size:11px; color:var(--u-muted); border-top:1px solid var(--u-line); }

/* Sign options */
.gc-sign-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px; }
.gc-sign-opt { background:var(--u-card); border-radius:12px; box-shadow:var(--u-shadow); border:2px dashed var(--u-line); padding:22px; text-align:center; cursor:pointer; transition:all .2s; }
.gc-sign-opt:hover { border-color:var(--u-brand); background:rgba(13,148,136,.02); }
.gc-sign-opt .ico { font-size:28px; margin-bottom:8px; }
.gc-sign-opt .ttl { font-size:14px; font-weight:700; margin-bottom:3px; }
.gc-sign-opt .sub { font-size:11px; color:var(--u-muted); }

/* Checklist */
.gc-check { display:flex; align-items:flex-start; gap:10px; padding:12px 16px; background:var(--u-card); border:1px solid var(--u-line); border-radius:8px; cursor:pointer; transition:all .15s; margin-bottom:6px; }
.gc-check:hover { border-color:var(--u-brand); }
.gc-check.checked { border-color:rgba(22,163,74,.3); background:rgba(22,163,74,.03); }
.gc-check-box { width:20px; height:20px; border-radius:5px; border:2px solid var(--u-line); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:11px; transition:all .2s; margin-top:1px; }
.gc-check.checked .gc-check-box { background:var(--u-ok); border-color:var(--u-ok); color:#fff; }
.gc-check-text { font-size:12px; line-height:1.4; flex:1; }

/* Submit */
.gc-submit { width:100%; padding:14px; border-radius:10px; background:linear-gradient(135deg,#065f46,#10b981); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 12px rgba(16,185,129,.3); transition:all .2s; font-family:inherit; margin:14px 0 20px; }
.gc-submit:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,185,129,.35); }
.gc-submit:disabled { opacity:.4; cursor:not-allowed; transform:none; }

/* File upload */
.gc-file-label { display:flex; align-items:center; gap:8px; border:1.5px dashed #c7d9f0; border-radius:8px; padding:10px 12px; cursor:pointer; font-size:12px; color:#4d6689; background:#f4f8ff; transition:background .15s,border-color .15s; }
.gc-file-label:hover { background:#eaf3ff; border-color:#93c5fd; }

/* Help */
.gc-help { background:var(--u-card); border:1px solid var(--u-line); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.gc-help p { font-size:12px; color:var(--u-muted); flex:1; line-height:1.5; }
.gc-help strong { color:var(--u-text); display:block; font-size:13px; margin-bottom:2px; }
.gc-help-btn { padding:7px 16px; border-radius:8px; background:var(--u-brand); color:#fff; border:none; font-size:11px; font-weight:700; cursor:pointer; font-family:inherit; white-space:nowrap; flex-shrink:0; text-decoration:none; }

/* Alert */
.gc-alert { border-radius:10px; padding:12px 16px; border:1px solid; display:flex; gap:10px; align-items:flex-start; margin-bottom:14px; }
.gc-alert.warn { border-color:#fecaca; background:#fff5f5; color:#b91c1c; }
.gc-alert.info { border-color:#c5dafd; background:#f0f7ff; color:#1a3e8a; }
.gc-alert.purple { border-color:#c4b5fd; background:#f5f3ff; color:#4c1d95; }
.gc-alert-icon { font-size:16px; flex-shrink:0; margin-top:1px; }
.gc-alert-body { flex:1; }
.gc-alert-body strong { display:block; margin-bottom:4px; }

/* Celebration */
.gc-celebrate { background:linear-gradient(135deg,#065f46,#10b981); color:#fff; border-radius:14px; padding:36px 28px; text-align:center; margin-bottom:20px; box-shadow:var(--u-shadow-md); }
.gc-celebrate .emoji { font-size:48px; margin-bottom:12px; }
.gc-celebrate h2 { font-size:22px; font-weight:800; margin-bottom:6px; }
.gc-celebrate p { font-size:14px; opacity:.85; max-width:460px; margin:0 auto; line-height:1.5; }

/* Popup */
.gc-popup-overlay { position:fixed; inset:0; background:rgba(7,18,35,.45); display:none; align-items:center; justify-content:center; z-index:9999; }
.gc-popup-card { width:min(92vw,520px); background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; box-shadow:0 14px 50px rgba(12,29,56,.24); padding:20px; }
.gc-popup-title { margin:0 0 10px; color:#a11b1b; font-size:18px; font-weight:800; }

/* Service chips */
.svc-chip-wrap { display:flex; flex-wrap:wrap; gap:8px; margin-top:6px; }
.svc-chip { border:1px solid var(--u-line); border-radius:999px; padding:7px 12px; background:var(--u-card); color:var(--u-text); font-size:12px; font-weight:700; cursor:pointer; user-select:none; transition:background .15s,border-color .15s; }
.svc-chip input { display:none; }
.svc-chip.active { background:#eaf3ff; border-color:#9ec2f3; color:#124682; }

@media(max-width:860px){
    .gc-funnel { flex-direction:column; }
    .gc-fs { border-right:none; border-bottom:1px solid var(--u-line); }
    .gc-info-grid { grid-template-columns:1fr; }
    .gc-sign-grid { grid-template-columns:1fr; }
}

/* Print — sadece contractHtmlPrintLayout goster, geri kalan her sey gizli */
@@media print {
    /* 1. Tum sayfa icerigini gizle */
    .sidebar, .side, .topbar, .shell .side, .trust-bar,
    .gc-funnel, .gc-hero, .gc-sign-grid, .gc-help, .gc-tip,
    .gc-alert, .gc-proc, .gc-info-grid, .gc-celebrate,
    .gc-viewer, .gc-popup-overlay, .dm-widget-container,
    .no-print, nav, header, footer,
    #contractTextSection,
    #digitalSignSection,
    #signedUploadSection,
    #quickDecisionForm { display:none !important; }

    /* 2. Print layout'u goster */
    #contractHtmlPrintLayout {
        display:block !important;
        position:static !important;
        width:100% !important;
    }
    #contractPrintBody {
        max-height:none !important;
        overflow:visible !important;
        border:none !important;
        padding:0 !important;
    }

    /* 3. Layout sifirlama */
    .shell { display:block !important; }
    .shell > .main { padding:0 !important; width:100% !important; margin:0 !important; }
    .main { padding:0 !important; }

    /* 4. Ekler */
    #contractHtmlPrintLayout details { display:block !important; }
    #contractHtmlPrintLayout details > div { display:block !important; }
    #contractHtmlPrintLayout details summary { display:none !important; }

    /* 5. Sayfa */
    @@page { margin:20mm; size:A4; }
    body { font-size:11pt; background:#fff !important; }
}

.jm-minimalist .gc-read-fill { background:var(--u-ok); }
.jm-minimalist .gc-viewer { box-shadow:none; }
.jm-minimalist .gc-submit { box-shadow:none; }
.jm-minimalist .gc-submit:hover:not(:disabled) { transform:none; box-shadow:none; opacity:.88; }
</style>
@endpush

@section('content')
@php
    $status = (string)($contractStatus ?? 'not_requested');
    $formDraftComplete  = (bool)($formDraftComplete  ?? false);
    $formRequiredFilled = (int) ($formRequiredFilled ?? 0);
    $formRequiredTotal  = (int) ($formRequiredTotal  ?? 0);

    $statusLabel = match($status) {
        'pending_manager'  => 'Hazırlanıyor',
        'requested'        => 'İmza Bekleniyor',
        'signed_uploaded'  => 'Onay Bekleniyor',
        'approved'         => 'Aktif',
        'rejected'         => 'Düzeltme Gerekli',
        'cancelled'        => 'İptal Edildi',
        'reopen_requested' => 'Yeniden Değerlendirme',
        default            => 'Henüz Başlamadı',
    };

    $statusPill = match($status) {
        'approved'                        => 'ok',
        'rejected', 'cancelled'           => 'danger',
        'pending_manager', 'requested',
        'signed_uploaded', 'reopen_requested' => 'info',
        default                           => 'warn',
    };

    // Her prereq için spesifik eksik liste — chip kutusu içinde tek bakışta görünür
    $formMissingItems = collect($formMissingItems ?? []);
    $docsMissingItems = collect($missingRequiredDocuments ?? [])->map(fn ($d) => (string) ($d['name'] ?? ''))->filter();

    $prereqs = [
        [
            'label'         => 'Kayıt Formu',
            'done'          => !empty($formCompleted),
            'value'         => !empty($formCompleted) ? 'Tamam' : 'Eksik',
            'link'          => route('guest.registration.form'),
            'missing_items' => $formMissingItems->values()->all(),
        ],
        [
            'label'         => 'Belgeler',
            'done'          => !empty($docsCompleted),
            'value'         => !empty($docsCompleted) ? 'Tamam' : 'Eksik',
            'link'          => route('guest.registration.documents'),
            'missing_items' => $docsMissingItems->values()->all(),
        ],
        [
            'label'         => 'Paket',
            'done'          => !empty($packageSelected),
            'value'         => !empty($packageSelected) ? ($guest?->selected_package_title ?: 'Seçildi') : 'Yapılmadı',
            'link'          => route('guest.services'),
            'missing_items' => !empty($packageSelected) ? [] : ['Henüz paket seçimi yapılmadı'],
        ],
    ];
    $allPrereqsDone = collect($prereqs)->every(fn($p) => $p['done']);

    $stepActive = match($status) {
        'not_requested'        => 0,
        'pending_manager'      => 1,
        'requested', 'rejected'=> 2,
        'signed_uploaded'      => 3,
        'approved'             => 4,
        default                => 0,
    };
    $funnelSteps = [
        ['name' => 'Hazırla',        'sub' => match(true) { $stepActive > 0 => 'Tamamlandı', default => 'Şimdi başlat' }],
        ['name' => 'Hazırlanma',     'sub' => match(true) { $stepActive > 1 => 'Tamamlandı', $stepActive === 1 => 'Hazırlanıyor...', default => 'Hazırlama sonrası' }],
        ['name' => 'Oku & İmzala',   'sub' => match(true) { $status === 'rejected' => 'Düzeltme gerekli', $stepActive > 2 => 'İmzalandı', $stepActive === 2 => 'İmzanı bekliyor', default => 'İmza sonrası' }],
        ['name' => 'Firma Onayı',    'sub' => match(true) { $stepActive > 3 => 'Onaylandı', $stepActive === 3 => 'Onay bekleniyor', default => '—' }],
    ];

    $allowContractUpdate = in_array($status, ['requested', 'signed_uploaded', 'rejected'], true);
    $missingRequiredDocuments = collect($missingRequiredDocuments ?? []);
@endphp

{{-- ── Page Header ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--u-text);">Sözleşme</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
            @if($status === 'approved') Tum surec tamamlandi.
            @elseif($status === 'not_requested') Paketini seçtin — şimdi sözleşme sürecini başlat.
            @elseif($status === 'pending_manager') Danışman sözleşmeni hazırlıyor.
            @elseif($status === 'requested') Sözleşmen hazir — oku ve imzala.
            @elseif($status === 'signed_uploaded') İmzalı sözleşmen firma onayında.
            @else Sözleşme sürecini takip et.
            @endif
        </div>
    </div>
    <span class="badge {{ $statusPill }}" style="font-size:var(--tx-xs);padding:5px 12px;">{{ $statusLabel }}</span>
</div>

{{-- ── Funnel Stepper ── --}}
<div class="gc-funnel">
    @foreach($funnelSteps as $i => $fs)
        @php
            $cls = $i < $stepActive ? 'done' : ($i === $stepActive ? 'active' : 'locked');
        @endphp
        <div class="gc-fs {{ $cls }}">
            <div class="gc-fs-num">{{ $i < $stepActive ? '✓' : ($i + 1) }}</div>
            <div>
                <div class="gc-fs-name">{{ $fs['name'] }}</div>
                <div class="gc-fs-sub">{{ $fs['sub'] }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Üst chip kutusu — her eksik prereq spesifik eksiklerle birlikte ── --}}
@if(!$allPrereqsDone)
@php $missingCount = collect($prereqs)->where('done', false)->count(); @endphp
<div style="background:rgba(37,99,235,.06);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:14px 16px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
        <span style="font-size:16px;color:var(--u-brand,#2563eb);">ℹ</span>
        <strong style="font-size:13px;color:var(--u-brand,#2563eb);">{{ $missingCount }} eksik adım var</strong>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
        @foreach($prereqs as $p)
            @if(!$p['done'])
            @php
                $items = collect($p['missing_items'] ?? []);
                $shown = $items->take(3);
                $remaining = max(0, $items->count() - 3);
            @endphp
            <a href="{{ $p['link'] }}" style="display:flex;flex-direction:column;gap:6px;padding:12px 14px;border-radius:10px;background:var(--u-card,#fff);border:1px solid rgba(37,99,235,.25);text-decoration:none;color:var(--u-brand,#2563eb);transition:all .15s;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <strong style="font-size:13px;font-weight:800;">{{ $p['label'] }}</strong>
                    <span style="font-size:11px;font-weight:700;">Tamamla →</span>
                </div>
                @if($shown->isNotEmpty())
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:3px;">
                    @foreach($shown as $item)
                    <li style="font-size:11px;color:#475569;font-weight:500;display:flex;gap:5px;align-items:flex-start;line-height:1.45;">
                        <span style="color:#dc2626;flex-shrink:0;">•</span>
                        <span>{{ $item }}</span>
                    </li>
                    @endforeach
                    @if($remaining > 0)
                    <li style="font-size:10px;color:#94a3b8;font-weight:600;font-style:italic;margin-top:2px;">
                        + {{ $remaining }} daha — {{ $remaining > 1 ? 'bunları' : 'bunu' }} doldurduğunda görünecek
                    </li>
                    @endif
                </ul>
                @endif
            </a>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- ── Rejected / Cancelled / Reopen uyarilari ── --}}
@if($status === 'rejected')
    <div class="gc-hero red" style="margin-bottom:14px;">
        <div style="font-size:28px;margin-bottom:8px;">⚠️</div>
        <div class="gc-hero-title">Sözleşmeniz reddedildi</div>
        <div class="gc-hero-sub" style="max-width:100%;">
            <strong>Red sebebi:</strong> {{ $guest?->status_message ?? 'Belirtilmedi.' }}
        </div>
        <div style="margin-top:10px;padding:10px 14px;background:rgba(255,255,255,.12);border-radius:8px;font-size:13px;color:rgba(255,255,255,.85);line-height:1.6;">
            Sözleşmenizi düzeltip tekrar imzalayabilir veya yeni imzalı dosya yükleyebilirsiniz.<br>
            Aşağıdaki imza seçeneklerini kullanarak tekrar gönderebilirsiniz.
        </div>
    </div>
@endif

@if($status === 'cancelled')
    <div class="gc-alert warn">
        <span class="gc-alert-icon">⛔</span>
        <div class="gc-alert-body">
            <strong>Sözleşme İptal Edildi</strong>
            Danışmanlik firmanız tarafından sözleşmeniz iptal edilmistir.
        </div>
    </div>
    <form method="POST" action="{{ route('guest.contract.reopen-request') }}"
          onsubmit="return confirm('Yeniden degerlendirme talebi göndermek istediginizden emin misiniz?');"
          style="margin-bottom:20px;">
        @csrf
        <label style="display:block;font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:6px;">Talebinizin nedeni <span style="color:var(--u-danger);">*</span></label>
        <textarea name="reopen_reason" required maxlength="1000" rows="3" placeholder="Neden yeniden degerlendirme istediginizi kisaca aciklayin..."
                  style="width:100%;padding:10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:var(--tx-sm);font-family:inherit;box-sizing:border-box;resize:vertical;">{{ old('reopen_reason') }}</textarea>
        <div style="margin-top:10px;"><button type="submit" class="btn ok">Yeniden Degerlendirme Talep Et</button></div>
    </form>
@endif

@if($status === 'reopen_requested')
    <div class="gc-alert purple" style="margin-bottom:20px;">
        <span class="gc-alert-icon">⏳</span>
        <div class="gc-alert-body">
            <strong>Yeniden Degerlendirme Talebiniz Iletildi</strong>
            Danışman ekibimiz talebinizi inceliyor.
            @if(!empty($guest?->reopen_requested_at))
                <br><span style="font-size:var(--tx-xs);">Talep tarihi: {{ optional($guest->reopen_requested_at)->format('d.m.Y H:i') }}</span>
            @endif
        </div>
    </div>
@endif

{{-- ══════════════════════════════════════════
     STATE 1: not_requested — Talep Et
══════════════════════════════════════════ --}}
@if($status === 'not_requested')
    @if($allPrereqsDone)
        {{-- Tüm ön koşullar tamam — yeşil hero, aktif buton --}}
        <div class="gc-hero teal">
            <div class="gc-hero-badge"><span class="pulse"></span> Sıra sende</div>
            <div class="gc-hero-title">Sözleşmen için her şey hazır</div>
            <div class="gc-hero-sub">Aşağıdaki butona basınca danışmanın sözleşmeni hazırlayacak ve sana iletecek. Birkaç dakika sürebilir.</div>
            <form method="POST" action="{{ route('guest.contract.request') }}" id="contractRequestForm" style="display:inline;">
                @csrf
                <button type="submit" class="gc-hero-btn" id="contractRequestButton">📄 Sözleşmemi Hazırla</button>
            </form>
            <div class="gc-hero-meta">
                <span>⏱️ Hazırlanma süresi: ~1 iş günü</span>
                <span>📧 E-posta ile bilgilendirileceksin</span>
            </div>
        </div>

        <div class="gc-info-grid">
            @foreach($prereqs as $p)
            <div class="gc-info-card">
                <div class="gc-info-icon" style="background:rgba(22,163,74,.08);">{{ $loop->index === 0 ? '📝' : ($loop->index === 1 ? '📄' : '📦') }}</div>
                <div>
                    <div style="font-size:11px;color:var(--u-muted);">{{ $p['label'] }}</div>
                    <div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ {{ $p['value'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="gc-tip">
            <div class="gc-tip-icon">💡</div>
            <div><h5>Sonraki adımlar ne olacak?</h5><p>Talep ettikten sonra danışmanın sözleşmeyi hazırlayacak. Hazır olunca okuyup dijital imza veya fiziksel imzalı dosya yükleyerek göndereceksin.</p></div>
        </div>
    @else
        {{-- Eksik ön koşul var — sade hero (eksik liste üstte chip kutusunda zaten var) --}}
        <div class="gc-hero" style="background:linear-gradient(135deg,#f1f5f9 0%,#e2e8f0 100%);color:#334155;">
            <div class="gc-hero-badge" style="background:rgba(100,116,139,.12);color:#475569;"><span>🔒</span> Henüz hazır değil</div>
            <div class="gc-hero-title" style="color:#0f172a;">Önce paket seçimini yap</div>
            <div class="gc-hero-sub" style="color:#475569;">
                Sözleşmen burada hazırlanacak. Yukarıdaki adımları tamamla, butonu otomatik açacağız.
            </div>
        </div>
    @endif
@endif

{{-- ══════════════════════════════════════════
     STATE 2: pending_manager — Hazırlanıyor
══════════════════════════════════════════ --}}
@if($status === 'pending_manager')
    <div class="gc-hero blue">
        <div class="gc-hero-badge"><span class="pulse"></span> Hazırlanıyor</div>
        <div class="gc-hero-title">Sözleşmen hazırlanıyor</div>
        <div class="gc-hero-sub">Birkaç dakika sürebilir. Hazır olduğunda e-posta ile haber vereceğiz; bu sayfaya geri dönüp imzalayabilirsin.</div>
        <div class="gc-hero-meta">
            <span>⏱️ Tahmini: 1 iş günü</span>
            <span>📧 E-posta bildirimi alacaksın</span>
        </div>
    </div>

    @if(!empty($contractStepper) && $contractStepper->count() > 0)
    <div class="gc-proc">
        <div class="gc-proc-head">📋 Sözleşme Süreçi</div>
        @foreach($contractStepper as $cs)
        <div class="gc-proc-step {{ $cs['status'] === 'done' ? 'is-done' : ($cs['status'] === 'active' ? 'is-now' : 'is-wait') }}">
            <div class="gc-proc-dot {{ $cs['status'] === 'done' ? 'done' : ($cs['status'] === 'active' ? 'now' : 'wait') }}">{{ $cs['status'] === 'done' ? '✓' : $cs['icon'] }}</div>
            <div class="gc-proc-name">{{ $cs['label'] }}</div>
            @if($cs['status'] === 'active')<span class="gc-proc-tag now">Su An</span>@elseif($cs['status'] === 'done')<span class="gc-proc-tag ok">Tamam</span>@endif
        </div>
        @endforeach
    </div>
    @endif

    <div style="display:flex;gap:8px;margin-bottom:20px;">
        <form method="POST" action="{{ route('guest.contract.withdraw') }}" onsubmit="return confirm('Sözleşme hazırlığını geri çekmek istediğinize emin misiniz?');">
            @csrf
            <button type="submit" class="btn warn" style="font-size:var(--tx-xs);">Hazırlığı Geri Çek</button>
        </form>
    </div>

    <div class="gc-tip">
        <div class="gc-tip-icon">☕</div>
        <div><h5>Şu an yapman gereken bir şey yok</h5><p>Sözleşmen hazırlanırken sen rahatça bekleyebilirsin.</p></div>
    </div>
@endif

{{-- ══════════════════════════════════════════
     STATE 3: requested — Oku & İmzala
══════════════════════════════════════════ --}}
@if(in_array($status, ['requested', 'rejected'], true))
    <div class="gc-hero {{ $status === 'rejected' ? 'purple' : 'purple' }}">
        <div class="gc-hero-badge"><span class="pulse"></span> {{ $status === 'rejected' ? 'Düzeltme gerekli' : 'Sıra sende' }}</div>
        <div class="gc-hero-title">{{ $status === 'rejected' ? 'Sözleşmende düzeltme gerekiyor' : 'Sözleşmen hazır. İncele ve imzala' }}</div>
        <div class="gc-hero-sub">{{ $status === 'rejected' ? 'Danışmanın seninle iletişime geçecek. Düzeltilmiş sözleşmeyi aşağıdan inceleyip tekrar imzalayabilirsin.' : 'Aşağıda sözleşme metnini okuyabilirsin. Okuduktan sonra dijital imza atabilir ya da imzalı PDF yükleyebilirsin.' }}</div>
    </div>

    {{-- Contract viewer --}}
    @if($contractSnapshotText !== '')
    <div class="gc-viewer no-print" id="contractTextSection">
        <div class="gc-viewer-top">
            <div>
                <div class="gc-viewer-title">📜 Sözleşme Metni</div>
                <div class="gc-viewer-meta">
                    Sablon: <strong>{{ $contractTemplateCode !== '' ? $contractTemplateCode : '-' }}</strong>
                    · Oluşturulma: <strong>{{ $contractGeneratedAt ? \Carbon\Carbon::parse($contractGeneratedAt)->format('d.m.Y H:i') : '-' }}</strong>
                </div>
            </div>
            <div style="display:flex;gap:6px;" class="no-print">
                <button type="button" class="gc-viewer-btn" onclick="window.print()" title="Yazdır veya PDF olarak kaydet">🖨 Yazdır / PDF</button>
            </div>
        </div>
        <div class="gc-read-bar"><div class="gc-read-fill" id="gcReadFill"></div></div>
        <div class="gc-viewer-body" id="gcContractBody" onscroll="gcUpdateReadProgress(this)">{{ $contractSnapshotText }}</div>
        <div class="gc-scroll-hint" id="gcScrollHint">📖 Lutfen sözleşmeyi tamamen okuyun</div>

        @if($contractAnnexKvkkText !== '')
        <div style="padding:0 18px 12px;">
            <details style="margin-top:10px;" id="annexKvkk">
                <summary style="cursor:pointer;font-weight:700;padding:5px 0;font-size:var(--tx-sm);"><strong>Ek-1 — KVKK Aydinlatma Metni</strong></summary>
                <div style="margin-top:6px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:8px;padding:14px;background:#fff;font-size:var(--tx-xs);line-height:1.7;">{{ $contractAnnexKvkkText }}</div>
            </details>
        </div>
        @endif
        @if($contractAnnexCommitmentText !== '')
        <div style="padding:0 18px 12px;">
            <details id="annexCommitment">
                <summary style="cursor:pointer;font-weight:700;padding:5px 0;font-size:var(--tx-sm);"><strong>Ek-2 — Taahhutname</strong></summary>
                <div style="margin-top:6px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:8px;padding:14px;background:#fff;font-size:var(--tx-xs);line-height:1.7;">{{ $contractAnnexCommitmentText }}</div>
            </details>
        </div>
        @endif
        @if(($contractAnnexPaymentText ?? '') !== '')
        <div style="padding:0 18px 12px;">
            <details id="annexPayment">
                <summary style="cursor:pointer;font-weight:700;padding:5px 0;font-size:var(--tx-sm);"><strong>Ek-3 — Odeme Plani</strong></summary>
                <div style="margin-top:6px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:8px;padding:14px;background:#fff;font-size:var(--tx-xs);line-height:1.7;">{{ $contractAnnexPaymentText }}</div>
            </details>
        </div>
        @endif
    </div>
    @endif

    {{-- Sign options --}}
    @if(empty($guest?->contract_digital_signed_at))
    <div class="gc-sign-grid">
        <div class="gc-sign-opt" onclick="document.getElementById('digitalSignSection')?.scrollIntoView({behavior:'smooth',block:'center'})">
            <div class="ico">✍️</div>
            <div class="ttl">Dijital Imza</div>
            <div class="sub">Ekranda parmagini veya farenle imzala</div>
        </div>
        <div class="gc-sign-opt" onclick="document.getElementById('signedUploadForm')?.scrollIntoView({behavior:'smooth',block:'center'})">
            <div class="ico">📎</div>
            <div class="ttl">Dosya Yükle</div>
            <div class="sub">İmzalı PDF veya fotografi yükle</div>
        </div>
    </div>
    @endif

    {{-- Digital signature --}}
    @if(empty($guest?->contract_digital_signed_at) && $contractSnapshotText !== '')
    <div class="gc-viewer no-print" id="digitalSignSection" style="border:2px dashed var(--u-line);">
        <div class="gc-viewer-top">
            <div class="gc-viewer-title">✍ E-Imza</div>
            <button type="button" class="gc-viewer-btn" id="signClearBtn">Temizle</button>
        </div>
        <div style="padding:14px 18px;">
            <p style="margin:0 0 12px;font-size:var(--tx-xs);color:var(--u-muted);">Sözleşmeyi aşağıdaki alana parmagini veya farenle imzalayabilirsin.</p>
            <div style="border:2px dashed var(--u-line);border-radius:10px;overflow:hidden;background:var(--u-bg);position:relative;transition:border-color .2s;" id="sigCanvasWrap">
                <canvas id="signatureCanvas" width="680" height="160" style="width:100%;height:160px;cursor:crosshair;display:block;touch-action:none;"></canvas>
                <div id="signCanvasPlaceholder" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:var(--tx-sm);color:#9ca3af;pointer-events:none;">İmzanızi buraya cizin</div>
            </div>
            <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;align-items:flex-start;">
                <label style="display:flex;align-items:flex-start;gap:6px;font-size:var(--tx-xs);color:var(--u-text);cursor:pointer;flex:1;min-width:200px;">
                    <input type="checkbox" id="signConsentCheck" style="margin-top:2px;flex-shrink:0;width:14px;height:14px;accent-color:var(--u-brand);">
                    <span>Sözleşmeyi okudum, tum sartlari kabul ediyorum ve bu dijital imzanın yasal gecerliligi oldugunu onayliyorum.</span>
                </label>
                <button type="button" id="signSubmitBtn" class="gc-submit" style="margin:0;width:auto;min-width:140px;padding:10px 16px;font-size:var(--tx-xs);">✅ Dijital Imzayi Gonder</button>
            </div>
            <div id="signFeedback" style="margin-top:6px;font-size:var(--tx-xs);display:none;"></div>
        </div>
    </div>
    @elseif(!empty($guest?->contract_digital_signed_at))
    <div style="padding:14px 18px;display:flex;align-items:center;gap:12px;background:rgba(22,163,74,.05);border:1px solid rgba(22,163,74,.2);border-radius:10px;margin-bottom:20px;">
        <span style="font-size:var(--tx-xl);">✅</span>
        <div>
            <div style="font-size:var(--tx-sm);font-weight:700;color:#166534;">Dijital Imza Alindi</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">{{ optional($guest->contract_digital_signed_at)->format('d.m.Y H:i') }} · e-imza hukuken gecerlidir</div>
        </div>
    </div>
    @endif

    {{-- File upload --}}
    @if($contractSnapshotText !== '')
    <div class="gc-viewer no-print" style="margin-bottom:20px;" id="signedUploadSection">
        <div class="gc-viewer-top">
            <div class="gc-viewer-title">📎 İmzalı Dosya Yükle</div>
        </div>
        <div style="padding:16px 18px;">
            @if($contractSignedFilePath !== '')
            <div class="gc-alert info" style="margin-bottom:10px;">
                <span class="gc-alert-icon">📎</span>
                <div class="gc-alert-body">Mevcut yuklu dosya: <strong>{{ basename($contractSignedFilePath) }}</strong></div>
            </div>
            @endif
            <form method="POST" action="{{ route('guest.contract.upload-signed') }}" enctype="multipart/form-data" id="signedUploadForm">
                @csrf
                <label class="gc-file-label" for="signedContractFile">
                    <span style="font-size:var(--tx-lg);">📎</span>
                    <span id="signedFileName">Dosya secmek için tiklayin (PDF, JPG, PNG — maks. 10 MB)</span>
                </label>
                <input type="file" id="signedContractFile" name="signed_contract" accept=".pdf,.jpg,.jpeg,.png" required
                       style="display:none;" onchange="var s=this.files[0];document.getElementById('signedFileName').textContent=s?s.name:'Dosya secilmedi';">
                <div style="display:flex;flex-direction:column;gap:8px;margin:12px 0;">
                    <label style="display:flex;align-items:flex-start;gap:6px;font-size:var(--tx-xs);cursor:pointer;">
                        <input type="checkbox" name="consent_contract" required style="margin-top:2px;flex-shrink:0;width:14px;height:14px;accent-color:var(--u-brand);">
                        <span>Sözleşme metnini okudum ve tum sartlari kabul ediyorum.</span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:6px;font-size:var(--tx-xs);cursor:pointer;">
                        <input type="checkbox" name="consent_kvkk" required style="margin-top:2px;flex-shrink:0;width:14px;height:14px;accent-color:var(--u-brand);">
                        <span>KVKK kapsaminda kisisel verilerimin danışmanlik sürecinde islenmesine onay veriyorum.</span>
                    </label>
                </div>
                <button type="submit" class="btn ok" style="min-width:180px;">İmzalı Dosyayi Gonder</button>
            </form>
        </div>
    </div>
    @endif
@endif

{{-- ══════════════════════════════════════════
     STATE 4: signed_uploaded — Onay Bekleniyor
══════════════════════════════════════════ --}}
@if($status === 'signed_uploaded')
    <div class="gc-hero amber">
        <div class="gc-hero-badge"><span class="pulse"></span> Onay bekleniyor</div>
        <div class="gc-hero-title">İmzaladın!</div>
        <div class="gc-hero-sub">Sözleşmen danışmanının onayını bekliyor (1-2 iş günü). Onaylandığında resmi {{ config('brand.name', 'MentorDE') }} öğrencisi olacaksın!</div>
        <div class="gc-hero-meta">
            <span>⏱️ Tahmini: 1-2 iş günü</span>
            <span>📧 Sonuç e-posta ile bildirilecek</span>
        </div>
    </div>

    @if(!empty($contractStepper) && $contractStepper->count() > 0)
    <div class="gc-proc">
        <div class="gc-proc-head">📋 Sözleşme Süreçi</div>
        @foreach($contractStepper as $cs)
        <div class="gc-proc-step {{ $cs['status'] === 'done' ? 'is-done' : ($cs['status'] === 'active' ? 'is-now' : 'is-wait') }}">
            <div class="gc-proc-dot {{ $cs['status'] === 'done' ? 'done' : ($cs['status'] === 'active' ? 'now' : 'wait') }}">{{ $cs['status'] === 'done' ? '✓' : $cs['icon'] }}</div>
            <div class="gc-proc-name">{{ $cs['label'] }}</div>
            @if($cs['status'] === 'active')<span class="gc-proc-tag now">Su An</span>@elseif($cs['status'] === 'done')<span class="gc-proc-tag ok">Tamam</span>@endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="gc-tip">
        <div class="gc-tip-icon">🎉</div>
        <div><h5>Neredeyse tamam!</h5><p>İmzalı sözleşmen danışmana ulasti. Onay geldiginde artik resmi ogrencimiz olacaksin.</p></div>
    </div>
@endif

{{-- ══════════════════════════════════════════
     STATE 5: approved — Tamamlandı
══════════════════════════════════════════ --}}
@if($status === 'approved')
    <div class="gc-celebrate">
        <div class="emoji">🎓</div>
        <h2>Tebrikler! 🎉</h2>
        <p>Sözleşmen aktif. Resmen {{ config('brand.name', 'MentorDE') }} öğrencisisin! Almanya yolculuğun resmen başladı.</p>
    </div>

    <div class="gc-info-grid">
        <div class="gc-info-card">
            <div class="gc-info-icon" style="background:rgba(22,163,74,.08);">📝</div>
            <div><div style="font-size:11px;color:var(--u-muted);">Kayıt Formu</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Tamamlandı</div></div>
        </div>
        <div class="gc-info-card">
            <div class="gc-info-icon" style="background:rgba(22,163,74,.08);">📄</div>
            <div><div style="font-size:11px;color:var(--u-muted);">Belgeler</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Onaylandı</div></div>
        </div>
        <div class="gc-info-card">
            <div class="gc-info-icon" style="background:rgba(22,163,74,.08);">📜</div>
            <div><div style="font-size:11px;color:var(--u-muted);">Sözleşme</div><div style="font-size:14px;font-weight:700;color:var(--u-ok);">✓ Onaylandı</div></div>
        </div>
    </div>

    <div class="gc-tip" style="background:linear-gradient(135deg,rgba(22,163,74,.04),rgba(22,163,74,.02));border-color:rgba(22,163,74,.12);">
        <div class="gc-tip-icon" style="background:rgba(22,163,74,.08);">🚀</div>
        <div><h5 style="color:#065f46;">Sirada ne var?</h5><p style="color:#047857;">Danışmanin seninle iletisime gecerek universite basvuru sürecini başlatacak.</p></div>
    </div>
@endif

{{-- ── Contract Update Request ── --}}
@if($allowContractUpdate)
<div class="gc-viewer no-print" style="margin-bottom:20px;">
    <div class="gc-viewer-top"><div class="gc-viewer-title">🔄 Sözleşmeyi Guncelle Talebi</div></div>
    <div style="padding:16px 18px;">
        <div class="gc-alert info" style="margin-bottom:10px;">
            <span class="gc-alert-icon">ℹ</span>
            <div class="gc-alert-body">Imza/onay tamamlanmadan once paket veya ek hizmet degisikligi isteyebilirsin.</div>
        </div>
        <form method="POST" action="{{ route('guest.contract.update-request') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Yeni Paket (opsiyonel)</label>
                    <select name="package_code" style="width:100%;padding:8px;border:1px solid var(--u-line);border-radius:6px;font-size:var(--tx-sm);font-family:inherit;">
                        <option value="">Mevcut paketi koru</option>
                        @foreach(($contractPackages ?? []) as $pkg)
                            <option value="{{ $pkg['code'] }}" @selected(($selectedPackageCode ?? '') === $pkg['code'])>{{ $pkg['title'] }} ({{ $pkg['price'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Ek Hizmetler (opsiyonel)</label>
                    <div class="svc-chip-wrap">
                        @php $activeCodes = collect(old('extra_service_codes', $selectedExtraServiceCodes ?? []))->map(fn($x) => (string)$x)->all(); @endphp
                        @foreach(($contractExtraServices ?? []) as $srv)
                            @php $isActive = in_array((string)$srv['code'], $activeCodes, true); @endphp
                            <label class="svc-chip {{ $isActive ? 'active' : '' }}">
                                <input type="checkbox" name="extra_service_codes[]" value="{{ $srv['code'] }}" @checked($isActive)>{{ $srv['title'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Guncelleme Notu (zorunlu)</label>
                <textarea name="update_note" placeholder="Neyi degistirmek istedigini yaz..." required
                          style="width:100%;padding:8px;border:1px solid var(--u-line);border-radius:6px;min-height:70px;font-size:var(--tx-sm);font-family:inherit;box-sizing:border-box;">{{ old('update_note') }}</textarea>
            </div>
            <button class="btn ok" type="submit">Guncelleme Talebi Gonder</button>
        </form>
    </div>
</div>
@endif

{{-- ── Help Card (always visible) ── --}}
<div class="gc-help">
    <div style="font-size:24px;">💬</div>
    <div><strong>Sorunuz mu var?</strong><p>Sözleşme hakkinda herhangi bir sorunuz varsa danışmaninizla gorusebilirsiniz.</p></div>
    <a href="{{ route('guest.messages') }}" class="gc-help-btn">Danışmana Sor</a>
</div>

{{-- ── HTML Print Layout (antetli kagit) ── --}}
@if(($contractSnapshotText ?? '') !== '')
<div id="contractHtmlPrintLayout" style="display:none;font-family:'Times New Roman',Times,serif;color:#000;">
    {{-- Antet --}}
    @if(($printHeaderHtml ?? '') !== '')
        {!! $printHeaderHtml !!}
    @else
        <div style="text-align:center;padding-bottom:16px;border-bottom:2px solid #333;margin-bottom:24px;">
            <div style="font-size:18pt;font-weight:700;letter-spacing:1px;">{{ config('brand.name', 'MentorDE') }}</div>
            <div style="font-size:9pt;color:#666;margin-top:4px;">{{ config('brand.tagline', 'Yurt Disi Egitim Danışmanligi') }}</div>
        </div>
    @endif

    {{-- Sözleşme metni --}}
    <div id="contractPrintBody" style="white-space:pre-wrap;font-size:11pt;line-height:1.7;">{{ $contractSnapshotText }}</div>

    {{-- Ekler --}}
    @if(($contractAnnexKvkkText ?? '') !== '')
        <div style="margin-top:24px;page-break-before:auto;">
            <h4 style="margin:0 0 8px;font-size:12pt;border-bottom:1px solid #999;padding-bottom:4px;">Ek-1 — KVKK Aydinlatma Metni</h4>
            <div style="white-space:pre-wrap;font-size:10pt;line-height:1.6;">{{ $contractAnnexKvkkText }}</div>
        </div>
    @endif
    @if(($contractAnnexCommitmentText ?? '') !== '')
        <div style="margin-top:24px;">
            <h4 style="margin:0 0 8px;font-size:12pt;border-bottom:1px solid #999;padding-bottom:4px;">Ek-2 — Taahhutname</h4>
            <div style="white-space:pre-wrap;font-size:10pt;line-height:1.6;">{{ $contractAnnexCommitmentText }}</div>
        </div>
    @endif

    {{-- Alt bilgi --}}
    @if(($printFooterHtml ?? '') !== '')
        {!! $printFooterHtml !!}
    @else
        <div style="margin-top:32px;padding-top:12px;border-top:1px solid #ccc;font-size:8pt;color:#999;text-align:center;">
            Bu belge {{ config('brand.name', 'MentorDE') }} sistemi uzerinden oluşturulmustur. · {{ now()->format('d.m.Y') }}
        </div>
    @endif
</div>
@endif

{{-- ── Error Popup ── --}}
<div id="contractErrorPopup" class="gc-popup-overlay" aria-hidden="true">
    <div class="gc-popup-card">
        <h4 class="gc-popup-title">Sözleşme talebi gonderilemedi</h4>
        <div id="contractErrorPopupBody" class="muted" style="white-space:pre-wrap;line-height:1.5;"></div>
        <div style="margin-top:14px;display:flex;justify-content:flex-end;"><button type="button" class="btn" id="contractErrorPopupClose">Tamam</button></div>
    </div>
</div>

<script>
window.__contractData = {
    serverError:      @json($errors->first('contract')),
    formCompleted:    @json((bool)($formCompleted ?? false)),
    formDraftComplete:@json((bool)($formDraftComplete ?? false)),
    docsCompleted:    @json((bool)($docsCompleted ?? false)),
    packageSelected:  @json((bool)($packageSelected ?? false)),
    status:           @json((string)($contractStatus ?? 'not_requested')),
    missingDocs:      @json($missingRequiredDocuments->values()->all())
};

function gcUpdateReadProgress(el) {
    var pct = (el.scrollTop / (el.scrollHeight - el.clientHeight)) * 100;
    var fill = document.getElementById('gcReadFill');
    var hint = document.getElementById('gcScrollHint');
    if (fill) fill.style.width = Math.min(pct, 100) + '%';
    if (pct > 90 && hint) { hint.textContent = '✅ Sözleşmeyi tamamen okudunuz'; hint.style.color = 'var(--u-ok)'; }
}
</script>
<script defer src="{{ Vite::asset('resources/js/guest-contract.js') }}"></script>

@if($status === 'requested' && empty($guest?->contract_digital_signed_at))
<script>
(function () {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const placeholder = document.getElementById('signCanvasPlaceholder');
    let drawing = false, hasMark = false;

    function pos(e) { const r = canvas.getBoundingClientRect(); const src = e.touches ? e.touches[0] : e; return { x: (src.clientX - r.left) * (canvas.width / r.width), y: (src.clientY - r.top) * (canvas.height / r.height) }; }
    function startDraw(e) { e.preventDefault(); drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
    function drawLine(e) { e.preventDefault(); if (!drawing) return; hasMark = true; placeholder.style.display = 'none'; const p = pos(e); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#1e40af'; ctx.lineTo(p.x, p.y); ctx.stroke(); const w = document.getElementById('sigCanvasWrap'); if (w) w.style.borderColor = 'rgba(22,163,74,.5)'; }
    function stopDraw() { drawing = false; }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', drawLine);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw, {passive:false});
    canvas.addEventListener('touchmove', drawLine, {passive:false});
    canvas.addEventListener('touchend', stopDraw);

    document.getElementById('signClearBtn').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasMark = false; placeholder.style.display = '';
        const w = document.getElementById('sigCanvasWrap'); if (w) w.style.borderColor = '';
    });

    document.getElementById('signSubmitBtn').addEventListener('click', function () {
        const fb = document.getElementById('signFeedback');
        if (!hasMark) { showFb('error', 'Lutfen once imzanızi cizin.'); return; }
        if (!document.getElementById('signConsentCheck').checked) { showFb('error', 'Onay kutusunu isaretleyin.'); return; }
        const btn = this;
        btn.disabled = true; btn.textContent = 'Gonderiliyor...';
        const data = canvas.toDataURL('image/png');
        fetch('{{ route("guest.contract.digital-sign") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' },
            body: JSON.stringify({ signature_data: data, consent: true })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showFb('ok', '✓ Dijital imzanız basariyla kaydedildi.'); setTimeout(() => location.reload(), 1500); }
            else { showFb('error', d.message || 'Hata olustu.'); btn.disabled = false; btn.textContent = 'Dijital Imzayi Gonder'; }
        })
        .catch(() => { showFb('error', 'Baglanti hatasi.'); btn.disabled = false; btn.textContent = 'Dijital Imzayi Gonder'; });
    });

    function showFb(type, msg) { const el = document.getElementById('signFeedback'); el.style.display = 'block'; el.style.color = type === 'ok' ? '#166534' : '#991b1b'; el.textContent = msg; }
})();
</script>
@endif
<script>
(function(){
    var _orig=window.__designToggle;
    window.__designToggle=function(){
        if(_orig)_orig.apply(this,arguments);
        setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist',localStorage.getItem('mentorde_design')==='minimalist'); },50);
    };
})();
</script>
@endsection
