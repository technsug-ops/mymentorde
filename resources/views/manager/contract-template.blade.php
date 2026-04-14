@extends('manager.layouts.app')

@section('title', 'Sözleşme Template Yönetimi')
@section('page_title', 'Sözleşme Template')

@push('head')
<style>
/* ── ct-* contract-template scoped ── */
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:1200px){ .mgr-kpi-strip { grid-template-columns:repeat(4,1fr); } }
@media(max-width:700px) { .mgr-kpi-strip { grid-template-columns:1fr 1fr; } }
.mgr-kpi { background:#fff; border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:10px 12px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:20px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-kpi-sub   { font-size:10px; color:var(--muted,#64748b); margin-top:2px; }

/* Top grid */
.ct-grid-top  { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
.ct-grid-main { display:grid; grid-template-columns:1.2fr .8fr; gap:12px; }
@media(max-width:1200px){ .ct-grid-top, .ct-grid-main { grid-template-columns:1fr; } }

.ct-row   { display:grid; grid-template-columns:1fr auto; gap:8px; }
.ct-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.ct-row-1 { display:grid; grid-template-columns:1fr; gap:8px; }
.ct-toolbar { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }

/* Student mini list */
.ct-mini-list { max-height:420px; overflow:auto; border:1px solid var(--border,#e2e8f0); border-radius:10px; background:#fff; margin-top:8px; }
.ct-mini-item { padding:8px 12px; border-bottom:1px solid var(--border,#e2e8f0); display:flex; align-items:center; gap:8px; }
.ct-mini-item:last-child { border-bottom:none; }
.ct-mini-item:hover { background:#f8fafc; }
.ct-mini-head { display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:4px; }
.ct-s-id { font-weight:700; font-size:12px; color:#0f172a; white-space:nowrap; }
.ct-mini-meta { flex:1; min-width:0; }
.ct-mini-name { font-size:12px; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ct-mini-sub { font-size:10px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Company settings accordion */
.ct-co-section { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; margin-bottom:8px; }
.ct-co-section:last-of-type { margin-bottom:0; }
.ct-co-toggle { display:flex; align-items:center; gap:8px; padding:9px 12px; background:#f8fafc; cursor:pointer; user-select:none; border:none; width:100%; text-align:left; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.04em; }
.ct-co-toggle:hover { background:#f1f5f9; }
.ct-co-toggle .ct-co-chev { margin-left:auto; font-size:10px; color:#94a3b8; transition:transform .2s; }
.ct-co-toggle[aria-expanded="true"] .ct-co-chev { transform:rotate(180deg); }
.ct-co-body { padding:0; display:none; }
.ct-co-body.open { display:block; }
/* Guide lines — each row in the accordion body */
.ct-co-body .ct-row-2,
.ct-co-body .ct-row-1 { margin-top:0 !important; padding:7px 12px; border-top:1px solid #f1f5f9; display:grid; gap:0; align-items:center; }
.ct-co-body .ct-row-2 { grid-template-columns:1fr 1fr; gap:1px; }
.ct-co-body .ct-row-2 input:first-child { border-right:1px solid #e2e8f0; border-radius:0; }
.ct-co-body .ct-row-2 input:last-child  { border-radius:0; }
.ct-co-body .ct-row-1 input { border-radius:0; }
.ct-co-body input { border:none; background:transparent; font-size:12px; color:#0f172a; padding:2px 6px; width:100%; outline:none; }
.ct-co-body input:focus { background:#eff6ff; }
.ct-co-body input::placeholder { color:#94a3b8; font-size:11px; }

/* Tab filters */
.ct-tab { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:7px; border:1px solid var(--border,#e2e8f0); font-size:12px; text-decoration:none; transition:all .15s; font-weight:600; color:#64748b; background:#fff; }
.ct-tab.active { background:#1e40af; border-color:#1e40af; color:#fff; font-weight:700; }
.ct-tab-count { background:rgba(0,0,0,.12); border-radius:999px; padding:1px 6px; font-size:11px; font-weight:700; min-width:18px; text-align:center; }
.ct-tab:not(.active) .ct-tab-count { background:#f1f5f9; color:#475569; }

/* Batch bar */
.ct-batch-bar { display:none; position:sticky; top:0; z-index:10; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 12px; margin-bottom:8px; align-items:center; gap:10px; flex-wrap:wrap; }

/* Buttons */
.ct-btn { border:1px solid var(--border,#e2e8f0); border-radius:8px; padding:7px 12px; font-weight:600; background:#fff; color:#0f172a; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:5px; font-size:12px; transition:all .15s; }
.ct-btn:hover { border-color:#1e40af; color:#1e40af; }
.ct-btn.primary { background:#1e40af; border-color:#1e40af; color:#fff; }
.ct-btn.primary:hover { background:#1d3a9a; }
.ct-btn.success { background:#f0fdf4; border-color:#86efac; color:#166534; }
.ct-btn.warn    { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
.ct-btn.danger  { background:#b91c1c; border-color:#b91c1c; color:#fff; }
.ct-btn.slim { padding:5px 10px; font-size:11px; }

/* Placeholders & preview */
.ct-preview-vars { border:1px solid var(--border,#e2e8f0); border-radius:10px; background:#fff; max-height:220px; overflow:auto; }
.ct-preview-vars table { width:100%; border-collapse:collapse; }
.ct-preview-vars th, .ct-preview-vars td { padding:7px 10px; border-bottom:1px solid #f1f5f9; font-size:12px; vertical-align:top; }
.ct-preview-vars th { text-align:left; color:#1e40af; width:35%; background:#f8fafc; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }

.ct-preview-box { white-space:pre-wrap; border:1px solid var(--border,#e2e8f0); border-radius:10px; background:#fff; padding:14px; min-height:440px; max-height:70vh; overflow:auto; font-size:13px; line-height:1.65; }

.ct-history-list { max-height:260px; overflow:auto; border:1px solid var(--border,#e2e8f0); border-radius:10px; background:#fff; }
.ct-history-item { border-bottom:1px solid var(--border,#e2e8f0); padding:8px 12px; font-size:12px; }
.ct-history-item:last-child { border-bottom:none; }

.ct-hint { border:1px dashed #bfdbfe; background:#eff6ff; border-radius:8px; padding:8px 12px; color:#1e40af; font-size:12px; }
.ct-section-title { margin:0 0 10px; font-size:15px; font-weight:700; color:#0f172a; text-transform:uppercase; letter-spacing:.04em; }

/* ── Selected Student Panel ── */
.ct-stu-panel { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:12px; overflow:hidden; }

/* Timeline stepper */
.ct-timeline { display:flex; align-items:center; gap:0; padding:18px 24px; background:#f8fafc; border-bottom:1px solid #e2e8f0; overflow-x:auto; }
.ct-tl-step { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.ct-tl-dot  { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; border:2px solid #e2e8f0; background:#fff; }
.ct-tl-dot.done    { background:#dcfce7; border-color:#22c55e; }
.ct-tl-dot.active  { background:#dbeafe; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.ct-tl-dot.error   { background:#fee2e2; border-color:#ef4444; }
.ct-tl-dot.pending { background:#f8fafc; border-color:#cbd5e1; color:#94a3b8; }
.ct-tl-label { font-size:11px; font-weight:600; }
.ct-tl-label span { display:block; font-size:10px; font-weight:400; color:#94a3b8; margin-top:1px; }
.ct-tl-conn { flex:1; min-width:20px; height:2px; background:#e2e8f0; margin:0 8px; }
.ct-tl-conn.done { background:#22c55e; }

/* Inner tabs */
.ct-inner-tabs { display:flex; gap:0; border-bottom:2px solid #e2e8f0; padding:0 12px; background:#f1f5f9; overflow-x:auto; scrollbar-width:none; }
.ct-inner-tabs::-webkit-scrollbar { display:none; }
.ct-inner-tab { padding:13px 16px; font-size:12px; font-weight:700; color:#64748b; cursor:pointer; border:none; background:none; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all .15s; display:flex; align-items:center; gap:6px; white-space:nowrap; letter-spacing:.01em; text-transform:uppercase; border-radius:6px 6px 0 0; }
.ct-inner-tab:hover { color:#1e40af; background:rgba(30,64,175,.06); }
.ct-inner-tab.active { color:#fff; border-bottom-color:#1e40af; background:#1e40af; box-shadow:0 -2px 8px rgba(30,64,175,.2); }
.ct-inner-tab-badge { background:#e2e8f0; color:#475569; border-radius:999px; padding:2px 7px; font-size:10px; font-weight:800; min-width:20px; text-align:center; }
.ct-inner-tab.active .ct-inner-tab-badge { background:rgba(255,255,255,.25); color:#fff; }
.ct-tab-sep { width:1px; background:#e2e8f0; margin:8px 4px; align-self:stretch; flex-shrink:0; }
.ct-tab-soon { opacity:.5; cursor:not-allowed !important; }
.ct-tab-soon:hover { color:#64748b !important; background:none !important; }
.ct-tab-soon-badge { background:#f59e0b22; color:#b45309; border:1px solid #fde68a; border-radius:999px; padding:1px 7px; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-left:4px; vertical-align:middle; }

.ct-tab-pane { padding:16px; display:none; min-height:320px; }
.ct-tab-pane.active { display:block; animation:tabFadeIn .18s ease-out; }
@keyframes tabFadeIn { from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);} }

/* ── Süreç & Aksiyon modern cards ── */
.sca-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:900px){ .sca-grid { grid-template-columns:1fr; } }
.sca-card { background:#fff; border:1px solid #e8edf4; border-radius:14px; overflow:hidden; margin-bottom:12px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.sca-card:last-child { margin-bottom:0; }
.sca-card-head { display:flex; align-items:center; gap:10px; padding:11px 16px; border-bottom:1px solid #f1f5f9; background:#f8fafc; }
.sca-card-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.sca-card-icon.blue { background:#dbeafe; }
.sca-card-icon.amber { background:#fef3c7; }
.sca-card-icon.green { background:#dcfce7; }
.sca-card-icon.red { background:#fee2e2; }
.sca-card-icon.purple { background:#ede9fe; }
.sca-card-title { font-size:11px; font-weight:800; color:#374151; text-transform:uppercase; letter-spacing:.06em; }
.sca-card-badge { margin-left:auto; }
.sca-card-body { padding:14px 16px; }

.sca-field-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
.sca-field-row.full { grid-template-columns:1fr; }

.sca-status { display:flex; align-items:center; gap:10px; border-radius:10px; padding:12px 14px; font-size:13px; font-weight:600; line-height:1.4; }
.sca-status.ok { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.sca-status.warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.sca-status.info { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
.sca-status-icon { font-size:20px; flex-shrink:0; }

.sca-decision-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px; }
.sca-btn-approve { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; border:none; border-radius:10px; padding:11px 14px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 4px 12px rgba(34,197,94,.25); transition:all .15s; }
.sca-btn-approve:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(34,197,94,.35); }
.sca-btn-reject { background:linear-gradient(135deg,#ef4444,#b91c1c); color:#fff; border:none; border-radius:10px; padding:11px 14px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 4px 12px rgba(239,68,68,.2); transition:all .15s; }
.sca-btn-reject:hover { transform:translateY(-1px); }
.sca-btn-start { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border:none; border-radius:10px; padding:10px 16px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(245,158,11,.2); transition:all .15s; }
.sca-btn-start:hover { transform:translateY(-1px); }
.sca-btn-refresh { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:10px; padding:9px 14px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; margin-top:8px; }
.sca-btn-refresh:hover { background:#dbeafe; }

.sca-reopen-card { background:linear-gradient(135deg,#f5f3ff,#faf5ff); border:1px solid #c4b5fd; border-radius:14px; overflow:hidden; margin-bottom:12px; }
.sca-reopen-head { padding:12px 16px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #e9d5ff; }
.sca-reopen-title { font-size:13px; font-weight:800; color:#6d28d9; }
.sca-reopen-body { padding:14px 16px; }
.sca-reason-box { background:#ede9fe; border:1px solid #ddd6fe; border-radius:8px; padding:10px 12px; font-size:12px; color:#5b21b6; margin-bottom:12px; line-height:1.5; }
.sca-reopen-btns { display:flex; gap:8px; flex-wrap:wrap; }
.sca-btn-accept { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; border:none; border-radius:9px; padding:9px 14px; font-size:13px; font-weight:700; cursor:pointer; }
.sca-btn-deny  { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; border-radius:9px; padding:9px 14px; font-size:13px; font-weight:700; cursor:pointer; }

.sca-cancel-toggle { display:flex; align-items:center; gap:10px; padding:12px 16px; cursor:pointer; user-select:none; background:#fff5f5; border:1px solid #fecaca; border-radius:14px; font-size:13px; font-weight:700; color:#9b1c1c; }
.sca-cancel-toggle[data-open="true"] { border-radius:14px 14px 0 0; border-bottom-color:#fecaca; }
.sca-cancel-body { border:1px solid #fecaca; border-top:none; border-radius:0 0 14px 14px; padding:14px 16px; background:#fffafa; display:none; }
.sca-cancel-body.open { display:block; }
.sca-fin-grid { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.sca-fin-item {}
.sca-fin-label { font-size:10px; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.04em; }
.sca-fin-val { font-size:16px; font-weight:900; color:#92400e; }
.sca-cancel-cat { border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:8px; }
.sca-cancel-cat-head { background:#f9fafb; padding:7px 12px; font-size:11px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e5e7eb; }
.sca-cancel-reason-row { display:flex; align-items:flex-start; gap:8px; padding:5px 12px; border-bottom:1px solid #f3f4f6; font-size:12px; color:#374151; cursor:pointer; }
.sca-cancel-reason-row:last-child { border-bottom:none; }

/* Student header bar */
.ct-stu-header { display:flex; align-items:center; gap:10px; padding:12px 16px; border-bottom:1px solid #e2e8f0; flex-wrap:wrap; }
.ct-stu-name { font-size:14px; font-weight:700; color:#0f172a; }
.ct-stu-meta { font-size:12px; color:#64748b; }
</style>
@endpush

@section('content')

{{-- ── Header + KPI ── --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
    <div>
        <div style="font-size:var(--tx-sm);font-weight:700;color:#0f172a;">Sözleşme Template Yönetimi</div>
        <div style="font-size:var(--tx-xs);color:#64748b;">Aday Öğrenci talebinden otomatik ya da manuel sözleşme başlatma</div>
    </div>
    <a href="/config" class="ct-btn">← Config'a Dön</a>
</div>

<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Aktif Template</div>
        <div class="mgr-kpi-val">#{{ $template->id }}</div>
        <div class="mgr-kpi-sub">{{ $template->code }} / v{{ $template->version }}</div>
    </div>
    <div class="mgr-kpi" style="{{ (int)($pendingManagerCount??0) > 0 ? 'border-top-color:#d97706;' : '' }}">
        <div class="mgr-kpi-label">Bekliyor</div>
        <div class="mgr-kpi-val" style="{{ (int)($pendingManagerCount??0) > 0 ? 'color:#b45309;' : '' }}">{{ (int)($pendingManagerCount??0) }}</div>
        <div class="mgr-kpi-sub">pending_manager</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Gönderildi</div>
        <div class="mgr-kpi-val" style="color:#1e40af;">{{ (int)($requestedCount??0) }}</div>
        <div class="mgr-kpi-sub">requested</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#0891b2;">
        <div class="mgr-kpi-label">İmzalı</div>
        <div class="mgr-kpi-val" style="color:#0e7490;">{{ (int)($signedUploadedCount??0) }}</div>
        <div class="mgr-kpi-sub">signed_uploaded</div>
    </div>
    <div class="mgr-kpi" style="{{ (int)($reopenRequestedCount??0) > 0 ? 'border-top-color:#7c3aed;' : '' }}">
        <div class="mgr-kpi-label">Yeniden Talep</div>
        <div class="mgr-kpi-val" style="{{ (int)($reopenRequestedCount??0) > 0 ? 'color:#6d28d9;' : '' }}">{{ (int)($reopenRequestedCount??0) }}</div>
        <div class="mgr-kpi-sub">reopen_requested</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#16a34a;">
        <div class="mgr-kpi-label">Tüm Onaylı</div>
        <div class="mgr-kpi-val" style="color:#15803d;">{{ (int)($totalConvertedCount??0) }}</div>
        <div class="mgr-kpi-sub">converted</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Aktif Firma</div>
        <div class="mgr-kpi-val" style="font-size:var(--tx-sm);font-weight:700;">{{ $company?->name ?? '–' }}</div>
        <div class="mgr-kpi-sub">{{ $company?->code ?? '–' }}</div>
    </div>
</div>

{{-- ── Top Grid: Search | Company Settings ── --}}
<div class="ct-grid-top">
    {{-- Left: Search + list --}}
    <section class="panel">
        <div style="font-size:var(--tx-xs);font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Öğrenci Ara</div>
        <form method="get" action="{{ route('manager.contract-template.show') }}" class="ct-row">
            <input name="q" value="{{ $searchQuery ?? '' }}" placeholder="ID / isim / email (ör: BCS-26-02)">
            <button class="ct-btn primary" type="submit">Ara</button>
        </form>

        @php
            $sf = $statusFilter ?? 'active_contracts';
            $activeFlow = ($pendingManagerCount??0) + ($requestedCount??0) + ($signedUploadedCount??0) + ($reopenRequestedCount??0);
            $tabDefs = [
                ['key' => 'active_contracts', 'label' => 'Aktif Akış',    'count' => $activeFlow],
                ['key' => 'pending_manager',  'label' => 'Bekliyor',      'count' => $pendingManagerCount??0],
                ['key' => 'requested',        'label' => 'Gönderildi',    'count' => $requestedCount??0],
                ['key' => 'signed_uploaded',  'label' => 'İmzalı',        'count' => $signedUploadedCount??0],
                ['key' => 'reopen_requested', 'label' => 'Yeniden Talep', 'count' => $reopenRequestedCount??0],
                ['key' => 'approved',         'label' => 'Onaylı',        'count' => null],
                ['key' => 'cancelled',        'label' => 'İptal',         'count' => null],
                ['key' => 'all',              'label' => 'Tümü',          'count' => null],
            ];
        @endphp
        <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:10px;">
            @foreach($tabDefs as $tab)
            <a href="{{ route('manager.contract-template.show', ['status' => $tab['key']]) }}"
               class="ct-tab {{ $sf === $tab['key'] ? 'active' : '' }}">
                {{ $tab['label'] }}
                @if($tab['count'] !== null)
                    <span class="ct-tab-count">{{ $tab['count'] }}</span>
                @endif
            </a>
            @endforeach
        </div>

        @php
            $statusBadgeCls = [
                'pending_manager'  => ['cls' => 'warn',    'label' => 'Bekliyor'],
                'requested'        => ['cls' => 'info',    'label' => 'Gönderildi'],
                'signed_uploaded'  => ['cls' => 'ok',      'label' => 'İmzalı'],
                'approved'         => ['cls' => 'ok',      'label' => 'Onaylı'],
                'rejected'         => ['cls' => 'danger',  'label' => 'Reddedildi'],
                'cancelled'        => ['cls' => 'danger',  'label' => 'İptal'],
                'reopen_requested' => ['cls' => 'pending', 'label' => 'Yeniden Talep'],
                'not_requested'    => ['cls' => 'pending', 'label' => 'Talepsiz'],
            ];
        @endphp

        {{-- Toplu İşlem Çubuğu --}}
        <div id="batchBar" class="ct-batch-bar">
            <span id="batchCount" style="font-size:var(--tx-xs);font-weight:700;color:#1e40af;">0 seçildi</span>
            <input type="text" id="batchNote" placeholder="Not (opsiyonel)" maxlength="500"
                   style="flex:1;min-width:120px;padding:5px 9px;border:1px solid #bfdbfe;border-radius:6px;font-size:var(--tx-xs);">
            <button type="button" class="ct-btn success" id="batchApproveBtn">✓ Toplu Onayla</button>
            <button type="button" class="ct-btn danger" id="batchRejectBtn">✕ Toplu Reddet</button>
        </div>
        <div id="batchFeedback" style="margin-bottom:8px;display:none;padding:8px 12px;border-radius:6px;font-size:var(--tx-xs);"></div>

        <div class="ct-mini-list">
            @forelse($students as $stu)
                @php
                    $cStatus = (string) ($stu->contract_status ?? 'not_requested');
                    $bc = $statusBadgeCls[$cStatus] ?? ['cls' => 'pending', 'label' => $cStatus];
                    $isBatchable = $cStatus === 'signed_uploaded';
                    $isSelected  = isset($selectedGuest) && $selectedGuest->id === $stu->id;
                @endphp
                <div class="ct-mini-item {{ $isSelected ? 'ct-mini-item--active' : '' }}" data-guest-id="{{ $stu->id }}" data-batch="{{ $isBatchable ? '1' : '0' }}">
                    @if($isBatchable)
                        <input type="checkbox" class="batch-chk" data-id="{{ $stu->id }}"
                               style="width:13px;height:13px;accent-color:#1e40af;flex-shrink:0;cursor:pointer;" title="Toplu işlem için seç">
                    @endif
                    <div class="ct-mini-meta">
                        <div class="ct-mini-name">{{ $stu->converted_student_id ?: 'GST-'.$stu->id }} — {{ trim(($stu->first_name ?? '').' '.($stu->last_name ?? '')) }}</div>
                        <div class="ct-mini-sub">{{ $stu->email }} · {{ $stu->selected_package_title ?: '–' }}{{ $stu->selected_package_price ? ' / '.$stu->selected_package_price : '' }}</div>
                    </div>
                    <span class="badge {{ $bc['cls'] }}" style="flex-shrink:0;">{{ $bc['label'] }}</span>
                    <a class="ct-btn slim" style="flex-shrink:0;" href="{{ route('manager.contract-template.show', ['q' => $searchQuery, 'status' => $statusFilter, 'guest_id' => $stu->id]) }}">Seç →</a>
                </div>
            @empty
                <div style="padding:16px;text-align:center;color:#94a3b8;font-size:var(--tx-xs);">Bu filtrede kayıt yok.</div>
            @endforelse
        </div>
    </section>

    {{-- Right: Company Settings --}}
    <section class="panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <div style="font-size:var(--tx-sm);font-weight:800;color:#0f172a;">{{ $company?->name ?? '—' }}</div>
                <div style="font-size:var(--tx-xs);color:#64748b;">{{ $companySettings['advisor_company_address'] ?? 'Adres girilmemiş' }}</div>
            </div>
            <span class="badge ok" style="flex-shrink:0;">Aktif Firma</span>
        </div>

        <form method="post" action="{{ route('manager.contract-template.company-settings') }}">
            @csrf

            {{-- Firma Kimliği -- always open --}}
            <div class="ct-co-section">
                <button type="button" class="ct-co-toggle" onclick="toggleCoSection(this)" aria-expanded="true">
                    🏢 Firma Kimliği <span class="ct-co-chev">▼</span>
                </button>
                <div class="ct-co-body open">
                    <div class="ct-row-2">
                        <input name="company_name" value="{{ old('company_name', (string) ($company?->name ?? '')) }}" placeholder="Firma ünvanı">
                        <input name="company_code" value="{{ old('company_code', (string) ($company?->code ?? '')) }}" placeholder="Firma kodu">
                    </div>
                    <div class="ct-row-1">
                        <input name="advisor_company_address" value="{{ old('advisor_company_address', (string) ($companySettings['advisor_company_address'] ?? '')) }}" placeholder="Firma adresi">
                    </div>
                    <div class="ct-row-2">
                        <input name="advisor_authorized_person" value="{{ old('advisor_authorized_person', (string) ($companySettings['advisor_authorized_person'] ?? '')) }}" placeholder="Yetkili kişi">
                        <input name="advisor_tax_info" value="{{ old('advisor_tax_info', (string) ($companySettings['advisor_tax_info'] ?? '')) }}" placeholder="Vergi VD/VN">
                    </div>
                    <div class="ct-row-2">
                        <input name="advisor_phone" value="{{ old('advisor_phone', (string) ($companySettings['advisor_phone'] ?? '')) }}" placeholder="Telefon">
                        <input name="advisor_email" value="{{ old('advisor_email', (string) ($companySettings['advisor_email'] ?? '')) }}" placeholder="E-posta">
                    </div>
                    <div class="ct-row-2">
                        <input name="advisor_website" value="{{ old('advisor_website', (string) ($companySettings['advisor_website'] ?? '')) }}" placeholder="Web sitesi">
                        <input name="jurisdiction_city" value="{{ old('jurisdiction_city', (string) ($companySettings['jurisdiction_city'] ?? 'Istanbul')) }}" placeholder="Yetkili mahkeme">
                    </div>
                    <div class="ct-row-2">
                        <input name="tax_status" value="{{ old('tax_status', (string) ($companySettings['tax_status'] ?? 'hariç')) }}" placeholder="KDV (dahil/hariç)">
                        <input name="max_university_count" value="{{ old('max_university_count', (string) ($companySettings['max_university_count'] ?? '5')) }}" placeholder="Maks. üniversite">
                    </div>
                </div>
            </div>

            {{-- Taksit --}}
            <div class="ct-co-section">
                <button type="button" class="ct-co-toggle" onclick="toggleCoSection(this)" aria-expanded="false">
                    💳 Ödeme & Taksit <span class="ct-co-chev">▼</span>
                </button>
                <div class="ct-co-body">
                    <div class="ct-row-2">
                        <input name="installment_1_amount" value="{{ old('installment_1_amount', (string) ($companySettings['installment_1_amount'] ?? '')) }}" placeholder="1. Taksit">
                        <input name="payment_plan" value="{{ old('payment_plan', (string) ($companySettings['payment_plan'] ?? '')) }}" placeholder="Ödeme planı notu">
                    </div>
                    <div class="ct-row-2">
                        <input name="installment_2_condition" value="{{ old('installment_2_condition', (string) ($companySettings['installment_2_condition'] ?? '')) }}" placeholder="2. Taksit koşulu">
                        <input name="installment_2_amount" value="{{ old('installment_2_amount', (string) ($companySettings['installment_2_amount'] ?? '')) }}" placeholder="2. Taksit tutarı">
                    </div>
                    <div class="ct-row-2">
                        <input name="installment_3_condition" value="{{ old('installment_3_condition', (string) ($companySettings['installment_3_condition'] ?? '')) }}" placeholder="3. Taksit koşulu">
                        <input name="installment_3_amount" value="{{ old('installment_3_amount', (string) ($companySettings['installment_3_amount'] ?? '')) }}" placeholder="3. Taksit tutarı">
                    </div>
                </div>
            </div>

            {{-- Banka --}}
            <div class="ct-co-section">
                <button type="button" class="ct-co-toggle" onclick="toggleCoSection(this)" aria-expanded="false">
                    🏦 Banka Bilgileri <span class="ct-co-chev">▼</span>
                </button>
                <div class="ct-co-body">
                    <div class="ct-row-2">
                        <input name="bank_name" value="{{ old('bank_name', (string) ($companySettings['bank_name'] ?? '')) }}" placeholder="Banka adı">
                        <input name="bank_branch" value="{{ old('bank_branch', (string) ($companySettings['bank_branch'] ?? '')) }}" placeholder="Şube">
                    </div>
                    <div class="ct-row-1">
                        <input name="bank_iban" value="{{ old('bank_iban', (string) ($companySettings['bank_iban'] ?? '')) }}" placeholder="IBAN">
                    </div>
                </div>
            </div>

            <div class="ct-toolbar" style="margin-top:10px;">
                <button class="ct-btn primary" type="submit">💾 Firma Bilgilerini Kaydet</button>
            </div>
        </form>
    </section>
</div>

{{-- ── Selected Student Panel (full-width, tabbed) ── --}}
@if($selectedGuest)
@php
    $selStatus = (string) ($selectedGuest->contract_status ?? 'not_requested');
    $selBc     = $statusBadgeCls[$selStatus] ?? ['cls'=>'pending','label'=>$selStatus];

    // Timeline step calculation
    $tlSteps = [
        ['key'=>'not_requested', 'label'=>'Henüz Talep Yok',   'icon'=>'📋', 'sub'=>'Sözleşme başlatılmadı'],
        ['key'=>'pending',       'label'=>'Sözleşme Gönderildi','icon'=>'📧', 'sub'=>'Öğrenciye gönderildi'],
        ['key'=>'signed',        'label'=>'İmzalı Alındı',      'icon'=>'📤', 'sub'=>'İnceleme bekliyor'],
        ['key'=>'decision',      'label'=>'Karar Verildi',       'icon'=>'✅', 'sub'=>'Onay / Red'],
    ];
    $tlActive = match($selStatus) {
        'not_requested','pending_manager' => 0,
        'requested','rejected'            => 1,
        'signed_uploaded','reopen_requested' => 2,
        'approved','cancelled'            => 3,
        default => 0,
    };
    $isError = in_array($selStatus, ['rejected','cancelled'], true);

    // Advisor
    $guestContractStatusForDecision = $selStatus;
    $signedFileExists = trim((string) ($selectedGuest->contract_signed_file_path ?? '')) !== '';
    $canDecide        = in_array($guestContractStatusForDecision, ['signed_uploaded', 'requested'], true);
    $refreshAllowed   = in_array($selStatus, ['not_requested','pending_manager','requested','rejected'], true);
    $allowContractUpdate = in_array($selStatus, ['requested','signed_uploaded','rejected'], true);
    $guestContractStatus = $selStatus;
    $canCancel        = !in_array($guestContractStatus, ['not_requested','cancelled','reopen_requested'], true);
    $cancelReasons    = $cancelReasons ?? [];

    // Tab event count
    $eventCount = ($contractEvents ?? collect())->count();
@endphp

<div class="ct-stu-panel">

    {{-- Student header bar --}}
    <div class="ct-stu-header">
        <div>
            <div class="ct-stu-name">{{ $selectedGuest->converted_student_id ?: 'GST-'.$selectedGuest->id }} — {{ trim(($selectedGuest->first_name ?? '').' '.($selectedGuest->last_name ?? '')) }}</div>
            <div class="ct-stu-meta">{{ $selectedGuest->email }} · {{ $selectedGuest->application_type ?: '–' }} · {{ $selectedGuest->application_country ?: '–' }}</div>
        </div>
        <span class="badge {{ $selBc['cls'] }}" style="margin-left:auto;">{{ $selBc['label'] }}</span>
        @if($signedFileExists)
            <a class="ct-btn slim success" href="{{ route('manager.contract-template.signed-file', $selectedGuest->id) }}" target="_blank">📎 İmzalı Dosya</a>
        @endif
        <a class="ct-btn slim primary" href="{{ route('manager.contract-template.print', $selectedGuest->id) }}" target="_blank">🖨 Yazdır / PDF</a>
    </div>

    {{-- Timeline --}}
    <div class="ct-timeline">
        @foreach($tlSteps as $i => $step)
            @php
                $isDone   = $i < $tlActive;
                $isActive = $i === $tlActive;
                $dotCls   = $isDone ? 'done' : ($isActive ? ($isError && $i === 3 ? 'error' : 'active') : 'pending');
                $connDone = $i < $tlActive;
            @endphp
            <div class="ct-tl-step">
                <div class="ct-tl-dot {{ $dotCls }}">
                    @if($isDone) ✓
                    @elseif($isActive && $isError && $i===3) ✕
                    @else {{ $step['icon'] }}
                    @endif
                </div>
                <div class="ct-tl-label" style="color:{{ $isDone ? '#166534' : ($isActive ? '#1e40af' : '#94a3b8') }};">
                    {{ $step['label'] }}
                    <span>{{ $step['sub'] }}</span>
                </div>
            </div>
            @if(!$loop->last)
                <div class="ct-tl-conn {{ $connDone ? 'done' : '' }}"></div>
            @endif
        @endforeach

        {{-- Special: reopen / reject badges --}}
        @if($selStatus === 'rejected')
            <div style="margin-left:12px;"><span class="badge danger">Reddedildi — Tekrar Yükleme Bekleniyor</span></div>
        @elseif($selStatus === 'reopen_requested')
            <div style="margin-left:12px;"><span class="badge pending">Yeniden Değerlendirme Talebi</span></div>
        @elseif($selStatus === 'cancelled')
            <div style="margin-left:12px;"><span class="badge danger">İptal Edildi</span></div>
        @endif
    </div>

    {{-- Hizli Onay/Red (timeline altinda, her zaman gorunur) --}}
    @if($canDecide)
    <div style="background:var(--u-card);border:2px solid {{ $guestContractStatusForDecision === 'signed_uploaded' ? '#22c55e' : '#3b82f6' }};border-radius:12px;padding:16px 18px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <span style="font-size:var(--tx-lg);">{{ $guestContractStatusForDecision === 'signed_uploaded' ? '✅' : '📋' }}</span>
            <div>
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">
                    {{ $guestContractStatusForDecision === 'signed_uploaded' ? 'Imzali sozlesme yuklendi' : 'Sozlesme gonderildi' }}
                    @if($guestContractStatusForDecision === 'rejected') <span class="badge danger" style="font-size:10px;">Daha once reddedildi</span> @endif
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;">Onay veya red karari verebilirsiniz. Not/sebep zorunludur.</div>
            </div>
        </div>
        <form method="post" action="{{ route('manager.contract-template.decision') }}" id="quickDecisionForm">
            @csrf
            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:3px;">Red Sebebi (reddetme icin)</label>
                    <select id="qd_reason" style="width:100%;padding:7px 10px;border:1px solid var(--u-line);border-radius:8px;font-size:var(--tx-xs);font-family:inherit;">
                        <option value="">-- Sebep secin --</option>
                        <option value="Imza eksik veya okunamiyor">Imza eksik veya okunamiyor</option>
                        <option value="Yanlis dosya yuklendi">Yanlis dosya yuklendi</option>
                        <option value="Belge kalitesi yetersiz">Belge kalitesi yetersiz</option>
                        <option value="Paket/fiyat uyusmazligi">Paket/fiyat uyusmazligi</option>
                        <option value="Ogrenci bilgileri hatali">Ogrenci bilgileri hatali</option>
                        <option value="Sozlesme sartlari degisti">Sozlesme sartlari degisti</option>
                        <option value="Diger">Diger</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);display:block;margin-bottom:3px;">Not / Aciklama <span style="color:#dc2626;">*</span></label>
                    <input name="note" id="qd_note" placeholder="Karar sebebini yazin..." required
                           style="width:100%;padding:7px 10px;border:1px solid var(--u-line);border-radius:8px;font-size:var(--tx-xs);font-family:inherit;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="submit" name="decision" value="approve"
                        onclick="if(!document.getElementById('qd_note').value.trim()){alert('Onay sebebi/notu zorunludur.');return false;}return confirm('Sozlesme onaylanacak. Devam?')"
                        style="padding:9px 22px;border-radius:8px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;font-size:var(--tx-xs);font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;">
                    ✓ Onayla
                </button>
                <button type="submit" name="decision" value="reject"
                        onclick="var r=document.getElementById('qd_reason').value;var n=document.getElementById('qd_note');if(!r&&!n.value.trim()){alert('Red sebebi veya not zorunludur.');return false;}if(r&&!n.value.trim()){n.value=r;}return confirm('Sozlesme reddedilecek. Devam?')"
                        style="padding:9px 22px;border-radius:8px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;border:none;font-size:var(--tx-xs);font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;">
                    ✕ Reddet
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Inner Tabs --}}
    <div class="ct-inner-tabs">
        <button class="ct-inner-tab active" onclick="ctSwitchTab('surec',this)">
            ⚡ Süreç & Aksiyon
        </button>
        <button class="ct-inner-tab" onclick="ctSwitchTab('onizleme',this)">
            📄 Önizleme
            @if($contractPreview) <span class="ct-inner-tab-badge">✓</span> @endif
        </button>
        <button class="ct-inner-tab" onclick="ctSwitchTab('gecmis',this)">
            📋 Geçmiş
            @if($eventCount > 0) <span class="ct-inner-tab-badge">{{ $eventCount }}</span> @endif
        </button>
        <div class="ct-tab-sep"></div>
        <button class="ct-inner-tab" onclick="ctSwitchTab('template',this)">
            ✏️ Template Düzenle
        </button>
        <button class="ct-inner-tab" onclick="ctSwitchTab('son-template',this)">
            📚 Son Template'ler
        </button>
        <div class="ct-tab-sep"></div>
        <button class="ct-inner-tab ct-tab-soon" disabled title="Yakında eklenecek">
            🤝 Dealer Sözleşmeleri <span class="ct-tab-soon-badge">Yakında</span>
        </button>
        <button class="ct-inner-tab ct-tab-soon" disabled title="Yakında eklenecek">
            👔 Çalışan Sözleşmeleri <span class="ct-tab-soon-badge">Yakında</span>
        </button>
    </div>

    {{-- ── Tab: Süreç & Aksiyon ── --}}
    <div class="ct-tab-pane active" id="ctTab-surec">
        <div class="sca-grid">

            {{-- ── Sol Kolon ── --}}
            <div>

                {{-- Servis Güncelleme --}}
                <div class="sca-card">
                    <div class="sca-card-head">
                        <div class="sca-card-icon blue">📦</div>
                        <div class="sca-card-title">Servis Güncelleme</div>
                    </div>
                    <div class="sca-card-body">
                        <form method="post" action="{{ route('manager.contract-template.student-services') }}">
                            @csrf
                            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                            <div class="sca-field-row">
                                <input name="selected_package_title" value="{{ old('selected_package_title', (string)($selectedGuest->selected_package_title ?? '')) }}" placeholder="Paket adı">
                                <input name="selected_package_price" value="{{ old('selected_package_price', (string)($selectedGuest->selected_package_price ?? '')) }}" placeholder="Paket ücreti (€)">
                            </div>
                            <div class="sca-field-row full" style="margin-top:8px;">
                                <input name="selected_extra_services" value="{{ old('selected_extra_services', collect(is_array($selectedGuest->selected_extra_services) ? $selectedGuest->selected_extra_services : [])->map(fn($x)=>$x['title'] ?? '')->filter()->implode(', ')) }}" placeholder="Ek servisler (virgülle ayır)">
                            </div>
                            <div style="margin-top:10px;">
                                <button class="ct-btn primary" type="submit">💾 Servisleri Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Sözleşme Başlatma --}}
                <div class="sca-card">
                    <div class="sca-card-head">
                        <div class="sca-card-icon amber">📄</div>
                        <div class="sca-card-title">Sözleşme Başlatma</div>
                    </div>
                    <div class="sca-card-body">
                        <form method="post" action="{{ route('manager.contract-template.start-contract') }}">
                            @csrf
                            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                            <button type="submit" class="sca-btn-start">📄 Sözleşme Başlat</button>
                        </form>
                        @if($refreshAllowed && trim((string)($selectedGuest->contract_snapshot_text ?? '')) !== '')
                        <form method="post" action="{{ route('manager.contract-template.refresh-snapshot') }}"
                              onsubmit="return confirm('Taslak yeniden oluşturulacak. Onaylıyor musunuz?')">
                            @csrf
                            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                            <button type="submit" class="sca-btn-refresh">↻ Taslağı Güncelle</button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Karar Uygula --}}
                <div class="sca-card">
                    <div class="sca-card-head">
                        <div class="sca-card-icon green">⚖️</div>
                        <div class="sca-card-title">Sözleşme Kararı</div>
                        @if($canDecide)<span class="sca-card-badge"><span class="badge info">Karar Bekliyor</span></span>@endif
                    </div>
                    <div class="sca-card-body">
                        @if($canDecide)
                        <div class="sca-status {{ $guestContractStatusForDecision === 'signed_uploaded' ? 'ok' : 'info' }}" style="margin-bottom:12px;">
                            <span class="sca-status-icon">{{ $guestContractStatusForDecision === 'signed_uploaded' ? '✅' : '📋' }}</span>
                            <span>{{ $guestContractStatusForDecision === 'signed_uploaded' ? 'İmzalı sözleşme yüklendi — onay kararı verebilirsiniz.' : 'Sözleşme gönderildi — imza beklenmeden de onay/red kararı verebilirsiniz.' }}</span>
                        </div>
                        <form method="post" action="{{ route('manager.contract-template.decision') }}">
                            @csrf
                            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                            <div class="sca-field-row full" style="margin-bottom:10px;">
                                <input name="note" placeholder="Karar notu (opsiyonel)">
                            </div>
                            <div class="sca-decision-grid">
                                <button type="submit" name="decision" value="approve" class="sca-btn-approve"
                                        onclick="return confirm('Onay verilirse öğrenci Öğrenci paneline geçer. Devam?')">
                                    ✓ Onayla
                                </button>
                                <button type="submit" name="decision" value="reject" class="sca-btn-reject"
                                        onclick="return confirm('Sözleşme reddedilecek ve yeniden yükleme istenecek. Devam?')">
                                    ✕ Reddet
                                </button>
                            </div>
                        </form>
                        @elseif($guestContractStatusForDecision === 'approved')
                        <div class="sca-status ok">
                            <span class="sca-status-icon">🎓</span>
                            <span>Sözleşme onaylandı — öğrenci Öğrenci paneline geçirildi.</span>
                        </div>
                        @elseif(in_array($guestContractStatusForDecision, ['requested','pending_manager'], true))
                        <div class="sca-status warn">
                            <span class="sca-status-icon">⏳</span>
                            <span>Öğrenci henüz imzalı sözleşmeyi yüklemedi.</span>
                        </div>
                        @else
                        <div class="sca-status info">
                            <span class="sca-status-icon">📋</span>
                            <span>Sözleşme sürecini başlatarak imzalı dosya bekleniyor.</span>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- ── Sağ Kolon ── --}}
            <div>

                {{-- Yeniden Değerlendirme --}}
                @if($guestContractStatus === 'reopen_requested')
                <div class="sca-reopen-card" style="margin-bottom:12px;">
                    <div class="sca-reopen-head">
                        <span style="font-size:var(--tx-xl);">🔄</span>
                        <div class="sca-reopen-title">Yeniden Değerlendirme Talebi</div>
                        <span class="badge pending" style="margin-left:auto;">Yanıt Bekliyor</span>
                    </div>
                    <div class="sca-reopen-body">
                        <div style="font-size:var(--tx-sm);color:#6d28d9;margin-bottom:8px;">
                            <strong>{{ $selectedGuest->first_name }} {{ $selectedGuest->last_name }}</strong> yeniden değerlendirme talep etti.
                            @if($selectedGuest->reopen_requested_at)
                                <span style="font-size:var(--tx-xs);font-weight:400;color:#7c3aed;margin-left:6px;">{{ \Carbon\Carbon::parse($selectedGuest->reopen_requested_at)->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        @if($selectedGuest->reopen_reason)
                        <div class="sca-reason-box">
                            <strong>Gerekçe:</strong> {{ $selectedGuest->reopen_reason }}
                        </div>
                        @endif
                        <div class="sca-reopen-btns">
                            <form method="POST" action="{{ route('manager.contract-template.reopen-approve') }}"
                                  onsubmit="return confirm('Talebi kabul edip süreci sıfırlamak istediğinizden emin misiniz?');">
                                @csrf
                                <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                                <button type="submit" class="sca-btn-accept">✓ Kabul Et — Sıfırla</button>
                            </form>
                            <form method="POST" action="{{ route('manager.contract-template.reopen-reject') }}"
                                  onsubmit="return confirm('Talebi reddetmek istediğinizden emin misiniz?');">
                                @csrf
                                <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <input type="text" name="reject_note" maxlength="500" placeholder="Red notu…" style="padding:7px 10px;border:1px solid #ddd6fe;border-radius:8px;font-size:var(--tx-xs);width:160px;">
                                    <button type="submit" class="sca-btn-deny">✕ Reddet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sözleşme İptal --}}
                <div>
                    <div class="sca-cancel-toggle" id="cancelToggle" onclick="toggleCancel()" data-open="false">
                        <span style="font-size:var(--tx-lg);">⛔</span>
                        <span>Sözleşmeyi İptal Et</span>
                        @if(!$canCancel)
                            <span style="font-size:var(--tx-xs);font-weight:400;margin-left:4px;opacity:.7;">({{ $guestContractStatus === 'cancelled' ? 'Zaten iptal edildi' : 'Aktif sözleşme yok' }})</span>
                        @endif
                        <span style="margin-left:auto;font-size:var(--tx-xs);opacity:.6;" id="cancelChevron">▼</span>
                    </div>
                    <div class="sca-cancel-body" id="cancelBody">

                        @if(in_array($guestContractStatus, ['cancelled'], true))
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 14px;margin-bottom:12px;font-size:var(--tx-xs);color:#9b1c1c;">
                            <div style="font-weight:700;margin-bottom:6px;">Bu sözleşme daha önce iptal edildi.</div>
                            <div>Kategori: <strong>{{ $selectedGuest->contract_cancel_category ?? '-' }}</strong></div>
                            <div>Neden: <strong>{{ $selectedGuest->contract_cancel_reason_code ?? '-' }}</strong></div>
                            <div>İptal eden: {{ $selectedGuest->contract_cancelled_by ?? '-' }} · {{ $selectedGuest->contract_cancelled_at ? \Carbon\Carbon::parse($selectedGuest->contract_cancelled_at)->format('d.m.Y H:i') : '-' }}</div>
                            @if($selectedGuest->contract_cancel_note)
                                <em style="display:block;margin-top:4px;color:#7f1d1d;">{{ $selectedGuest->contract_cancel_note }}</em>
                            @endif
                            <form method="POST" action="{{ route('manager.contract-template.reset') }}" style="margin-top:10px;padding-top:10px;border-top:1px solid #fecaca;"
                                  onsubmit="return confirm('Süreci sıfırlamak istediğinizden emin misiniz?');">
                                @csrf
                                <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                                <button type="submit" class="ct-btn slim">↺ Süreci Sıfırla</button>
                            </form>
                        </div>
                        @endif

                        @if($canCancel)
                        @php
                            $pkgPrice   = (float) ($selectedGuest->selected_package_price ?? 0);
                            $lsWeight   = ['new'=>0.10,'contacted'=>0.20,'verified'=>0.30,'follow_up'=>0.35,'interested'=>0.45,'qualified'=>0.55,'sales_ready'=>0.70,'champion'=>0.90];
                            $lw         = $lsWeight[$selectedGuest->lead_status ?? ''] ?? 0;
                            $stage1done = in_array($guestContractStatus, ['requested','signed_uploaded','approved','rejected','reopen_requested'], true);
                            $stage2done = $lw >= 0.55;
                            $stagesDone = array_sum([$stage1done,$stage2done,false,false]);
                            $renderedAmt = $pkgPrice > 0 ? round($pkgPrice * 0.25 * $stagesDone, 2) : 0;
                            $rawExtra    = $selectedGuest->selected_extra_services ?? [];
                            $extraServices = collect(is_array($rawExtra) ? $rawExtra : json_decode((string)$rawExtra, true) ?? []);
                            $extraTotal  = $extraServices->sum('price');
                        @endphp
                        @if($pkgPrice > 0)
                        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 14px;margin-bottom:12px;">
                            <div style="font-size:var(--tx-xs);font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">📊 Tahmini Finansal Etki</div>
                            <div class="sca-fin-grid">
                                <div class="sca-fin-item"><div class="sca-fin-label">Paket</div><div class="sca-fin-val">€{{ number_format($pkgPrice,2) }}</div></div>
                                <div class="sca-fin-item"><div class="sca-fin-label">Tamamlanan ({{ $stagesDone }}/4)</div><div class="sca-fin-val">€{{ number_format($renderedAmt,2) }}</div></div>
                                @if($extraTotal > 0)<div class="sca-fin-item"><div class="sca-fin-label">Ek Hizmetler</div><div class="sca-fin-val">€{{ number_format($extraTotal,2) }}</div></div>@endif
                            </div>
                            <div id="firmCategoryNotice" style="display:none;margin-top:8px;padding:6px 10px;background:#dbeafe;border:1px solid #93c5fd;border-radius:6px;font-size:var(--tx-xs);color:#1e40af;font-weight:700;">
                                ⚠️ Firma Kaynaklı — Tam İade: €{{ number_format($pkgPrice + $extraTotal, 2) }}
                            </div>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('manager.contract-template.cancel') }}" enctype="multipart/form-data"
                              onsubmit="return confirm('Bu sözleşmeyi iptal etmek istediğinizden emin misiniz?');">
                            @csrf
                            <input type="hidden" name="guest_id" value="{{ $selectedGuest->id }}">
                            <input type="hidden" name="cancel_category" id="cancelCategoryField" value="">
                            <div style="font-size:var(--tx-xs);font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">İptal Kategorisi <span style="color:#b91c1c;">*</span></div>
                            @foreach($cancelReasons as $catKey => $cat)
                            <div class="sca-cancel-cat" style="margin-bottom:8px;">
                                <div class="sca-cancel-cat-head">{{ $cat['icon'] }} {{ $cat['label'] }}</div>
                                @foreach($cat['reasons'] as $code => $label)
                                <label class="sca-cancel-reason-row">
                                    <input type="radio" name="cancel_reason_code" value="{{ $code }}" data-category="{{ $catKey }}" style="margin-top:2px;flex-shrink:0;"
                                           onchange="document.getElementById('cancelCategoryField').value=this.dataset.category;var n=document.getElementById('firmCategoryNotice');if(n)n.style.display=this.dataset.category==='firm'?'block':'none';" required>
                                    <span>{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @endforeach
                            <textarea name="cancel_note" rows="3" style="width:100%;padding:9px;border:1px solid #d1d5db;border-radius:8px;font-size:var(--tx-xs);resize:vertical;box-sizing:border-box;margin-top:4px;" placeholder="İptal gerekçesi…" required></textarea>
                            <label for="cancelAttachmentFile" style="display:inline-flex;align-items:center;gap:8px;border:1.5px dashed #fca5a5;border-radius:8px;padding:8px 12px;cursor:pointer;font-size:var(--tx-xs);color:#7f1d1d;background:#fff5f5;margin-top:8px;">
                                📎 <span id="cancelFileName">Ek dosya (PDF/JPG/PNG — maks. 20MB)</span>
                            </label>
                            <input type="file" id="cancelAttachmentFile" name="cancel_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;"
                                   onchange="document.getElementById('cancelFileName').textContent=this.files[0]?.name||'Seçilmedi'">
                            <div style="margin-top:12px;display:flex;align-items:center;gap:12px;">
                                <button type="submit" class="ct-btn danger">⛔ İptal Et</button>
                                <span style="font-size:var(--tx-xs);color:#9ca3af;">Bu işlem geri alınamaz.</span>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tab: Sözleşme Önizleme ── --}}
    <div class="ct-tab-pane" id="ctTab-onizleme">
        @if($contractPreview)
            <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;">
                <div>
                    <div class="ct-preview-box">{{ $contractPreview['body_text'] ?? '' }}</div>
                    @if(($contractPreview['annex_kvkk_text'] ?? '') !== '')
                    <details style="margin-top:8px;"><summary style="cursor:pointer;font-weight:700;font-size:var(--tx-sm);padding:4px 0;">Ek-1 — KVKK</summary>
                        <div class="ct-preview-box" style="min-height:auto;margin-top:6px;font-size:var(--tx-xs);">{{ $contractPreview['annex_kvkk_text'] }}</div>
                    </details>
                    @endif
                    @if(($contractPreview['annex_commitment_text'] ?? '') !== '')
                    <details><summary style="cursor:pointer;font-weight:700;font-size:var(--tx-sm);padding:4px 0;">Ek-2 — Taahhütname</summary>
                        <div class="ct-preview-box" style="min-height:auto;margin-top:6px;font-size:var(--tx-xs);">{{ $contractPreview['annex_commitment_text'] }}</div>
                    </details>
                    @endif
                    @if(($contractPreview['annex_payment_text'] ?? '') !== '')
                    <details><summary style="cursor:pointer;font-weight:700;font-size:var(--tx-sm);padding:4px 0;">Ek-3 — Ödeme Planı</summary>
                        <div class="ct-preview-box" style="min-height:auto;margin-top:6px;font-size:var(--tx-xs);">{{ $contractPreview['annex_payment_text'] }}</div>
                    </details>
                    @endif
                </div>
                <div>
                    <div style="font-size:var(--tx-xs);font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Placeholder Değerleri</div>
                    <div class="ct-preview-vars">
                        <table>
                            <thead><tr><th>Placeholder</th><th>Değer</th></tr></thead>
                            <tbody>
                                @foreach($placeholders as $ph)
                                <tr>
                                    <th><code>{{ '{' . '{' . $ph . '}' . '}' }}</code></th>
                                    <td>{{ (string)($previewVariables[$ph] ?? '–') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
                        <a class="ct-btn slim primary" href="{{ route('manager.contract-template.print', $selectedGuest->id) }}" target="_blank">🖨 Yazdır / PDF</a>
                    </div>
                </div>
            </div>
        @else
            <div class="ct-hint">Önizleme için önce bir öğrenci seçin ve sözleşme oluşturun.</div>
        @endif
    </div>

    {{-- ── Tab: Olay Geçmişi ── --}}
    <div class="ct-tab-pane" id="ctTab-gecmis">
        <div class="ct-history-list">
            @forelse(($contractEvents ?? collect()) as $ev)
                <div class="ct-history-item">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <strong style="font-size:var(--tx-xs);">{{ $ev->event_type }}</strong>
                        <span class="muted" style="font-size:var(--tx-xs);">{{ $ev->created_at }}</span>
                    </div>
                    <div class="muted" style="margin-top:2px;">{{ $ev->message }}</div>
                    <div class="muted" style="font-size:var(--tx-xs);">Aktör: {{ $ev->actor_email ?: '–' }}</div>
                </div>
            @empty
                <div class="ct-history-item muted">Kayıtlı sözleşme olayı yok.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Tab: Template Düzenle ── --}}
    <div class="ct-tab-pane" id="ctTab-template">
        <form method="post" action="{{ route('manager.contract-template.save') }}">
            @csrf
            <div class="ct-row-2" style="align-items:center;margin-bottom:10px;">
                <input name="name" value="{{ old('name', $template->name) }}" placeholder="Template adı">
                <label style="display:flex;align-items:center;gap:8px;font-size:var(--tx-sm);">
                    <input type="checkbox" name="new_version" value="1" style="width:auto;min-height:auto;">
                    Yeni versiyon aç
                </label>
            </div>
            <div class="ct-row-1">
                <label>Ana Sözleşme Metni</label>
                <textarea name="body_text">{{ old('body_text', $template->body_text) }}</textarea>
            </div>
            <div class="ct-row-1">
                <label>Ek-1 KVKK</label>
                <textarea name="annex_kvkk_text">{{ old('annex_kvkk_text', $template->annex_kvkk_text) }}</textarea>
            </div>
            <div class="ct-row-1">
                <label>Ek-2 Taahhütname</label>
                <textarea name="annex_commitment_text">{{ old('annex_commitment_text', $template->annex_commitment_text) }}</textarea>
            </div>
            <div class="ct-row-1">
                <label>Ek-3 Ödeme Planı</label>
                <textarea name="annex_payment_text">{{ old('annex_payment_text', $template->annex_payment_text ?? '') }}</textarea>
            </div>
            <div class="ct-row-1">
                <label>Notlar</label>
                <textarea name="notes" style="min-height:80px;">{{ old('notes', $template->notes) }}</textarea>
            </div>
            <div style="height:1px;background:#e2e8f0;margin:14px 0 10px;"></div>
            <details id="printLayoutSection" {{ old('print_header_html', $template->print_header_html ?? '') !== '' || old('print_footer_html', $template->print_footer_html ?? '') !== '' ? 'open' : '' }}>
                <summary style="cursor:pointer;font-weight:700;font-size:var(--tx-sm);padding:4px 0;user-select:none;">
                    PDF Print Şablonu (HTML) <span style="font-weight:400;font-size:var(--tx-xs);color:#94a3b8;">— Opsiyonel</span>
                </summary>
                <div style="margin-top:10px;">
                    <div class="ct-hint" style="margin-bottom:10px;">
                        <strong>Nasıl çalışır?</strong> Sözleşme yazdırıldığında üstüne/altına eklenir. Aynı <code>@{{değişken}}</code> placeholderları kullanılabilir.
                    </div>
                    <div class="ct-row-1" style="margin-bottom:8px;">
                        <label>Başlık HTML</label>
                        <textarea name="print_header_html" style="min-height:120px;font-family:Consolas,monospace;font-size:var(--tx-xs);" placeholder="<div style=...>Logo + Başlık</div>">{{ old('print_header_html', $template->print_header_html ?? '') }}</textarea>
                    </div>
                    <div class="ct-row-1">
                        <label>Alt Bilgi HTML</label>
                        <textarea name="print_footer_html" style="min-height:120px;font-family:Consolas,monospace;font-size:var(--tx-xs);" placeholder="<div style=...>İmza alanı</div>">{{ old('print_footer_html', $template->print_footer_html ?? '') }}</textarea>
                    </div>
                    @if($contractPreview)
                    <div style="margin-top:8px;">
                        <button type="button" class="ct-btn slim" onclick="previewPrintLayout()">Print Layout Önizle</button>
                    </div>
                    @endif
                </div>
            </details>
            <div class="ct-toolbar" style="margin-top:12px;">
                <button class="ct-btn primary" type="submit">💾 Kaydet</button>
            </div>
        </form>
    </div>

    {{-- ── Tab: Son Template'ler ── --}}
    <div class="ct-tab-pane" id="ctTab-son-template">
        <div class="ct-mini-list" style="max-height:340px;">
            @forelse($templates as $row)
                <div class="ct-mini-item">
                    <div class="ct-mini-head">
                        <div class="ct-s-id">#{{ $row->id }} {{ $row->name }}</div>
                        <span class="badge {{ $row->is_active ? 'ok' : 'pending' }}">{{ $row->is_active ? 'Aktif' : 'Pasif' }}</span>
                    </div>
                    <div style="font-size:var(--tx-xs);color:#64748b;">{{ $row->code }} · v{{ $row->version }} · {{ $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('d.m.Y') : '–' }}</div>
                </div>
            @empty
                <div class="ct-mini-item muted">Template yok.</div>
            @endforelse
        </div>
        <div class="ct-hint" style="margin-top:10px;">
            Kullanım: 1) Template Düzenle sekmesinden metni güncelle 2) Gerekirse yeni versiyon aç 3) Öğrenci seç → Servis doğrula → Sözleşme başlat.
        </div>
    </div>

</div>{{-- /ct-stu-panel --}}

@else
{{-- No student selected — still show template tabs --}}
<div class="ct-stu-panel" style="margin-bottom:12px;">
    <div class="ct-inner-tabs">
        <button class="ct-inner-tab active" onclick="ctSwitchTab('template-ns',this)">✏️ Template Düzenle</button>
        <button class="ct-inner-tab" onclick="ctSwitchTab('son-template-ns',this)">📚 Son Template'ler</button>
    </div>
    <div class="ct-tab-pane active" id="ctTab-template-ns">
        <div class="ct-hint" style="margin-bottom:12px;">Listeden bir öğrenci seçerek sözleşme sürecini yönetin.</div>
        <form method="post" action="{{ route('manager.contract-template.save') }}">
            @csrf
            <div class="ct-row-2" style="align-items:center;margin-bottom:10px;">
                <input name="name" value="{{ old('name', $template->name) }}" placeholder="Template adı">
                <label style="display:flex;align-items:center;gap:8px;font-size:var(--tx-sm);">
                    <input type="checkbox" name="new_version" value="1" style="width:auto;min-height:auto;">
                    Yeni versiyon aç
                </label>
            </div>
            <div class="ct-row-1"><label>Ana Sözleşme Metni</label><textarea name="body_text">{{ old('body_text', $template->body_text) }}</textarea></div>
            <div class="ct-row-1"><label>Ek-1 KVKK</label><textarea name="annex_kvkk_text">{{ old('annex_kvkk_text', $template->annex_kvkk_text) }}</textarea></div>
            <div class="ct-row-1"><label>Ek-2 Taahhütname</label><textarea name="annex_commitment_text">{{ old('annex_commitment_text', $template->annex_commitment_text) }}</textarea></div>
            <div class="ct-row-1"><label>Ek-3 Ödeme Planı</label><textarea name="annex_payment_text">{{ old('annex_payment_text', $template->annex_payment_text ?? '') }}</textarea></div>
            <div class="ct-row-1"><label>Notlar</label><textarea name="notes" style="min-height:80px;">{{ old('notes', $template->notes) }}</textarea></div>
            <div class="ct-toolbar" style="margin-top:12px;"><button class="ct-btn primary" type="submit">💾 Kaydet</button></div>
        </form>
    </div>
    <div class="ct-tab-pane" id="ctTab-son-template-ns">
        <div class="ct-mini-list" style="max-height:300px;">
            @forelse($templates as $row)
                <div class="ct-mini-item">
                    <div class="ct-mini-head">
                        <div class="ct-s-id">#{{ $row->id }} {{ $row->name }}</div>
                        <span class="badge {{ $row->is_active ? 'ok' : 'pending' }}">{{ $row->is_active ? 'Aktif' : 'Pasif' }}</span>
                    </div>
                    <div style="font-size:var(--tx-xs);color:#64748b;">{{ $row->code }} · v{{ $row->version }}</div>
                </div>
            @empty
                <div class="ct-mini-item muted">Template yok.</div>
            @endforelse
        </div>
    </div>
</div>
@endif

{{-- HTML Print Layout --}}
@if(($printHeaderHtml ?? '') !== '' || ($printFooterHtml ?? '') !== '')
<div id="contractHtmlPrintLayout" style="display:none;font-family:inherit;">
    @if(($printHeaderHtml ?? '') !== '') {!! $printHeaderHtml !!} @endif
    @if($contractSnapshotText ?? '' !== '')
        <div id="contractPrintBody" style="white-space:pre-wrap;margin:16px 0;font-size:11pt;line-height:1.7;">{{ $contractSnapshotText ?? '' }}</div>
    @endif
    @if(($printFooterHtml ?? '') !== '') {!! $printFooterHtml !!} @endif
</div>
@endif

<script>
// ── Cancel toggle ──
function toggleCancel() {
    var body = document.getElementById('cancelBody');
    var toggle = document.getElementById('cancelToggle');
    var chevron = document.getElementById('cancelChevron');
    if (!body) return;
    var isOpen = body.classList.toggle('open');
    if (toggle) toggle.dataset.open = isOpen ? 'true' : 'false';
    if (chevron) chevron.textContent = isOpen ? '▲' : '▼';
}

// ── Company settings accordion ──
function toggleCoSection(btn) {
    var body = btn.nextElementSibling;
    if (!body) return;
    var isOpen = body.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    var chev = btn.querySelector('.ct-co-chev');
    if (chev) chev.textContent = isOpen ? '▲' : '▼';
}

// ── Inner tab switching ──
function ctSwitchTab(id, btn) {
    // Sadece aynı panel grubundaki sekmeleri değiştir
    var panel = btn ? btn.closest('.ct-stu-panel') : null;
    if (panel) {
        panel.querySelectorAll('.ct-tab-pane').forEach(p => p.classList.remove('active'));
        panel.querySelectorAll('.ct-inner-tab').forEach(b => b.classList.remove('active'));
    } else {
        document.querySelectorAll('.ct-tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.ct-inner-tab').forEach(b => b.classList.remove('active'));
    }
    var pane = document.getElementById('ctTab-' + id);
    if (pane) pane.classList.add('active');
    if (btn)  btn.classList.add('active');
}

// ── Print layout preview ──
function previewPrintLayout() {
    var header = document.querySelector('textarea[name="print_header_html"]').value;
    var footer = document.querySelector('textarea[name="print_footer_html"]').value;
    var body   = @json($contractPreview['body_text'] ?? '');
    var annex1 = @json($contractPreview['annex_kvkk_text'] ?? '');
    var annex2 = @json($contractPreview['annex_commitment_text'] ?? '');
    var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;margin:20mm;font-size:11pt;line-height:1.7;}pre{white-space:pre-wrap;font-family:inherit;}</style></head><body>';
    html += header;
    html += '<pre>' + body.replace(/</g,'&lt;') + '</pre>';
    if (annex1) html += '<hr><h4>Ek-1 — KVKK</h4><pre>' + annex1.replace(/</g,'&lt;') + '</pre>';
    if (annex2) html += '<h4>Ek-2 — Taahhütname</h4><pre>' + annex2.replace(/</g,'&lt;') + '</pre>';
    html += footer + '</body></html>';
    var win = window.open('', '_blank', 'width=900,height=700');
    win.document.write(html); win.document.close();
}

// ── Toplu Sözleşme İşlemi ──
(function () {
    const bar      = document.getElementById('batchBar');
    const cntEl    = document.getElementById('batchCount');
    const feedback = document.getElementById('batchFeedback');
    function selectedIds() { return Array.from(document.querySelectorAll('.batch-chk:checked')).map(c => parseInt(c.dataset.id, 10)); }
    function updateBar() {
        const ids = selectedIds();
        if (ids.length > 0) { bar.style.display = 'flex'; cntEl.textContent = ids.length + ' seçildi'; }
        else { bar.style.display = 'none'; }
    }
    document.addEventListener('change', e => { if (e.target.classList.contains('batch-chk')) updateBar(); });
    async function doBatch(decision) {
        const ids = selectedIds();
        if (!ids.length) return;
        if (!confirm(ids.length + ' sözleşmeyi toplu ' + (decision==='approve'?'onaylamak':'reddetmek') + ' istiyor musunuz?')) return;
        const note = (document.getElementById('batchNote')?.value || '').trim();
        const btn  = decision === 'approve' ? document.getElementById('batchApproveBtn') : document.getElementById('batchRejectBtn');
        btn.disabled = true;
        try {
            const r = await fetch('{{ route("manager.contract-template.batch-decision") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ guest_ids: ids, decision, note })
            });
            const d = await r.json();
            const ok = d.processed > 0;
            feedback.style.display   = 'block';
            feedback.style.background = ok ? '#f0fdf4' : '#fef2f2';
            feedback.style.color      = ok ? '#166534' : '#991b1b';
            feedback.style.border     = '1px solid ' + (ok ? '#bbf7d0' : '#fca5a5');
            feedback.textContent = '✓ İşlendi: ' + d.processed + ', Atlandı: ' + d.skipped + (d.errors?.length ? ' | Atlandı: ' + d.errors.join('; ') : '');
            if (d.processed > 0) setTimeout(() => location.reload(), 1800);
        } catch { feedback.style.display = 'block'; feedback.style.background = '#fef2f2'; feedback.style.color = '#991b1b'; feedback.textContent = 'Bağlantı hatası.'; }
        btn.disabled = false;
    }
    document.getElementById('batchApproveBtn')?.addEventListener('click', () => doBatch('approve'));
    document.getElementById('batchRejectBtn')?.addEventListener('click',  () => doBatch('reject'));
})();

// Auto-open önizleme tab if signed_uploaded (imzalı geldi)
@if(isset($selStatus) && $selStatus === 'signed_uploaded')
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.querySelector('.ct-inner-tab[onclick*="onizleme"]');
    if (btn) ctSwitchTab('onizleme', btn);
});
@endif
</script>

@endsection
