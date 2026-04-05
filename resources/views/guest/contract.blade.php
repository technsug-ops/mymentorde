@extends('guest.layouts.app')

@section('title', 'Sözleşme')
@section('page_title', 'Sözleşme')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gc-* Guest Contract scoped ── */

/* Breadcrumb step chips */
.gc-breadcrumb { display:flex; align-items:center; gap:6px; margin-bottom:20px; flex-wrap:wrap; }
.gc-step-chip { display:flex; align-items:center; gap:5px; padding:5px 13px; border-radius:20px; font-size:12px; font-weight:600; color:var(--u-muted,#64748b); background:var(--u-subtle,#f8fafc); border:1px solid var(--u-line,#e2e8f0); white-space:nowrap; }
.gc-step-chip.done { background:rgba(22,163,74,.1); color:#166534; border-color:rgba(22,163,74,.3); }
.gc-step-chip.active { background:rgba(37,99,235,.1); color:var(--u-brand,#2563eb); border-color:rgba(37,99,235,.3); font-weight:700; }
.gc-step-arrow { color:var(--u-muted,#64748b); font-size:14px; }

/* 2-column layout */
.gc-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
.gc-right-sticky { position: sticky; top: 80px; max-height: calc(100vh - 96px); overflow-y: auto; display: flex; flex-direction: column; gap: 16px; scrollbar-width: thin; }
@media(max-width:860px){ .gc-right-sticky { position: static; max-height: none; } }

/* Prereq cards */
.gc-prereq { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.gc-prereq-card {
    border-radius: 12px; border: 2px solid var(--u-line); overflow: hidden;
    display: flex; flex-direction: column;
    transition: box-shadow .15s;
}
.gc-prereq-card:hover { box-shadow: var(--u-shadow); }
.gc-prereq-head {
    background: linear-gradient(135deg, var(--pq-a), var(--pq-b)); color: #fff;
    padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.gc-prereq-title  { font-size: 13px; font-weight: 700; }
.gc-prereq-status { background: rgba(255,255,255,.25); border-radius: 999px; padding: 3px 10px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.gc-prereq-body   { padding: 12px 14px; background: var(--u-card); display: flex; flex-direction: column; gap: 6px; flex: 1; }
.gc-prereq-val    { font-size: 14px; font-weight: 800; color: var(--u-text); }
.gc-prereq-link a {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;
    font-size: 12px; font-weight: 700; color: var(--u-brand); text-decoration: none;
    background: #eef4fb; border: 1.5px solid #c5d9f0; border-radius: 6px; padding: 5px 11px;
    transition: background .15s, border-color .15s;
}
.gc-prereq-link a:hover { background: #ddeeff; border-color: var(--u-brand); }

/* Alerts */
.gc-alert { border-radius: 10px; padding: 12px 16px; border: 1px solid; display: flex; gap: 10px; align-items: flex-start; }
.gc-alert.warn   { border-color: #fecaca; background: #fff5f5; color: #b91c1c; }
.gc-alert.info   { border-color: #c5dafd; background: #f0f7ff; color: #1a3e8a; }
.gc-alert.purple { border-color: #c4b5fd; background: #f5f3ff; color: #4c1d95; }
.gc-alert-icon   { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.gc-alert-body   { flex: 1; }
.gc-alert-body strong { display: block; margin-bottom: 4px; }

/* File upload */
.gc-file-label {
    display: flex; align-items: center; gap: 8px;
    border: 1.5px dashed #c7d9f0; border-radius: 8px; padding: 10px 12px;
    cursor: pointer; font-size: 12px; color: #4d6689; background: #f4f8ff;
    transition: background .15s, border-color .15s;
}
.gc-file-label:hover { background: #eaf3ff; border-color: #93c5fd; }

/* Service chips */
.svc-chip-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.svc-chip { border: 1px solid var(--u-line); border-radius: 999px; padding: 7px 12px; background: var(--u-card); color: var(--u-text); font-size: 12px; font-weight: 700; cursor: pointer; user-select: none; transition: background .15s, border-color .15s; }
.svc-chip input { display: none; }
.svc-chip.active { background: #eaf3ff; border-color: #9ec2f3; color: #124682; }

/* Popup */
.gc-popup-overlay { position: fixed; inset: 0; background: rgba(7,18,35,.45); display: none; align-items: center; justify-content: center; z-index: 9999; }
.gc-popup-card    { width: min(92vw,520px); background: var(--u-card); border: 1px solid var(--u-line); border-radius: 14px; box-shadow: 0 14px 50px rgba(12,29,56,.24); padding: 20px; }
.gc-popup-title   { margin: 0 0 10px; color: #a11b1b; font-size: 18px; font-weight: 800; }

/* Contract viewer */
.gc-viewer { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.08); margin-bottom: 14px; }
.gc-viewer-top { padding: 14px 20px; border-bottom: 1px solid var(--u-line); display: flex; align-items: center; justify-content: space-between; background: var(--u-subtle, #f8fafc); }
.gc-viewer-title { font-size: 15px; font-weight: 700; color: var(--u-text); }
.gc-viewer-meta  { font-size: 12px; color: var(--u-muted); margin-top: 2px; }
.gc-viewer-actions { display: flex; gap: 8px; flex-shrink: 0; }
.gc-viewer-btn { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid var(--u-line); background: var(--u-card); color: var(--u-text); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: all .15s; }
.gc-viewer-btn:hover { border-color: var(--u-brand); color: var(--u-brand); }
.gc-viewer-body { padding: 28px 32px; max-height: 560px; overflow-y: auto; font-size: 13px; line-height: 1.7; white-space: pre-wrap; }
.gc-viewer-body h2 { font-size: 16px; font-weight: 700; text-align: center; margin: 0 0 12px; }
.gc-viewer-body h3 { font-size: 14px; font-weight: 700; margin: 20px 0 8px; color: var(--u-brand); border-bottom: 1px solid var(--u-line); padding-bottom: 4px; }
.gc-scroll-hint { text-align: center; padding: 8px; background: var(--u-subtle, #f8fafc); font-size: 12px; color: var(--u-muted); border-top: 1px solid var(--u-line); transition: color .3s; }

/* Read progress bar */
.gc-read-progress { height: 3px; background: var(--u-line); margin-bottom: 14px; border-radius: 2px; overflow: hidden; }
.gc-read-fill { height: 100%; background: linear-gradient(90deg, var(--u-brand), #7c3aed); width: 0%; transition: width .3s; border-radius: 2px; }

/* Sidebar cards */
.gc-sidebar-card { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 14px; overflow: hidden; margin-bottom: 14px; }
.gc-sidebar-head { padding: 12px 16px; border-bottom: 1px solid var(--u-line); font-size: 13px; font-weight: 700; color: var(--u-text); display: flex; align-items: center; gap: 7px; position: sticky; top: 0; background: var(--u-card); z-index: 1; }
.gc-sidebar-body { padding: 14px 16px; max-height: 300px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--u-line) transparent; }
.gc-sidebar-body::-webkit-scrollbar { width: 4px; }
.gc-sidebar-body::-webkit-scrollbar-track { background: transparent; }
.gc-sidebar-body::-webkit-scrollbar-thumb { background: var(--u-line); border-radius: 4px; }
.gc-info-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--u-line); }
.gc-info-row:last-child { border-bottom: none; }
.gc-info-key { font-size: 12px; color: var(--u-muted); }
.gc-info-val { font-size: 12px; font-weight: 600; color: var(--u-text); }

/* Checklist */
.gc-check-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid var(--u-line); border-radius: 8px; cursor: pointer; transition: all .15s; margin-bottom: 8px; background: var(--u-subtle, #f8fafc); }
.gc-check-item:last-child { margin-bottom: 0; }
.gc-check-item:hover { border-color: var(--u-brand); }
.gc-check-item.checked { border-color: rgba(22,163,74,.3); background: rgba(22,163,74,.04); }
.gc-check-box { width: 18px; height: 18px; border-radius: 4px; border: 2px solid var(--u-line); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; transition: all .15s; margin-top: 1px; }
.gc-check-item.checked .gc-check-box { background: #16a34a; border-color: #16a34a; color: #fff; }
.gc-check-label { font-size: 12px; color: var(--u-text); line-height: 1.4; flex: 1; }

/* Submit button */
.gc-submit-btn { width: 100%; padding: 14px; border-radius: 10px; background: linear-gradient(135deg, #16a34a, #059669); color: #fff; font-size: 15px; font-weight: 700; border: none; cursor: pointer; transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(22,163,74,.3); margin-top: 14px; }
.gc-submit-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22,163,74,.4); }
.gc-submit-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }

/* Quick actions link */
.gc-quick-link { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--u-line); text-decoration: none; color: var(--u-text); font-size: 13px; font-weight: 600; transition: color .15s; }
.gc-quick-link:last-child { border-bottom: none; }
.gc-quick-link:hover { color: var(--u-brand); }
.gc-quick-link-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--u-subtle, #f8fafc); border: 1px solid var(--u-line); display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }

/* Help box */
.gc-help-box { background: rgba(37,99,235,.05); border: 1px solid rgba(37,99,235,.2); border-radius: 14px; padding: 14px 16px; margin-bottom: 14px; overflow: hidden; }
.gc-help-title { font-size: 13px; font-weight: 700; color: var(--u-brand); margin-bottom: 6px; }
.gc-help-body  { font-size: 12px; color: var(--u-muted); line-height: 1.5; margin-bottom: 10px; }

@media(max-width:860px){
    .gc-layout  { grid-template-columns: 1fr; }
    .gc-prereq  { grid-template-columns: 1fr 1fr; }
}
@media(max-width:560px){
    .gc-prereq { grid-template-columns: 1fr; }
    .gc-steps  { flex-wrap: wrap; gap: 8px; }
    .gc-step::after { display: none; }
}
/* ════════════════════════════════════════
   MINIMALİST OVERRIDES
════════════════════════════════════════ */
.jm-minimalist .gc-read-fill { background: var(--u-ok, #16a34a); }
.jm-minimalist .gc-viewer { box-shadow: none; }
.jm-minimalist .gc-submit-btn { box-shadow: none; }
.jm-minimalist .gc-submit-btn:hover:not(:disabled) { transform: none; box-shadow: none; opacity: .88; }

@@media print {
    .shell .side { display: none !important; }
    .shell { display: block !important; }
    .shell > .main { padding: 0 !important; width: 100% !important; }
    .no-print { display: none !important; }
    body:has(#contractHtmlPrintLayout) .card,
    body:has(#contractHtmlPrintLayout) .panel { display: none !important; }
    body:has(#contractHtmlPrintLayout) #contractHtmlPrintLayout { display: block !important; }
    #contractPrintBody { max-height: none !important; overflow: visible !important; border: none !important; padding: 0 !important; }
    details { display: block !important; }
    details > div { display: block !important; }
    details summary { display: none !important; }
    @@page { margin: 20mm; }
    body { font-size: 11pt; }
}
</style>
@endpush

@section('content')
@php
    $status = (string)($contractStatus ?? 'not_requested');
    $formDraftComplete  = (bool)($formDraftComplete  ?? false);
    $formRequiredFilled = (int) ($formRequiredFilled ?? 0);
    $formRequiredTotal  = (int) ($formRequiredTotal  ?? 0);

    $statusLabel = match($status) {
        'pending_manager'  => 'Danışman Hazırlıyor',
        'requested'        => 'İmza Bekleniyor',
        'signed_uploaded'  => 'Firma Onayı Bekleniyor',
        'approved'         => 'Onaylandı',
        'rejected'         => 'Reddedildi',
        'cancelled'        => 'İptal Edildi',
        'reopen_requested' => 'Yeniden Değerlendirme',
        default            => 'Talep Edilmedi',
    };

    $statusPill = match($status) {
        'approved'                                              => 'ok',
        'rejected', 'cancelled'                                 => 'danger',
        'pending_manager', 'requested',
        'signed_uploaded', 'reopen_requested'                   => 'info',
        default                                                 => 'warn',
    };

    $badgeDot  = ['ok'=>'#22c55e','warn'=>'#f59e0b','danger'=>'#ef4444','info'=>'#3b82f6'];
    $badgeText = ['ok'=>'#065f46','warn'=>'#92400e','danger'=>'#991b1b','info'=>'#1e40af'];
    $bdc = $badgeDot[$statusPill]  ?? '#6b7280';
    $btc = $badgeText[$statusPill] ?? '#374151';

    $stepActive = match($status) {
        'pending_manager'          => 0,
        'requested', 'rejected'    => 1,
        'signed_uploaded'          => 2,
        'approved'                 => 3,
        default                    => 0,
    };
    $heroSteps = [
        ['name' => 'Sözleşme Talebi', 'date' => $contractRequestedAt ?? null],
        ['name' => 'İmzalı Yükleme',  'date' => $contractSignedAt    ?? null],
        ['name' => 'Firma Onayı',     'date' => $contractApprovedAt  ?? null],
        ['name' => 'Tamamlandı',      'date' => ($status === 'approved' ? ($contractApprovedAt ?? null) : null)],
    ];

    $prereqs = [
        [
            'label'     => 'Kayıt Formu',
            'done'      => !empty($formCompleted),
            'warn'      => !empty($formDraftComplete) && empty($formCompleted),
            'value'     => !empty($formCompleted)
                            ? 'Tamamlandı'
                            : ($formDraftComplete ? 'Gönderilmedi' : "Eksik ({$formRequiredFilled}/{$formRequiredTotal})"),
            'link'      => route('guest.registration.form'),
            'link_text' => 'Forma git',
            'pq_a'      => '#4b8cf7', 'pq_b' => '#2563eb',
        ],
        [
            'label'     => 'Belgeler',
            'done'      => !empty($docsCompleted),
            'warn'      => false,
            'value'     => !empty($docsCompleted) ? 'Tamamlandı' : 'Eksik',
            'link'      => route('guest.registration.documents'),
            'link_text' => 'Belgelere git',
            'pq_a'      => '#6366f1', 'pq_b' => '#4338ca',
        ],
        [
            'label'     => 'Paket Seçimi',
            'done'      => !empty($packageSelected),
            'warn'      => false,
            'value'     => !empty($packageSelected) ? 'Seçildi' : 'Yapılmadı',
            'link'      => route('guest.services'),
            'link_text' => 'Servislere git',
            'pq_a'      => '#0891b2', 'pq_b' => '#0369a1',
        ],
    ];

    $allowContractUpdate      = in_array($status, ['requested', 'signed_uploaded', 'rejected'], true);
    $missingRequiredDocuments = collect($missingRequiredDocuments ?? []);
@endphp

{{-- ── Breadcrumb Steps ── --}}
@php
    $breadcrumbSteps = [
        ['label' => 'Başvuru',       'done' => true,                    'active' => false],
        ['label' => 'Değerlendirme', 'done' => true,                    'active' => false],
        ['label' => 'Sözleşme',      'done' => $status === 'approved',  'active' => $status !== 'approved'],
        ['label' => 'Belgeler',      'done' => false,                   'active' => $status === 'approved'],
        ['label' => 'Kayıt',         'done' => false,                   'active' => false],
    ];
@endphp
<div class="gc-breadcrumb">
    @foreach($breadcrumbSteps as $i => $bcs)
        @if($i > 0)<span class="gc-step-arrow">›</span>@endif
        <div class="gc-step-chip {{ $bcs['done'] ? 'done' : ($bcs['active'] ? 'active' : '') }}">
            @if($bcs['done'])✓ @elseif($bcs['active'])● @endif{{ $bcs['label'] }}
        </div>
    @endforeach
    <span style="margin-left:auto;"><span class="badge {{ $statusPill }}">{{ $statusLabel }}</span></span>
</div>

{{-- ── 2-Column Layout ── --}}
<div class="gc-layout">

    {{-- ══ LEFT COLUMN ══ --}}
    <div>

        {{-- ── Prerequisites ── --}}
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Ön Koşullar</div>
            </div>
            <div class="card-body">
                <div class="gc-prereq">
                    @foreach($prereqs as $p)
                        <div class="gc-prereq-card" style="--pq-a:{{ $p['pq_a'] }};--pq-b:{{ $p['pq_b'] }};">
                            <div class="gc-prereq-head">
                                <span class="gc-prereq-title">{{ $p['label'] }}</span>
                                <span class="gc-prereq-status">
                                    @if($p['done']) ✓ Tamam @elseif($p['warn']) ⚠ Eksik @else △ Eksik @endif
                                </span>
                            </div>
                            <div class="gc-prereq-body">
                                <div class="gc-prereq-val">{{ $p['value'] }}</div>
                                @if(!$p['done'])
                                    <div class="gc-prereq-link"><a href="{{ $p['link'] }}">{{ $p['link_text'] }} →</a></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($missingRequiredDocuments->isNotEmpty())
                    <div class="gc-alert warn" style="margin-top:12px;">
                        <span class="gc-alert-icon">⚠</span>
                        <div class="gc-alert-body">
                            <strong>Eksik zorunlu belgeler:</strong>
                            {{ $missingRequiredDocuments->pluck('document_code')->implode(', ') }}
                        </div>
                    </div>
                @endif

                @if($status === 'rejected')
                    <div class="gc-alert warn" style="margin-top:12px;">
                        <span class="gc-alert-icon">✕</span>
                        <div class="gc-alert-body">
                            <strong>Reddedildi</strong>
                            {{ $guest?->status_message ?? '-' }} — Düzenleyip tekrar imzalı dosya yükleyebilirsiniz.
                        </div>
                    </div>
                @endif

                @if($status === 'cancelled')
                    <div class="gc-alert warn" style="margin-top:12px;">
                        <span class="gc-alert-icon">⛔</span>
                        <div class="gc-alert-body">
                            <strong>Sözleşme İptal Edildi</strong>
                            Danışmanlık firmanız tarafından sözleşmeniz iptal edilmiştir.
                            Süreci yeniden başlatmak için aşağıdan talebinizi iletebilirsiniz.
                        </div>
                    </div>
                    <form method="POST" action="{{ route('guest.contract.reopen-request') }}"
                          onsubmit="return confirm('Yeniden değerlendirme talebi göndermek istediğinizden emin misiniz?');"
                          style="margin-top:14px;">
                        @csrf
                        <label style="display:block;font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:6px;">
                            Talebinizin nedeni <span style="color:var(--u-danger);">*</span>
                        </label>
                        <textarea name="reopen_reason" required maxlength="1000" rows="3"
                                  placeholder="Neden yeniden değerlendirme istediğinizi kısaca açıklayın…"
                                  style="width:100%;padding:10px;border:1.5px solid var(--u-line);border-radius:8px;font-size:var(--tx-sm);font-family:inherit;box-sizing:border-box;resize:vertical;">{{ old('reopen_reason') }}</textarea>
                        <div style="margin-top:10px;">
                            <button type="submit" class="btn ok">Yeniden Değerlendirme Talep Et</button>
                        </div>
                    </form>
                @endif

                @if($status === 'reopen_requested')
                    <div class="gc-alert purple" style="margin-top:12px;">
                        <span class="gc-alert-icon">⏳</span>
                        <div class="gc-alert-body">
                            <strong>Yeniden Değerlendirme Talebiniz İletildi</strong>
                            Danışman ekibimiz talebinizi inceliyor. Karar sonrası bilgilendirileceksiniz.
                            @if(!empty($guest?->reopen_requested_at))
                                <br><span style="font-size:var(--tx-xs);">Talep tarihi: {{ optional($guest->reopen_requested_at)->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Contract Stepper ── --}}
        @if(!empty($contractStepper) && $contractStepper->count() > 0)
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Sözleşme Süreci</div>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:0;">
                    @foreach($contractStepper as $cs)
                    <div style="display:flex;gap:14px;align-items:flex-start;padding:12px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--u-line);' : '' }}">
                        <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:var(--tx-base);
                            {{ $cs['status'] === 'done' ? 'background:#dcfce7;border:2px solid #22c55e;' : ($cs['status'] === 'active' ? 'background:#dbeafe;border:2px solid #3b82f6;' : 'background:var(--u-bg);border:2px solid var(--u-line);') }}">
                            {{ $cs['status'] === 'done' ? '✓' : $cs['icon'] }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:var(--tx-sm);font-weight:700;color:{{ $cs['status'] === 'done' ? 'var(--u-ok)' : ($cs['status'] === 'active' ? 'var(--u-brand)' : 'var(--u-muted)') }};">
                                {{ $cs['label'] }}
                                @if($cs['status'] === 'active')
                                    <span class="badge info" style="margin-left:6px;font-size:var(--tx-xs);">Şu An</span>
                                @endif
                            </div>
                            @if($cs['description'] && $cs['status'] !== 'pending')
                            <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;line-height:1.4;">{{ $cs['description'] }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ── Step 1: Request Contract ── --}}
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">1. Sözleşme Talep Et</div>
                @if(in_array($status, ['pending_manager','requested','signed_uploaded','approved'], true))
                    <span class="badge ok">Bu adım tamamlandı</span>
                @endif
            </div>
            <div class="card-body">
                <p class="muted" style="margin:0 0 14px;font-size:var(--tx-sm);line-height:1.6;">
                    @if($status === 'pending_manager')
                        Talebiniz alındı. Danışmanınız sözleşmeyi hazırlayıp sisteme yükleyecek.
                    @elseif(in_array($status, ['requested','signed_uploaded','approved'], true))
                        Bu adım tamamlandı. Sözleşme talebiniz alındı.
                    @else
                        Önkoşullar tamamlandıktan sonra aşağıdaki buton ile talep oluşturun.
                    @endif
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <form method="POST" action="{{ route('guest.contract.request') }}" id="contractRequestForm">
                        @csrf
                        <button type="submit" id="contractRequestButton" class="btn ok"
                            @disabled(in_array($status, ['pending_manager','requested','signed_uploaded','approved','cancelled','reopen_requested'], true))>
                            📄 Sözleşme Talep Et
                        </button>
                    </form>
                    @if($status === 'pending_manager')
                        <form method="POST" action="{{ route('guest.contract.withdraw') }}"
                              onsubmit="return confirm('Sözleşme talebinizi geri çekmek istediğinize emin misiniz?');">
                            @csrf
                            <button type="submit" class="btn warn">Talebi Geri Çek</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Contract Viewer (when contract text exists) ── --}}
        @if($contractSnapshotText !== '')
        <div class="gc-viewer no-print" id="contractTextSection">
            <div class="gc-viewer-top">
                <div>
                    <div class="gc-viewer-title">📜 Sözleşme Metni</div>
                    <div class="gc-viewer-meta">
                        Şablon: <strong>{{ $contractTemplateCode !== '' ? $contractTemplateCode : '-' }}</strong>
                        &nbsp;·&nbsp; Oluşturulma: <strong>{{ $contractGeneratedAt ? \Carbon\Carbon::parse($contractGeneratedAt)->format('d.m.Y H:i') : '-' }}</strong>
                    </div>
                </div>
                <div class="gc-viewer-actions">
                    <a href="#" class="gc-viewer-btn" onclick="event.preventDefault();window.print()">⬇ İndir (PDF)</a>
                    <button type="button" class="gc-viewer-btn" onclick="window.print()">🖨 Yazdır</button>
                </div>
            </div>

            {{-- Read progress bar --}}
            <div class="gc-read-progress"><div class="gc-read-fill" id="gcReadFill"></div></div>

            <div class="gc-viewer-body" id="gcContractBody" onscroll="gcUpdateReadProgress(this)">{{ $contractSnapshotText }}</div>

            <div class="gc-scroll-hint" id="gcScrollHint">
                📖 Lütfen sözleşmeyi tamamen okuyun (aşağı kaydırın)
            </div>

            @if($contractAnnexKvkkText !== '')
                <div style="padding:0 20px 14px;">
                    <details style="margin-top:12px;" id="annexKvkk">
                        <summary style="cursor:pointer;font-weight:700;padding:6px 0;"><strong>Ek-1 — KVKK Aydınlatma Metni</strong></summary>
                        <div style="margin-top:8px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:10px;padding:16px;background:#fff;font-size:var(--tx-sm);line-height:1.7;">{{ $contractAnnexKvkkText }}</div>
                    </details>
                </div>
            @endif
            @if($contractAnnexCommitmentText !== '')
                <div style="padding:0 20px 14px;">
                    <details style="margin-top:2px;" id="annexCommitment">
                        <summary style="cursor:pointer;font-weight:700;padding:6px 0;"><strong>Ek-2 — Taahhütname</strong></summary>
                        <div style="margin-top:8px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:10px;padding:16px;background:#fff;font-size:var(--tx-sm);line-height:1.7;">{{ $contractAnnexCommitmentText }}</div>
                    </details>
                </div>
            @endif
            @if(($contractAnnexPaymentText ?? '') !== '')
                <div style="padding:0 20px 14px;">
                    <details style="margin-top:2px;" id="annexPayment">
                        <summary style="cursor:pointer;font-weight:700;padding:6px 0;"><strong>Ek-3 — Ödeme Planı</strong></summary>
                        <div style="margin-top:8px;white-space:pre-wrap;border:1px solid var(--u-line);border-radius:10px;padding:16px;background:#fff;font-size:var(--tx-sm);line-height:1.7;">{{ $contractAnnexPaymentText }}</div>
                    </details>
                </div>
            @endif
        </div>
        @endif

        {{-- ── Step 2: Upload Signed Contract ── --}}
        @if(in_array($status, ['requested', 'rejected'], true) && $contractSnapshotText !== '')
        <div class="card no-print" style="margin-bottom:14px;border-color:{{ $status === 'signed_uploaded' ? '#bbf7d0' : '#c7d9f0' }};border-width:2px;">
            <div class="card-head">
                <div class="card-title">2. İmzalı Sözleşmeyi Yükle &amp; Gönder</div>
                @if($status === 'signed_uploaded')
                    <span class="badge ok">Dosya Yüklendi — Onay Bekleniyor</span>
                @elseif($status === 'rejected')
                    <span class="badge danger">Reddedildi — Tekrar Yükle</span>
                @else
                    <span class="badge info">İmza Bekleniyor</span>
                @endif
            </div>
            <div class="card-body">
                @if($contractSignedFilePath !== '')
                    <div class="gc-alert info" style="margin-bottom:12px;">
                        <span class="gc-alert-icon">📎</span>
                        <div class="gc-alert-body">
                            Mevcut yüklü dosya: <strong>{{ basename($contractSignedFilePath) }}</strong>
                            &nbsp;·&nbsp; Yeni dosya seçerseniz eski dosyanın üzerine yazılır.
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('guest.contract.upload-signed') }}" enctype="multipart/form-data" id="signedUploadForm">
                    @csrf
                    <div style="margin-bottom:12px;">
                        <label class="gc-file-label" for="signedContractFile">
                            <span style="font-size:var(--tx-lg);">📎</span>
                            <span id="signedFileName">Dosya seçmek için tıklayın (PDF, JPG, PNG — maks. 10 MB)</span>
                        </label>
                        <input type="file" id="signedContractFile" name="signed_contract" accept=".pdf,.jpg,.jpeg,.png" required
                               style="display:none;"
                               onchange="var s=this.files[0];document.getElementById('signedFileName').textContent=s?s.name:'Dosya seçilmedi';document.getElementById('signedUploadBtn').textContent=s?'Gönder: '+s.name:'İmzalı Dosyayı Gönder';">
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:var(--tx-xs);color:var(--u-text);cursor:pointer;">
                            <input type="checkbox" name="consent_contract" required style="margin-top:2px;flex-shrink:0;width:15px;height:15px;accent-color:var(--u-brand);">
                            <span>Sözleşme metnini okudum ve tüm şartları kabul ediyorum.</span>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:var(--tx-xs);color:var(--u-text);cursor:pointer;">
                            <input type="checkbox" name="consent_kvkk" required style="margin-top:2px;flex-shrink:0;width:15px;height:15px;accent-color:var(--u-brand);">
                            <span>KVKK kapsamında kişisel verilerimin danışmanlık sürecinde işlenmesine onay veriyorum.</span>
                        </label>
                    </div>
                    <button type="submit" id="signedUploadBtn" class="btn ok" style="min-width:200px;">İmzalı Dosyayı Gönder</button>
                    <div class="muted" style="margin-top:8px;font-size:var(--tx-xs);">
                        Yukarıdaki sözleşme metnini okuyun → "Yazdır / PDF İndir" ile indirin → imzalayın → dosyayı seçip "Gönder" butonuna tıklayın.
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Digital Signature ── --}}
        @if($status === 'requested' && $contractSnapshotText !== '' && empty($guest?->contract_digital_signed_at))
        <div class="gc-viewer no-print" id="digitalSignSection" style="margin-bottom:14px;border:2px dashed var(--u-line,#e2e8f0);">
            <div class="gc-viewer-top">
                <div class="gc-viewer-title">✍ E-İmza <span style="font-size:var(--tx-xs);font-weight:400;color:var(--u-muted);">(dosya yüklemeye alternatif)</span></div>
                <button type="button" class="gc-viewer-btn" id="signClearBtn">Temizle</button>
            </div>
            <div style="padding:16px 20px;">
                <p class="muted" style="margin:0 0 14px;font-size:var(--tx-sm);">
                    Sözleşmeyi aşağıdaki alana parmağınız veya farenizle imzalayabilirsiniz.
                    Dijital imza, fiziksel imzalı dosya yüklemeyle eşdeğer kabul edilir.
                </p>
                <div style="border:2px dashed var(--u-line,#e2e8f0);border-radius:10px;overflow:hidden;background:var(--u-subtle,#f8fafc);position:relative;transition:border-color .2s;" id="sigCanvasWrap">
                    <canvas id="signatureCanvas" width="680" height="160"
                            style="width:100%;height:160px;cursor:crosshair;display:block;touch-action:none;"></canvas>
                    <div id="signCanvasPlaceholder"
                         style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:var(--tx-sm);color:#9ca3af;pointer-events:none;">
                        İmzanızı buraya çizin
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;align-items:flex-start;">
                    <label style="display:flex;align-items:flex-start;gap:6px;font-size:var(--tx-xs);color:var(--u-text);cursor:pointer;flex:1;min-width:200px;">
                        <input type="checkbox" id="signConsentCheck" style="margin-top:2px;flex-shrink:0;width:14px;height:14px;accent-color:var(--u-brand,#2563eb);">
                        <span>Sözleşmeyi okudum, tüm şartları kabul ediyorum ve bu dijital imzanın yasal geçerliliği olduğunu onaylıyorum.</span>
                    </label>
                    <button type="button" id="signSubmitBtn" class="gc-submit-btn" style="margin-top:0;width:auto;min-width:160px;padding:10px 16px;font-size:var(--tx-sm);">✅ Dijital İmzayı Gönder</button>
                </div>
                <div id="signFeedback" style="margin-top:8px;font-size:var(--tx-xs);display:none;"></div>
            </div>
        </div>
        @elseif(!empty($guest?->contract_digital_signed_at))
        <div class="gc-viewer" style="margin-bottom:14px;border-color:rgba(22,163,74,.3);">
            <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;background:rgba(22,163,74,.05);">
                <span style="font-size:var(--tx-xl);">✅</span>
                <div>
                    <div style="font-size:var(--tx-sm);font-weight:700;color:#166534;">Dijital İmza Alındı</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">
                        {{ optional($guest->contract_digital_signed_at)->format('d.m.Y H:i') }} · e-imza hukuken geçerlidir
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Contract Update Request ── --}}
        @if($allowContractUpdate)
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Sözleşmeyi Güncelle Talebi</div>
            </div>
            <div class="card-body">
                <div class="gc-alert info" style="margin-bottom:12px;">
                    <span class="gc-alert-icon">ℹ</span>
                    <div class="gc-alert-body">
                        İmza/onay tamamlanmadan önce paket veya ek hizmet değişikliği isteyebilirsin.
                        Bu istekten sonra sözleşme yeniden "requested" aşamasına çekilir ve tekrar imza gerekir.
                    </div>
                </div>
                <form method="POST" action="{{ route('guest.contract.update-request') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div>
                            <label class="muted" style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Yeni Paket (opsiyonel)</label>
                            <select name="package_code" style="width:100%;padding:10px;border:1px solid var(--u-line);border-radius:8px;font-size:var(--tx-sm);font-family:inherit;">
                                <option value="">Mevcut paketi koru</option>
                                @foreach(($contractPackages ?? []) as $pkg)
                                    <option value="{{ $pkg['code'] }}" @selected(($selectedPackageCode ?? '') === $pkg['code'])>
                                        {{ $pkg['title'] }} ({{ $pkg['price'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="muted" style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Ek Hizmetler (opsiyonel)</label>
                            <div class="svc-chip-wrap">
                                @php
                                    $activeCodes = collect(old('extra_service_codes', $selectedExtraServiceCodes ?? []))->map(fn($x) => (string)$x)->all();
                                @endphp
                                @foreach(($contractExtraServices ?? []) as $srv)
                                    @php $isActive = in_array((string)$srv['code'], $activeCodes, true); @endphp
                                    <label class="svc-chip {{ $isActive ? 'active' : '' }}">
                                        <input type="checkbox" name="extra_service_codes[]" value="{{ $srv['code'] }}" @checked($isActive)>
                                        {{ $srv['title'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="muted" style="font-size:var(--tx-xs);display:block;margin-bottom:4px;">Güncelleme Notu (zorunlu)</label>
                        <textarea name="update_note" placeholder="Neyi değiştirmek istediğini yaz…" required
                                  style="width:100%;padding:10px;border:1px solid var(--u-line);border-radius:8px;min-height:90px;font-size:var(--tx-sm);font-family:inherit;box-sizing:border-box;">{{ old('update_note') }}</textarea>
                    </div>
                    <button class="btn ok" type="submit">Sözleşmeyi Güncelle Talebi Gönder</button>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Guide ── --}}
        <div class="card" style="margin-bottom:14px;">
            <div class="card-head">
                <div class="card-title">Kullanım Kılavuzu</div>
            </div>
            <div class="card-body">
                <ol class="muted" style="margin:0;padding-left:18px;line-height:2;">
                    <li>Önce "Sözleşme Talep Et" ile süreci başlat.</li>
                    <li>İmzaladığın dosyayı PDF olarak yükle.</li>
                    <li>Durum "Onaylandı" olduğunda dönüşüm koşulunun son adımı tamamlanır.</li>
                </ol>
            </div>
        </div>

    </div>{{-- /LEFT --}}

    {{-- ══ RIGHT COLUMN ══ --}}
    <div class="gc-right-sticky">

        {{-- ── Summary / Status Card ── --}}
        <div class="gc-sidebar-card">
            <div class="gc-sidebar-head">📋 Sözleşme Özeti</div>
            <div class="gc-sidebar-body">
                <div class="gc-info-row">
                    <span class="gc-info-key">Durum</span>
                    <span class="badge {{ $statusPill }}">{{ $statusLabel }}</span>
                </div>
                @if(!empty($guest?->service_package))
                <div class="gc-info-row">
                    <span class="gc-info-key">Hizmet Paketi</span>
                    <span class="gc-info-val">{{ $guest->service_package }}</span>
                </div>
                @endif
                @if(!empty($guest?->target_university))
                <div class="gc-info-row">
                    <span class="gc-info-key">Hedef Üniversite</span>
                    <span class="gc-info-val">{{ $guest->target_university }}</span>
                </div>
                @endif
                @if(!empty($guest?->target_program))
                <div class="gc-info-row">
                    <span class="gc-info-key">Program</span>
                    <span class="gc-info-val">{{ $guest->target_program }}</span>
                </div>
                @endif
                @if(!empty($guest?->target_semester))
                <div class="gc-info-row">
                    <span class="gc-info-key">Dönem</span>
                    <span class="gc-info-val">{{ $guest->target_semester }}</span>
                </div>
                @endif
                @if(!empty($contractRequestedAt))
                <div class="gc-info-row">
                    <span class="gc-info-key">Talep Tarihi</span>
                    <span class="gc-info-val">{{ $contractRequestedAt }}</span>
                </div>
                @endif
                @if(!empty($contractApprovedAt))
                <div class="gc-info-row">
                    <span class="gc-info-key">Onay Tarihi</span>
                    <span class="gc-info-val">{{ $contractApprovedAt }}</span>
                </div>
                @endif
                <div class="gc-info-row">
                    <span class="gc-info-key">Kayıt Formu</span>
                    <span class="gc-info-val" style="{{ !empty($formCompleted) ? 'color:#166534;' : 'color:#b91c1c;' }}">{{ !empty($formCompleted) ? '✓ Tamam' : '✕ Eksik' }}</span>
                </div>
                <div class="gc-info-row">
                    <span class="gc-info-key">Belgeler</span>
                    <span class="gc-info-val" style="{{ !empty($docsCompleted) ? 'color:#166534;' : 'color:#b91c1c;' }}">{{ !empty($docsCompleted) ? '✓ Tamam' : '✕ Eksik' }}</span>
                </div>
                <div class="gc-info-row">
                    <span class="gc-info-key">Paket</span>
                    <span class="gc-info-val" style="{{ !empty($packageSelected) ? 'color:#166534;' : '' }}">{{ !empty($packageSelected) ? '✓ Seçildi' : '— Yapılmadı' }}</span>
                </div>
            </div>
        </div>

        {{-- ── Checklist Card (show when contract is in signing stage) ── --}}
        @if(in_array($status, ['requested', 'signed_uploaded'], true) && $contractSnapshotText !== '')
        <div class="gc-sidebar-card">
            <div class="gc-sidebar-head">✅ Onay Listesi</div>
            <div class="gc-sidebar-body">
                <div class="gc-check-item checked" id="gcCheck1" onclick="gcToggleCheck(this, 'gcCheck1')">
                    <div class="gc-check-box">✓</div>
                    <div class="gc-check-label">Sözleşmeyi okudum ve anladım</div>
                </div>
                <div class="gc-check-item checked" id="gcCheck2" onclick="gcToggleCheck(this, 'gcCheck2')">
                    <div class="gc-check-box">✓</div>
                    <div class="gc-check-label">Ödeme koşullarını ve iptal politikasını kabul ediyorum</div>
                </div>
                <div class="gc-check-item checked" id="gcCheck3" onclick="gcToggleCheck(this, 'gcCheck3')">
                    <div class="gc-check-box">✓</div>
                    <div class="gc-check-label">KVKK/GDPR kapsamında kişisel verilerimin işlenmesine onay veriyorum</div>
                </div>
                <div class="gc-check-item checked" id="gcCheck4" onclick="gcToggleCheck(this, 'gcCheck4')">
                    <div class="gc-check-box">✓</div>
                    <div class="gc-check-label">18 yaşından büyüğüm veya yasal velim adına imza atıyorum</div>
                </div>

                <button type="button" class="gc-submit-btn" id="gcChecklistSubmitBtn"
                        onclick="document.getElementById('signedUploadForm')?.scrollIntoView({behavior:'smooth',block:'center'}) || document.getElementById('digitalSignSection')?.scrollIntoView({behavior:'smooth',block:'center'})">
                    ✅ Sözleşmeyi İmzala ve Onayla
                </button>
                <div style="margin-top:10px;text-align:center;font-size:var(--tx-xs);color:var(--u-muted);">
                    🔒 256-bit SSL ile güvenli · e-imza hukuken geçerlidir
                </div>
            </div>
        </div>
        @endif

        {{-- ── Quick Actions Card ── --}}
        <div class="gc-sidebar-card">
            <div class="gc-sidebar-head">⚡ Hızlı Erişim</div>
            <div class="gc-sidebar-body">
                <a href="{{ route('guest.registration.form') }}" class="gc-quick-link">
                    <div class="gc-quick-link-icon">📝</div>
                    Kayıt Formu
                </a>
                <a href="{{ route('guest.registration.documents') }}" class="gc-quick-link">
                    <div class="gc-quick-link-icon">📁</div>
                    Belgelerim
                </a>
                <a href="{{ route('guest.services') }}" class="gc-quick-link">
                    <div class="gc-quick-link-icon">📦</div>
                    Paket &amp; Hizmetler
                </a>
                <a href="{{ route('guest.messages') }}" class="gc-quick-link">
                    <div class="gc-quick-link-icon">💬</div>
                    Mesajlar
                </a>
                <a href="{{ route('guest.tickets') }}" class="gc-quick-link">
                    <div class="gc-quick-link-icon">🎫</div>
                    Destek Talepleri
                </a>
            </div>
        </div>

        {{-- ── Help Box ── --}}
        <div class="gc-help-box">
            <div class="gc-help-title">❓ Sorunuz mu var?</div>
            <div class="gc-help-body">Sözleşme hakkında herhangi bir sorunuz varsa danışmanınızla görüşebilir veya destek talep edebilirsiniz.</div>
            <a href="{{ route('guest.messages') }}" class="gc-viewer-btn">💬 Danışmana Sor</a>
        </div>

    </div>{{-- /RIGHT --}}

</div>{{-- /gc-layout --}}

{{-- ── HTML Print Layout ── --}}
@if(($printHeaderHtml ?? '') !== '' || ($printFooterHtml ?? '') !== '')
<div id="contractHtmlPrintLayout" style="display:none;font-family:inherit;">
    @if(($printHeaderHtml ?? '') !== '')
        {!! $printHeaderHtml !!}
    @endif
    @if($contractSnapshotText !== '')
        <div id="contractPrintBody" style="white-space:pre-wrap;margin:16px 0;font-size:11pt;line-height:1.7;">{{ $contractSnapshotText }}</div>
        @if($contractAnnexKvkkText !== '')
            <div style="margin-top:24px;page-break-before:auto;">
                <h4 style="margin:0 0 8px;font-size:12pt;">Ek-1 — KVKK Aydınlatma Metni</h4>
                <div style="white-space:pre-wrap;font-size:10pt;line-height:1.6;">{{ $contractAnnexKvkkText }}</div>
            </div>
        @endif
        @if($contractAnnexCommitmentText !== '')
            <div style="margin-top:24px;">
                <h4 style="margin:0 0 8px;font-size:12pt;">Ek-2 — Taahhütname</h4>
                <div style="white-space:pre-wrap;font-size:10pt;line-height:1.6;">{{ $contractAnnexCommitmentText }}</div>
            </div>
        @endif
    @endif
    @if(($printFooterHtml ?? '') !== '')
        {!! $printFooterHtml !!}
    @endif
</div>
@endif

{{-- ── Error Popup ── --}}
<div id="contractErrorPopup" class="gc-popup-overlay" aria-hidden="true">
    <div class="gc-popup-card">
        <h4 class="gc-popup-title">Sözleşme talebi gönderilemedi</h4>
        <div id="contractErrorPopupBody" class="muted" style="white-space:pre-wrap;line-height:1.5;"></div>
        <div style="margin-top:14px;display:flex;justify-content:flex-end;">
            <button type="button" class="btn" id="contractErrorPopupClose">Tamam</button>
        </div>
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

// Checklist logic
function gcToggleCheck(el, id) {
    el.classList.toggle('checked');
    var box = el.querySelector('.gc-check-box');
    if (el.classList.contains('checked')) {
        box.textContent = '✓';
    } else {
        box.textContent = '';
    }
    gcUpdateSubmitBtn();
}
function gcUpdateSubmitBtn() {
    var checks = ['gcCheck1','gcCheck2','gcCheck3','gcCheck4'];
    var btn = document.getElementById('gcChecklistSubmitBtn');
    if (!btn) return;
    var allChecked = checks.every(function(id) {
        var el = document.getElementById(id);
        return el && el.classList.contains('checked');
    });
    btn.disabled = !allChecked;
}

// Read progress
function gcUpdateReadProgress(el) {
    var pct = (el.scrollTop / (el.scrollHeight - el.clientHeight)) * 100;
    var fill = document.getElementById('gcReadFill');
    var hint = document.getElementById('gcScrollHint');
    if (fill) fill.style.width = Math.min(pct, 100) + '%';
    if (pct > 90 && hint) {
        hint.textContent = '✅ Sözleşmeyi tamamen okudunuz';
        hint.style.color = 'var(--u-ok)';
    }
}
</script>
<script defer src="{{ Vite::asset('resources/js/guest-contract.js') }}"></script>

@if($status === 'requested' && empty($guest?->contract_digital_signed_at))
<script>
(function () {
    const canvas      = document.getElementById('signatureCanvas');
    if (!canvas) return;
    const ctx         = canvas.getContext('2d');
    const placeholder = document.getElementById('signCanvasPlaceholder');
    let drawing = false, hasMark = false;

    function pos(e) {
        const r = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: (src.clientX - r.left) * (canvas.width / r.width),
                 y: (src.clientY - r.top)  * (canvas.height / r.height) };
    }
    function startDraw(e) { e.preventDefault(); drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
    function drawLine(e)  { e.preventDefault(); if (!drawing) return; hasMark = true; placeholder.style.display = 'none'; const p = pos(e); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#1e40af'; ctx.lineTo(p.x, p.y); ctx.stroke(); const w = document.getElementById('sigCanvasWrap'); if (w) w.style.borderColor = 'rgba(22,163,74,.5)'; }
    function stopDraw()   { drawing = false; }

    canvas.addEventListener('mousedown',  startDraw);
    canvas.addEventListener('mousemove',  drawLine);
    canvas.addEventListener('mouseup',    stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw, {passive:false});
    canvas.addEventListener('touchmove',  drawLine,  {passive:false});
    canvas.addEventListener('touchend',   stopDraw);

    document.getElementById('signClearBtn').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasMark = false; placeholder.style.display = '';
        const w = document.getElementById('sigCanvasWrap'); if (w) w.style.borderColor = '';
    });

    document.getElementById('signSubmitBtn').addEventListener('click', function () {
        const fb = document.getElementById('signFeedback');
        if (!hasMark) { showFb('error', 'Lütfen önce imzanızı çizin.'); return; }
        if (!document.getElementById('signConsentCheck').checked) { showFb('error', 'Onay kutusunu işaretleyin.'); return; }
        const btn = this;
        btn.disabled = true; btn.textContent = 'Gönderiliyor…';
        const data = canvas.toDataURL('image/png');
        fetch('{{ route("guest.contract.digital-sign") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' },
            body: JSON.stringify({ signature_data: data, consent: true })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showFb('ok', '✓ Dijital imzanız başarıyla kaydedildi. Sayfa yenileniyor…'); setTimeout(() => location.reload(), 1500); }
            else { showFb('error', d.message || 'Hata oluştu.'); btn.disabled = false; btn.textContent = 'Dijital İmzayı Gönder'; }
        })
        .catch(() => { showFb('error', 'Bağlantı hatası. Lütfen tekrar deneyin.'); btn.disabled = false; btn.textContent = 'Dijital İmzayı Gönder'; });
    });

    function showFb(type, msg) {
        const el = document.getElementById('signFeedback');
        el.style.display = 'block';
        el.style.color   = type === 'ok' ? '#166534' : '#991b1b';
        el.textContent   = msg;
    }
})();
</script>
@endif
<script>
(function(){
    var _orig=window.__designToggle;
    window.__designToggle=function(){
        if(_orig)_orig.apply(this,arguments);
        setTimeout(function(){
            var isMin=localStorage.getItem('mentorde_design')==='minimalist';
            document.documentElement.classList.toggle('jm-minimalist',isMin);
        },50);
    };
})();
</script>
@endsection
