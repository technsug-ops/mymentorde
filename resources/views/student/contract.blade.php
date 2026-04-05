@extends('student.layouts.app')

@section('title', 'Sözleşme')
@section('page_title', 'Sözleşme')

@push('head')
<style>
/* ── ct-* Contract scoped ── */

/* Status banner */
.ct-status-banner {
    border-radius: 16px; overflow: hidden;
    margin-bottom: 12px; position: relative;
}
.ct-status-inner {
    display: flex; align-items: center; gap: 18px;
    padding: 20px 22px;
}
.ct-status-icon-wrap {
    width: 54px; height: 54px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
}
.ct-status-text-block { flex: 1; min-width: 0; }
.ct-status-label {
    font-size: 19px; font-weight: 800; line-height: 1.15; margin-bottom: 4px;
}
.ct-status-desc { font-size: 13px; line-height: 1.5; opacity: .85; }

/* Step tracker (standalone, below banner) */
.ct-step-track {
    display: flex; align-items: center;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 12px 18px; margin-bottom: 16px; gap: 0;
}
.ct-stp { display: flex; align-items: center; flex: 1; }
.ct-stp-circle {
    width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    border: 2px solid var(--u-line); color: var(--u-muted); background: var(--u-bg);
}
.ct-stp.s-done .ct-stp-circle  { background: #dcfce7; border-color: #86efac; color: #15803d; }
.ct-stp.s-active .ct-stp-circle { background: #7c3aed; border-color: #7c3aed; color: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,.15); }
.ct-stp-line { flex: 1; height: 2px; background: var(--u-line); margin: 0 6px; }
.ct-stp.s-done .ct-stp-line { background: #86efac; }
.ct-stp-wrap { display: flex; align-items: center; flex: 1; flex-direction: column; gap: 4px; }
.ct-stp-row  { display: flex; align-items: center; flex: 1; width: 100%; }
.ct-stp-name { font-size: 10px; font-weight: 600; color: var(--u-muted); text-align: center; }
.ct-stp.s-done .ct-stp-name  { color: #15803d; }
.ct-stp.s-active .ct-stp-name { color: #7c3aed; font-weight: 700; }
.ct-stp-date { font-size: 9px; color: var(--u-muted); text-align: center; }

/* Prereq row */
.ct-prereq-row {
    display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 20px;
}
@media(max-width:640px){ .ct-prereq-row { grid-template-columns: 1fr; } }
.ct-prereq-card {
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid var(--u-line); background: var(--u-card);
    text-decoration: none; transition: border-color .15s;
}
.ct-prereq-card.done  { border-color: #86efac; background: #f0fdf4; }
.ct-prereq-card.miss  { border-color: #fca5a5; background: #fff5f5; }
.ct-prereq-card:hover { border-color: var(--u-brand); }
.ct-prereq-card.done:hover  { border-color: #4ade80; }
.ct-prereq-card.miss:hover  { border-color: #f87171; }
.ct-prereq-icon-b {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
    background: var(--u-bg);
}
.ct-prereq-card.done  .ct-prereq-icon-b { background: #dcfce7; }
.ct-prereq-card.miss  .ct-prereq-icon-b { background: #fee2e2; }
.ct-prereq-title { font-size: 12px; font-weight: 700; color: var(--u-text); }
.ct-prereq-sub   { font-size: 11px; color: var(--u-muted); margin-top: 1px; }
.ct-prereq-card.miss .ct-prereq-sub { color: #ef4444; }
.ct-prereq-card.done .ct-prereq-sub { color: #16a34a; }

/* Action card */
.ct-action-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 16px; overflow: hidden; margin-bottom: 16px;
}
.ct-action-head {
    padding: 14px 20px; border-bottom: 1px solid var(--u-line);
    font-size: 13px; font-weight: 700; color: var(--u-text);
    display: flex; align-items: center; gap: 8px; justify-content: space-between;
}
.ct-action-body { padding: 18px 20px; }

/* Primary action block */
.ct-primary-action {
    border-radius: 14px; padding: 20px;
    display: flex; align-items: center; gap: 16px; margin-bottom: 16px;
}
.ct-primary-action.pa-brand   { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.ct-primary-action.pa-success { background: linear-gradient(135deg, #16a34a, #15803d); }
.ct-primary-action.pa-warn    { background: linear-gradient(135deg, #d97706, #b45309); }
.ct-primary-action.pa-muted   { background: var(--u-bg); border: 1px solid var(--u-line); }
.ct-pa-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    background: rgba(255,255,255,.15); display: flex; align-items: center;
    justify-content: center; font-size: 24px;
}
.ct-primary-action.pa-muted .ct-pa-icon { background: var(--u-card); border: 1px solid var(--u-line); }
.ct-pa-body { flex: 1; }
.ct-pa-title { font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 4px; }
.ct-pa-desc  { font-size: 12px; color: rgba(255,255,255,.75); line-height: 1.5; }
.ct-primary-action.pa-muted .ct-pa-title { color: var(--u-text); }
.ct-primary-action.pa-muted .ct-pa-desc  { color: var(--u-muted); }
.ct-pa-btn {
    flex-shrink: 0; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700;
    background: rgba(255,255,255,.95); color: var(--u-brand); border: none; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
    transition: background .15s;
}
.ct-pa-btn:hover { background: #fff; color: var(--u-brand); }
.ct-primary-action.pa-success .ct-pa-btn { color: #15803d; }
.ct-primary-action.pa-warn    .ct-pa-btn { color: #b45309; }
.ct-primary-action.pa-muted .ct-pa-btn {
    background: var(--u-brand); color: #fff;
}

/* Upload zone */
.ct-upload-zone {
    border: 2px dashed var(--u-line); border-radius: 12px;
    padding: 24px; text-align: center; background: var(--u-bg);
    transition: border-color .15s; margin-bottom: 14px; cursor: pointer;
}
.ct-upload-zone:hover { border-color: var(--u-brand); }
.ct-upload-zone input[type=file] { display: none; }
.ct-upload-icon { font-size: 32px; margin-bottom: 8px; }
.ct-upload-label { font-size: 14px; font-weight: 700; color: var(--u-text); margin-bottom: 4px; }
.ct-upload-sub   { font-size: 12px; color: var(--u-muted); }
.ct-upload-chosen { font-size: 12px; font-weight: 600; color: var(--u-brand); margin-top: 8px; }

/* 2-col layout */
.ct-layout { display: grid; grid-template-columns: 1fr 340px; gap: 16px; align-items: start; }
@media(max-width:900px){ .ct-layout { grid-template-columns: 1fr; } }

/* Contract text viewer */
.ct-text-viewer {
    white-space: pre-wrap; border: 1px solid var(--u-line); border-radius: 10px;
    padding: 14px; background: var(--u-bg); max-height: 280px;
    overflow: auto; font-size: 12px; line-height: 1.7; color: var(--u-text);
}

/* Form fields */
.ct-field {
    width: 100%; box-sizing: border-box;
    padding: 9px 12px; border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px; font-family: inherit;
    transition: border-color .15s; margin-bottom: 10px;
}
.ct-field:focus { outline: none; border-color: var(--u-brand); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ct-field:disabled { opacity: .45; cursor: not-allowed; }
.ct-label { font-size: 11px; font-weight: 700; color: var(--u-muted); margin-bottom: 4px; display: block; text-transform: uppercase; letter-spacing: .4px; }
.ct-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

/* Side quick links */
.ct-quick-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 10px; background: var(--u-bg);
    border: 1px solid var(--u-line); text-decoration: none; color: var(--u-text);
    margin-bottom: 8px; transition: border-color .15s; font-size: 13px; font-weight: 600;
}
.ct-quick-link:last-child { margin-bottom: 0; }
.ct-quick-link:hover { border-color: var(--u-brand); color: var(--u-brand); }
.ct-quick-link-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: var(--u-card); border: 1px solid var(--u-line);
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}

/* Info notice */
.ct-notice {
    border-radius: 10px; padding: 12px 14px; border: 1px solid;
    display: flex; gap: 10px; align-items: flex-start; font-size: 13px;
}
.ct-notice.info { border-color: var(--u-line); background: var(--u-bg); color: var(--u-muted); }
.ct-notice.warn { border-color: #fca5a5; background: #fff5f5; color: #b91c1c; }
.ct-notice.ok   { border-color: #86efac; background: #f0fdf4; color: #15803d; }
.ct-notice-icon { font-size: 15px; flex-shrink: 0; }

/* Chip toggle */
.ct-chip-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.ct-chip {
    border: 1.5px solid var(--u-line); border-radius: 999px;
    padding: 5px 12px; font-size: 12px; font-weight: 600;
    color: var(--u-text); background: var(--u-card); cursor: pointer; transition: all .15s;
}
.ct-chip input { display: none; }
.ct-chip.active { background: var(--u-bg); border-color: var(--u-brand); color: var(--u-brand); }

/* Popup */
.popup-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.45);
    display: none; align-items: center; justify-content: center; z-index: 9999;
}
.popup-card {
    width: min(92vw,500px); background: var(--u-card); border-radius: 14px;
    box-shadow: 0 14px 50px rgba(0,0,0,.2); padding: 24px; border: 1px solid var(--u-line);
}
.popup-title   { margin: 0 0 8px; color: #b91c1c; font-size: 17px; font-weight: 800; }
.popup-body    { color: var(--u-text); white-space: pre-wrap; line-height: 1.5; font-size: 13px; }
.popup-actions { margin-top: 14px; display: flex; justify-content: flex-end; }
.ct-btn-ghost {
    display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px;
    background: var(--u-card); color: var(--u-text); border: 1px solid var(--u-line);
    border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none;
    cursor: pointer; transition: border-color .15s;
}
.ct-btn-ghost:hover { border-color: var(--u-brand); color: var(--u-brand); }
</style>
@endpush

@section('content')
@php
    $g = $guestApplication;
    $contractUi = (array)($contractUi ?? []);
    $status = (string)($contractUi['status'] ?? ($g?->contract_status ?? 'not_requested'));

    $allDone = (bool)($formCompleted ?? false)
               && (bool)($docsCompleted ?? false)
               && (bool)($packageSelected ?? false);

    // Per-status config
    $statusConfig = [
        'not_requested'   => ['color'=>'brand', 'icon'=>'📋', 'label'=>'Sözleşme Henüz Talep Edilmedi',  'desc'=>'Kayıt formunu, belgelerini ve hizmet paketini tamamladıktan sonra sözleşme talebinde bulunabilirsin.'],
        'pending_manager' => ['color'=>'warn',  'icon'=>'⏳', 'label'=>'Danışmanın Sözleşmeni Hazırlıyor','desc'=>'Danışmanın sözleşme taslağını hazırlıyor. Tamamlandığında imzalamana gönderilecek.'],
        'requested'       => ['color'=>'brand', 'icon'=>'✍️', 'label'=>'Sözleşme İmzanı Bekliyor',        'desc'=>'Sözleşme taslağı hazır. İnceleyip imzalı halini yüklemen gerekiyor.'],
        'signed_uploaded' => ['color'=>'warn',  'icon'=>'🔍', 'label'=>'İmzalı Sözleşme Onay Bekliyor',  'desc'=>'İmzalı sözleşmeni aldık, danışman/operasyon ekibi inceliyor.'],
        'approved'        => ['color'=>'success','icon'=>'🎉', 'label'=>'Sözleşme Onaylandı!',             'desc'=>'Tebrikler! Sözleşmen başarıyla onaylandı. Kayıt sürecine devam edebilirsin.'],
        'rejected'        => ['color'=>'warn',  'icon'=>'❌', 'label'=>'Sözleşme Reddedildi',              'desc'=>'Sözleşmende bir sorun var. Aşağıdan detayları incele ve destek talebi aç.'],
        'cancelled'       => ['color'=>'muted', 'icon'=>'🚫', 'label'=>'Sözleşme İptal Edildi',           'desc'=>'Sözleşme iptal edildi. Detaylar için danışmanınla iletişime geç.'],
    ];
    $sc = $statusConfig[$status] ?? ['color'=>'muted','icon'=>'📄','label'=>ucfirst(str_replace('_',' ',$status)),'desc'=>''];

    $bannerBg = [
        'brand'   => 'linear-gradient(135deg, #7c3aed, #6d28d9)',
        'warn'    => 'linear-gradient(135deg, #d97706, #b45309)',
        'success' => 'linear-gradient(135deg, #16a34a, #15803d)',
        'muted'   => 'var(--u-bg)',
    ][$sc['color']] ?? 'var(--u-bg)';

    $bannerTxtColor = $sc['color'] === 'muted' ? 'var(--u-text)' : '#fff';
    $bannerSubColor = $sc['color'] === 'muted' ? 'var(--u-muted)' : 'rgba(255,255,255,.75)';

    $fmt      = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('d.m.Y H:i') : null;
    $fmtShort = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('d.m.Y') : null;

    $stepActive = ['not_requested'=>0,'pending_manager'=>1,'requested'=>1,'signed_uploaded'=>2,'approved'=>3,'rejected'=>1,'cancelled'=>0][$status] ?? 0;
    $steps = [
        ['name'=>'Talep',    'date'=>$fmtShort($g?->contract_requested_at)],
        ['name'=>'İmzalama', 'date'=>$fmtShort($g?->contract_signed_at)],
        ['name'=>'Onay',     'date'=>$fmtShort($g?->contract_approved_at)],
        ['name'=>'Tamam',    'date'=>$status==='approved' ? $fmtShort($g?->contract_approved_at) : null],
    ];

    $formCompleted      = (bool)($formCompleted ?? false);
    $formDraftComplete  = (bool)($formDraftComplete ?? false);
    $docsCompleted      = (bool)($docsCompleted ?? false);
    $packageSelected    = (bool)($packageSelected ?? false);
    $contractMissing    = collect($contractPrereqSummary['missing'] ?? []);
    $contractInconsistencies = collect($contractUi['inconsistencies'] ?? []);
    $canRequestAddendum = (bool)($contractUi['canRequestAddendum'] ?? false);
    $showContractPanel  = (bool)($contractUi['showCurrentContractPanel'] ?? false);
    $showSnapshotPanel  = (bool)($contractUi['showSnapshotPanel'] ?? false);
    $canOpenSignedFile  = (bool)($contractUi['canOpenSignedFile'] ?? false);

    $prereqs = [
        ['icon'=>'📋','label'=>'Kayıt Formu', 'done'=>$formCompleted,
         'val'=>$formCompleted ? '✓ Gönderildi' : ($formDraftComplete ? 'Gönderilmedi' : 'Eksik'),
         'url'=>'/student/registration'],
        ['icon'=>'📂','label'=>'Belgeler', 'done'=>$docsCompleted,
         'val'=>$docsCompleted ? '✓ Tamam' : 'Eksik belgeler var',
         'url'=>'/student/registration/documents'],
        ['icon'=>'📦','label'=>'Hizmet Paketi', 'done'=>$packageSelected,
         'val'=>$packageSelected ? '✓ Seçildi' : 'Paket seçilmedi',
         'url'=>'/student/services'],
    ];
@endphp

{{-- ── STATUS BANNER ── --}}
<div class="ct-status-banner" style="background:{{ $bannerBg }}; {{ $sc['color']==='muted' ? 'border:1px solid var(--u-line);' : '' }}">
    <div class="ct-status-inner">
        <div class="ct-status-icon-wrap" style="{{ $sc['color']!=='muted' ? 'background:rgba(255,255,255,.15);' : 'background:var(--u-card);border:1px solid var(--u-line);' }}">
            {{ $sc['icon'] }}
        </div>
        <div class="ct-status-text-block">
            <div class="ct-status-label" style="color:{{ $bannerTxtColor }};">{{ $sc['label'] }}</div>
            <div class="ct-status-desc" style="color:{{ $bannerSubColor }};">{{ $sc['desc'] }}</div>
        </div>
    </div>
</div>

{{-- ── STEP TRACKER (standalone, compact) ── --}}
@if($status !== 'cancelled')
<div class="ct-step-track">
    @foreach($steps as $i => $step)
    @php
        $isDone   = $i < $stepActive;
        $isActive = ($i === $stepActive && $status !== 'not_requested');
        $cls = $isDone ? 's-done' : ($isActive ? 's-active' : '');
    @endphp
    <div class="ct-stp-wrap {{ $cls }}">
        <div class="ct-stp-row">
            <div class="ct-stp-circle">{{ $isDone ? '✓' : ($i+1) }}</div>
            @if($i < count($steps)-1)<div class="ct-stp-line"></div>@endif
        </div>
        <div class="ct-stp-name">{{ $step['name'] }}</div>
        @if($step['date'])<div class="ct-stp-date">{{ $step['date'] }}</div>@endif
    </div>
    @endforeach
</div>
@endif

{{-- ── PREREQ ROW ── --}}
<div class="ct-prereq-row">
    @foreach($prereqs as $pr)
    <a class="ct-prereq-card {{ $pr['done'] ? 'done' : 'miss' }}" href="{{ $pr['url'] }}">
        <div class="ct-prereq-icon-b">{{ $pr['done'] ? '✅' : $pr['icon'] }}</div>
        <div>
            <div class="ct-prereq-title">{{ $pr['label'] }}</div>
            <div class="ct-prereq-sub">{{ $pr['val'] }}</div>
        </div>
    </a>
    @endforeach
</div>

{{-- ── MAIN LAYOUT ── --}}
<div class="ct-layout">

    {{-- LEFT COLUMN --}}
    <div>

        {{-- PRIMARY ACTION BLOCK (status-driven) --}}
        @if($status === 'not_requested')
            <div class="ct-action-card">
                <div class="ct-action-head">⚡ Şimdi Ne Yapmalısın?</div>
                <div class="ct-action-body">
                    @if($allDone)
                        <div class="ct-primary-action pa-brand">
                            <div class="ct-pa-icon">📬</div>
                            <div class="ct-pa-body">
                                <div class="ct-pa-title">Sözleşme Talep Et</div>
                                <div class="ct-pa-desc">Tüm ön koşullar tamamlandı! Danışmanınla sözleşme sürecini başlatabilirsin.</div>
                            </div>
                            <form method="post" action="{{ route('student.contract.request') }}" style="flex-shrink:0;">
                                @csrf
                                <button type="submit" class="ct-pa-btn">Talep Gönder →</button>
                            </form>
                        </div>
                    @else
                        <div class="ct-primary-action pa-muted">
                            <div class="ct-pa-icon" style="font-size:var(--tx-xl);">🔒</div>
                            <div class="ct-pa-body">
                                <div class="ct-pa-title">Ön Koşulları Tamamla</div>
                                <div class="ct-pa-desc">Yukarıdaki 3 adımı tamamladıktan sonra sözleşme talebinde bulunabilirsin.</div>
                            </div>
                        </div>
                        @if($contractMissing->isNotEmpty())
                        <div class="ct-notice warn">
                            <span class="ct-notice-icon">⚠️</span>
                            <div>
                                <strong style="display:block;margin-bottom:4px;">Eksikler:</strong>
                                <ul style="margin:0;padding-left:16px;">
                                    @foreach($contractMissing as $msg)<li style="font-size:var(--tx-xs);">{{ $msg }}</li>@endforeach
                                </ul>
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>

        @elseif($status === 'pending_manager')
            <div class="ct-action-card">
                <div class="ct-action-head">⏳ Danışman Hazırlıyor</div>
                <div class="ct-action-body">
                    <div class="ct-primary-action pa-warn">
                        <div class="ct-pa-icon">📝</div>
                        <div class="ct-pa-body">
                            <div class="ct-pa-title">Sözleşme Hazırlanıyor</div>
                            <div class="ct-pa-desc">Danışmanın taslağı hazırladıktan sonra sana iletecek. Bu süreçte herhangi bir işlem yapman gerekmiyor.</div>
                        </div>
                    </div>
                    @if($g?->contract_requested_at)
                    <div class="ct-notice info">
                        <span class="ct-notice-icon">📅</span>
                        <span>Talep tarihi: <strong>{{ $fmt($g->contract_requested_at) }}</strong></span>
                    </div>
                    @endif
                </div>
            </div>

        @elseif($status === 'requested')
            <div class="ct-action-card">
                <div class="ct-action-head">✍️ İmzalı Sözleşme Yükle</div>
                <div class="ct-action-body">
                    @if($showContractPanel && $canOpenSignedFile)
                    <div class="ct-notice ok" style="margin-bottom:14px;">
                        <span class="ct-notice-icon">📄</span>
                        <div>
                            <strong>Sözleşme hazır.</strong> Önce indir, imzala, ardından imzalı halini yükle.
                            <a href="{{ route('student.contract.download-signed') }}" style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;font-weight:700;color:#15803d;text-decoration:none;">
                                ⬇ Sözleşmeyi İndir
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="ct-notice info" style="margin-bottom:14px;">
                        <span class="ct-notice-icon">ℹ️</span>
                        <span>Sözleşme dosyası danışmanın tarafından sisteme eklenecek. Eklendiğinde buradan indirebilirsin.</span>
                    </div>
                    @endif

                    <form method="post" action="{{ route('student.contract.upload-signed') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="ct-upload-zone" for="ct_signed_file" id="ctDropZone">
                            <input type="file" id="ct_signed_file" name="signed_file" accept=".pdf,.jpg,.jpeg,.png" onchange="document.getElementById('ctFileName').textContent=this.files[0]?.name||''">
                            <div class="ct-upload-icon">📎</div>
                            <div class="ct-upload-label">İmzalı sözleşmeni yükle</div>
                            <div class="ct-upload-sub">PDF, JPG veya PNG · Maks 10 MB</div>
                            <div class="ct-upload-chosen" id="ctFileName"></div>
                        </label>
                        <button type="submit" class="ct-pa-btn" style="background:var(--u-brand);color:#fff;width:100%;justify-content:center;padding:12px;">
                            📤 İmzalı Sözleşmeyi Gönder
                        </button>
                    </form>
                </div>
            </div>

        @elseif($status === 'signed_uploaded')
            <div class="ct-action-card">
                <div class="ct-action-head">🔍 İnceleme Süreci</div>
                <div class="ct-action-body">
                    <div class="ct-primary-action pa-warn">
                        <div class="ct-pa-icon">⏳</div>
                        <div class="ct-pa-body">
                            <div class="ct-pa-title">Onay Bekleniyor</div>
                            <div class="ct-pa-desc">İmzalı sözleşmeni aldık. Danışman/operasyon ekibi inceliyor, sonuç için seni bilgilendireceğiz.</div>
                        </div>
                    </div>
                    @if($g?->contract_signed_at)
                    <div class="ct-notice info">
                        <span class="ct-notice-icon">📅</span>
                        <span>Yükleme tarihi: <strong>{{ $fmt($g->contract_signed_at) }}</strong></span>
                    </div>
                    @endif
                </div>
            </div>

        @elseif($status === 'approved')
            <div class="ct-action-card">
                <div class="ct-action-head">🎉 Sözleşme Onaylandı</div>
                <div class="ct-action-body">
                    <div class="ct-primary-action pa-success">
                        <div class="ct-pa-icon">✅</div>
                        <div class="ct-pa-body">
                            <div class="ct-pa-title">Sözleşmen Onaylandı!</div>
                            <div class="ct-pa-desc">Tebrikler! Kayıt sürecinde bir sonraki adıma geçebilirsin.</div>
                        </div>
                        @if($canOpenSignedFile)
                        <a href="{{ route('student.contract.download-signed') }}" class="ct-pa-btn">⬇ İndir</a>
                        @endif
                    </div>
                    @if($g?->contract_approved_at)
                    <div class="ct-notice ok">
                        <span class="ct-notice-icon">📅</span>
                        <span>Onay tarihi: <strong>{{ $fmt($g->contract_approved_at) }}</strong></span>
                    </div>
                    @endif
                </div>
            </div>

        @elseif($status === 'rejected')
            <div class="ct-action-card">
                <div class="ct-action-head">❌ Sözleşme Reddedildi</div>
                <div class="ct-action-body">
                    <div class="ct-primary-action pa-warn">
                        <div class="ct-pa-icon">⚠️</div>
                        <div class="ct-pa-body">
                            <div class="ct-pa-title">İşlem Gerekiyor</div>
                            <div class="ct-pa-desc">Sözleşmende bir sorun tespit edildi. Destek talebi açarak danışmanınla iletişime geç.</div>
                        </div>
                        <a href="/student/tickets" class="ct-pa-btn">🎫 Destek Aç</a>
                    </div>
                    @if($contractMissing->isNotEmpty())
                    <div class="ct-notice warn">
                        <span class="ct-notice-icon">⚠️</span>
                        <div>
                            <strong style="display:block;margin-bottom:4px;">Red nedenleri:</strong>
                            <ul style="margin:0;padding-left:16px;">
                                @foreach($contractMissing as $msg)<li style="font-size:var(--tx-xs);">{{ $msg }}</li>@endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Contract inconsistency warnings --}}
        @if($contractInconsistencies->isNotEmpty())
        <div class="ct-action-card">
            <div class="ct-action-head">⚠️ Uyarılar</div>
            <div class="ct-action-body">
                <div class="ct-notice warn">
                    <span class="ct-notice-icon">⚠️</span>
                    <div>
                        <strong style="display:block;margin-bottom:4px;">Tutarsızlık uyarısı:</strong>
                        <ul style="margin:0;padding-left:16px;">
                            @foreach($contractInconsistencies as $msg)<li style="font-size:var(--tx-xs);">{{ $msg }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Contract text (collapsible) --}}
        @if($showSnapshotPanel)
        <details class="ct-action-card" style="overflow:visible;">
            <summary class="ct-action-head" style="cursor:pointer;list-style:none;display:flex;">
                <span>📝 Sözleşme Metni</span>
                @if($g?->contract_generated_at)
                    <span class="muted" style="font-size:var(--tx-xs);font-weight:400;margin-left:auto;">{{ $fmt($g->contract_generated_at) }}</span>
                @endif
            </summary>
            <div class="ct-action-body">
                <div class="ct-text-viewer">{{ $g?->contract_snapshot_text }}</div>
                @if(!empty($g?->contract_annex_kvkk_text))
                <details style="margin-top:8px;">
                    <summary style="cursor:pointer;font-weight:600;font-size:var(--tx-sm);padding:6px 0;">Ek-1 KVKK</summary>
                    <div class="ct-text-viewer" style="margin-top:6px;">{{ $g->contract_annex_kvkk_text }}</div>
                </details>
                @endif
                @if(!empty($g?->contract_annex_commitment_text))
                <details style="margin-top:8px;">
                    <summary style="cursor:pointer;font-weight:600;font-size:var(--tx-sm);padding:6px 0;">Ek-2 Taahhütname</summary>
                    <div class="ct-text-viewer" style="margin-top:6px;">{{ $g->contract_annex_commitment_text }}</div>
                </details>
                @endif
            </div>
        </details>
        @endif

        {{-- Addendum request (conditional) --}}
        @if($canRequestAddendum || in_array($status, ['not_requested','pending_manager','requested','signed_uploaded']))
        <details class="ct-action-card" style="overflow:visible;">
            <summary class="ct-action-head" style="cursor:pointer;list-style:none;display:flex;">
                <span>✏️ Değişiklik / Ek Talep</span>
                @if(!$canRequestAddendum)<span class="badge" style="font-size:var(--tx-xs);margin-left:auto;">Kapalı</span>@endif
            </summary>
            <div class="ct-action-body">
                @if(!$canRequestAddendum)
                <div class="ct-notice info" style="margin-bottom:14px;">
                    <span class="ct-notice-icon">ℹ️</span>
                    <span>{{ $status==='approved' ? 'Onaylı sözleşmede değişiklik talebi açılamaz.' : 'Bu aşamada değişiklik talebi kapalı.' }}</span>
                </div>
                @endif
                <form method="post" action="{{ route('student.contract.addendum-request') }}" id="studentContractAddendumForm">
                    @csrf
                    <div class="ct-grid2">
                        <div>
                            <label class="ct-label">Konu</label>
                            <input class="ct-field" name="subject" value="Sözleşme değişiklik talebi" @disabled(!$canRequestAddendum)>
                        </div>
                        <div>
                            <label class="ct-label">Öncelik</label>
                            <select class="ct-field" name="priority" @disabled(!$canRequestAddendum)>
                                <option value="high">Yüksek</option>
                                <option value="normal" selected>Normal</option>
                                <option value="urgent">Acil</option>
                            </select>
                        </div>
                    </div>
                    @if(!empty($contractPackages))
                    <label class="ct-label">Yeni Paket (opsiyonel)</label>
                    <select class="ct-field" name="package_code" @disabled(!$canRequestAddendum)>
                        <option value="">Mevcut paketi koru</option>
                        @foreach(($contractPackages ?? []) as $pkg)
                            <option value="{{ $pkg['code'] }}" @selected(($selectedPackageCode??'')===$pkg['code'])>{{ $pkg['title'] }} ({{ $pkg['price'] }})</option>
                        @endforeach
                    </select>
                    @endif
                    @if(!empty($contractExtraServices))
                    <label class="ct-label">Ek Hizmetler</label>
                    <div class="ct-chip-row">
                        @php $activeCodes = collect(old('extra_service_codes',$selectedExtraServiceCodes??[]))->map(fn($x)=>(string)$x)->all(); @endphp
                        @foreach($contractExtraServices as $srv)
                        @php $active = in_array((string)$srv['code'],$activeCodes,true); @endphp
                        <label class="ct-chip {{ $active?'active':'' }}">
                            <input type="checkbox" name="extra_service_codes[]" value="{{ $srv['code'] }}" @checked($active) @disabled(!$canRequestAddendum)>
                            {{ $srv['title'] }}
                        </label>
                        @endforeach
                    </div>
                    @endif
                    <label class="ct-label">Mesaj</label>
                    <textarea class="ct-field" name="message" style="min-height:80px;" placeholder="Değişiklik talebinizi açıklayın..." @disabled(!$canRequestAddendum)></textarea>
                    <button type="submit" @disabled(!$canRequestAddendum)
                        style="padding:10px 20px;background:var(--u-brand);color:#fff;border:none;border-radius:8px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;opacity:{{ $canRequestAddendum?1:.45 }};">
                        Talep Gönder →
                    </button>
                </form>
            </div>
        </details>
        @endif

    </div>

    {{-- RIGHT COLUMN --}}
    <div>
        <div class="ct-action-card">
            <div class="ct-action-head">🔗 Hızlı Erişim</div>
            <div class="ct-action-body" style="padding:14px;">
                @if($canOpenSignedFile)
                <a class="ct-quick-link" href="{{ route('student.contract.download-signed') }}">
                    <div class="ct-quick-link-icon">⬇</div>
                    <span>Sözleşmemi İndir / Gör</span>
                </a>
                @endif
                <a class="ct-quick-link" href="/student/tickets?subject=Sözleşme%20destek&department=operations&priority=high">
                    <div class="ct-quick-link-icon">🎫</div>
                    <span>Destek Ticket Aç</span>
                </a>
                <a class="ct-quick-link" href="/student/messages">
                    <div class="ct-quick-link-icon">💬</div>
                    <span>Danışmana Mesaj Yaz</span>
                </a>
                <a class="ct-quick-link" href="/student/registration">
                    <div class="ct-quick-link-icon">📋</div>
                    <span>Kayıt Formuna Git</span>
                </a>
                <a class="ct-quick-link" href="/student/registration/documents">
                    <div class="ct-quick-link-icon">📂</div>
                    <span>Belgelerime Git</span>
                </a>
                <a class="ct-quick-link" href="/student/services">
                    <div class="ct-quick-link-icon">📦</div>
                    <span>Hizmet Paketleri</span>
                </a>
            </div>
        </div>

        {{-- Timeline summary --}}
        @if($status !== 'not_requested')
        <div class="ct-action-card" style="margin-top:12px;">
            <div class="ct-action-head">📅 Tarihler</div>
            <div class="ct-action-body" style="padding:12px 14px;">
                @php
                    $dates = [
                        ['Talep',    $fmt($g?->contract_requested_at)],
                        ['İmzalama', $fmt($g?->contract_signed_at)],
                        ['Onay',     $fmt($g?->contract_approved_at)],
                    ];
                @endphp
                @foreach($dates as [$lbl, $val])
                @if($val)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--u-line);">
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $lbl }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);">{{ $val }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Error Popup --}}
<div id="studentContractErrorPopup" class="popup-overlay" aria-hidden="true">
    <div class="popup-card">
        <h4 class="popup-title">Sözleşme işlemi tamamlanamadı</h4>
        <div id="studentContractErrorPopupBody" class="popup-body"></div>
        <div class="popup-actions">
            <button type="button" class="ct-btn-ghost" id="studentContractErrorPopupClose">Tamam</button>
        </div>
    </div>
</div>

<script>
window.__studentContractData = { serverError: @json($errors->first('contract')) };
</script>
<script defer src="{{ Vite::asset('resources/js/student-contract.js') }}"></script>
@endsection
