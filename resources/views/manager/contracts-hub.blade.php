@extends('manager.layouts.app')

@section('title', 'Tüm Sözleşmeler')
@section('page_title', 'Tüm Sözleşmeler')

@push('head')
<style>
/* ═══ Contracts Hub ═══ */
.ch-wrap { display:grid; grid-template-columns:240px 1fr; gap:14px; }
@media(max-width:1000px){ .ch-wrap { grid-template-columns:1fr; } }

/* ── Sidebar (kategori ağacı) ── */
.ch-side { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:12px; height:fit-content; position:sticky; top:12px; }
.ch-side-title { font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid var(--u-line,#e5e9f0); }
.ch-cat { margin-bottom:8px; }
.ch-cat:last-child { margin-bottom:0; }
.ch-cat-head { display:flex; justify-content:space-between; align-items:center; padding:6px 8px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; color:var(--u-text,#0f172a); transition:background .1s; user-select:none; }
.ch-cat-head:hover { background:var(--u-bg,#f5f7fa); }
.ch-cat-head.active { background:#eef4ff; color:#1e40af; }
.ch-cat-count { font-size:10px; background:var(--u-line,#e5e9f0); color:var(--u-muted,#64748b); padding:1px 7px; border-radius:999px; font-weight:700; min-width:20px; text-align:center; }
.ch-cat-head.active .ch-cat-count { background:#1e40af; color:#fff; }
.ch-subs { margin-top:2px; display:none; padding-left:12px; }
.ch-cat.open .ch-subs { display:block; }
.ch-sub { display:flex; justify-content:space-between; align-items:center; padding:4px 8px; border-radius:5px; cursor:pointer; font-size:11px; color:var(--u-muted,#64748b); transition:all .1s; user-select:none; }
.ch-sub:hover { background:var(--u-bg,#f5f7fa); color:var(--u-text,#0f172a); }
.ch-sub.active { background:#dbeafe; color:#1e40af; font-weight:600; }
.ch-sub-count { font-size:10px; color:var(--u-muted,#9ca3af); }
.ch-sub.active .ch-sub-count { color:#1e40af; font-weight:700; }

/* ── Main panel ── */
.ch-main { min-width:0; }
.ch-toolbar { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:12px 14px; margin-bottom:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ch-search { flex:1; min-width:220px; position:relative; }
.ch-search input { width:100%; box-sizing:border-box; font-size:12px; padding:9px 12px 9px 34px; border:1px solid var(--u-line,#e5e9f0); border-radius:7px; background:#fff; color:var(--u-text,#0f172a); outline:none; }
.ch-search input:focus { border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.12); }
.ch-search::before { content:'🔍'; position:absolute; left:11px; top:50%; transform:translateY(-50%); font-size:13px; opacity:.6; }
.ch-view-toggle { display:flex; gap:0; border:1px solid var(--u-line,#e5e9f0); border-radius:7px; overflow:hidden; }
.ch-view-toggle button { padding:8px 14px; background:#fff; border:none; font-size:11px; font-weight:600; color:var(--u-muted,#64748b); cursor:pointer; transition:all .12s; }
.ch-view-toggle button:not(:last-child) { border-right:1px solid var(--u-line,#e5e9f0); }
.ch-view-toggle button.active { background:#1e40af; color:#fff; }
.ch-count-pill { font-size:11px; color:var(--u-muted,#64748b); padding:6px 10px; background:var(--u-bg,#f5f7fa); border-radius:7px; }
.ch-count-pill strong { color:var(--u-text,#0f172a); }

/* ── Results area ── */
.ch-results { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; overflow:hidden; }
.ch-empty { padding:40px 20px; text-align:center; color:var(--u-muted,#64748b); font-size:12px; }

/* List view */
.ch-list-table { width:100%; border-collapse:collapse; font-size:12px; }
.ch-list-table thead th { padding:10px 12px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; background:var(--u-bg,#f5f7fa); border-bottom:1px solid var(--u-line,#e5e9f0); white-space:nowrap; }
.ch-list-table tbody td { padding:10px 12px; border-bottom:1px solid var(--u-line,#e5e9f0); vertical-align:top; }
.ch-list-table tbody tr:last-child td { border-bottom:none; }
.ch-list-table tbody tr:hover { background:#f8fafc; }
.ch-list-table .pri { font-weight:600; color:var(--u-text,#0f172a); }
.ch-list-table .sub { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
.ch-list-table .btn { font-size:11px !important; padding:4px 12px !important; min-height:28px !important; text-decoration:none; }

/* Grid view */
.ch-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:10px; padding:12px; }
.ch-card { background:#fff; border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:12px 14px; transition:all .12s; display:flex; flex-direction:column; gap:6px; }
.ch-card:hover { border-color:#1e40af; box-shadow:0 2px 8px rgba(30,64,175,.08); transform:translateY(-1px); }
.ch-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
.ch-card-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); line-height:1.3; }
.ch-card-no { font-size:10px; color:var(--u-muted,#64748b); font-family:monospace; margin-top:2px; }
.ch-card-owner { font-size:12px; color:var(--u-text,#0f172a); padding-top:6px; border-top:1px dashed var(--u-line,#e5e9f0); }
.ch-card-meta { font-size:11px; color:var(--u-muted,#64748b); display:flex; justify-content:space-between; align-items:center; gap:6px; padding-top:6px; }
.ch-card-meta a { color:#1e40af; text-decoration:none; font-weight:600; }
.ch-card-meta a:hover { text-decoration:underline; }

/* Status badge */
.ch-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.ch-badge.approved        { background:#dcfce7; color:#15803d; }
.ch-badge.signed_uploaded { background:#dbeafe; color:#1d4ed8; }
.ch-badge.issued          { background:#fef9c3; color:#854d0e; }
.ch-badge.cancelled       { background:#fee2e2; color:#991b1b; }

/* Category tag (on card) */
.ch-tag { display:inline-block; padding:2px 8px; border-radius:5px; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
.ch-tag.personel { background:#eef4ff; color:#1e40af; }
.ch-tag.partner  { background:#fef3c7; color:#92400e; }
.ch-tag.danisan  { background:#f3e8ff; color:#6b21a8; }
.ch-tag.kurum    { background:#e0f2fe; color:#075985; }

/* Hidden rows (filter) */
.ch-hidden { display:none !important; }

/* ─── Dosyalar section (bottom) ─── */
.ch-files-section { margin-top:16px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:14px 16px; }
.ch-files-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--u-line,#e5e9f0); }
.ch-files-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); }
.ch-files-sub { font-size:11px; color:var(--u-muted,#64748b); }
.ch-files-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px; }
.ch-file-card { background:#fff; border:1px solid var(--u-line,#e5e9f0); border-radius:8px; padding:12px; display:flex; flex-direction:column; gap:8px; transition:all .12s; cursor:pointer; }
.ch-file-card:hover { border-color:#1e40af; box-shadow:0 2px 8px rgba(30,64,175,.08); transform:translateY(-1px); }
.ch-file-icon { font-size:32px; text-align:center; padding:10px 0; background:#fef2f2; border-radius:6px; color:#dc2626; }
.ch-file-title { font-size:12px; font-weight:700; color:var(--u-text,#0f172a); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ch-file-meta { font-size:10px; color:var(--u-muted,#64748b); display:flex; justify-content:space-between; gap:4px; }
.ch-file-actions { display:flex; gap:4px; padding-top:6px; border-top:1px dashed var(--u-line,#e5e9f0); }
.ch-file-actions a { flex:1; text-align:center; font-size:10px; font-weight:600; padding:4px 8px; border:1px solid var(--u-line,#e5e9f0); border-radius:5px; text-decoration:none; color:var(--u-text,#0f172a); transition:all .12s; }
.ch-file-actions a:hover { background:#eef4ff; border-color:#1e40af; color:#1e40af; }

/* ─── Preview Modal ─── */
.ch-modal { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.7); align-items:center; justify-content:center; padding:20px; }
.ch-modal.open { display:flex; }
.ch-modal-inner { background:#fff; border-radius:12px; width:90vw; max-width:1100px; height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.4); }
.ch-modal-head { padding:12px 18px; border-bottom:1px solid var(--u-line,#e5e9f0); display:flex; justify-content:space-between; align-items:center; gap:10px; }
.ch-modal-title { font-size:13px; font-weight:700; color:var(--u-text,#0f172a); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ch-modal-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }
.ch-modal-actions .btn { font-size:11px !important; padding:5px 12px !important; min-height:28px !important; text-decoration:none; }
.ch-modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:var(--u-muted,#64748b); line-height:1; padding:0 4px; }
.ch-modal-body { flex:1; overflow:hidden; background:#f5f7fa; }
.ch-modal-body iframe { width:100%; height:100%; border:none; display:block; }
</style>
@endpush

@section('content')

@include('partials.manager-hero', [
    'label' => 'Sözleşme Merkezi',
    'title' => 'Sözleşme Yönetimi',
    'sub'   => 'İmzalanmış, bekleyen ve iptal edilen tüm sözleşmeler kategori ağacında. Şablon yönetimi ve yeni kayıt oluşturma da bu merkezden.',
    'icon'  => '📜',
    'bg'    => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1400&q=80',
    'tone'  => 'slate',
    'stats' => [
        ['icon' => '📋', 'text' => ($totalCount ?? 0) . ' toplam kayıt'],
    ],
])

<div class="ch-wrap">

    {{-- ─── SIDEBAR: kategori ağacı ─── --}}
    <aside class="ch-side">
        <div class="ch-side-title">Kategoriler</div>

        {{-- Tümü --}}
        <div class="ch-cat">
            <div class="ch-cat-head active" data-cat="all">
                <span>📋 Tümü</span>
                <span class="ch-cat-count">{{ $totalCount }}</span>
            </div>
        </div>

        @foreach($tree as $catKey => $cat)
        <div class="ch-cat {{ $cat['count'] > 0 ? 'open' : '' }}">
            <div class="ch-cat-head" data-cat="{{ $catKey }}">
                <span>{{ $cat['label'] }}</span>
                <span class="ch-cat-count">{{ $cat['count'] }}</span>
            </div>
            <div class="ch-subs">
                @foreach($cat['subs'] as $subKey => $sub)
                <div class="ch-sub" data-cat="{{ $catKey }}" data-sub="{{ $subKey }}">
                    <span>{{ $sub['label'] }}</span>
                    <span class="ch-sub-count">{{ $sub['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </aside>

    {{-- ─── MAIN: toolbar + results ─── --}}
    <div class="ch-main">
        {{-- Toolbar --}}
        <div class="ch-toolbar">
            <div class="ch-search">
                <input type="text" id="chSearch" placeholder="İsim, soyisim, e-posta veya ID ile ara...">
            </div>
            <div class="ch-view-toggle">
                <button type="button" id="chViewList" class="active">📋 Liste</button>
                <button type="button" id="chViewGrid">🔳 Grid</button>
            </div>
            <div class="ch-count-pill"><strong id="chVisibleCount">{{ $rows->count() }}</strong> / {{ $totalCount }}</div>
        </div>

        {{-- Results --}}
        <div class="ch-results" id="chResults">
            @if($rows->isEmpty())
                <div class="ch-empty">
                    Henüz hiç bitmiş sözleşme yok.
                </div>
            @else
                {{-- LIST VIEW (default visible) --}}
                <div id="chListView">
                    <table class="ch-list-table">
                        <thead>
                            <tr>
                                <th>Sözleşme</th>
                                <th>Kategori</th>
                                <th>Sahip</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                            <tr class="ch-row"
                                data-cat="{{ $row['category'] }}"
                                data-sub="{{ $row['subcategory'] }}"
                                data-search="{{ strtolower($row['owner_name'] . ' ' . $row['owner_email'] . ' ' . $row['owner_code'] . ' ' . $row['contract_no']) }}">
                                <td>
                                    <div class="pri">{{ $row['title'] }}</div>
                                    <div class="sub">{{ $row['contract_no'] }}</div>
                                </td>
                                <td>
                                    <span class="ch-tag {{ $row['category'] }}">{{ ucfirst($row['category']) }}</span>
                                    <div class="sub">{{ $tree[$row['category']]['subs'][$row['subcategory']]['label'] ?? $row['subcategory'] }}</div>
                                </td>
                                <td>
                                    <div class="pri">{{ $row['owner_name'] ?: '—' }}</div>
                                    <div class="sub">{{ $row['owner_email'] ?: $row['owner_code'] }}</div>
                                </td>
                                <td class="sub">{{ $row['issued_at'] ?: '—' }}</td>
                                <td><span class="ch-badge {{ $row['status'] }}">{{ $row['status_label'] }}</span></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if(!empty($row['has_file']) && !empty($row['preview_url']))
                                        <button type="button" class="btn ch-preview-btn" data-preview-url="{{ $row['preview_url'] }}" data-download-url="{{ $row['download_url'] }}" data-title="{{ $row['title'] }} — {{ $row['owner_name'] }}" style="background:#1e40af;color:#fff;margin-right:4px;border:none;cursor:pointer;">👁 Önizle</button>
                                    @else
                                        <span class="sub" style="margin-right:4px;color:#94a3b8;" title="Dosya yok">📄 —</span>
                                    @endif
                                    <a class="btn" href="{{ $row['view_url'] }}">Aç</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- GRID VIEW (hidden by default) --}}
                <div id="chGridView" style="display:none;">
                    <div class="ch-grid">
                        @foreach($rows as $row)
                        <div class="ch-card ch-row"
                             data-cat="{{ $row['category'] }}"
                             data-sub="{{ $row['subcategory'] }}"
                             data-search="{{ strtolower($row['owner_name'] . ' ' . $row['owner_email'] . ' ' . $row['owner_code'] . ' ' . $row['contract_no']) }}">
                            <div class="ch-card-head">
                                <div>
                                    <div class="ch-card-title">{{ $row['title'] }}</div>
                                    <div class="ch-card-no">{{ $row['contract_no'] }}</div>
                                </div>
                                <span class="ch-tag {{ $row['category'] }}">{{ ucfirst($row['category']) }}</span>
                            </div>
                            <div class="ch-card-owner">
                                <strong>{{ $row['owner_name'] ?: '—' }}</strong><br>
                                <span style="font-size:11px;color:var(--u-muted,#64748b);">{{ $row['owner_email'] ?: $row['owner_code'] }}</span>
                            </div>
                            <div class="ch-card-meta">
                                <span>
                                    <span class="ch-badge {{ $row['status'] }}">{{ $row['status_label'] }}</span>
                                    <span style="margin-left:4px;">{{ $row['issued_at'] ?: '—' }}</span>
                                </span>
                                <span style="display:flex;gap:6px;align-items:center;">
                                    @if(!empty($row['has_file']) && !empty($row['preview_url']))
                                        <button type="button" class="ch-preview-btn" data-preview-url="{{ $row['preview_url'] }}" data-download-url="{{ $row['download_url'] }}" data-title="{{ $row['title'] }} — {{ $row['owner_name'] }}" style="color:#1e40af;font-weight:700;background:none;border:none;cursor:pointer;padding:0;font-size:11px;">👁 Önizle</button>
                                    @else
                                        <span style="color:#94a3b8;" title="Dosya yok">📄 —</span>
                                    @endif
                                    <a href="{{ $row['view_url'] }}">Aç →</a>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ─── Dosyalar: tüm PDF'ler tek sayfada ─── --}}
@php $filesOnly = $rows->filter(fn($r) => !empty($r['has_file'])); @endphp
@if($filesOnly->isNotEmpty())
<section class="ch-files-section">
    <div class="ch-files-head">
        <div>
            <div class="ch-files-title">📁 Sözleşme Dosyaları</div>
            <div class="ch-files-sub">PDF yüklü tüm sözleşmeler — önizlemek için tıkla</div>
        </div>
        <span class="ch-count-pill"><strong>{{ $filesOnly->count() }}</strong> dosya</span>
    </div>
    <div class="ch-files-grid">
        @foreach($filesOnly as $row)
        <div class="ch-file-card ch-preview-btn" data-preview-url="{{ $row['preview_url'] }}" data-download-url="{{ $row['download_url'] }}" data-title="{{ $row['title'] }} — {{ $row['owner_name'] }}">
            <div class="ch-file-icon">📄</div>
            <div class="ch-file-title" title="{{ $row['title'] }}">{{ $row['title'] }}</div>
            <div class="ch-file-meta">
                <span>{{ $row['contract_no'] }}</span>
                <span class="ch-tag {{ $row['category'] }}" style="padding:1px 6px;font-size:9px;">{{ ucfirst($row['category']) }}</span>
            </div>
            <div class="ch-file-meta">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px;">{{ $row['owner_name'] ?: '—' }}</span>
                <span>{{ $row['issued_at'] ?: '—' }}</span>
            </div>
            <div class="ch-file-actions">
                <a href="{{ $row['preview_url'] }}" target="_blank" onclick="event.stopPropagation();">Aç</a>
                <a href="{{ $row['download_url'] }}" onclick="event.stopPropagation();">İndir</a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- ─── Preview Modal ─── --}}
<div id="chPreviewModal" class="ch-modal">
    <div class="ch-modal-inner">
        <div class="ch-modal-head">
            <div class="ch-modal-title" id="chPreviewTitle">Önizleme</div>
            <div class="ch-modal-actions">
                <a id="chPreviewDownload" class="btn btn-primary" href="#">⬇ İndir</a>
                <a id="chPreviewOpen" class="btn alt" href="#" target="_blank">↗ Yeni Sekme</a>
                <button type="button" class="ch-modal-close" id="chPreviewClose">✕</button>
            </div>
        </div>
        <div class="ch-modal-body">
            <iframe id="chPreviewFrame" src="about:blank"></iframe>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var searchInput = document.getElementById('chSearch');
    var visibleCount = document.getElementById('chVisibleCount');
    var btnList = document.getElementById('chViewList');
    var btnGrid = document.getElementById('chViewGrid');
    var listView = document.getElementById('chListView');
    var gridView = document.getElementById('chGridView');

    var activeCat = 'all';
    var activeSub = null;

    function applyFilters() {
        var q = (searchInput && searchInput.value || '').toLowerCase().trim();
        var rows = document.querySelectorAll('.ch-row');
        var visible = 0;
        rows.forEach(function(row){
            var rowCat = row.getAttribute('data-cat');
            var rowSub = row.getAttribute('data-sub');
            var rowText = row.getAttribute('data-search') || '';

            var catOk = (activeCat === 'all') || (rowCat === activeCat);
            var subOk = (!activeSub) || (rowSub === activeSub);
            var searchOk = (!q) || (rowText.indexOf(q) !== -1);

            var show = catOk && subOk && searchOk;
            row.classList.toggle('ch-hidden', !show);
            if (show) visible++;
        });
        // List/grid both share .ch-row, but visible count should reflect unique items. Since grid duplicates
        // list rows (both render), we count only half when grid is active or only from list view.
        // Simpler: always count from the currently visible view.
        var activeView = (gridView && gridView.style.display !== 'none') ? gridView : listView;
        var activeVisible = activeView ? activeView.querySelectorAll('.ch-row:not(.ch-hidden)').length : 0;
        if (visibleCount) visibleCount.textContent = activeVisible;
    }

    // Search input
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    // Category tree clicks
    document.querySelectorAll('.ch-cat-head').forEach(function(el){
        el.addEventListener('click', function(){
            document.querySelectorAll('.ch-cat-head, .ch-sub').forEach(function(e){ e.classList.remove('active'); });
            el.classList.add('active');
            activeCat = el.getAttribute('data-cat');
            activeSub = null;
            applyFilters();
        });
    });
    document.querySelectorAll('.ch-sub').forEach(function(el){
        el.addEventListener('click', function(e){
            e.stopPropagation();
            document.querySelectorAll('.ch-cat-head, .ch-sub').forEach(function(x){ x.classList.remove('active'); });
            el.classList.add('active');
            activeCat = el.getAttribute('data-cat');
            activeSub = el.getAttribute('data-sub');
            applyFilters();
        });
    });

    // View toggle
    function setView(mode){
        if (!listView || !gridView) return;
        if (mode === 'list') {
            listView.style.display = '';
            gridView.style.display = 'none';
            btnList.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            listView.style.display = 'none';
            gridView.style.display = '';
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        }
        applyFilters();
    }
    if (btnList) btnList.addEventListener('click', function(){ setView('list'); });
    if (btnGrid) btnGrid.addEventListener('click', function(){ setView('grid'); });

    // ─── Preview Modal ───
    var modal = document.getElementById('chPreviewModal');
    var frame = document.getElementById('chPreviewFrame');
    var titleEl = document.getElementById('chPreviewTitle');
    var downloadLink = document.getElementById('chPreviewDownload');
    var openLink = document.getElementById('chPreviewOpen');
    var closeBtn = document.getElementById('chPreviewClose');

    function openPreview(previewUrl, downloadUrl, title){
        if (!modal || !frame) return;
        frame.src = previewUrl;
        titleEl.textContent = title || 'Önizleme';
        if (downloadLink) downloadLink.href = downloadUrl || previewUrl;
        if (openLink) openLink.href = previewUrl;
        modal.classList.add('open');
    }
    function closePreview(){
        if (!modal || !frame) return;
        modal.classList.remove('open');
        frame.src = 'about:blank';
    }

    document.querySelectorAll('.ch-preview-btn').forEach(function(el){
        el.addEventListener('click', function(e){
            // file-card üzerinde link'lere tıklamayı bloklama
            if (e.target && e.target.tagName === 'A') return;
            e.preventDefault();
            var p = this.getAttribute('data-preview-url');
            var d = this.getAttribute('data-download-url');
            var t = this.getAttribute('data-title');
            if (p) openPreview(p, d, t);
        });
    });
    if (closeBtn) closeBtn.addEventListener('click', closePreview);
    if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) closePreview(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal && modal.classList.contains('open')) closePreview(); });
})();
</script>
@endpush
