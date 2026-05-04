@extends('uni-match.layout')

@section('og_title', 'UniMatch ile sana özel ' . count($recommendations) . ' Almanya programı seçtim')
@section('og_description', config('brand.name') . ' UniMatch sihirbazı, ' . config('brand.catalog.program_count_full') . ' program arasından profil ve hedeflerime en uygun olanları sıraladı. Sen de dene → /uni-match')
@section('title', 'Sana özel program önerileri — UniMatch')

@php
    // ── Filter facet hesaplama (sidebar'da count badge'leri için) ──────
    $facets = [
        'field'    => [],
        'language' => [],
        'tuition'  => ['free' => 0, 'low' => 0, 'mid' => 0, 'high' => 0],
        'duration' => ['short' => 0, 'mid' => 0, 'long' => 0],
        'degree'   => [],
        'source'   => ['uni-assist' => 0, 'direkt' => 0],
    ];
    foreach ($recommendations as $r) {
        foreach (($r['study_fields'] ?? []) as $f) {
            $facets['field'][$f] = ($facets['field'][$f] ?? 0) + 1;
        }
        foreach (($r['languages_raw'] ?? []) as $l) {
            $facets['language'][$l] = ($facets['language'][$l] ?? 0) + 1;
        }
        $tu = (int) ($r['tuition_eur'] ?? 0);
        if ($tu === 0)         $facets['tuition']['free']++;
        elseif ($tu < 1000)    $facets['tuition']['low']++;
        elseif ($tu < 3000)    $facets['tuition']['mid']++;
        else                   $facets['tuition']['high']++;

        $du = (int) ($r['duration_semesters'] ?? 0);
        if ($du && $du <= 2)        $facets['duration']['short']++;
        elseif ($du && $du <= 4)    $facets['duration']['mid']++;
        elseif ($du > 4)            $facets['duration']['long']++;

        $deg = $r['degree_specification'] ?? null;
        if ($deg) {
            $facets['degree'][$deg] = ($facets['degree'][$deg] ?? 0) + 1;
        }

        if (! empty($r['is_uni_assist_member'])) $facets['source']['uni-assist']++;
        else $facets['source']['direkt']++;
    }
    arsort($facets['field']);
    arsort($facets['language']);
    arsort($facets['degree']);
    $favList = (array) ($response->favorite_program_ids ?? []);
@endphp

@push('scripts')
<style>
/* ════════════════════════════════════════════════════════════════════
   Bachelorsportal tarzı result page — temiz beyaz cardlar + zengin filter
   sidebar + vertical list (grid değil)
════════════════════════════════════════════════════════════════════ */

