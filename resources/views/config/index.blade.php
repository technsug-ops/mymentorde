@extends('manager.layouts.app')

@section('title', 'MentorDE Config Panel')
@section('page_title', 'Sistem Ayarları')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    /* Tüm kurallar .cfg-page ile scope'landı — portal CSS'ine müdahale etmez */
    .cfg-page .grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }
    .cfg-page .card {
        background: var(--u-card);
        border: 1px solid var(--u-line);
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 6px 16px rgba(15, 42, 72, 0.05);
    }
    .cfg-page .card h2 { margin: 0; font-size: 18px; }
    .cfg-page .meta { color: var(--u-muted); font-size: 13px; margin-bottom: 10px; overflow-wrap: anywhere; word-break: break-word; }
    .cfg-page .list {
        max-height: 320px;
        overflow: auto;
        border: 1px solid var(--u-line);
        border-radius: 10px;
        background: #fbfdff;
        padding: 8px;
        margin-bottom: 12px;
    }
    .cfg-page .item {
        border-bottom: 1px solid #e8eef5;
        padding: 8px 4px;
        font-size: 14px;
        overflow-wrap: anywhere;
        word-break: break-word;
        display: block;
    }
    .cfg-page .item:last-child { border-bottom: none; }
    .accordion-item { border: 1px solid var(--u-line); border-radius: 10px; background: #fff; margin-bottom: 8px; padding: 0; }
    .accordion-item summary { list-style: none; cursor: pointer; padding: 10px; display: flex; justify-content: space-between; gap: 8px; align-items: center; }
    .accordion-item summary::-webkit-details-marker { display: none; }
    .status-compact { margin-top: 8px; padding: 8px 10px; border: 1px solid #dbe7ff; border-radius: 8px; background: #f7faff; color: #274062; }
    .status-compact.error { border-color: #f3c2c2; background: #fff6f6; color: #7a1c1c; }
    .status-compact details { margin-top: 6px; }
    .status-compact pre { margin: 6px 0 0 0; padding: 8px; max-height: 180px; overflow: auto; white-space: pre-wrap; word-break: break-word; border: 1px dashed #d9d9d9; border-radius: 6px; background: #fff; font-size: 12px; color: #334e6d; }
    .accordion-title { font-weight: 700; color: var(--u-text); }
    .accordion-meta { font-size: 12px; color: var(--u-muted); }
    .accordion-body { border-top: 1px dashed #e2eaf4; padding: 10px; font-size: 12px; color: #425c7e; background: #fafcff; }
    .badge-mini { border: 1px solid var(--u-line); border-radius: 999px; padding: 2px 8px; font-size: 11px; color: #294a74; background: #f5f9ff; white-space: nowrap; }
    .cfg-page .row { display: flex; gap: 8px; margin-bottom: 8px; min-width: 0; }
    .cfg-page .row > input, .cfg-page .row > select { flex: 1 1 0; min-width: 0; max-width: 100%; }
    .cfg-page .row > button { flex: 0 0 auto; }
    .cfg-page .row-wrap { flex-wrap: wrap; }
    .cfg-page .row-wrap > button { flex: 1 1 calc(50% - 8px); min-width: 140px; text-align: center; }
    .cfg-page input, .cfg-page select { width: 100%; border: 1px solid var(--u-line); border-radius: 8px; padding: 9px 10px; font-size: 14px; max-width: 100%; }
    .cfg-page select { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cfg-page button { border: 0; border-radius: 8px; background: var(--u-brand); color: #fff; padding: 9px 12px; cursor: pointer; font-size: 14px; }
    .cfg-page .status { margin-top: 8px; font-size: 13px; color: var(--u-muted); min-height: 18px; overflow-wrap: anywhere; word-break: break-word; }
    .cfg-page .status.error { color: #b91c1c; font-weight: 600; }
    .cfg-page .systematic-invalid { border-color: #dc2626 !important; box-shadow: 0 0 0 1px rgba(220,38,38,.22); }
    .cfg-page .field-hint { margin-top: 4px; margin-bottom: 6px; font-size: 12px; color: var(--u-muted); line-height: 1.25; }
    .cfg-page .row > .field-hint { flex: 0 0 100%; width: 100%; margin-top: -2px; margin-bottom: 0; }
    .cfg-page .field-hint.ok { color: #0f766e; }
    .cfg-page .field-hint.error { color: #b91c1c; font-weight: 600; }
    .cfg-page .status-pill { display: inline-flex; align-items: center; min-height: 34px; border: 1px dashed var(--u-line); border-radius: 8px; padding: 0 10px; background: #fff; }
    .cfg-page-btn { border: 0; border-radius: 8px; background: var(--u-brand); color: #fff; padding: 9px 12px; cursor: pointer; font-size: 14px; white-space: nowrap; }

    /* ── Info butonu ── */
    .cfg-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
    .info-tag { position: relative; flex-shrink: 0; }
    .info-tag > summary { list-style: none; cursor: pointer; background: #e8f0fe; border: 1px solid #b3c8f7; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #1f5fa6; user-select: none; margin-top: 2px; }
    .info-tag > summary::-webkit-details-marker { display: none; }
    .info-tag[open] > summary { background: #1f6fd9; color: #fff; border-color: #1f6fd9; }
    .info-panel { position: absolute; right: 0; top: calc(100% + 6px); z-index: 300; background: #fff; border: 1px solid #c8daef; border-radius: 10px; padding: 10px 14px; width: 300px; font-size: 12px; line-height: 1.6; color: #253a56; box-shadow: 0 8px 24px rgba(15,42,72,.14); }
    .info-panel strong { color: #1f4e8c; }

    @media (max-width: 700px) {
        .cfg-page .row { flex-wrap: wrap; }
        .cfg-page .row > input, .cfg-page .row > select, .cfg-page .row > button { flex: 1 1 100%; }
    }

    /* ── Sekmeli Navigasyon ── */
    .cfg-tabs { display:flex; border-bottom:2px solid #e2e8f0; overflow-x:auto; scrollbar-width:none; margin-bottom:16px; gap:2px; }
    .cfg-tabs::-webkit-scrollbar { display:none; }
    .cfg-tab { padding:9px 14px; font-size:11px; font-weight:700; color:#64748b; border:none; background:none;
               border-bottom:3px solid transparent; margin-bottom:-2px; cursor:pointer; white-space:nowrap;
               text-transform:uppercase; letter-spacing:.04em; border-radius:6px 6px 0 0; transition:all .15s; }
    .cfg-tab:hover { color:#1e40af; background:#f0f4ff; }
    .cfg-tab.active { color:#fff; background:#1e40af; border-bottom-color:#1e40af; }
    .cfg-tab.cfg-tab-dim { opacity:.38; }
    .cfg-tab-cnt { display:inline-block; border-radius:999px; font-size:9px; padding:1px 5px; margin-left:4px;
                   font-weight:900; line-height:1.5; background:rgba(255,255,255,.28); }
    .cfg-tab:not(.active) .cfg-tab-cnt { background:#dbeafe; color:#1e40af; }
    /* Pane gizle/göster — !important ile portal CSS'ini geçersiz kıl */
    .cfg-page .cfg-pane               { display:none !important; }
    .cfg-page .cfg-pane.active        { display:grid !important;
                                        grid-template-columns:repeat(3, minmax(0,1fr));
                                        gap:16px; align-items:start; }
    .cfg-page .cfg-pane.p2.active     { grid-template-columns:repeat(2, minmax(0,1fr)); }
    .cfg-page .cfg-pane.p4.active     { grid-template-columns:repeat(4, minmax(0,1fr)); }
    @media(max-width:1100px){ .cfg-page .cfg-pane.p4.active { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:980px) { .cfg-page .cfg-pane.active,.cfg-page .cfg-pane.p2.active,.cfg-page .cfg-pane.p4.active { grid-template-columns:1fr; } }

    /* ── Canlı Arama ── */
    .cfg-search-wrap { position:relative; }
    .cfg-search-wrap::before { content:''; position:absolute; left:9px; top:50%; transform:translateY(-50%);
        width:13px; height:13px; pointer-events:none;
        background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath fill-rule='evenodd' d='M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z' clip-rule='evenodd'/%3E%3C/svg%3E") center/contain no-repeat; }
    .cfg-search-input { width:200px; padding:7px 10px 7px 28px; border:1px solid var(--u-line); border-radius:8px;
                        font-size:12px; background:#fff; transition:border-color .15s, box-shadow .15s; }
    .cfg-search-input:focus { outline:none; border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.12); }
    .cfg-search-input::placeholder { color:#94a3b8; }
    .cfg-no-res { padding:36px 16px; text-align:center; color:var(--u-muted); font-size:13px;
                  grid-column:1/-1; border:1px dashed var(--u-line); border-radius:10px; }
</style>
@endpush

@section('content')
<div class="cfg-page">
    <div class="panel" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        <strong style="white-space:nowrap;">Aktif Firma:</strong>
        <select id="companySwitchSelect" style="width:220px;" onchange="switchCompanyContext()"></select>
        <button type="button" class="cfg-page-btn" onclick="loadCompanies()">Firmalari Yenile</button>
        <span id="companyTopStatus" class="status status-pill" style="margin-top:0;min-width:180px;"></span>
        <div class="cfg-search-wrap" style="margin-left:auto;">
            <input type="search" id="cfgSearchInput" class="cfg-search-input"
                   placeholder="Ayarlarda ara..." oninput="cfgSearch(this.value)" autocomplete="off">
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="/manager/theme" class="btn">Tema</a>
            <a href="/student-card" class="btn">Student Card</a>
            <a href="/config/export-code/safe" class="btn">Kodu İndir</a>
            <a href="/demo" class="btn">Demo</a>
        </div>
    </div>

    {{-- Tab Bar --}}
    <div class="cfg-tabs">
        <button class="cfg-tab active" data-tab="firma"       onclick="cfgTab('firma',this)">🏢 Firma &amp; Kullanıcılar</button>
        <button class="cfg-tab"        data-tab="bayiler"     onclick="cfgTab('bayiler',this)">🤝 Bayiler</button>
        <button class="cfg-tab"        data-tab="surec"       onclick="cfgTab('surec',this)">⚙ Süreç &amp; Entegrasyon</button>
        <button class="cfg-tab"        data-tab="belgeler"    onclick="cfgTab('belgeler',this)">📄 Belgeler</button>
        <button class="cfg-tab"        data-tab="icerik"      onclick="cfgTab('icerik',this)">📝 İçerik &amp; Şablonlar</button>
        <button class="cfg-tab"        data-tab="analitik"    onclick="cfgTab('analitik',this)">📊 Analitik</button>
        <button class="cfg-tab"        data-tab="portallar"   onclick="cfgTab('portallar',this)">👥 Başvurular &amp; Portallar</button>
    </div>

    {{-- Panes — grid direkt pane üzerinde, wrapper div yok --}}
    <div class="cfg-pane p2 active" id="cfgPane-firma">
        @include('config.partials._company-users')
    </div>

    <div class="cfg-pane" id="cfgPane-bayiler">
        @include('config.partials._dealers')
    </div>

    <div class="cfg-pane p4" id="cfgPane-surec">
        @include('config.partials._processes-integrations')
    </div>

    <div class="cfg-pane" id="cfgPane-belgeler">
        @include('config.partials._documents')
    </div>

    <div class="cfg-pane" id="cfgPane-icerik">
        @include('config.partials._content')
    </div>

    <div class="cfg-pane p2" id="cfgPane-analitik">
        @include('config.partials._analytics')
    </div>

    <div class="cfg-pane" id="cfgPane-portallar">
        @include('config.partials._guests')
        @include('config.partials._portal-users')
    </div>
</div>

<datalist id="studentIdSuggestions"></datalist>
<datalist id="dealerIdSuggestions"></datalist>
<datalist id="seniorEmailSuggestions"></datalist>
<datalist id="seniorIdSuggestions"></datalist>
<datalist id="branchSuggestions"></datalist>
<datalist id="docCategorySuggestions"></datalist>
<datalist id="fieldSuggestions"></datalist>

<script>
var _cfgQ = '';

/* ── Tab geçişi ── */
function cfgTab(id, btn) {
    document.querySelectorAll('.cfg-pane').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.cfg-tab').forEach(function(b) { b.classList.remove('active'); });
    var pane = document.getElementById('cfgPane-' + id);
    if (pane) pane.classList.add('active');
    btn.classList.add('active');
    history.replaceState(null, '', '#' + id);
    if (_cfgQ) _cfgFilter(id); // arama aktifse bu sekmeyi de filtrele
}

/* ── Canlı Arama ── */
function cfgSearch(val) {
    _cfgQ = val.trim().toLowerCase();

    // Her sekmedeki eşleşme sayısını hesapla, badge ve dim güncelle
    document.querySelectorAll('.cfg-tab').forEach(function(btn) {
        var tabId = btn.dataset.tab;
        var pane  = document.getElementById('cfgPane-' + tabId);
        if (!pane) return;

        var cards   = pane.querySelectorAll('section.card');
        var matches = _cfgQ
            ? Array.from(cards).filter(function(c) {
                  return _cfgText(c).includes(_cfgQ);
              }).length
            : cards.length;

        // Badge
        var badge = btn.querySelector('.cfg-tab-cnt');
        if (_cfgQ) {
            if (!badge) { badge = document.createElement('span'); badge.className = 'cfg-tab-cnt'; btn.appendChild(badge); }
            badge.textContent = matches;
        } else {
            if (badge) badge.remove();
        }

        // Soluk renk → eşleşme yoksa
        btn.classList.toggle('cfg-tab-dim', _cfgQ.length > 0 && matches === 0);
    });

    // Aktif sekmeyi filtrele
    var activeBtn = document.querySelector('.cfg-tab.active');
    if (activeBtn) _cfgFilter(activeBtn.dataset.tab);
}

/* Aktif sekmede kartları göster/gizle */
function _cfgFilter(tabId) {
    var pane = document.getElementById('cfgPane-' + tabId);
    if (!pane) return;
    var visible = 0;

    pane.querySelectorAll('section.card').forEach(function(card) {
        var show = !_cfgQ || _cfgText(card).includes(_cfgQ);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // "Sonuç yok" mesajı
    var noRes = pane.querySelector('.cfg-no-res');
    if (visible === 0 && _cfgQ) {
        if (!noRes) {
            noRes = document.createElement('div');
            noRes.className = 'cfg-no-res';
            pane.appendChild(noRes);
        }
        noRes.textContent = '"' + _cfgQ + '" için bu sekmede sonuç bulunamadı — diğer sekmelere bakın.';
    } else if (noRes) {
        noRes.remove();
    }
}

/* Kartın aranabilir metni (başlık + meta) */
function _cfgText(card) {
    return ((card.querySelector('h2')?.textContent || '') + ' ' +
            (card.querySelector('.meta')?.textContent || '')).toLowerCase();
}

document.addEventListener('DOMContentLoaded', function() {
    var hash = location.hash.replace('#', '');
    var btn  = document.querySelector('[data-tab="' + hash + '"]');
    if (btn) cfgTab(hash, btn);
});
</script>
<script defer src="{{ Vite::asset('resources/js/csv-field.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/config-panel.js') }}"></script>
@endsection
