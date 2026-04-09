@extends('guest.layouts.app')

@section('title', 'Kayıt Belgeleri')
@section('page_title', 'Kayıt Belgeleri')

@push('head')
<style>
/* ═══ Guest Portal Yeşil Tema (sabit, override-immune) ═══ */
.sdoc {
    --bg: #f0f2f5;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --light: #94a3b8;
    --line: #e2e8f0;
    --line-light: #f1f5f9;
    --brand: #16a34a;
    --brand-light: #dcfce7;
    --brand-dark: #065f46;
    --brand-mid: #15803d;
    --brand-soft: rgba(22,163,74,0.08);
    --ok: #16a34a;
    --ok-light: #dcfce7;
    --warn: #d97706;
    --warn-light: #fef3c7;
    --danger: #dc2626;
    --danger-light: #fee2e2;
    --blue: #2563eb;
    --blue-light: #dbeafe;
    --teal: #0891b2;
    --teal-light: #cffafe;
    --shadow: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.1);
    --radius: 16px;
    --radius-sm: 12px;
    --radius-xs: 8px;
}
/* Dark mode uyumu */
[data-theme="dark"] .sdoc {
    --bg: #0f172a;
    --card: #1e293b;
    --text: #f1f5f9;
    --muted: #94a3b8;
    --line: #334155;
    --line-light: #1e293b;
    --brand-light: rgba(22,163,74,0.18);
    --brand-soft: rgba(22,163,74,0.15);
}
.sdoc .journey { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow-md); border:1px solid var(--line-light); overflow:hidden; margin-bottom:24px; }
.sdoc .journey-top { padding:16px 24px 12px; display:flex; align-items:center; justify-content:space-between; }
.sdoc .journey-title h3 { font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; margin:0; }
.sdoc .journey-tag { font-size:11px; font-weight:600; padding:2px 10px; border-radius:10px; }
.sdoc .journey-tag.progress { background:var(--brand-soft); color:var(--brand); }
.sdoc .journey-tag.done { background:var(--ok-light); color:var(--ok); }
.sdoc .journey-pct { font-size:24px; font-weight:800; color:var(--brand); letter-spacing:-1px; }
.sdoc .journey-bar-wrap { padding:0 24px; margin-bottom:12px; }
.sdoc .journey-bar { height:6px; background:var(--line-light); border-radius:3px; overflow:hidden; }
.sdoc .journey-bar-fill { height:100%; background:linear-gradient(90deg,var(--brand),#4ade80); border-radius:3px; transition:width 0.8s cubic-bezier(0.4,0,0.2,1); }
.sdoc .journey-steps { display:grid; grid-template-columns:repeat(5,1fr); border-top:1px solid var(--line-light); }
.sdoc .j-step { padding:12px 8px; display:flex; flex-direction:column; align-items:center; gap:5px; cursor:default; border-right:1px solid var(--line-light); text-align:center; position:relative; }
.sdoc .j-step:last-child { border-right:none; }
.sdoc .j-step-num { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; border:2px solid var(--line); background:var(--bg); color:var(--muted); transition:all 0.3s; }
.sdoc .j-step.done .j-step-num { background:var(--ok); border-color:var(--ok); color:#fff; }
.sdoc .j-step.active .j-step-num { background:var(--brand-soft); border-color:var(--brand); color:var(--brand); box-shadow:0 0 0 3px rgba(22,163,74,0.1); animation:sdoc-pulse-j 2s infinite; }
@keyframes sdoc-pulse-j { 0%,100%{box-shadow:0 0 0 3px rgba(22,163,74,0.1);} 50%{box-shadow:0 0 0 6px rgba(22,163,74,0.05);} }
.sdoc .j-step.locked .j-step-num { opacity:0.35; }
.sdoc .j-step-name { font-size:10px; font-weight:600; line-height:1.2; }
.sdoc .j-step.done .j-step-name { color:var(--ok); }
.sdoc .j-step.active .j-step-name { color:var(--brand); }
.sdoc .j-step.locked .j-step-name { color:var(--light); }
.sdoc .j-step.active::after { content:''; position:absolute; top:-1px; left:50%; transform:translateX(-50%); width:0; height:0; border-left:5px solid transparent; border-right:5px solid transparent; border-top:5px solid var(--brand); }
.sdoc .hero-task { border-radius:var(--radius); padding:28px 32px; margin-bottom:24px; color:#fff; box-shadow:var(--shadow-lg); overflow:hidden; position:relative; display:flex; align-items:center; justify-content:space-between; gap:32px; }
.sdoc .hero-content { flex:1; min-width:0; }
.sdoc .hero-task.blue { background:linear-gradient(135deg,#065f46,#16a34a); }
.sdoc .hero-task.teal { background:linear-gradient(135deg,#064e3b,#059669); }
.sdoc .hero-task.green { background:linear-gradient(135deg,#065f46,var(--ok)); }
.sdoc .hero-badge { display:inline-flex; align-items:center; gap:6px; font-size:11px; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.6); margin-bottom:8px; }
.sdoc .hero-badge .pulse { width:8px; height:8px; border-radius:50%; background:#34d399; box-shadow:0 0 6px rgba(52,211,153,0.6); animation:sdoc-pulse-dot 1.5s infinite; }
@keyframes sdoc-pulse-dot { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
.sdoc .hero-title { font-size:22px; font-weight:700; margin-bottom:6px; line-height:1.3; }
.sdoc .hero-sub { font-size:14px; color:rgba(255,255,255,0.75); line-height:1.5; margin-bottom:16px; }
.sdoc .grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
.sdoc .stat-card { background:var(--card); border-radius:var(--radius-sm); padding:18px 20px; box-shadow:var(--shadow); border:1px solid var(--line-light); display:flex; align-items:center; gap:14px; }
.sdoc .stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.sdoc .stat-icon.green { background:var(--ok-light); } .sdoc .stat-icon.amber { background:var(--warn-light); } .sdoc .stat-icon.red { background:var(--danger-light); } .sdoc .stat-icon.blue { background:var(--blue-light); }
.sdoc .stat-label { font-size:11px; color:var(--muted); margin-bottom:2px; }
.sdoc .stat-value { font-size:20px; font-weight:700; line-height:1.2; }
.sdoc .alert-bar { display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:var(--radius-xs); margin-bottom:20px; font-size:13px; }
.sdoc .alert-bar.danger { background:var(--danger-light); border:1px solid rgba(220,38,38,0.15); color:#991b1b; }
.sdoc .alert-bar.warn { background:var(--warn-light); border:1px solid rgba(217,119,6,0.15); color:#78350f; }
.sdoc .alert-bar .alert-icon { font-size:18px; flex-shrink:0; }
.sdoc .section-card { background:var(--card); border-radius:var(--radius-sm); box-shadow:var(--shadow); border:1px solid var(--line-light); overflow:hidden; margin-bottom:20px; }
.sdoc .section-header { padding:16px 20px; border-bottom:1px solid var(--line-light); display:flex; align-items:center; justify-content:space-between; }
.sdoc .section-header h4 { font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; margin:0; }
.sdoc .section-link { font-size:11px; font-weight:600; color:var(--brand); text-decoration:none; cursor:pointer; background:none; border:none; font-family:inherit; }
.sdoc .filter-pills { display:flex; gap:8px; padding:14px 20px; border-bottom:1px solid var(--line-light); flex-wrap:wrap; align-items:center; }
.sdoc .filter-pill { padding:5px 14px; border-radius:999px; font-size:11px; font-weight:600; border:1px solid var(--line); background:var(--card); color:var(--muted); cursor:pointer; font-family:inherit; transition:all 0.15s; }
.sdoc .filter-pill:hover { border-color:var(--brand); color:var(--brand); }
.sdoc .filter-pill.active { background:var(--brand); border-color:var(--brand); color:#fff; }
.sdoc .filter-pill .cnt { margin-left:4px; opacity:0.6; }
.sdoc .filter-label { font-size:10px; font-weight:700; color:var(--light); text-transform:uppercase; letter-spacing:1px; margin-right:6px; }
.sdoc .cat-tabs { display:flex; gap:0; padding:0 20px; border-bottom:1px solid var(--line-light); overflow-x:auto; }
.sdoc .cat-tab { padding:10px 16px; font-size:12px; font-weight:600; color:var(--muted); border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; white-space:nowrap; font-family:inherit; transition:all 0.15s; }
.sdoc .cat-tab:hover { color:var(--text); }
.sdoc .cat-tab.active { color:var(--brand); border-bottom-color:var(--brand); }
.sdoc .cat-tab .tab-badge { display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; border-radius:9px; font-size:9px; font-weight:700; margin-left:6px; padding:0 5px; }
.sdoc .cat-tab .tab-badge.red { background:var(--danger-light); color:var(--danger); }
.sdoc .doc-list { padding:8px 20px 20px; }
.sdoc .doc-group-title { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; padding:16px 0 8px; border-bottom:1px solid var(--line-light); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; }
.sdoc .doc-group-title .grp-stats { font-size:11px; font-weight:500; color:var(--light); text-transform:none; letter-spacing:0; }
.sdoc .doc-card { display:grid; grid-template-columns:44px 1fr auto; gap:16px; align-items:center; padding:14px 16px; border-bottom:1px solid var(--line-light); transition:all 0.15s; border-radius:var(--radius-xs); margin:0 -4px; }
.sdoc .doc-card:last-child { border-bottom:none; }
.sdoc .doc-card:hover { background:var(--line-light); }
.sdoc .doc-card.urgent { background:linear-gradient(90deg, rgba(220,38,38,0.03), transparent); }
.sdoc .doc-icon-wrap { width:44px; height:44px; border-radius:var(--radius-xs); display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.sdoc .doc-icon-wrap.uploaded { background:var(--ok-light); } .sdoc .doc-icon-wrap.waiting { background:var(--warn-light); } .sdoc .doc-icon-wrap.missing { background:var(--danger-light); }
.sdoc .doc-icon-wrap.approved { background:var(--ok-light); border:2px solid var(--ok); } .sdoc .doc-icon-wrap.rejected { background:var(--danger-light); border:2px solid var(--danger); }
.sdoc .doc-info { min-width:0; }
.sdoc .doc-name { font-size:13px; font-weight:600; margin-bottom:2px; display:flex; align-items:center; gap:8px; }
.sdoc .doc-name .required-dot { width:6px; height:6px; border-radius:50%; background:var(--danger); flex-shrink:0; }
.sdoc .doc-meta { font-size:11px; color:var(--light); display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.sdoc .doc-meta .chip { padding:1px 8px; border-radius:999px; font-size:10px; font-weight:600; }
.sdoc .doc-meta .chip.ok { background:var(--ok-light); color:var(--ok); } .sdoc .doc-meta .chip.wait { background:var(--warn-light); color:var(--warn); } .sdoc .doc-meta .chip.danger { background:var(--danger-light); color:var(--danger); }
.sdoc .doc-meta .chip.rejected { background:var(--danger-light); color:var(--danger); border:1px solid rgba(220,38,38,0.2); }
.sdoc .doc-meta .chip.approved { background:var(--ok-light); color:var(--ok); border:1px solid rgba(22,163,74,0.2); }
.sdoc .doc-meta .chip.generated { background:var(--blue-light); color:var(--blue); }
.sdoc .doc-actions { display:flex; gap:8px; align-items:center; flex-shrink:0; }
.sdoc .doc-btn { padding:7px 16px; border-radius:var(--radius-xs); font-size:12px; font-weight:600; border:1px solid var(--line); background:var(--card); color:var(--text); cursor:pointer; font-family:inherit; transition:all 0.15s; display:inline-flex; align-items:center; gap:5px; text-decoration:none; }
.sdoc .doc-btn:hover { border-color:var(--brand); color:var(--brand); text-decoration:none; }
.sdoc .doc-btn.primary { background:var(--brand); border-color:var(--brand); color:#fff; } .sdoc .doc-btn.primary:hover { background:var(--brand-mid); }
.sdoc .doc-btn.danger { border-color:var(--danger); color:var(--danger); }
.sdoc .doc-btn.small { padding:5px 10px; font-size:11px; }
.sdoc .upload-zone { grid-column:1/-1; padding:16px; margin-top:8px; background:var(--line-light); border-radius:var(--radius-xs); border:2px dashed var(--line); display:none; }
.sdoc .upload-zone.open { display:block; }
.sdoc .upload-zone-inner { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.sdoc .upload-zone .uz-icon { font-size:28px; opacity:0.3; } .sdoc .upload-zone .uz-text { font-size:12px; color:var(--muted); } .sdoc .upload-zone .uz-text strong { color:var(--brand); cursor:pointer; }
.sdoc .upload-zone .selected-file { font-size:12px; color:var(--text); font-weight:600; display:none; align-items:center; gap:6px; } .sdoc .upload-zone .selected-file.show { display:flex; }
.sdoc .ring-wrap { display:flex; align-items:center; gap:16px; } .sdoc .ring { width:64px; height:64px; position:relative; }
.sdoc .ring svg { width:100%; height:100%; transform:rotate(-90deg); }
.sdoc .ring-bg { fill:none; stroke:rgba(255,255,255,0.15); stroke-width:5; } .sdoc .ring-fill { fill:none; stroke:#fff; stroke-width:5; stroke-linecap:round; transition:stroke-dashoffset 0.8s ease; }
.sdoc .ring-text { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:#fff; }
.sdoc .celebration { text-align:center; padding:48px 24px; }
.sdoc .celebration .cel-icon { font-size:56px; margin-bottom:16px; animation:sdoc-bounce-cel 1s infinite; }
@keyframes sdoc-bounce-cel { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.sdoc .celebration h2 { font-size:24px; font-weight:800; margin-bottom:8px; }
.sdoc .celebration p { font-size:14px; color:var(--muted); max-width:520px; margin:0 auto 24px; line-height:1.6; }
.sdoc .cel-btn { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; border-radius:var(--radius-xs); background:linear-gradient(135deg,var(--brand),#4ade80); color:#fff; font-size:15px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; box-shadow:0 4px 14px rgba(22,163,74,0.3); }
.sdoc .cel-btn:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(22,163,74,0.35); color:#fff; text-decoration:none; }
.sdoc .sdoc-hidden { display:none !important; } .sdoc .sdoc-collapsed { display:none !important; }
@media (max-width:1024px) { .sdoc .grid-4 { grid-template-columns:1fr 1fr; } }
@media (max-width:740px) { .sdoc .grid-4 { grid-template-columns:1fr; } .sdoc .journey-steps { grid-template-columns:repeat(3,1fr); } .sdoc .doc-card { grid-template-columns:36px 1fr; } .sdoc .doc-card .doc-actions { grid-column:1/-1; } .sdoc .hero-task { padding:20px; flex-direction:column; gap:16px; } }
</style>
@endpush

@section('content')
@php
    $check = collect($requiredDocumentChecklist ?? []);
    $allCount = $check->count();
    $uploadedCount = $check->where('uploaded', true)->count();
    $requiredTotal = $check->where('is_required', true)->count();
    $requiredUploaded = $check->where('is_required', true)->where('uploaded', true)->count();
    $missingRequired = max(0, $requiredTotal - $requiredUploaded);
    $pct = $allCount > 0 ? (int) round(($uploadedCount / $allCount) * 100) : 0;

    $docs = collect($documents ?? []);
    $approvedCount = $docs->where('status', 'approved')->count();
    $rejectedDocs = $docs->where('status', 'rejected')->values();
    $rejectedCount = $rejectedDocs->count();
    $pendingCount = max(0, $docs->count() - $approvedCount - $rejectedCount);

    $docByCode = [];
    foreach ($docs as $d) { $code = (string) ($d->category->code ?? ''); if ($code !== '' && !isset($docByCode[$code])) $docByCode[$code] = $d; }

    $allRequiredDone = $missingRequired === 0 && $requiredTotal > 0;
    $allDocsApproved = $approvedCount > 0 && $pendingCount === 0 && $rejectedCount === 0 && $allRequiredDone;
    if ($allDocsApproved) { $scenario = 'done'; }
    elseif ($allRequiredDone && $rejectedCount === 0) { $scenario = 'waiting'; }
    elseif ($pct >= 35) { $scenario = 'progress'; }
    else { $scenario = 'start'; }

    $formDone = !empty($formCompleted ?? false);
    $docsDone = $scenario === 'done';
    $packageDone = !empty($packageSelected ?? false);
    $contractDoneFlag = ($contractStatus ?? '') === 'approved';

    $missingRequiredItems = $check->filter(fn($x) => !empty($x['is_required']) && empty($x['uploaded']))->values();
    $uploadedItems = $check->filter(fn($x) => !empty($x['uploaded']))->values();
    $otherItems = $check->filter(fn($x) => empty($x['is_required']) && empty($x['uploaded']))->values();
    $categoryMissing = $check->filter(fn($x) => !empty($x['is_required']) && empty($x['uploaded']))->groupBy(fn($x) => (string) ($x['top_category_code'] ?? 'diger'))->map->count();
    $topCats = $check->pluck('top_category_code')->filter()->unique()->values();
    $circumference = 113.1;
    $ringOffset = round($circumference - ($circumference * $pct / 100), 1);
@endphp

<div class="sdoc">
    <div class="journey">
        <div class="journey-top"><div class="journey-title"><h3>🎓 Almanya Yolculuğun <span class="journey-tag {{ $docsDone ? 'done' : 'progress' }}">Belgeler</span></h3></div><div class="journey-pct">{{ $pct }}%</div></div>
        <div class="journey-bar-wrap"><div class="journey-bar"><div class="journey-bar-fill" style="width:{{ $pct }}%"></div></div></div>
        <div class="journey-steps">
            <div class="j-step {{ $formDone ? 'done' : 'active' }}"><div class="j-step-num">{{ $formDone ? '✓' : '1' }}</div><div class="j-step-name">Kayıt Formu</div></div>
            <div class="j-step {{ $docsDone ? 'done' : ($formDone ? 'active' : 'locked') }}"><div class="j-step-num">{{ $docsDone ? '✓' : '2' }}</div><div class="j-step-name">Belgeler</div></div>
            <div class="j-step {{ $packageDone ? 'done' : ($docsDone ? 'active' : 'locked') }}"><div class="j-step-num">{{ $packageDone ? '✓' : '3' }}</div><div class="j-step-name">Paket Seçimi</div></div>
            <div class="j-step {{ $contractDoneFlag ? 'done' : ($packageDone ? 'active' : 'locked') }}"><div class="j-step-num">{{ $contractDoneFlag ? '✓' : '4' }}</div><div class="j-step-name">Sözleşme</div></div>
            <div class="j-step {{ ($contractDoneFlag && $formDone) ? 'done' : 'locked' }}"><div class="j-step-num">{{ ($contractDoneFlag && $formDone) ? '✓' : '5' }}</div><div class="j-step-name">Tamamlandı</div></div>
        </div>
    </div>

    @if($scenario === 'done')
        <div class="section-card"><div class="celebration"><div class="cel-icon">🎉</div><h2>Tüm Belgeler Onaylandı!</h2><p>Harika iş çıkardın! Tüm zorunlu belgelerin danışman tarafından onaylandı. Şimdi hizmet paketini seçebilirsin.</p><a class="cel-btn" href="{{ route('guest.services') }}">📦 Paket Seçimine Geç →</a></div></div>
        <div class="grid-4">
            <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-label">Onaylı</div><div class="stat-value" style="color:var(--ok);">{{ $approvedCount }}</div></div></div>
            <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-label">Bekliyor</div><div class="stat-value">0</div></div></div>
            <div class="stat-card"><div class="stat-icon red">⚠️</div><div><div class="stat-label">Eksik Zorunlu</div><div class="stat-value" style="color:var(--ok);">0</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📄</div><div><div class="stat-label">Toplam</div><div class="stat-value">{{ $allCount }}</div></div></div>
        </div>
        <div class="section-card">
            <div class="section-header"><h4>✅ Tüm Belgeler ({{ $approvedCount }} onaylı)</h4><button class="section-link" type="button" id="toggle-done-list">Göster / Gizle</button></div>
            <div class="doc-list" id="done-list" style="display:none;">
                @foreach($uploadedItems as $ui)
                    @php $uiDoc = $docByCode[$ui['category_code'] ?? ''] ?? null; @endphp
                    <div class="doc-card"><div class="doc-icon-wrap approved">✅</div><div class="doc-info"><div class="doc-name">{{ $ui['name'] ?: '-' }}</div><div class="doc-meta"><span class="chip approved">Onaylandı</span><span>{{ $documentTopCategoryLabels[$ui['top_category_code'] ?? ''] ?? '' }}</span></div></div><div class="doc-actions">@if($uiDoc)<button class="doc-btn small" type="button" data-preview="{{ $uiDoc->id }}">👁</button><a class="doc-btn small" href="{{ route('guest.registration.documents.serve', $uiDoc->id) }}">⬇</a>@endif</div></div>
                @endforeach
            </div>
        </div>
    @endif

    @if($scenario === 'waiting')
        <div class="hero-task teal"><div class="hero-content"><div class="hero-badge"><span class="pulse"></span> Adım 2/5</div><div class="hero-title">Zorunlu belgeler tamam!</div><div class="hero-sub">Tüm zorunlu belgelerin yüklendi. Danışmanın belgeleri kontrol ediyor. Onay sonrası paket seçimi açılacak.</div></div><div class="ring-wrap"><div class="ring"><svg viewBox="0 0 44 44"><circle class="ring-bg" cx="22" cy="22" r="18"/><circle class="ring-fill" cx="22" cy="22" r="18" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $ringOffset }}" style="stroke:var(--ok)"/></svg><div class="ring-text" style="color:var(--ok)">{{ $pct }}%</div></div><div><div style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.9);">{{ $uploadedCount }} / {{ $allCount }}</div><div style="font-size:11px;color:rgba(255,255,255,0.5);">belge yüklendi</div></div></div></div>
        <div class="grid-4">
            <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-label">Onaylı</div><div class="stat-value">{{ $approvedCount }}</div></div></div>
            <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-label">İnceleniyor</div><div class="stat-value">{{ $pendingCount }}</div></div></div>
            <div class="stat-card"><div class="stat-icon red">⚠️</div><div><div class="stat-label">Eksik Zorunlu</div><div class="stat-value" style="color:var(--ok);">0</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📄</div><div><div class="stat-label">Toplam</div><div class="stat-value">{{ $allCount }}</div></div></div>
        </div>
        @php $waitingDocs = $docs->filter(fn($d) => !in_array($d->status, ['approved','rejected']))->values(); @endphp
        @if($waitingDocs->count() > 0)
        <div class="section-card"><div class="section-header"><h4>⏳ İnceleme Bekleyen ({{ $waitingDocs->count() }})</h4></div><div class="doc-list">
            @foreach($waitingDocs->take(5) as $wd)
                @php $wdCatLabel = ''; foreach ($check as $ci) { if (($ci['category_code'] ?? '') === (string)($wd->category->code ?? '')) { $wdCatLabel = $documentTopCategoryLabels[$ci['top_category_code'] ?? ''] ?? ''; break; } } @endphp
                <div class="doc-card"><div class="doc-icon-wrap uploaded">⏳</div><div class="doc-info"><div class="doc-name">{{ $wd->title ?: ($wd->category->name_tr ?? $wd->document_id) }}</div><div class="doc-meta"><span class="chip wait">İnceleniyor</span>@if($wdCatLabel)<span>{{ $wdCatLabel }}</span>@endif<span>{{ $wd->updated_at?->format('d M Y') ?? '' }}</span></div></div><div class="doc-actions"><button class="doc-btn small" type="button" data-preview="{{ $wd->id }}">👁</button></div></div>
            @endforeach
        </div></div>
        @endif
        <div class="section-card"><div style="padding:32px;text-align:center;"><div style="font-size:36px;margin-bottom:12px;">☕</div><h3 style="font-size:16px;font-weight:700;margin-bottom:6px;">Rahatla, danışmanın çalışıyor</h3><p style="font-size:13px;color:var(--muted);max-width:420px;margin:0 auto;line-height:1.6;">Belgelerinin incelenmesi genellikle 1-3 iş günü sürer. Sonuç çıkınca seni bilgilendireceğiz.</p></div></div>
    @endif

    @if($scenario === 'start' || $scenario === 'progress')
        @php
            $heroClass = $scenario === 'start' ? 'blue' : 'teal';
            $heroTitle = $scenario === 'start' ? 'Belgelerini yükle' : 'Harika gidiyorsun!';
            $heroSub = $scenario === 'start' ? 'Gerekli belgelerini yükleyerek kayıt sürecini ilerlet. Önce zorunlu belgelere odaklan.' : "Belgelerin büyük kısmı tamam. Kalan {$missingRequired} zorunlu belgeyi yükleyince sonraki adıma geçebilirsin.";
        @endphp
        <div class="hero-task {{ $heroClass }}"><div class="hero-content"><div class="hero-badge"><span class="pulse"></span> Adım 2/5</div><div class="hero-title">{{ $heroTitle }}</div><div class="hero-sub">{{ $heroSub }}</div></div><div class="ring-wrap"><div class="ring"><svg viewBox="0 0 44 44"><circle class="ring-bg" cx="22" cy="22" r="18"/><circle class="ring-fill" cx="22" cy="22" r="18" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $ringOffset }}"/></svg><div class="ring-text">{{ $pct }}%</div></div><div><div style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.9);">{{ $uploadedCount }} / {{ $allCount }}</div><div style="font-size:11px;color:rgba(255,255,255,0.5);">belge yüklendi</div></div></div></div>

        <div class="grid-4">
            <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-label">Onaylı</div><div class="stat-value">{{ $approvedCount }}</div></div></div>
            <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-label">Bekliyor</div><div class="stat-value">{{ $pendingCount }}</div></div></div>
            <div class="stat-card"><div class="stat-icon red">⚠️</div><div><div class="stat-label">Eksik Zorunlu</div><div class="stat-value" style="color:var(--danger);">{{ $missingRequired }}</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📄</div><div><div class="stat-label">Toplam</div><div class="stat-value">{{ $allCount }}</div></div></div>
        </div>

        @if($missingRequired > 0 && $missingRequired <= 3)
            <div class="alert-bar warn"><span class="alert-icon">⚡</span><div><strong>{{ $missingRequired }} zorunlu belge kaldı!</strong> Tamamladığında paket seçimi açılacak.</div></div>
        @elseif($missingRequired > 3)
            <div class="alert-bar danger"><span class="alert-icon">🔴</span><div><strong>{{ $missingRequired }} zorunlu belge eksik.</strong> Sonraki adıma geçmek için tüm zorunlu belgeleri yüklemen gerekiyor.</div></div>
        @endif

        @if($rejectedCount > 0)
        <div class="section-card"><div class="section-header"><h4>🔴 Reddedilen Belge ({{ $rejectedCount }})</h4></div><div class="doc-list">
            @foreach($rejectedDocs as $rd)
                @php $rdName = $rd->title ?: ($rd->category->name_tr ?? ($rd->category->code ?? '-')); $rdCatLabel = ''; foreach ($check as $ci) { if (($ci['category_code'] ?? '') === (string)($rd->category->code ?? '')) { $rdCatLabel = $documentTopCategoryLabels[$ci['top_category_code'] ?? ''] ?? ''; break; } } $rejFid = 'rej-' . preg_replace('/[^a-z0-9]/', '-', strtolower((string)($rd->category->code ?? 'x'))); @endphp
                <div class="doc-card" style="background:linear-gradient(90deg,rgba(220,38,38,0.04),transparent);"><div class="doc-icon-wrap rejected">❌</div><div class="doc-info"><div class="doc-name">{{ $rdName }}</div><div class="doc-meta"><span class="chip rejected">Reddedildi</span>@if($rdCatLabel)<span>{{ $rdCatLabel }}</span>@endif</div>@if($rd->review_note)<div style="font-size:11px;color:var(--danger);margin-top:4px;line-height:1.4;">💬 Red sebebi: "{{ $rd->review_note }}"</div>@endif</div><div class="doc-actions"><form method="post" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" style="display:flex;align-items:center;gap:6px;margin:0;">@csrf<input type="hidden" name="category_code" value="{{ $rd->category->code ?? '' }}"><label class="doc-btn small" for="{{ $rejFid }}" style="cursor:pointer;">Dosya Seç</label><input type="file" name="file" id="{{ $rejFid }}" required style="display:none;" data-fname-target="sf-{{ $rejFid }}"><span id="sf-{{ $rejFid }}" style="font-size:11px;color:var(--muted);max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span><button class="doc-btn danger" type="submit">🔄 Yeniden Yükle</button></form></div></div>
            @endforeach
        </div></div>
        @endif

        <div class="section-card" id="docsSection">
            <div class="section-header"><h4>📂 Belgelerim</h4><span style="font-size:11px;color:var(--light);">{{ $uploadedCount }}/{{ $allCount }} tamamlandı</span></div>
            <div class="filter-pills"><span class="filter-label">Filtre</span><button class="filter-pill active" data-filter="urgent">Önce Zorunlu<span class="cnt">({{ $missingRequired }})</span></button><button class="filter-pill" data-filter="all">Tümü<span class="cnt">({{ $allCount }})</span></button><button class="filter-pill" data-filter="uploaded">Yüklenen<span class="cnt">({{ $uploadedCount }})</span></button><button class="filter-pill" data-filter="missing">Eksik<span class="cnt">({{ $allCount - $uploadedCount }})</span></button></div>
            @if($topCats->count() > 1)
            <div class="cat-tabs"><button class="cat-tab active" data-cattab="all">Tümü</button>@foreach($topCats as $tc)<button class="cat-tab" data-cattab="{{ $tc }}">{{ $documentTopCategoryLabels[$tc] ?? $tc }}@if(($categoryMissing[$tc] ?? 0) > 0)<span class="tab-badge red">{{ $categoryMissing[$tc] }}</span>@endif</button>@endforeach</div>
            @endif
            <div class="doc-list" id="docList">
                @if($missingRequiredItems->count() > 0)
                <div class="doc-group-title" data-grp="missing-required"><span>🔴 Zorunlu — Eksik ({{ $missingRequiredItems->count() }})</span><span class="grp-stats">Hemen yükle</span></div>
                @foreach($missingRequiredItems as $mi)
                    @php $miFid = 'mi-' . preg_replace('/[^a-z0-9]/', '-', strtolower((string)($mi['category_code'] ?? 'x'))); @endphp
                    <div class="doc-card urgent" data-cat="{{ $mi['top_category_code'] ?? '' }}" data-req="1" data-up="0"><div class="doc-icon-wrap missing">📋</div><div class="doc-info"><div class="doc-name"><span class="required-dot"></span>{{ $mi['name'] ?: '-' }}</div><div class="doc-meta"><span class="chip danger">Zorunlu</span><span>{{ $documentTopCategoryLabels[$mi['top_category_code'] ?? ''] ?? '' }}</span><span>{{ $mi['accepted'] ?? 'pdf,jpg,png' }} — max {{ (int)($mi['max_mb'] ?? 10) }}MB</span></div></div><div class="doc-actions"><button class="doc-btn primary" type="button" data-upload="{{ $miFid }}">📤 Yükle</button></div><div class="upload-zone" id="uz-{{ $miFid }}"><form method="post" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" style="margin:0;">@csrf<input type="hidden" name="category_code" value="{{ $mi['category_code'] }}"><div class="upload-zone-inner"><span class="uz-icon">📎</span><div class="uz-text">Dosyanı buraya sürükle veya <strong><label for="{{ $miFid }}" style="cursor:pointer;">bilgisayarından seç</label></strong></div><input type="file" name="file" id="{{ $miFid }}" required style="display:none;" data-fname-target="sf-{{ $miFid }}"><div class="selected-file" id="sf-{{ $miFid }}">📄 <span class="sf-name"></span> <button class="doc-btn small primary" type="submit">Gönder</button></div></div></form></div></div>
                @endforeach
                @endif

                @if($uploadedItems->count() > 0)
                <div class="doc-group-title" data-grp="uploaded" @if($missingRequiredItems->count() > 0) style="margin-top:8px;" @endif><span>✅ Yüklenen ({{ $uploadedItems->count() }})</span><span class="grp-stats">İnceleme durumunu takip et</span></div>
                @foreach($uploadedItems as $ui)
                    @php $uiCode=(string)($ui['category_code']??''); $uiDoc=$docByCode[$uiCode]??null; $uiStatus=(string)($uiDoc->status??'uploaded'); $uiIconClass=match($uiStatus){'approved'=>'approved','rejected'=>'rejected',default=>'uploaded'}; $uiIcon=match($uiStatus){'approved'=>'✅','rejected'=>'❌',default=>'⏳'}; $uiChipClass=match($uiStatus){'approved'=>'approved','rejected'=>'rejected','generated'=>'generated',default=>'wait'}; $uiChipLabel=match($uiStatus){'approved'=>'Onaylandı','rejected'=>'Reddedildi','generated'=>'Oluşturuldu',default=>'İnceleniyor'}; $uiFid='ui-'.preg_replace('/[^a-z0-9]/','-',strtolower($uiCode)); @endphp
                    <div class="doc-card" data-cat="{{ $ui['top_category_code'] ?? '' }}" data-req="{{ !empty($ui['is_required']) ? '1' : '0' }}" data-up="1"><div class="doc-icon-wrap {{ $uiIconClass }}">{{ $uiIcon }}</div><div class="doc-info"><div class="doc-name">{{ $ui['name'] ?: '-' }}</div><div class="doc-meta"><span class="chip {{ $uiChipClass }}">{{ $uiChipLabel }}</span><span>{{ $documentTopCategoryLabels[$ui['top_category_code'] ?? ''] ?? '' }}</span>@if($uiDoc)<span>{{ $uiDoc->updated_at?->format('d M Y') ?? $uiDoc->updated_at }}</span>@endif</div></div><div class="doc-actions">@if($uiDoc)<button class="doc-btn small" type="button" data-preview="{{ $uiDoc->id }}">👁 Önizle</button>@if($uiStatus!=='approved')<button class="doc-btn small" type="button" data-upload="{{ $uiFid }}">🔄 Güncelle</button>@else<a class="doc-btn small" href="{{ route('guest.registration.documents.serve', $uiDoc->id) }}">⬇ İndir</a>@endif @endif</div><div class="upload-zone" id="uz-{{ $uiFid }}"><form method="post" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" style="margin:0;">@csrf<input type="hidden" name="category_code" value="{{ $uiCode }}"><div class="upload-zone-inner"><span class="uz-icon">📎</span><div class="uz-text">Dosyanı sürükle veya <strong><label for="{{ $uiFid }}" style="cursor:pointer;">seç</label></strong></div><input type="file" name="file" id="{{ $uiFid }}" required style="display:none;" data-fname-target="sf-{{ $uiFid }}"><div class="selected-file" id="sf-{{ $uiFid }}">📄 <span class="sf-name"></span> <button class="doc-btn small primary" type="submit">Gönder</button></div></div></form></div></div>
                @endforeach
                @endif

                @if($otherItems->count() > 0)
                @php $showInitial = 2; @endphp
                <div class="doc-group-title" data-grp="other" style="margin-top:8px;"><span>📝 Diğer Belgeler ({{ $otherItems->count() }})</span><span class="grp-stats">İsteğe bağlı</span></div>
                @foreach($otherItems as $idx => $oi)
                    @php $oiFid = 'oi-' . preg_replace('/[^a-z0-9]/', '-', strtolower((string)($oi['category_code'] ?? 'x'))); @endphp
                    <div class="doc-card {{ $idx >= $showInitial ? 'extra-doc sdoc-collapsed' : '' }}" data-cat="{{ $oi['top_category_code'] ?? '' }}" data-req="0" data-up="0"><div class="doc-icon-wrap waiting">📄</div><div class="doc-info"><div class="doc-name">{{ $oi['name'] ?: '-' }}</div><div class="doc-meta"><span>{{ $documentTopCategoryLabels[$oi['top_category_code'] ?? ''] ?? '' }}</span><span>{{ $oi['accepted'] ?? 'pdf,jpg,png' }} — max {{ (int)($oi['max_mb'] ?? 10) }}MB</span></div></div><div class="doc-actions"><button class="doc-btn" type="button" data-upload="{{ $oiFid }}">📤 Yükle</button></div><div class="upload-zone" id="uz-{{ $oiFid }}"><form method="post" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" style="margin:0;">@csrf<input type="hidden" name="category_code" value="{{ $oi['category_code'] }}"><div class="upload-zone-inner"><span class="uz-icon">📎</span><div class="uz-text">Dosyanı sürükle veya <strong><label for="{{ $oiFid }}" style="cursor:pointer;">seç</label></strong></div><input type="file" name="file" id="{{ $oiFid }}" required style="display:none;" data-fname-target="sf-{{ $oiFid }}"><div class="selected-file" id="sf-{{ $oiFid }}">📄 <span class="sf-name"></span> <button class="doc-btn small primary" type="submit">Gönder</button></div></div></form></div></div>
                @endforeach
                @if($otherItems->count() > $showInitial)
                    <div style="text-align:center;padding:12px;" id="showMoreWrap"><button class="doc-btn" style="color:var(--muted);" type="button" id="showMoreBtn">+ {{ $otherItems->count() - $showInitial }} belge daha göster</button></div>
                @endif
                @endif

                @if($allCount === 0)
                <div style="text-align:center;padding:40px 20px;"><div style="font-size:48px;margin-bottom:12px;opacity:0.3;">📂</div><h3 style="font-size:16px;font-weight:700;margin-bottom:4px;">Henüz belge alanı yok</h3><p style="font-size:13px;color:var(--muted);">Başvuru tipine uygun belge listesi oluşturulduğunda burada görünecek.</p></div>
                @endif
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
            <a class="doc-btn small" href="/guest/registration/form" style="text-decoration:none;">📋 Forma Dön</a>
            <a class="doc-btn small" href="{{ route('guest.services') }}" style="text-decoration:none;">📦 Paketler</a>
        </div>
    @endif
</div>

<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--surface,#fff);border-radius:16px;max-width:860px;width:100%;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f1f5f9;"><strong id="preview-filename" style="font-size:15px;font-weight:700;"></strong><button id="preview-close-btn" type="button" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;padding:4px 8px;border-radius:6px;">✕</button></div>
        <div id="preview-container" style="flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;background:#f9fafb;padding:12px;min-height:300px;"></div>
        <div id="preview-review-note" style="display:none;padding:10px 16px;background:#fef3c7;border-top:1px solid #fde68a;font-size:12px;color:#92400e;"></div>
        <div style="padding:10px 16px;border-top:1px solid #f1f5f9;"><span id="preview-status" style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:999px;"></span></div>
    </div>
</div>

@if(session('docs_complete'))
<div id="docsCompleteModal" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;max-width:420px;width:100%;padding:32px 28px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);animation:dcPop .4s cubic-bezier(.34,1.56,.64,1);"><div style="font-size:56px;margin-bottom:12px;">🎉</div><div style="font-size:22px;font-weight:800;margin-bottom:8px;">Tebrikler!</div><div style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:24px;">Tüm belgeler başarıyla yüklendi.<br>Şimdi hizmet paketini seçebilirsin.</div><a href="{{ route('guest.services') }}" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-size:15px;font-weight:700;text-decoration:none;box-shadow:0 4px 14px rgba(13,148,136,.3);">Paketlere Git →</a><div style="margin-top:14px;"><button type="button" id="docsCompleteClose" style="background:none;border:none;font-size:13px;color:#64748b;cursor:pointer;padding:4px 8px;">Sonra bakarım</button></div></div>
</div>
<style>@keyframes dcPop{0%{transform:scale(.8);opacity:0}100%{transform:scale(1);opacity:1}}</style>
@endif

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('input[type="file"][data-fname-target]').forEach(function(inp){ inp.addEventListener('change', function(){ var tgt=document.getElementById(this.dataset.fnameTarget); if(tgt){tgt.classList.add('show');var n=tgt.querySelector('.sf-name');if(n)n.textContent=this.files[0]?this.files[0].name:'';} }); });
    document.querySelectorAll('[data-upload]').forEach(function(btn){ btn.addEventListener('click', function(){ var zone=document.getElementById('uz-'+this.dataset.upload); if(zone) zone.classList.toggle('open'); }); });
    var smBtn=document.getElementById('showMoreBtn'); if(smBtn) smBtn.addEventListener('click', function(){ document.querySelectorAll('.extra-doc').forEach(function(el){el.classList.remove('sdoc-collapsed');}); var w=document.getElementById('showMoreWrap'); if(w) w.style.display='none'; });
    ['toggle-approved','toggle-done-list','toggle-optional'].forEach(function(id){ var btn=document.getElementById(id); if(!btn) return; var listId=id==='toggle-approved'?'approved-list':id==='toggle-done-list'?'done-list':'optional-list'; btn.addEventListener('click', function(){ var list=document.getElementById(listId); if(!list) return; var h=list.style.display==='none'; list.style.display=h?'block':'none'; this.textContent=h?'Gizle':'Göster'; }); });

    var hasUrgent=document.querySelectorAll('#docList .doc-card[data-req="1"][data-up="0"]').length>0;
    var curFilter=hasUrgent?'urgent':'all';
    if(!hasUrgent){ document.querySelectorAll('.sdoc .filter-pill').forEach(function(p){p.classList.remove('active')}); var ap=document.querySelector('.sdoc .filter-pill[data-filter="all"]'); if(ap) ap.classList.add('active'); }
    document.querySelectorAll('.sdoc .filter-pill').forEach(function(pill){ pill.addEventListener('click', function(){ document.querySelectorAll('.sdoc .filter-pill').forEach(function(p){p.classList.remove('active')}); this.classList.add('active'); curFilter=this.dataset.filter; applyFilters(); }); });

    var curCat='all';
    document.querySelectorAll('.sdoc .cat-tab').forEach(function(tab){ tab.addEventListener('click', function(){ document.querySelectorAll('.sdoc .cat-tab').forEach(function(t){t.classList.remove('active')}); this.classList.add('active'); curCat=this.dataset.cattab; if(curFilter==='urgent'){ var ws=false; document.querySelectorAll('#docList .doc-card').forEach(function(c){if(c.dataset.req==='1'&&c.dataset.up==='0'&&(curCat==='all'||(c.dataset.cat||'')===curCat))ws=true;}); if(!ws){curFilter='all';document.querySelectorAll('.sdoc .filter-pill').forEach(function(p){p.classList.remove('active')});var a=document.querySelector('.sdoc .filter-pill[data-filter="all"]');if(a)a.classList.add('active');} } applyFilters(); }); });

    function applyFilters(){ var cards=document.querySelectorAll('#docList .doc-card'); var grp={'missing-required':0,'uploaded':0,'other':0}; cards.forEach(function(c){ var req=c.dataset.req==='1',up=c.dataset.up==='1',cat=c.dataset.cat||'',show=true; if(curFilter==='urgent')show=req&&!up; else if(curFilter==='uploaded')show=up; else if(curFilter==='missing')show=!up; if(show&&curCat!=='all')show=cat===curCat; c.classList.toggle('sdoc-hidden',!show); if(show){if(req&&!up)grp['missing-required']++;else if(up)grp['uploaded']++;else grp['other']++;} }); document.querySelectorAll('#docList .doc-group-title').forEach(function(t){var g=t.dataset.grp;if(g)t.classList.toggle('sdoc-hidden',(grp[g]||0)===0);}); if(curFilter!=='all'||curCat!=='all'){document.querySelectorAll('.extra-doc').forEach(function(el){el.classList.remove('sdoc-collapsed');});var w=document.getElementById('showMoreWrap');if(w)w.style.display='none';} }
    applyFilters();

    var modal=document.getElementById('preview-modal'),container=document.getElementById('preview-container'),fname=document.getElementById('preview-filename'),rnote=document.getElementById('preview-review-note'),pstatus=document.getElementById('preview-status');
    function openPreview(id){ if(!modal)return; container.innerHTML='<div style="color:#94a3b8;font-size:13px;">Yükleniyor...</div>'; fname.textContent='';rnote.style.display='none';pstatus.textContent='';modal.style.display='flex'; fetch('/guest/registration/documents/'+id+'/preview',{headers:{'Accept':'application/json'}}).then(function(r){if(!r.ok)throw 0;return r.json()}).then(function(d){ fname.textContent=d.filename||'Belge'; var sm={approved:'Onaylandı',rejected:'Reddedildi',uploaded:'Yüklendi',pending:'Bekliyor'}; pstatus.textContent=sm[d.status]||d.status||''; pstatus.style.background=d.status==='approved'?'#dcfce7':d.status==='rejected'?'#fee2e2':'#fef3c7'; pstatus.style.color=d.status==='approved'?'#16a34a':d.status==='rejected'?'#dc2626':'#d97706'; if(d.mime==='application/pdf')container.innerHTML='<iframe src="'+d.url+'" style="width:100%;height:600px;border:none;border-radius:8px;"></iframe>'; else container.innerHTML='<img src="'+d.url+'" alt="'+(d.filename||'')+'" style="max-width:100%;max-height:70vh;border-radius:8px;object-fit:contain;">'; if(d.review_note){rnote.textContent='İnceleme notu: '+d.review_note;rnote.style.display='block';} }).catch(function(){container.innerHTML='<div style="color:#dc2626;font-size:13px;">Önizleme yüklenemedi.</div>';}); }
    function closePreview(){if(modal)modal.style.display='none';container.innerHTML='';rnote.style.display='none';}
    document.querySelectorAll('[data-preview]').forEach(function(b){b.addEventListener('click',function(){openPreview(this.dataset.preview)})});
    var cb=document.getElementById('preview-close-btn');if(cb)cb.addEventListener('click',closePreview);
    if(modal)modal.addEventListener('click',function(e){if(e.target===this)closePreview()});
    var dc=document.getElementById('docsCompleteClose');if(dc)dc.addEventListener('click',function(){var m=document.getElementById('docsCompleteModal');if(m)m.style.display='none'});
})();
</script>
@endsection