/* Hero (üst mor bant — Bachelorsportal mavi bant gibi) */
.bp-hero {
    margin: 0 calc(-1 * (50vw - 50%));
    padding: 28px 20px 24px;
    background: linear-gradient(135deg, #7e58bf 0%, #6a47a8 100%);
    color: #fff;
    margin-bottom: 0;
}
.bp-hero-inner { max-width: 1180px; margin: 0 auto; }
.bp-hero-meta { font-size: 13px; opacity: .85; margin-bottom: 6px; display: flex; gap: 8px; align-items: center; }
.bp-hero-meta a { color: #fff; text-decoration: none; opacity: .85; }
.bp-hero-meta a:hover { opacity: 1; text-decoration: underline; }
.bp-hero-title { font-size: 28px; font-weight: 700; letter-spacing: -.5px; line-height: 1.2; margin-bottom: 14px; }
.bp-hero-title strong { color: #fff; }
.bp-hero-tabs { display: flex; gap: 24px; align-items: center; }
.bp-hero-tab {
    color: #fff; text-decoration: none; font-size: 15px; font-weight: 600;
    padding: 8px 0; border-bottom: 3px solid transparent;
    transition: border-color .2s cubic-bezier(.4,0,.2,1);
}
.bp-hero-tab.active { border-bottom-color: #fff; }
.bp-hero-tab:not(.active) { opacity: .7; }
.bp-hero-tab:hover { opacity: 1; }

/* Body wide */
.bp-wide {
    margin: 0 calc(-1 * (50vw - 50%));
    padding: 28px 20px 60px;
    background: #f4f2ee;
}
.bp-inner { max-width: 1180px; margin: 0 auto; }

.bp-toolbar {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 12px;
    margin-bottom: 18px; padding: 0 4px;
}
.bp-result-count { font-size: 13px; color: #1a1a1a; font-weight: 600; }
.bp-result-count strong { color: #7e58bf; }
.bp-toolbar-right { display: flex; gap: 14px; align-items: center; font-size: 13px; color: #6b5894; font-weight: 600; }
.bp-toolbar-right select {
    font-family: inherit; font-size: 13px; font-weight: 600;
    padding: 6px 10px; border: 1px solid #d4c5e8; border-radius: 6px;
    background: #fff; color: #1a1a1a; cursor: pointer;
}

/* Layout: sidebar + main */
.bp-layout {
    display: grid; grid-template-columns: 280px 1fr; gap: 18px;
    align-items: start;
}
@media (max-width: 880px) {
    .bp-layout { grid-template-columns: 1fr; }
    .bp-sidebar { position: relative !important; max-height: none !important; }
}

/* ── SIDEBAR (filter accordion) ───────────────────────────────────── */
.bp-sidebar {
    position: sticky; top: 8px;
    background: #fff; border: 1px solid #e5e5e5;
    border-radius: 10px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
}
.bp-sidebar-section {
    border-bottom: 1px solid #f0ecf6;
    padding: 0;
}
.bp-sidebar-section:last-child { border-bottom: none; }
.bp-section-toggle {
    width: 100%; padding: 16px 18px;
    display: flex; justify-content: space-between; align-items: center;
    background: transparent; border: none; cursor: pointer;
    font-family: inherit; font-size: 14px; font-weight: 700; color: #1a1a1a;
    text-align: left;
}
.bp-section-toggle:hover { background: #f9f6fc; }
.bp-section-toggle .bp-section-arrow {
    color: #7e58bf; font-size: 16px; transition: transform .2s cubic-bezier(.4,0,.2,1);
}
.bp-section-toggle[aria-expanded="false"] .bp-section-arrow { transform: rotate(-90deg); }

.bp-section-body { padding: 0 18px 14px; max-height: 280px; overflow-y: auto; }
.bp-section-body[hidden] { display: none; }

/* Selected filters */
.bp-active-filters { padding: 14px 18px; background: #faf7fd; }
.bp-active-filters-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px;
}
.bp-active-filters-head h3 { font-size: 13px; font-weight: 700; color: #1a1a1a; }
.bp-active-filters-clear {
    background: none; border: none; color: #7e58bf;
    font-family: inherit; font-size: 12px; font-weight: 600; cursor: pointer;
}
.bp-active-filters-clear:hover { text-decoration: underline; }
.bp-active-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.bp-active-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px;
    border: 1px solid #b79ae9;
    border-radius: 999px;
    background: #fff;
    font-size: 11.5px; font-weight: 600; color: #6c47a8;
}
.bp-active-chip button {
    background: none; border: none; cursor: pointer;
    color: #6c47a8; font-size: 14px; line-height: 1; padding: 0;
}
.bp-empty-active { font-size: 12px; color: #8a7baf; }

/* Filter checkbox row */
.bp-filter-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 7px 0; font-size: 13px; color: #1a1a1a;
    cursor: pointer; user-select: none;
}
.bp-filter-row:hover { color: #6c47a8; }
.bp-filter-row input[type="checkbox"] {
    width: 16px; height: 16px; accent-color: #7e58bf;
    margin-right: 10px; cursor: pointer; flex-shrink: 0;
}
.bp-filter-row-label {
    flex: 1; display: inline-flex; align-items: center;
    line-height: 1.4;
}
.bp-filter-row-count {
    font-size: 11.5px; color: #8a7baf; font-weight: 600;
    background: #f4f2ee; padding: 2px 7px; border-radius: 999px;
    flex-shrink: 0;
}
.bp-filter-row.is-disabled { opacity: .4; pointer-events: none; }

/* ── MAIN: kart listesi (vertical, NOT grid) ──────────────────────── */
.bp-results { display: flex; flex-direction: column; gap: 14px; }

.bp-card {
    background: #fff; border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 20px 22px;
    transition: border-color .2s cubic-bezier(.4,0,.2,1),
                box-shadow .25s cubic-bezier(.4,0,.2,1),
                transform .25s cubic-bezier(.4,0,.2,1);
    will-change: transform;
}
.bp-card:hover {
    border-color: #b79ae9;
    box-shadow: 0 6px 20px rgba(126,88,191,.10);
    transform: translateY(-1px);
}

.bp-card-top {
    display: flex; align-items: flex-start; gap: 14px;
    margin-bottom: 10px;
}
.bp-uni-logo {
    width: 48px; height: 48px; border-radius: 8px;
    background: linear-gradient(135deg, #7e58bf, #a07ed9);
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; font-weight: 700;
    flex-shrink: 0; letter-spacing: -.5px;
    text-transform: uppercase;
}
.bp-uni-info { flex: 1; min-width: 0; }
.bp-uni-name {
    font-size: 14px; font-weight: 700; color: #1a1a1a;
    line-height: 1.3; display: inline;
}
.bp-uni-rating {
    font-size: 12.5px; color: #7e58bf; font-weight: 700;
    margin-left: 6px; white-space: nowrap;
}
.bp-uni-location {
    font-size: 12px; color: #6b5894; margin-top: 2px;
    display: flex; align-items: center; gap: 4px;
}
.bp-fav-btn {
    background: none; border: none; cursor: pointer;
    font-size: 22px; line-height: 1; color: #d4c5e8;
    flex-shrink: 0; padding: 0;
    transition: transform .2s cubic-bezier(.4,0,.2,1), color .2s;
}
.bp-fav-btn:hover { transform: scale(1.15); color: #a07ed9; }
.bp-fav-btn.is-fav { color: #f59e0b; }
.bp-fav-btn.is-fav:hover { color: #d97706; }

.bp-prog-name {
    font-size: 19px; font-weight: 700; color: #1a1a1a;
    line-height: 1.3; margin-bottom: 8px; letter-spacing: -.3px;
}
.bp-prog-desc {
    font-size: 13px; color: #6b5894; line-height: 1.55;
    margin-bottom: 12px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.bp-prog-tags {
    display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px;
}
.bp-tag {
    font-size: 11.5px; font-weight: 600;
    padding: 4px 11px; border-radius: 6px;
    background: #f4f2ee; color: #1a1a1a;
}
.bp-tag-degree   { background: rgba(126,88,191,.1); color: #6c47a8; }
.bp-tag-lang     { background: #ede9fe; color: #6d28d9; }
.bp-tag-free     { background: rgba(22,163,74,.12); color: #15803d; }
.bp-tag-paid     { background: #fef9c3; color: #854d0e; }
.bp-tag-uniassist{ background: rgba(217,119,6,.12); color: #92400e; }
.bp-tag-direkt   { background: rgba(5,150,105,.12); color: #065f46; }

.bp-card-reasons {
    font-size: 12px; color: #6b5894; line-height: 1.6;
    padding: 10px 12px; background: #faf7fd;
    border-radius: 6px; margin-bottom: 12px;
    border-left: 3px solid #b79ae9;
}
.bp-card-reasons div { margin-bottom: 2px; }

.bp-card-bottom {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
    padding-top: 10px; border-top: 1px solid #f0ecf6;
}
.bp-featured {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; color: #7e58bf; font-weight: 700;
}
.bp-card-meta {
    font-size: 13px; color: #1a1a1a; font-weight: 700;
}
.bp-card-meta-light { font-size: 12px; color: #6b5894; font-weight: 500; margin-right: 8px; }
.bp-card-cta {
    display: inline-flex; align-items: center; gap: 4px;
    color: #7e58bf; text-decoration: none;
    font-size: 13px; font-weight: 700;
    transition: gap .2s cubic-bezier(.4,0,.2,1);
}
.bp-card-cta:hover { gap: 8px; text-decoration: underline; }

.bp-empty {
    text-align: center; padding: 60px 20px;
    color: #8a7baf; font-size: 14px;
    background: #fff; border-radius: 10px; border: 1px dashed #d4c5e8;
}

@media (prefers-reduced-motion: no-preference) {
    .bp-card { animation: bp-card-in .35s cubic-bezier(.4,0,.2,1) both; }
    .bp-card:nth-child(1) { animation-delay: 50ms; }
    .bp-card:nth-child(2) { animation-delay: 100ms; }
    .bp-card:nth-child(3) { animation-delay: 150ms; }
    .bp-card:nth-child(4) { animation-delay: 200ms; }
    .bp-card:nth-child(5) { animation-delay: 250ms; }
    .bp-card:nth-child(n+6) { animation-delay: 300ms; }
    @keyframes bp-card-in {
        0% { opacity: 0; transform: translateY(8px); }
        100% { opacity: 1; transform: translateY(0); }
    }
}

/* Favorite + toast (mevcut) */
.fav-toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#1a1a1a; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; z-index:9999; box-shadow:0 8px 24px rgba(0,0,0,.25); opacity:0; transition:opacity .3s; pointer-events:none; }
.fav-toast.show { opacity:1; }
.fav-toast.error { background:#dc2626; }
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var toast = function(msg, isErr){
        var el = document.createElement('div');
        el.className = 'fav-toast' + (isErr ? ' error' : '');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function(){ el.classList.add('show'); }, 50);
        setTimeout(function(){ el.classList.remove('show'); setTimeout(function(){ el.remove(); }, 300); }, 2400);
    };

    var favSet = new Set({!! json_encode(array_map('strval', $favList)) !!});

    // ── Favorite toggle
    document.querySelectorAll('[data-favorite-toggle]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var pid = String(btn.dataset.programId);
            fetch('{{ route("uni-match.favorite.toggle") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ program_id: pid })
            }).then(function(r){ return r.json().then(function(d){ return { ok: r.ok, data: d }; }); })
            .then(function(res){
                if (! res.ok) {
                    toast(res.data.message || 'Favorilere eklenemedi', true);
                    return;
                }
                var sync = document.querySelectorAll('[data-program-id="' + pid + '"][data-favorite-toggle]');
                if (res.data.action === 'added') {
                    favSet.add(pid);
                    sync.forEach(function(b){ b.classList.add('is-fav'); });
                    toast('⭐ Favorilere eklendi (' + res.data.count + '/3)');
                } else {
                    favSet.delete(pid);
                    sync.forEach(function(b){ b.classList.remove('is-fav'); });
                    toast('✓ Favorilerden kaldırıldı');
                }
                applyAll();
            }).catch(function(){ toast('Bir hata oldu, tekrar dene', true); });
        });
    });

    // ── Filter accordion toggle
    document.querySelectorAll('.bp-section-toggle').forEach(function(btn){
        btn.addEventListener('click', function(){
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            var body = document.getElementById(btn.getAttribute('aria-controls'));
            if (body) body.hidden = open;
        });
    });

    // ── Filter logic (multi-checkbox AND between groups, OR within group)
    var activeFilters = { field: new Set(), language: new Set(), tuition: new Set(), duration: new Set(), degree: new Set(), source: new Set(), favorite: false };

    function tuitionTier(eur){
        eur = parseInt(eur || 0, 10);
        if (eur === 0) return 'free';
        if (eur < 1000) return 'low';
        if (eur < 3000) return 'mid';
        return 'high';
    }
    function durationTier(s){
        s = parseInt(s || 0, 10);
        if (s && s <= 2) return 'short';
        if (s && s <= 4) return 'mid';
        if (s > 4) return 'long';
        return null;
    }

    function applyAll(){
        var cards = document.querySelectorAll('.bp-card');
        var visible = 0;
        cards.forEach(function(card){
            var fields    = (card.dataset.fields || '').split('|').filter(Boolean);
            var langs     = (card.dataset.langs  || '').split('|').filter(Boolean);
            var tier      = card.dataset.tuitionTier || '';
            var dur       = card.dataset.durationTier || '';
            var degree    = card.dataset.degree || '';
            var source    = card.dataset.sourceType || '';
            var pid       = String(card.dataset.programId);

            var ok = true;
            if (activeFilters.field.size > 0) {
                ok = ok && fields.some(function(f){ return activeFilters.field.has(f); });
            }
            if (activeFilters.language.size > 0) {
                ok = ok && langs.some(function(l){ return activeFilters.language.has(l); });
            }
            if (activeFilters.tuition.size > 0)  ok = ok && activeFilters.tuition.has(tier);
            if (activeFilters.duration.size > 0) ok = ok && activeFilters.duration.has(dur);
            if (activeFilters.degree.size > 0)   ok = ok && activeFilters.degree.has(degree);
            if (activeFilters.source.size > 0)   ok = ok && activeFilters.source.has(source);
            if (activeFilters.favorite)          ok = ok && favSet.has(pid);

            card.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        var countEl = document.querySelector('[data-result-count]');
        if (countEl) countEl.textContent = visible;
        renderActiveChips();
    }

    function renderActiveChips(){
        var box = document.querySelector('[data-active-chips]');
        if (! box) return;
        box.innerHTML = '';
        var hasAny = false;

        function addChip(group, value, label){
            hasAny = true;
            var chip = document.createElement('span');
            chip.className = 'bp-active-chip';
            chip.innerHTML = '<span></span>';
            chip.querySelector('span').textContent = label;
            var x = document.createElement('button');
            x.type = 'button'; x.textContent = '×';
            x.setAttribute('aria-label', 'Kaldır: ' + label);
            x.addEventListener('click', function(){
                if (group === 'favorite') {
                    activeFilters.favorite = false;
                    var fb = document.querySelector('[data-filter-favorite]');
                    if (fb) fb.checked = false;
                } else {
                    activeFilters[group].delete(value);
                    var cb = document.querySelector('input[data-filter-group="' + group + '"][value="' + CSS.escape(value) + '"]');
                    if (cb) cb.checked = false;
                }
                applyAll();
            });
            chip.appendChild(x);
            box.appendChild(chip);
        }

        ['field','language','tuition','duration','degree','source'].forEach(function(g){
            activeFilters[g].forEach(function(v){
                var cb = document.querySelector('input[data-filter-group="' + g + '"][value="' + CSS.escape(v) + '"]');
                var label = cb ? cb.dataset.label : v;
                addChip(g, v, label);
            });
        });
        if (activeFilters.favorite) addChip('favorite', '_', '⭐ Favorilerim');

        var emptyEl = document.querySelector('[data-active-empty]');
        if (emptyEl) emptyEl.style.display = hasAny ? 'none' : '';
    }

    document.querySelectorAll('input[data-filter-group]').forEach(function(cb){
        cb.addEventListener('change', function(){
            var g = cb.dataset.filterGroup;
            var v = cb.value;
            if (cb.checked) activeFilters[g].add(v);
            else activeFilters[g].delete(v);
            applyAll();
        });
    });
    var favCb = document.querySelector('[data-filter-favorite]');
    if (favCb) favCb.addEventListener('change', function(){
        activeFilters.favorite = favCb.checked;
        applyAll();
    });

    var clearBtn = document.querySelector('[data-clear-filters]');
    if (clearBtn) clearBtn.addEventListener('click', function(){
        ['field','language','tuition','duration','degree','source'].forEach(function(g){
            activeFilters[g].clear();
        });
        activeFilters.favorite = false;
        document.querySelectorAll('input[data-filter-group]').forEach(function(cb){ cb.checked = false; });
        if (favCb) favCb.checked = false;
        applyAll();
    });

    // ── Sort
    var sortSelect = document.querySelector('[data-sort]');
    if (sortSelect) {
        sortSelect.addEventListener('change', function(){
            var list = document.querySelector('[data-bp-list]');
            if (! list) return;
            var cards = Array.from(list.querySelectorAll('.bp-card'));
            var mode = sortSelect.value;
            cards.sort(function(a, b){
                if (mode === 'tuition_asc')  return parseInt(a.dataset.tuition || '0', 10) - parseInt(b.dataset.tuition || '0', 10);
                if (mode === 'tuition_desc') return parseInt(b.dataset.tuition || '0', 10) - parseInt(a.dataset.tuition || '0', 10);
                return parseInt(a.dataset.rank || '99', 10) - parseInt(b.dataset.rank || '99', 10);
            });
            cards.forEach(function(c){ list.appendChild(c); });
        });
    }

    // İlk render
    renderActiveChips();
})();
</script>
@endpush

@section('content')
{{-- Üst hero (Bachelorsportal mavi bant tarzı) --}}
<section class="bp-hero">
    <div class="bp-hero-inner">
        <div class="bp-hero-meta">
            <a href="/">Anasayfa</a>
            <span style="opacity:.6;">›</span>
            <a href="{{ route('uni-match.landing') }}">UniMatch</a>
            <span style="opacity:.6;">›</span>
            <span>Sonuçlarım</span>
        </div>
        <h1 class="bp-hero-title">
            Sana özel <strong>{{ count($recommendations) }} program</strong> · 9-faktör akıllı sıralama
        </h1>
        <div class="bp-hero-tabs">
            <span class="bp-hero-tab active">Programlar</span>
            <a href="{{ route('uni-match.start') }}" class="bp-hero-tab">Yeniden Başla</a>
        </div>
    </div>
</section>

{{-- Body --}}
<div class="bp-wide">
    <div class="bp-inner">
        @if(count($recommendations) === 0)
            <div class="bp-empty">
                <p style="margin-bottom:14px;">Cevaplarına tam uyan program bulunamadı. Filtreleri biraz genişletmek için cevaplarını tekrar gözden geçirelim.</p>
                <a href="{{ route('uni-match.start') }}" class="sb-btn sb-btn-primary">Yeniden Başla</a>
            </div>
        @else
            {{-- Toolbar: count + sort --}}
            <div class="bp-toolbar">
                <div class="bp-result-count">
                    <strong data-result-count>{{ count($recommendations) }}</strong> program · sana özel sıralandı
                </div>
                <div class="bp-toolbar-right">
                    <span>EUR 🌍</span>
                    <label for="bp-sort">Sırala:</label>
                    <select id="bp-sort" data-sort>
                        <option value="match">Eşleşme skoru</option>
                        <option value="tuition_asc">Ücret (düşükten yükseğe)</option>
                        <option value="tuition_desc">Ücret (yüksekten düşüğe)</option>
                    </select>
                </div>
            </div>

            <div class="bp-layout">
                {{-- ─── SOL: filter sidebar ─── --}}
                <aside class="bp-sidebar" aria-label="Programları filtrele">
                    {{-- Selected filters --}}
                    <div class="bp-active-filters">
                        <div class="bp-active-filters-head">
                            <h3>Seçili filtreler</h3>
                            <button type="button" class="bp-active-filters-clear" data-clear-filters>Hepsini temizle</button>
                        </div>
                        <div class="bp-active-chips" data-active-chips></div>
                        <div class="bp-empty-active" data-active-empty>Henüz filtre yok</div>
                    </div>

                    {{-- Çalışma alanı --}}
                    @if(! empty($facets['field']))
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-field">
                            Çalışma Alanı <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-field">
                            @foreach($facets['field'] as $field => $cnt)
                                <label class="bp-filter-row">
                                    <input type="checkbox" data-filter-group="field" value="{{ $field }}" data-label="{{ $field }}">
                                    <span class="bp-filter-row-label">{{ $field }}</span>
                                    <span class="bp-filter-row-count">{{ $cnt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Dil --}}
                    @if(! empty($facets['language']))
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-lang">
                            Dil <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-lang">
                            @foreach($facets['language'] as $lang => $cnt)
                                <label class="bp-filter-row">
                                    <input type="checkbox" data-filter-group="language" value="{{ $lang }}" data-label="{{ $lang }}">
                                    <span class="bp-filter-row-label">{{ $lang }}</span>
                                    <span class="bp-filter-row-count">{{ $cnt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Ücret --}}
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-tuition">
                            Ücret <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-tuition">
                            @php $tuitionLabels = ['free' => 'Ücretsiz (Devlet)', 'low' => '<1.000€/sem', 'mid' => '1.000–3.000€/sem', 'high' => '3.000€+/sem']; @endphp
                            @foreach($tuitionLabels as $key => $label)
                                @if(($facets['tuition'][$key] ?? 0) > 0)
                                    <label class="bp-filter-row">
                                        <input type="checkbox" data-filter-group="tuition" value="{{ $key }}" data-label="{{ $label }}">
                                        <span class="bp-filter-row-label">{{ $label }}</span>
                                        <span class="bp-filter-row-count">{{ $facets['tuition'][$key] }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Süre --}}
                    @if(array_sum($facets['duration']) > 0)
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-dur">
                            Süre <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-dur">
                            @php $durLabels = ['short' => '1–2 sömestr', 'mid' => '3–4 sömestr', 'long' => '5+ sömestr']; @endphp
                            @foreach($durLabels as $key => $label)
                                @if(($facets['duration'][$key] ?? 0) > 0)
                                    <label class="bp-filter-row">
                                        <input type="checkbox" data-filter-group="duration" value="{{ $key }}" data-label="{{ $label }}">
                                        <span class="bp-filter-row-label">{{ $label }}</span>
                                        <span class="bp-filter-row-count">{{ $facets['duration'][$key] }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Derece --}}
                    @if(! empty($facets['degree']))
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-deg">
                            Derece <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-deg">
                            @foreach($facets['degree'] as $deg => $cnt)
                                <label class="bp-filter-row">
                                    <input type="checkbox" data-filter-group="degree" value="{{ $deg }}" data-label="{{ $deg }}">
                                    <span class="bp-filter-row-label">{{ $deg }}</span>
                                    <span class="bp-filter-row-count">{{ $cnt }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Başvuru tipi --}}
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-src">
                            Başvuru tipi <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-src">
                            @if($facets['source']['uni-assist'] > 0)
                                <label class="bp-filter-row">
                                    <input type="checkbox" data-filter-group="source" value="uni-assist" data-label="📨 uni-assist">
                                    <span class="bp-filter-row-label">📨 uni-assist</span>
                                    <span class="bp-filter-row-count">{{ $facets['source']['uni-assist'] }}</span>
                                </label>
                            @endif
                            @if($facets['source']['direkt'] > 0)
                                <label class="bp-filter-row">
                                    <input type="checkbox" data-filter-group="source" value="direkt" data-label="✅ Direkt">
                                    <span class="bp-filter-row-label">✅ Direkt</span>
                                    <span class="bp-filter-row-count">{{ $facets['source']['direkt'] }}</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    {{-- Listelerim --}}
                    <div class="bp-sidebar-section">
                        <button class="bp-section-toggle" type="button" aria-expanded="true" aria-controls="bp-sec-list">
                            Listelerim <span class="bp-section-arrow">▾</span>
                        </button>
                        <div class="bp-section-body" id="bp-sec-list">
                            <label class="bp-filter-row">
                                <input type="checkbox" data-filter-favorite>
                                <span class="bp-filter-row-label">⭐ Favorilerim</span>
                                <span class="bp-filter-row-count">{{ count($favList) }}</span>
                            </label>
                            <a href="{{ route('uni-match.result.pdf') }}"
                               class="bp-filter-row" style="text-decoration:none; padding-top:10px;">
                                <span class="bp-filter-row-label">📄 PDF olarak indir</span>
                            </a>
                        </div>
                    </div>
                </aside>

                {{-- ─── SAĞ: vertical card list ─── --}}
                <main class="bp-results" data-bp-list>
                    @foreach($recommendations as $i => $rec)
                        @php
                            $sourceType = ! empty($rec['is_uni_assist_member']) ? 'uni-assist' : 'direkt';
                            $tuitionVal = isset($rec['tuition_eur']) && $rec['tuition_eur'] !== null ? (int) $rec['tuition_eur'] : 0;
                            $tuitionTier = $tuitionVal === 0 ? 'free' : ($tuitionVal < 1000 ? 'low' : ($tuitionVal < 3000 ? 'mid' : 'high'));
                            $du = (int) ($rec['duration_semesters'] ?? 0);
                            $durTier = $du && $du <= 2 ? 'short' : ($du && $du <= 4 ? 'mid' : ($du > 4 ? 'long' : ''));
                            $isFav = in_array($rec['program_id'], $favList, true);
                            $uniInitial = mb_substr($rec['university_name'] ?? '?', 0, 1);
                            // İlk 10 görünür, 11+ pagination ile açılır (kullanıcı isterse)
                            $isHiddenInitially = $i >= 10;
                        @endphp
                        <article class="bp-card{{ $isHiddenInitially ? ' bp-card-extra' : '' }}"
                                 @if($isHiddenInitially) style="display:none;" @endif
                                 data-program-id="{{ $rec['program_id'] }}"
                                 data-rank="{{ $i + 1 }}"
                                 data-tuition="{{ $tuitionVal }}"
                                 data-tuition-tier="{{ $tuitionTier }}"
                                 data-duration-tier="{{ $durTier }}"
                                 data-degree="{{ $rec['degree_specification'] ?? '' }}"
                                 data-source-type="{{ $sourceType }}"
                                 data-fields="{{ implode('|', $rec['study_fields'] ?? []) }}"
                                 data-langs="{{ implode('|', $rec['languages_raw'] ?? []) }}">

                            <div class="bp-card-top">
                                <div class="bp-uni-logo" aria-hidden="true">{{ $uniInitial }}</div>
                                <div class="bp-uni-info">
                                    <span class="bp-uni-name">{{ $rec['university_name'] ?? '—' }}</span>
                                    <span class="bp-uni-rating">{{ $rec['match_score'] }}/100 ★</span>
                                    @if(! empty($rec['location']))
                                        <div class="bp-uni-location">📍 {{ $rec['location'] }}</div>
                                    @endif
                                </div>
                                <button type="button"
                                        data-favorite-toggle
                                        data-program-id="{{ $rec['program_id'] }}"
                                        class="bp-fav-btn {{ $isFav ? 'is-fav' : '' }}"
                                        title="Favorile (max 3)"
                                        aria-label="Favorile">★</button>
                            </div>

                            <h2 class="bp-prog-name">{{ $rec['course_name'] ?? '—' }}</h2>

                            @if(! empty($rec['description']))
                                <p class="bp-prog-desc">{{ \Illuminate\Support\Str::limit($rec['description'], 220) }}</p>
                            @endif

                            <div class="bp-prog-tags">
                                @if(! empty($rec['degree_specification']))
                                    <span class="bp-tag bp-tag-degree">{{ $rec['degree_specification'] }}</span>
                                @endif
                                @foreach(($rec['languages_raw'] ?? []) as $lang)
                                    <span class="bp-tag bp-tag-lang">{{ $lang }}</span>
                                @endforeach
                                @if($tuitionVal === 0)
                                    <span class="bp-tag bp-tag-free">✓ Ücretsiz</span>
                                @else
                                    <span class="bp-tag bp-tag-paid">{{ $tuitionVal }}€/sem</span>
                                @endif
                                @if($sourceType === 'uni-assist')
                                    <span class="bp-tag bp-tag-uniassist">📨 uni-assist</span>
                                @else
                                    <span class="bp-tag bp-tag-direkt">✅ Direkt</span>
                                @endif
                            </div>

                            @if(! empty($rec['reasons']))
                                <div class="bp-card-reasons">
                                    @foreach(array_slice($rec['reasons'], 0, 3) as $reason)
                                        <div>· {{ $reason }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="bp-card-bottom">
                                <span class="bp-featured">⭐ #{{ $i + 1 }} öneri</span>
                                <div>
                                    @if(! empty($rec['duration_semesters']))
                                        <span class="bp-card-meta-light">{{ $rec['duration_semesters'] }} sömestr</span>
                                    @endif
                                    <span class="bp-card-meta">
                                        @if($tuitionVal === 0) Ücretsiz @else {{ $tuitionVal }}€/sem @endif
                                    </span>
                                </div>
                                <a href="{{ route('program.show', ['program' => $rec['program_id']]) }}" target="_blank"
                                   class="bp-card-cta">
                                    Detayları Gör <span style="font-size:14px;">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach

                    {{-- Pagination — 11+ programlar başlangıçta gizli, kullanıcı tıklayınca açılır --}}
                    @if(count($recommendations) > 10)
                        @php $extraCount = count($recommendations) - 10; @endphp
                        <button type="button" id="bp-show-more-btn" data-track="cta_clicked" data-ph-cta-name="result_show_more"
                                style="margin:24px auto;display:flex;align-items:center;gap:8px;padding:13px 28px;background:#fff;color:#7e58bf;border:2px solid #7e58bf;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;transition:all .15s;">
                            ↓ Daha fazla program göster (<span id="bp-extra-count">{{ $extraCount }}</span> tane daha)
                        </button>
                    @endif
                </main>
            </div>
        @endif
    </div>
</div>

@if(count($recommendations) > 10)
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var btn = document.getElementById('bp-show-more-btn');
    if (!btn) return;
    btn.addEventListener('click', function(){
        document.querySelectorAll('.bp-card-extra').forEach(function(card){
            card.style.display = '';
        });
        btn.style.display = 'none';
    });
})();
</script>
@endif

{{-- Sosyal proof + share + PDF magnet + CTA korunuyor (eski hâliyle) --}}
@if(count($recommendations) > 0)
    @if(($socialProof ?? 0) >= 5)
    <div style="text-align:center;margin:20px auto;padding:12px 18px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;max-width:680px;">
        <div style="font-size:13px;color:#166534;font-weight:600;">
            <span style="display:inline-block;width:8px;height:8px;background:#16a34a;border-radius:50%;animation:pulse 1.5s infinite;margin-right:6px;vertical-align:middle;"></span>
            Son 7 günde <strong style="color:#15803d;">{{ number_format($socialProof) }}</strong> öğrenci UniMatch'ı tamamladı
        </div>
    </div>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
    @endif

    @php
        $shareText = "🎯 " . config('brand.name') . " UniMatch sihirbazı bana özel " . count($recommendations) . " Almanya programı seçti! Sen de dene:";
        $shareUrl = url('/uni-match');
    @endphp
    <div style="margin: 20px auto; padding: 14px 18px; background: #f9f6fc; border-radius: 10px; border-left: 4px solid #7e58bf; max-width:680px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div style="flex:1;min-width:200px;">
                <div style="font-size: 14px; font-weight: 700; color: #6b5894;">📢 Bunu paylaş</div>
                <div style="font-size: 12px; color: #8a7baf; margin-top: 2px;">Almanya'ya gitmek isteyen arkadaşların da denemeli</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="https://wa.me/?text={{ urlencode($shareText . ' ' . $shareUrl) }}" target="_blank" rel="noopener"
                   style="background:#25d366;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">💬 WhatsApp</a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener"
                   style="background:#000;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">𝕏 Twitter</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener"
                   style="background:#0a66c2;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">💼 LinkedIn</a>
                <a href="mailto:?subject={{ urlencode('UniMatch — Almanya programı bul') }}&body={{ urlencode($shareText . ' ' . $shareUrl) }}"
                   style="background:#7e58bf;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">✉️ E-posta</a>
            </div>
        </div>
    </div>

    @php $favCount = count($favList); @endphp
    <div style="margin: 20px auto; padding: 14px 18px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px; border-left: 4px solid #d97706; max-width:680px;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="font-size: 26px;">📄</div>
            <div style="flex: 1; min-width: 200px;">
                <div style="font-size: 14px; font-weight: 700; color: #78350f;">Sonuçlarımı PDF olarak indir</div>
                <div style="font-size: 12px; color: #92400e; margin-top: 2px;">Tüm {{ count($recommendations) }} program + profilin — paylaşıma hazır</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('uni-match.result.pdf') }}" style="background: #92400e; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">Tümünü İndir →</a>
                @if($favCount > 0)
                <a href="{{ route('uni-match.result.pdf') }}?favorites=1" style="background: #f59e0b; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">⭐ {{ $favCount }} Favorimi İndir</a>
                @endif
            </div>
        </div>
    </div>

    <div class="sb-card" style="margin: 24px auto; text-align: center; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03)); max-width:680px;">
        <div style="font-size: 32px; margin-bottom: 8px;">🚀</div>
        <h2 class="sb-title">Hadi adım atalım</h2>
        <p class="sb-subtitle">{{ config('brand.name') }}'ye kayıt ol, danışmanın bu programlardan hangisinin sana en uygun olduğunu birlikte değerlendirin. Cevapların form'a otomatik aktarılacak — sadece kalan bilgileri tamamlarsın.</p>
        <form method="POST" action="{{ route('uni-match.convert') }}">
            @csrf
            <button type="submit" class="sb-btn sb-btn-primary" style="padding: 16px 36px; font-size: 16px; font-weight: 700;">
                Şimdi Kayıt Ol & Danışmanla Görüş <span style="font-size: 18px;">→</span>
            </button>
        </form>
        <div style="margin-top: 14px; font-size: 12px; color: #8a7baf;">
            Wizard cevapların kaydedildi — istediğin zaman bu sayfaya geri dönebilirsin.
        </div>
    </div>
@endif
@endsection
