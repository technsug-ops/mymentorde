@extends('guest.layouts.app')

@section('title', 'Ön Kayıt Belgeleri')
@section('page_title', 'Kayıt Süreci - Ön Kayıt Belgeleri')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── grd-* Guest Registration Documents scoped ── */

/* KPI strip */
.grd-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media(max-width:1100px){ .grd-kpi-strip { grid-template-columns: repeat(2,1fr); } }
.grd-kpi {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 14px 18px;
    transition: box-shadow .15s;
}
.grd-kpi.ok   { border-color: #b7ddc6; background: #f3faf6; }
.grd-kpi.warn { border-color: #f0c9c9; background: #fff8f8; }
.grd-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--u-muted); margin-bottom: 6px; }
.grd-kpi-val   { font-size: 26px; font-weight: 800; color: var(--u-text); line-height: 1.1; }
.grd-kpi.ok   .grd-kpi-val { color: #1c5c39; }
.grd-kpi.warn .grd-kpi-val { color: #9f1c1c; }

/* Progress */
.grd-progress-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.grd-prog-row  { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--u-muted); margin-bottom: 5px; }
.grd-prog-bar  { height: 8px; border-radius: 999px; background: var(--u-line); overflow: hidden; margin-bottom: 12px; }
.grd-prog-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg,#135fcf,#3b82f6); }
.grd-prog-fill.done { background: linear-gradient(90deg,#1c8f4f,#22c55e); }

/* Missing action strip */
.grd-missing-strip {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 12px 16px;
    border: 1.5px solid #fecaca; border-radius: 12px;
    background: #fff5f5; margin-bottom: 16px;
}
.grd-missing-strip.hidden { display: none; }
.grd-missing-label { font-size: 13px; font-weight: 600; color: #9f1c1c; flex: 1; }
.grd-missing-btn {
    border: 1px solid #fecaca; background: #fff; color: #9f1c1c;
    border-radius: 999px; padding: 6px 16px;
    font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: background .15s;
}
.grd-missing-btn:hover { background: #fff0f0; }

/* Category nav */
.grd-cat-nav {
    display: flex; gap: 6px; overflow-x: auto;
    padding-bottom: 2px; scrollbar-width: thin;
    flex-wrap: nowrap; margin-bottom: 4px;
}
.grd-cat-nav a {
    display: inline-block; white-space: nowrap;
    padding: 5px 12px; border: 1px solid var(--u-line);
    border-radius: 999px; background: var(--u-card);
    font-size: 12px; font-weight: 600; color: var(--u-text);
    text-decoration: none; flex-shrink: 0;
    transition: background .15s, border-color .15s, color .15s;
}
.grd-cat-nav a:hover { background: #f0f6ff; border-color: #93c5fd; color: var(--u-brand); text-decoration: none; }

/* Filters */
.grd-filter-bar {
    display: flex; gap: 8px; flex-wrap: wrap; padding: 10px;
    border: 1px dashed var(--u-line); border-radius: 10px;
    background: var(--u-bg); margin-top: 8px;
}
.grd-filter-btn {
    border: 1px solid var(--u-line); background: var(--u-card);
    color: var(--u-text); border-radius: 999px; padding: 5px 12px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: background .15s, border-color .15s;
}
.grd-filter-btn.active { border-color: #a9c4ea; background: #edf3fc; color: #173d66; }
.grd-filter-input {
    flex: 1 1 220px; border: 1px solid var(--u-line);
    border-radius: 999px; background: var(--u-card); color: var(--u-text);
    padding: 6px 14px; font-size: 13px; font-family: inherit;
}
.grd-filter-input:focus { outline: none; border-color: var(--u-brand); }
.grd-compact-btn {
    border: 1px solid var(--u-line); background: var(--u-card);
    color: var(--u-text); border-radius: 999px; padding: 5px 12px;
    font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit;
}
.grd-compact-btn:hover { background: var(--u-bg); }

/* Document groups */
.grd-group {
    border: 1px solid var(--u-line); border-radius: 14px;
    background: var(--u-bg); margin-bottom: 12px; overflow: hidden;
}
.grd-group-summary {
    display: flex; justify-content: space-between; align-items: center;
    gap: 8px; padding: 12px 16px; cursor: pointer; list-style: none;
}
.grd-group-summary::-webkit-details-marker { display: none; }
.grd-group-title { font-size: 14px; font-weight: 800; color: var(--u-text); margin: 0; }
.grd-group-badges { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.grd-group-count  {
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; color: var(--u-muted); border: 1px solid var(--u-line);
    border-radius: 999px; padding: 3px 10px; background: var(--u-card);
}
.grd-group-status {
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; border-radius: 999px; padding: 3px 10px;
    border: 1px solid var(--u-line); background: var(--u-card); color: var(--u-muted);
}
.grd-group-status.ok   { border-color: #b7ddc6; background: #f1faf4; color: #1c5c39; font-weight: 700; }
.grd-group-status.warn { border-color: #f0d8ae; background: #fffbf3; color: #7d5b22; font-weight: 700; }
.grd-group-body { padding: 0 12px 12px; }

/* Checklist grid */
.grd-checklist { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; }
@media(max-width:760px){ .grd-checklist { grid-template-columns: 1fr; } }

/* Check item cards */
.grd-check-item {
    border: 1px solid var(--u-line); border-radius: 12px;
    padding: 12px 14px; background: var(--u-card);
    display: flex; flex-direction: column; gap: 0;
    transition: box-shadow .15s;
}
.grd-check-item:hover { box-shadow: var(--u-shadow); }
.grd-check-item.missing { border-color: #f0c9c9; background: #fffbfb; }
.grd-check-item.uploaded { border-color: #b7ddc6; background: #f8fdf9; }

.grd-check-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
.grd-check-header-left { min-width: 0; flex: 1; }
.grd-doc-code {
    display: inline-block; font-size: 10px; font-weight: 700; color: #35567d;
    background: #f4f7fc; border: 1px solid #dfe8f4; border-radius: 999px;
    padding: 1px 7px; margin-bottom: 4px;
}
.grd-check-title { margin: 0; font-size: 13px; font-weight: 700; color: var(--u-text); line-height: 1.35; }
.grd-check-meta { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
.grd-check-desc { font-size: 11px; color: var(--u-muted); margin-top: 6px; line-height: 1.4; }

/* State pills */
.grd-state { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.grd-state-ok   { background: #edf9f2; border: 1px solid #9dd6b5; color: #186b3e; }
.grd-state-wait { background: #fffbf3; border: 1px solid #e8d09a; color: #7d5b22; }
.grd-state-req  { background: #fff2f2; border: 1px solid #ebb5b5; color: #9b2d2d; }
.grd-state-opt  { background: #f4f7fc; border: 1px solid #dfe8f4; color: #38557a; }

/* Upload row */
.grd-upload-row { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
.grd-upload-trigger {
    flex: 1; min-width: 0;
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px;
    border: 1.5px dashed #b5cde8; border-radius: 8px;
    background: #f7faff; cursor: pointer;
    transition: background .15s, border-color .15s;
    overflow: hidden;
}
.grd-upload-trigger:hover { background: #edf4fd; border-color: #78aadf; }
.grd-upload-trigger input[type="file"] { display: none; }
.grd-upload-icon { font-size: 15px; color: #5a8fc4; flex-shrink: 0; }
.grd-upload-name { font-size: 11px; color: #5a7899; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.grd-upload-row.has-file .grd-upload-trigger { background: #f0faf5; border-color: #7ecfa8; border-style: solid; }
.grd-upload-row.has-file .grd-upload-name { color: #1c6040; font-weight: 600; }
.grd-upload-row.drag-over .grd-upload-trigger { background: #deeefb; border-color: #3e75bf; border-style: solid; }

/* Compact mode */
.compact-mode .grd-check-meta,
.compact-mode .grd-check-desc { display: none; }

/* Doc list (uploaded) */
.grd-doc-list { display: grid; gap: 8px; }
.grd-doc-row {
    border: 1px solid var(--u-line); border-radius: 10px;
    background: var(--u-card); padding: 12px 14px;
    display: flex; justify-content: space-between; gap: 10px; align-items: center;
    transition: box-shadow .15s;
}
.grd-doc-row:hover { box-shadow: var(--u-shadow); }
.grd-doc-row-meta { font-size: 12px; color: var(--u-muted); margin-top: 3px; }

/* Extra upload */
.grd-extra-pair {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
@media(max-width:760px){ .grd-extra-pair { grid-template-columns: 1fr; } }
.grd-extra-label { font-size: 12px; font-weight: 700; color: var(--u-text); margin-bottom: 6px; display: block; }
.grd-extra-select {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-card); font-size: 14px; font-weight: 500; color: var(--u-text);
    height: 44px; box-sizing: border-box; font-family: inherit; appearance: auto;
    transition: border-color .15s;
}
.grd-extra-select:focus { outline: none; border-color: var(--u-brand); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.grd-extra-file-label {
    display: flex; align-items: center; gap: 10px;
    height: 44px; padding: 0 14px;
    border: 1.5px dashed #b5cde8; border-radius: 8px;
    background: #f7faff; cursor: pointer; box-sizing: border-box;
    transition: background .15s, border-color .15s; overflow: hidden;
}
.grd-extra-file-label:hover { background: #edf4fd; border-color: #78aadf; }
.grd-extra-file-label input[type="file"] { display: none; }
.grd-extra-file-icon { font-size: 16px; color: #4a80c0; flex-shrink: 0; }
.grd-extra-file-name { font-size: 14px; font-weight: 500; color: #2e5280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; }
.grd-extra-file-label.has-file { background: #f0faf5; border-color: #7ecfa8; border-style: solid; }
.grd-extra-file-label.has-file .grd-extra-file-name { color: #1c6040; font-weight: 600; }

/* ── Minimalist overrides ── */
.jm-minimalist .grd-prog-fill       { background: var(--u-brand, #111) !important; }
.jm-minimalist .grd-prog-fill.done  { background: var(--u-ok, #059669) !important; }
</style>
@endpush

@section('content')

@php
    $checklist        = collect($requiredDocumentChecklist ?? []);
    $totalDocs        = $checklist->count();
    $uploadedDocs     = $checklist->where('uploaded', true)->count();
    $requiredTotal    = $checklist->where('is_required', true)->count();
    $requiredUploaded = $checklist->where('is_required', true)->where('uploaded', true)->count();
    $requiredMissing  = max(0, $requiredTotal - $requiredUploaded);
    $completion         = $totalDocs     > 0 ? (int)round(($uploadedDocs / $totalDocs) * 100) : 0;
    $requiredCompletion = $requiredTotal > 0 ? (int)round(($requiredUploaded / $requiredTotal) * 100) : 0;
@endphp

{{-- ── KPI Strip ── --}}
<div class="grd-kpi-strip">
    <div class="grd-kpi">
        <div class="grd-kpi-label">Toplam Belge</div>
        <div class="grd-kpi-val">{{ $totalDocs }}</div>
    </div>
    <div class="grd-kpi {{ $uploadedDocs === $totalDocs && $totalDocs > 0 ? 'ok' : '' }}">
        <div class="grd-kpi-label">Yüklenen</div>
        <div class="grd-kpi-val">{{ $uploadedDocs }}</div>
    </div>
    <div class="grd-kpi {{ $requiredUploaded === $requiredTotal && $requiredTotal > 0 ? 'ok' : '' }}">
        <div class="grd-kpi-label">Zorunlu Tamamlandı</div>
        <div class="grd-kpi-val">{{ $requiredUploaded }}/{{ $requiredTotal }}</div>
    </div>
    <div class="grd-kpi {{ $requiredMissing > 0 ? 'warn' : 'ok' }}">
        <div class="grd-kpi-label">Eksik Zorunlu</div>
        <div class="grd-kpi-val">{{ $requiredMissing }}</div>
    </div>
</div>

{{-- ── Progress ── --}}
<div class="grd-progress-card">
    <div class="grd-prog-row">
        <span>Genel İlerleme</span>
        <span style="color:var(--u-brand);font-weight:800;">%{{ $completion }}</span>
    </div>
    <div class="grd-prog-bar">
        <div class="grd-prog-fill {{ $completion >= 100 ? 'done' : '' }}" style="width:{{ $completion }}%;"></div>
    </div>
    <div class="grd-prog-row">
        <span>Zorunlu Belgeler</span>
        <span style="color:var(--u-ok);font-weight:800;">%{{ $requiredCompletion }}</span>
    </div>
    <div class="grd-prog-bar" style="margin-bottom:0;">
        <div class="grd-prog-fill {{ $requiredCompletion >= 100 ? 'done' : '' }}" style="width:{{ $requiredCompletion }}%;"></div>
    </div>
</div>

{{-- ── İnceleme Notu Uyarı Banner ── --}}
@php
    $docsWithNotes = $checklist->map(function($item) use ($documentsByCategory) {
        $doc = ($documentsByCategory ?? collect())->get((string)($item['category_code'] ?? ''));
        if ($doc && trim((string)($doc->review_note ?? '')) !== '') {
            return ['name' => $item['name'] ?? $item['document_code'] ?? '-', 'note' => $doc->review_note];
        }
        return null;
    })->filter()->values();
@endphp
@if($docsWithNotes->isNotEmpty())
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px 16px;margin-bottom:14px;display:flex;gap:12px;align-items:flex-start;">
    <span style="font-size:var(--tx-xl);flex-shrink:0;line-height:1.3;">⚠️</span>
    <div style="flex:1;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:#92400e;margin-bottom:8px;">
            {{ $docsWithNotes->count() }} belgede danışman inceleme notu var — lütfen kontrol edin
        </div>
        @foreach($docsWithNotes as $d)
        <div style="font-size:var(--tx-xs);color:#78350f;padding:5px 0;border-bottom:1px solid #fde68a;display:flex;gap:8px;align-items:flex-start;">
            <span style="font-weight:700;flex-shrink:0;">📄 {{ $d['name'] }}:</span>
            <span>{{ $d['note'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Missing Strip ── --}}
<div class="grd-missing-strip {{ $requiredMissing === 0 ? 'hidden' : '' }}" id="docActionStrip">
    <span class="grd-missing-label">{{ $requiredMissing }} zorunlu belge eksik — önce bunları tamamlayın.</span>
    <button id="btnFocusMissing" type="button" class="grd-missing-btn">Eksik Belgelere Git ↓</button>
</div>

{{-- ── Checklist Card ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-head">
        <div class="card-title">Ön Kayıt Belge Checklist</div>
    </div>
    <div class="card-body">
        @if($checklist->isEmpty())
            <div class="muted">Tanımlı checklist bulunamadı.</div>
        @else
            @php
                $topLabels = $documentTopCategoryLabels ?? [];
                $groupedChecklist = $checklist
                    ->groupBy(fn($item) => (string)($item['top_category_code'] ?? 'diger_dokümanlar'))
                    ->map(function($items, $code) use ($topLabels) {
                        $required         = (int)$items->where('is_required', true)->count();
                        $requiredUploaded = (int)$items->where('is_required', true)->where('uploaded', true)->count();
                        $missingRequired  = max(0, $required - $requiredUploaded);
                        return [
                            'code'              => (string)$code,
                            'label'             => (string)($topLabels[$code] ?? 'Diğer dokümanlar'),
                            'items'             => $items->values(),
                            'required'          => $required,
                            'required_uploaded' => $requiredUploaded,
                            'missing_required'  => $missingRequired,
                            'total'             => (int)$items->count(),
                            'uploaded'          => (int)$items->where('uploaded', true)->count(),
                        ];
                    })
                    ->sortBy([['missing_required','desc'],['label','asc']])
                    ->values();
            @endphp

            {{-- Controls --}}
            <div style="border:1px solid var(--u-line);border-radius:12px;background:var(--u-card);padding:12px;margin-bottom:14px;">
                <div style="display:grid;grid-template-columns:1fr auto;align-items:start;gap:10px;margin-bottom:8px;">
                    <div>
                        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Kategoriler</div>
                        <div class="grd-cat-nav">
                            @foreach($groupedChecklist as $group)
                                <a href="#group-{{ $group['code'] }}">{{ $group['label'] }} ({{ $group['total'] }})</a>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <button type="button" class="grd-compact-btn" id="btnCompactMode">Kompakt Görünüm</button>
                    </div>
                </div>
                <details id="guestDocFiltersAdvanced">
                    <summary style="cursor:pointer;list-style:none;font-size:var(--tx-xs);font-weight:700;color:#35567d;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border:1px solid var(--u-line);border-radius:8px;background:var(--u-bg);">
                        Filtreler / Arama
                    </summary>
                    <div style="display:none;"><!-- revealed by <details> --></div>
                    <div class="grd-filter-bar" style="margin-top:8px;">
                        <button type="button" class="grd-filter-btn" data-doc-filter="all">Tüm Belgeler</button>
                        <button type="button" class="grd-filter-btn active" data-doc-filter="required">Sadece Zorunlu</button>
                        <button type="button" class="grd-filter-btn" data-doc-filter="missing">Sadece Eksik</button>
                        <button type="button" class="grd-filter-btn" data-doc-filter="required_missing">Zorunlu + Eksik</button>
                        <input id="docSearchInput" class="grd-filter-input" placeholder="Kod veya belge adı ara...">
                    </div>
                </details>
            </div>

            @foreach($groupedChecklist as $group)
                @php
                    $topCode    = (string)$group['code'];
                    $isComplete = (int)$group['missing_required'] === 0;
                @endphp
                <details id="group-{{ $topCode }}" class="grd-group" data-doc-group {{ $group['missing_required'] > 0 ? 'open' : '' }}>
                    <summary class="grd-group-summary">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;">
                            <h3 class="grd-group-title">{{ $group['label'] }}</h3>
                            <div class="grd-group-badges">
                                <span class="grd-group-count">{{ $group['uploaded'] }}/{{ $group['total'] }}</span>
                                <span class="grd-group-status {{ $isComplete ? 'ok' : 'warn' }}">
                                    {{ $isComplete ? '✓ Tamam' : 'Eksik: '.$group['missing_required'] }}
                                </span>
                            </div>
                        </div>
                    </summary>
                    <div class="grd-group-body">
                        <div class="grd-checklist">
                            @foreach($group['items'] as $item)
                                @php
                                    $latestDoc  = ($documentsByCategory ?? collect())->get((string)($item['category_code'] ?? ''));
                                    $isUploaded = !empty($item['uploaded']);
                                @endphp
                                <article
                                    class="grd-check-item {{ !empty($item['is_required']) && !$isUploaded ? 'missing' : ($isUploaded ? 'uploaded' : '') }}"
                                    data-doc-item
                                    data-required="{{ !empty($item['is_required']) ? '1' : '0' }}"
                                    data-uploaded="{{ $isUploaded ? '1' : '0' }}"
                                    data-doc-search="{{ strtolower(trim((string)(($item['document_code'] ?? '').' '.($item['name'] ?? '').' '.($item['category_code'] ?? '')))) }}"
                                >
                                    <div class="grd-check-header">
                                        <div class="grd-check-header-left">
                                            <span class="grd-doc-code">{{ $item['document_code'] ?? '-' }}</span>
                                            <h4 class="grd-check-title">{{ $item['name'] ?? '-' }}</h4>
                                        </div>
                                        @if($isUploaded)
                                            <span class="grd-state grd-state-ok">✓ Yüklendi</span>
                                        @elseif(!empty($item['is_required']))
                                            <span class="grd-state grd-state-req">Eksik</span>
                                        @else
                                            <span class="grd-state grd-state-wait">Bekliyor</span>
                                        @endif
                                    </div>

                                    <div class="grd-check-meta">
                                        @if(!empty($item['is_required']))
                                            <span class="grd-state grd-state-req">Zorunlu</span>
                                        @else
                                            <span class="grd-state grd-state-opt">Opsiyonel</span>
                                        @endif
                                        @if($latestDoc)
                                            <span class="grd-state grd-state-wait">{{ $latestDoc->status }}</span>
                                        @endif
                                        <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $item['accepted'] ?? 'pdf,jpg,png' }} · max {{ $item['max_mb'] ?? 10 }}MB</span>
                                    </div>

                                    @if(!empty($item['description']))
                                        <div class="grd-check-desc">{{ $item['description'] }}</div>
                                    @endif
                                    @if($latestDoc && trim((string)($latestDoc->review_note ?? '')) !== '')
                                        <div class="grd-check-desc" style="color:#8a4b00;margin-top:4px;">⚠ {{ $latestDoc->review_note }}</div>
                                    @endif

                                    {{-- Guide --}}
                                    @php $docCard = ($documentCards ?? [])[(string)($item['category_code'] ?? '')] ?? null; @endphp
                                    @if(!empty($docCard['guide']['steps']) || !empty($docCard['guide']['tips']))
                                    <details style="margin-top:8px;">
                                        <summary style="cursor:pointer;font-size:var(--tx-xs);font-weight:700;color:#35567d;list-style:none;display:inline-flex;align-items:center;gap:4px;">
                                            📋 Nasıl hazırlanır?
                                        </summary>
                                        <div style="margin-top:6px;padding:8px 10px;background:#f7faff;border-radius:8px;border:1px solid #dde8f4;">
                                            @if(!empty($docCard['guide']['steps']))
                                            <ol style="margin:0 0 6px;padding-left:16px;">
                                                @foreach($docCard['guide']['steps'] as $step)
                                                <li style="font-size:var(--tx-xs);color:#2e4f73;margin-bottom:2px;">{{ $step }}</li>
                                                @endforeach
                                            </ol>
                                            @endif
                                            @if(!empty($docCard['guide']['tips']))
                                            <div>
                                                @foreach($docCard['guide']['tips'] as $tip)
                                                <div style="font-size:var(--tx-xs);color:#5e7a8a;margin-bottom:2px;">💡 {{ $tip }}</div>
                                                @endforeach
                                            </div>
                                            @endif
                                            @if(!empty($docCard['guide']['expected_format']))
                                            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">Format: {{ $docCard['guide']['expected_format'] }}</div>
                                            @endif
                                        </div>
                                    </details>
                                    @endif

                                    {{-- Upload form --}}
                                    <form method="POST" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" class="quick-upload-form">
                                        @csrf
                                        <input type="hidden" name="category_code" value="{{ $item['category_code'] ?? '' }}">
                                        <div class="grd-upload-row" data-dropzone>
                                            <label class="grd-upload-trigger">
                                                <input type="file" name="file" required>
                                                <span class="grd-upload-icon">↑</span>
                                                <span class="grd-upload-name" data-upload-name>{{ $isUploaded ? 'Yeni dosya seç...' : 'Dosya seç veya bırak' }}</span>
                                            </label>
                                            <button class="btn {{ $isUploaded ? 'alt' : 'ok' }}" type="submit" style="font-size:var(--tx-xs);padding:6px 14px;white-space:nowrap;">
                                                {{ $isUploaded ? 'Güncelle' : 'Yükle' }}
                                            </button>
                                        </div>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        @endif
    </div>
</div>

{{-- ── Extra Upload ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-head">
        <div class="card-title">Ek Belge Yükle (Liste Dışı)</div>
    </div>
    <div class="card-body">
        <p class="muted" style="margin:0 0 14px;font-size:var(--tx-sm);">Checklist dışında ekstra bir belge yüklemek için bu alanı kullanın.</p>
        <form method="POST" action="{{ route('guest.registration.documents.upload') }}" enctype="multipart/form-data" id="extraUploadForm">
            @csrf
            <div class="grd-extra-pair" style="margin-bottom:14px;">
                <div>
                    <label class="grd-extra-label">Kategori</label>
                    <select name="category_code" class="grd-extra-select" required>
                        <option value="">Belge kategorisi seçiniz</option>
                        @foreach($documentCategories as $cat)
                            <option value="{{ $cat->code }}">{{ ($documentTopCategoryLabels[$cat->top_category_code] ?? 'Diğer') }} / {{ $cat->name_tr }} ({{ $cat->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="grd-extra-label">Dosya</label>
                    <label class="grd-extra-file-label" id="extraFileLabel">
                        <input type="file" name="file" required id="extraFileInput">
                        <span class="grd-extra-file-icon">↑</span>
                        <span class="grd-extra-file-name" id="extraFileName">Dosya seç veya bırak</span>
                    </label>
                </div>
            </div>
            <button class="btn ok" type="submit">Belge Yükle</button>
        </form>
    </div>
</div>

{{-- ── Uploaded Documents ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-head">
        <div class="card-title">Yüklenmiş Belgeler</div>
        <span class="badge info">{{ $documents->count() }} belge</span>
    </div>
    <div class="card-body">
        @if($documents->isEmpty())
            <div class="muted" style="padding:20px 0;text-align:center;">Henüz yüklenmiş belge bulunmuyor.</div>
        @else
            <div class="grd-doc-list">
                @foreach($documents as $doc)
                    <div class="grd-doc-row">
                        <div>
                            <strong>{{ $doc->category->name_tr ?? '—' }}</strong>
                            <div class="grd-doc-row-meta">{{ $doc->original_file_name }} | Durum: {{ $doc->status }} | {{ $doc->updated_at }}</div>
                            @if(trim((string)($doc->review_note ?? '')) !== '')
                                <div class="grd-doc-row-meta" style="color:#8a4b00;">Not: {{ $doc->review_note }}</div>
                            @endif
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">
                            @php $prevMime = strtolower((string)($doc->mime_type ?? '')); @endphp
                            @if(str_starts_with($prevMime, 'image/') || $prevMime === 'application/pdf')
                            <button type="button" class="btn alt" style="font-size:var(--tx-xs);padding:5px 10px;"
                                    onclick="grdPreview('{{ route('guest.registration.documents.preview', $doc->id) }}','{{ addslashes($doc->original_file_name ?? '') }}','{{ $prevMime }}')"
                                    title="Önizle">👁</button>
                            @endif
                            <form method="POST" action="{{ route('guest.registration.documents.delete', ['document' => $doc->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn warn" style="font-size:var(--tx-xs);padding:5px 12px;">Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ── Guide ── --}}
<div class="card">
    <div class="card-head">
        <div class="card-title">Kullanım Kılavuzu</div>
    </div>
    <div class="card-body">
        <ol class="muted" style="margin:0;padding-left:18px;line-height:2;">
            <li>Üstten kategori seçip ilgili belge kartına gidin.</li>
            <li>Zorunlu belgeler önce tamamlanmalıdır.</li>
            <li>Her karttan tekil belge yükleyin veya güncelleyin.</li>
            <li>Liste dışı belge gerekirse "Ek Belge Yükle" kullanın.</li>
        </ol>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/guest-registration-documents.js') }}"></script>
<script>
(function () {
    var inp = document.getElementById('extraFileInput');
    var lbl = document.getElementById('extraFileLabel');
    var nm  = document.getElementById('extraFileName');
    if (!inp || !lbl || !nm) return;
    inp.addEventListener('change', function () {
        if (inp.files && inp.files.length > 0) {
            nm.textContent = inp.files[0].name;
            lbl.classList.add('has-file');
        } else {
            nm.textContent = 'Dosya seç veya bırak';
            lbl.classList.remove('has-file');
        }
    });
    lbl.addEventListener('dragover', function (e) { e.preventDefault(); lbl.style.background = '#deeefb'; lbl.style.borderColor = '#3e75bf'; });
    lbl.addEventListener('dragleave', function () { lbl.style.background = ''; lbl.style.borderColor = ''; });
    lbl.addEventListener('drop', function (e) {
        e.preventDefault();
        lbl.style.background = ''; lbl.style.borderColor = '';
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            inp.files = dt.files;
            nm.textContent = dt.files[0].name;
            lbl.classList.add('has-file');
        }
    });
    (function(){
        var _orig = window.__designToggle;
        window.__designToggle = function(d){
            if(_orig) _orig(d);
            setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist', d==='minimalist'); }, 50);
        };
    })();
}());
</script>
{{-- Belge Önizleme Modal --}}
<div id="grdPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);display:none;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;max-width:860px;width:100%;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #e5e7eb;">
            <span id="grdPreviewTitle" style="font-weight:700;font-size:var(--tx-sm);color:#111827;"></span>
            <button onclick="grdClosePreview()" style="border:none;background:none;cursor:pointer;font-size:var(--tx-xl);color:#6b7280;line-height:1;">×</button>
        </div>
        <div id="grdPreviewBody" style="flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;padding:8px;background:#f9fafb;min-height:300px;">
            <div id="grdPreviewSpinner" style="font-size:var(--tx-sm);color:#6b7280;">Yükleniyor...</div>
        </div>
    </div>
</div>
<script>
function grdPreview(url, name, mime) {
    var modal = document.getElementById('grdPreviewModal');
    var body  = document.getElementById('grdPreviewBody');
    var title = document.getElementById('grdPreviewTitle');
    var spin  = document.getElementById('grdPreviewSpinner');
    modal.style.display = 'flex';
    title.textContent = name;
    body.innerHTML = '<div style="font-size:var(--tx-sm);color:#6b7280;">Yükleniyor...</div>';
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
        .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function(data) {
            if (data.mime && data.mime.indexOf('image/') === 0) {
                body.innerHTML = '<img src="' + data.url + '" alt="' + name + '" style="max-width:100%;max-height:75vh;border-radius:8px;display:block;">';
            } else {
                body.innerHTML = '<iframe src="' + data.url + '#toolbar=0" style="width:100%;height:75vh;border:none;border-radius:8px;"></iframe>';
            }
        })
        .catch(function() {
            body.innerHTML = '<div style="color:#dc2626;font-size:var(--tx-sm);">Önizleme yüklenemedi. Dosya tipi desteklenmiyor olabilir.</div>';
        });
}
function grdClosePreview() {
    var modal = document.getElementById('grdPreviewModal');
    modal.style.display = 'none';
    document.getElementById('grdPreviewBody').innerHTML = '';
}
document.getElementById('grdPreviewModal')?.addEventListener('click', function(e) {
    if (e.target === this) grdClosePreview();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') grdClosePreview(); });
</script>
@endsection
