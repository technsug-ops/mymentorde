@extends('manager.layouts.app')
@section('title', ($aiLabsName ?? 'AI Labs') . ' — Dış Kaynaklar')
@section('page_title','🌐 ' . ($aiLabsName ?? 'AI Labs') . ' — Dış Kaynaklar')

@section('content')
<style>
.ale-wrap { max-width:1100px; margin:20px auto; padding:0 16px; }
.ale-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:18px; }
.ale-card h2 { margin:0 0 6px; font-size:17px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.ale-card p.hint { margin:0 0 14px; font-size:12px; color:#64748b; line-height:1.6; }

.ale-msg-ok { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
.ale-msg-warn { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }

.ale-tabs { display:flex; gap:6px; margin-bottom:16px; }
.ale-tab {
    flex:1; padding:12px 14px; text-align:center; border:2px solid #e2e8f0;
    border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; color:#64748b;
    background:#fff; transition:all .15s;
}
.ale-tab.active { background:#5b2e91; color:#fff; border-color:#5b2e91; }
.ale-tab.disabled { opacity:.5; cursor:not-allowed; }
.ale-panel { display:none; }
.ale-panel.active { display:block; }

.ale-field { margin-bottom:12px; }
.ale-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
.ale-field input, .ale-field select {
    width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px;
    font-size:13px; background:#fff; box-sizing:border-box; font-family:inherit;
}

.ale-btn { padding:10px 20px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.ale-btn-primary { background:#5b2e91; color:#fff; }
.ale-btn-primary:hover { background:#4a2578; }
.ale-btn-primary:disabled { opacity:.5; cursor:not-allowed; }
.ale-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; font-size:11px; padding:6px 12px; }
.ale-btn-sm { font-size:11px; padding:6px 12px; }

.ale-input-row { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; }
.ale-input-row .ale-field { flex:1; margin-bottom:0; min-width:200px; }

.ale-results { margin-top:16px; }
.ale-result-item {
    background:#faf7ff; border:1px solid #ede9fe; border-radius:10px; padding:14px;
    margin-bottom:10px; display:flex; gap:14px; align-items:flex-start;
}
.ale-result-item .body { flex:1; min-width:0; }
.ale-result-item h3 { margin:0 0 4px; font-size:14px; color:#0f172a; font-weight:700; }
.ale-result-item h3 a { color:#0f172a; text-decoration:none; }
.ale-result-item h3 a:hover { color:#5b2e91; }
.ale-result-item .meta { font-size:11px; color:#64748b; margin-bottom:6px; }
.ale-result-item .snippet { font-size:12.5px; color:#334155; line-height:1.5; }
.ale-result-item .actions { flex-shrink:0; }

.ale-empty { text-align:center; padding:30px 20px; color:#94a3b8; font-size:13px; }
.ale-loading { text-align:center; padding:30px; color:#64748b; font-size:13px; font-style:italic; }

/* Import modal */
.ale-modal-backdrop {
    display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:100;
    align-items:center; justify-content:center; padding:20px;
}
.ale-modal-backdrop.active { display:flex; }
.ale-modal {
    background:#fff; border-radius:14px; max-width:600px; width:100%;
    max-height:90vh; overflow-y:auto; padding:24px;
}
.ale-modal h3 { margin:0 0 14px; font-size:17px; color:#0f172a; }
.ale-role-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:8px; }
@media(max-width:900px){ .ale-role-grid { grid-template-columns:repeat(2, 1fr); } }
.ale-role-chip {
    display:flex; align-items:center; gap:6px; padding:10px 12px; border:2px solid #e2e8f0;
    border-radius:8px; cursor:pointer; background:#fff; font-size:12px; font-weight:600; user-select:none;
}
.ale-role-chip input { accent-color:#5b2e91; }
.ale-role-chip:has(input:checked) { border-color:#5b2e91; background:#faf7ff; color:#5b2e91; }
.ale-role-presets { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
.ale-role-preset { padding:4px 10px; border:1px solid #ddd; border-radius:12px; background:#fff; font-size:11px; cursor:pointer; color:#64748b; }

.ale-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.ale-badge.wiki { background:#dbeafe; color:#1e40af; }
.ale-badge.rss  { background:#fef3c7; color:#92400e; }
.ale-badge.web  { background:#ede9fe; color:#5b2e91; }

/* Bulk toolbar */
.ale-bulk-bar {
    position:sticky; top:10px; z-index:5;
    background:#5b2e91; color:#fff; padding:12px 16px; border-radius:10px;
    margin-bottom:12px; display:none; align-items:center; gap:12px; flex-wrap:wrap;
}
.ale-bulk-bar.active { display:flex; }
.ale-bulk-bar .count { font-weight:700; }
.ale-bulk-bar button {
    padding:6px 14px; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;
}
.ale-bulk-bar button.primary { background:#fff; color:#5b2e91; }
.ale-bulk-bar button.ghost { background:transparent; color:#fff; border:1px solid rgba(255,255,255,.4); }
.ale-bulk-bar .spacer { flex:1; }

.ale-results-header {
    display:flex; align-items:center; gap:10px; margin:16px 0 8px;
    font-size:12px; color:#64748b;
}
.ale-results-header input[type=checkbox] { accent-color:#5b2e91; transform:scale(1.2); }
</style>

<div class="ale-wrap">
    @if (session('status'))
        <div class="ale-msg-ok">{{ session('status') }}</div>
    @endif

    {{-- Bulk toolbar (sticky, seçim olduğunda görünür) --}}
    <div class="ale-bulk-bar" id="bulk-bar">
        <span class="count"><span id="bulk-count">0</span> öğe seçildi</span>
        <div class="spacer"></div>
        <button type="button" class="primary" id="bulk-import-btn">📥 Tümünü Bilgi Havuzuna Ekle</button>
        <button type="button" class="ghost" id="bulk-clear">Temizle</button>
    </div>

    <div class="ale-card">
        <h2>🌐 Dış Kaynaklardan İçerik Keşfet</h2>
        <p class="hint">
            Wikipedia, RSS akışları ve web aramadan içerik bul → tek tık ile bilgi havuzuna ekle.
            Eklenen kaynaklar normal bilgi havuzu gibi AI asistan tarafından kullanılır, rollere görünürlük atanır.
        </p>

        <div class="ale-tabs" id="ale-tabs">
            <div class="ale-tab active" data-tab="wiki">📚 Wikipedia</div>
            <div class="ale-tab" data-tab="rss">📰 RSS Feed</div>
            <div class="ale-tab {{ $webSearchEnabled ? '' : 'disabled' }}" data-tab="web" @if (!$webSearchEnabled) title="Serper.dev API key gerekir — .env veya AI Labs ayarlarından ekle" @endif>
                🔍 Web Arama {{ $webSearchEnabled ? '' : '(kapalı)' }}
            </div>
        </div>

        {{-- Wikipedia Panel --}}
        <div class="ale-panel active" data-panel="wiki">
            <div class="ale-input-row">
                <div class="ale-field">
                    <label>Wikipedia'da Ara</label>
                    <input type="text" id="wiki-q" placeholder="örn: Uni-Assist, APS, Sperrkonto, Humboldt Üniversitesi">
                </div>
                <div class="ale-field" style="max-width:120px;">
                    <label>Dil</label>
                    <select id="wiki-lang">
                        <option value="tr">Türkçe</option>
                        <option value="en">İngilizce</option>
                        <option value="de">Almanca</option>
                    </select>
                </div>
                <button type="button" class="ale-btn ale-btn-primary" id="wiki-search">Ara</button>
            </div>
            <div class="ale-results" id="wiki-results"></div>
        </div>

        {{-- RSS Panel --}}
        <div class="ale-panel" data-panel="rss">
            <div class="ale-input-row">
                <div class="ale-field">
                    <label>RSS Feed URL</label>
                    <input type="url" id="rss-url" placeholder="https://www.daad.de/rss/feed.xml">
                </div>
                <button type="button" class="ale-btn ale-btn-primary" id="rss-parse">Yükle</button>
            </div>
            <p class="hint" style="margin-top:8px;">
                💡 Bir haber sitesi veya blog RSS URL'ini yapıştır. Bir bloğu veya siteyi destekleyen "rss.xml" veya "feed.xml" uzantısı arayabilirsin.
                Feed'den tek tek veya toplu olarak öğe seçip bilgi havuzuna ekleyebilirsin.
            </p>
            <div class="ale-results" id="rss-results"></div>
        </div>

        {{-- Web Search Panel --}}
        <div class="ale-panel" data-panel="web">
            @if (!$webSearchEnabled)
                <div class="ale-msg-warn">
                    ⚠️ Web arama için <strong>Serper.dev</strong> API anahtarı gerekli.
                    <a href="https://serper.dev" target="_blank" style="color:#92400e; text-decoration:underline;">serper.dev</a>'den ücretsiz al (2500 sorgu).
                    <br>Sonra <code>.env</code>'e <code>SERPER_API_KEY=...</code> ekle veya AI Labs Ayarlar'dan gir.
                </div>
            @else
                <div class="ale-input-row">
                    <div class="ale-field">
                        <label>Google'da Ara</label>
                        <input type="text" id="web-q" placeholder="örn: Almanya öğrenci vizesi başvuru belgeler 2026">
                    </div>
                    <div class="ale-field" style="max-width:120px;">
                        <label>Dil</label>
                        <select id="web-lang">
                            <option value="tr">TR</option>
                            <option value="en">EN</option>
                            <option value="de">DE</option>
                        </select>
                    </div>
                    <button type="button" class="ale-btn ale-btn-primary" id="web-search">Ara</button>
                </div>
                <div class="ale-results" id="web-results"></div>
            @endif
        </div>
    </div>
</div>

{{-- Bulk Import Modal --}}
<div class="ale-modal-backdrop" id="bulk-modal">
    <div class="ale-modal">
        <h3>📦 Toplu Bilgi Havuzu'na Ekle</h3>
        <p class="hint" style="font-size:12px; color:#64748b; margin-bottom:14px;">
            <strong id="bulk-modal-count">0</strong> öğe seçili. Tümüne aynı kategori ve rol görünürlüğü uygulanacak.
            Her öğe kendi başlığı + URL'i ile kaydedilir.
        </p>
        <form method="POST" action="{{ url('/manager/ai-labs/external/import-bulk') }}" id="bulk-form">
            @csrf
            <input type="hidden" name="source_type" id="bulk-source-type">
            <div id="bulk-items-container"></div>

            <div class="ale-field">
                <label>Kategori (hepsine uygulanır)</label>
                <input type="text" name="category" id="bulk-category" maxlength="80" placeholder="örn: wikipedia, haber, vize">
            </div>

            <div class="ale-field">
                <label>Hangi roller görsün? *</label>
                <div class="ale-role-presets">
                    <button type="button" class="ale-role-preset" data-bulk-preset="external">Dış roller</button>
                    <button type="button" class="ale-role-preset" data-bulk-preset="internal">İç roller</button>
                    <button type="button" class="ale-role-preset" data-bulk-preset="all">Hepsi</button>
                    <button type="button" class="ale-role-preset" data-bulk-preset="none">Temizle</button>
                </div>
                <div class="ale-role-grid" id="bulk-roles">
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="guest" checked> 🙋 Aday</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="student" checked> 🎓 Öğrenci</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="senior"> 👨‍🏫 Senior</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="manager"> 👔 Yönetici</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="admin_staff"> 🏢 Admin</label>
                </div>
            </div>

            <div style="background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:10px 14px; border-radius:8px; font-size:11.5px; margin:12px 0;">
                ⏳ Her öğe için içerik fetch edileceği için 10-30 saniye sürebilir.
                Başarısız olanlar (ör. erişilemeyen URL) atlanır, rapor edilir.
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="ale-btn ale-btn-ghost" id="bulk-modal-cancel">İptal</button>
                <button type="submit" class="ale-btn ale-btn-primary" id="bulk-submit">📥 Tümünü Ekle</button>
            </div>
        </form>
    </div>
</div>

{{-- Tekil Import Modal --}}
<div class="ale-modal-backdrop" id="import-modal">
    <div class="ale-modal">
        <h3>✨ Kaynağı Bilgi Havuzuna Ekle</h3>
        <form method="POST" action="{{ url('/manager/ai-labs/external/import') }}" id="import-form">
            @csrf
            <input type="hidden" name="source_type" id="import-source-type">
            <input type="hidden" name="url" id="import-url">
            <input type="hidden" name="wikipedia_title" id="import-wikipedia-title">
            <input type="hidden" name="wikipedia_lang" id="import-wikipedia-lang">

            <div class="ale-field">
                <label>Başlık (bilgi havuzunda görünecek ad)</label>
                <input type="text" name="title" id="import-title" required maxlength="200">
            </div>
            <div class="ale-field">
                <label>Kategori (opsiyonel)</label>
                <input type="text" name="category" id="import-category" maxlength="80" placeholder="örn: wikipedia, haber, vize">
            </div>
            <div class="ale-field">
                <label>Hangi roller görsün? *</label>
                <div class="ale-role-presets">
                    <button type="button" class="ale-role-preset" data-preset="external">Dış roller</button>
                    <button type="button" class="ale-role-preset" data-preset="internal">İç roller</button>
                    <button type="button" class="ale-role-preset" data-preset="all">Hepsi</button>
                    <button type="button" class="ale-role-preset" data-preset="none">Temizle</button>
                </div>
                <div class="ale-role-grid" id="import-roles">
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="guest" checked> 🙋 Aday</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="student" checked> 🎓 Öğrenci</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="senior"> 👨‍🏫 Senior</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="manager"> 👔 Yönetici</label>
                    <label class="ale-role-chip"><input type="checkbox" name="visible_to_roles[]" value="admin_staff"> 🏢 Admin</label>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:14px;">
                <button type="button" class="ale-btn ale-btn-ghost" id="import-cancel">İptal</button>
                <button type="submit" class="ale-btn ale-btn-primary">📥 Bilgi Havuzuna Ekle</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    const token = () => document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // ── Tab switch ─────────────────────────────────────────────────
    const tabs = document.querySelectorAll('#ale-tabs .ale-tab');
    const panels = document.querySelectorAll('.ale-panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            if (tab.classList.contains('disabled')) return;
            tabs.forEach(t => t.classList.toggle('active', t === tab));
            panels.forEach(p => p.classList.toggle('active', p.dataset.panel === tab.dataset.tab));
        });
    });

    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    async function postJson(url, body) {
        const fd = new FormData();
        Object.entries(body).forEach(([k, v]) => fd.append(k, v));
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        return await res.json();
    }

    function renderLoading(el) { el.innerHTML = '<div class="ale-loading">⏳ Aranıyor...</div>'; }
    function renderEmpty(el, msg) { el.innerHTML = '<div class="ale-empty">' + esc(msg) + '</div>'; }

    // ── Wikipedia ──────────────────────────────────────────────────
    document.getElementById('wiki-search')?.addEventListener('click', async () => {
        const q = document.getElementById('wiki-q').value.trim();
        const lang = document.getElementById('wiki-lang').value;
        if (!q) return;
        const out = document.getElementById('wiki-results');
        renderLoading(out);
        const data = await postJson('{{ url("/manager/ai-labs/external/wikipedia") }}', { q, lang });
        if (!data.ok) { renderEmpty(out, 'Hata: ' + (data.error || 'unknown')); return; }
        if (!data.results?.length) { renderEmpty(out, 'Sonuç bulunamadı.'); return; }
        out.innerHTML = renderResultsHeader('wikipedia') + data.results.map(r => `
            <div class="ale-result-item">
                <input type="checkbox" class="bulk-check" style="margin-top:4px; accent-color:#5b2e91; transform:scale(1.2);"
                       data-type="wikipedia" data-title="${esc(r.title)}" data-wiki-title="${esc(r.title)}" data-wiki-lang="${lang}" data-url="${esc(r.url)}">
                <div class="body">
                    <h3><a href="${esc(r.url)}" target="_blank">${esc(r.title)}</a> <span class="ale-badge wiki">wiki</span></h3>
                    <div class="meta">${esc(r.url)} · ${r.size} byte</div>
                    <div class="snippet">${esc(r.snippet)}</div>
                </div>
                <div class="actions">
                    <button type="button" class="ale-btn ale-btn-primary ale-btn-sm import-btn"
                            data-type="wikipedia" data-title="${esc(r.title)}" data-wiki-title="${esc(r.title)}" data-wiki-lang="${lang}" data-url="${esc(r.url)}">
                        + Ekle
                    </button>
                </div>
            </div>`).join('');
        bindImportButtons();
        bindBulkChecks();
    });

    // ── RSS ────────────────────────────────────────────────────────
    document.querySelectorAll('.rss-preset').forEach(a => {
        a.addEventListener('click', (e) => { e.preventDefault(); document.getElementById('rss-url').value = a.dataset.url; });
    });

    document.getElementById('rss-parse')?.addEventListener('click', async () => {
        const url = document.getElementById('rss-url').value.trim();
        if (!url) return;
        const out = document.getElementById('rss-results');
        renderLoading(out);
        const data = await postJson('{{ url("/manager/ai-labs/external/rss") }}', { url });
        if (!data.ok) { renderEmpty(out, 'Hata: ' + (data.error || 'unknown')); return; }
        const items = data.items || [];
        if (!items.length) { renderEmpty(out, 'Feed boş.'); return; }
        out.innerHTML = `<p class="hint">📰 <strong>${esc(data.feed_title)}</strong> — ${items.length} öğe</p>`
            + renderResultsHeader('rss')
            + items.map(it => `
            <div class="ale-result-item">
                <input type="checkbox" class="bulk-check" style="margin-top:4px; accent-color:#5b2e91; transform:scale(1.2);"
                       data-type="rss" data-title="${esc(it.title)}" data-url="${esc(it.link)}">
                <div class="body">
                    <h3><a href="${esc(it.link)}" target="_blank">${esc(it.title)}</a> <span class="ale-badge rss">rss</span></h3>
                    <div class="meta">${esc(it.published)}</div>
                    <div class="snippet">${esc((it.description||'').slice(0, 220))}${(it.description||'').length > 220 ? '…' : ''}</div>
                </div>
                <div class="actions">
                    <button type="button" class="ale-btn ale-btn-primary ale-btn-sm import-btn"
                            data-type="rss" data-title="${esc(it.title)}" data-url="${esc(it.link)}">
                        + Ekle
                    </button>
                </div>
            </div>`).join('');
        bindImportButtons();
        bindBulkChecks();
    });

    // ── Web Search (Serper) ────────────────────────────────────────
    document.getElementById('web-search')?.addEventListener('click', async () => {
        const q = document.getElementById('web-q').value.trim();
        const lang = document.getElementById('web-lang').value;
        if (!q) return;
        const out = document.getElementById('web-results');
        renderLoading(out);
        const data = await postJson('{{ url("/manager/ai-labs/external/web") }}', { q, lang });
        if (!data.ok) { renderEmpty(out, 'Hata: ' + (data.error || 'unknown')); return; }
        if (!data.results?.length) { renderEmpty(out, 'Sonuç yok.'); return; }
        out.innerHTML = renderResultsHeader('web') + data.results.map(r => `
            <div class="ale-result-item">
                <input type="checkbox" class="bulk-check" style="margin-top:4px; accent-color:#5b2e91; transform:scale(1.2);"
                       data-type="web" data-title="${esc(r.title)}" data-url="${esc(r.link)}">
                <div class="body">
                    <h3><a href="${esc(r.link)}" target="_blank">${esc(r.title)}</a> <span class="ale-badge web">web</span></h3>
                    <div class="meta">${esc(r.link)}</div>
                    <div class="snippet">${esc(r.snippet)}</div>
                </div>
                <div class="actions">
                    <button type="button" class="ale-btn ale-btn-primary ale-btn-sm import-btn"
                            data-type="web" data-title="${esc(r.title)}" data-url="${esc(r.link)}">
                        + Ekle
                    </button>
                </div>
            </div>`).join('');
        bindImportButtons();
        bindBulkChecks();
    });

    // ── Import Modal ───────────────────────────────────────────────
    const modal = document.getElementById('import-modal');
    const f = {
        type: document.getElementById('import-source-type'),
        url: document.getElementById('import-url'),
        wikiTitle: document.getElementById('import-wikipedia-title'),
        wikiLang: document.getElementById('import-wikipedia-lang'),
        title: document.getElementById('import-title'),
        category: document.getElementById('import-category'),
    };

    function openImport(btn) {
        f.type.value = btn.dataset.type || '';
        f.url.value = btn.dataset.url || '';
        f.wikiTitle.value = btn.dataset.wikiTitle || '';
        f.wikiLang.value = btn.dataset.wikiLang || 'tr';
        f.title.value = btn.dataset.title || '';
        f.category.value = btn.dataset.type === 'wikipedia' ? 'wikipedia' : '';
        modal.classList.add('active');
    }
    function closeImport() { modal.classList.remove('active'); }

    function bindImportButtons() {
        document.querySelectorAll('.import-btn').forEach(btn => {
            btn.addEventListener('click', () => openImport(btn));
        });
    }

    document.getElementById('import-cancel')?.addEventListener('click', closeImport);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeImport(); });

    // Import modal role presets (tekil)
    const presets = {
        external: ['guest', 'student'],
        internal: ['senior', 'manager', 'admin_staff'],
        all:      ['guest', 'student', 'senior', 'manager', 'admin_staff'],
        none:     [],
    };
    document.querySelectorAll('.ale-role-preset[data-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const selected = presets[btn.dataset.preset] || [];
            document.querySelectorAll('#import-roles input[type=checkbox]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        });
    });

    // Bulk modal role presets
    document.querySelectorAll('.ale-role-preset[data-bulk-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const selected = presets[btn.dataset.bulkPreset] || [];
            document.querySelectorAll('#bulk-roles input[type=checkbox]').forEach(cb => {
                cb.checked = selected.includes(cb.value);
            });
        });
    });

    // ── Bulk selection ──────────────────────────────────────────────
    function renderResultsHeader(sourceType) {
        return `<div class="ale-results-header">
            <label style="cursor:pointer;">
                <input type="checkbox" class="select-all-results" data-source="${sourceType}">
                Hepsini seç
            </label>
            <span>·</span>
            <span class="results-count"></span>
        </div>`;
    }

    function updateBulkBar() {
        const selected = document.querySelectorAll('.bulk-check:checked');
        const n = selected.length;
        const bar = document.getElementById('bulk-bar');
        document.getElementById('bulk-count').textContent = n;
        bar.classList.toggle('active', n > 0);
    }

    function bindBulkChecks() {
        // Result checkboxes
        document.querySelectorAll('.bulk-check').forEach(cb => {
            cb.removeEventListener('change', updateBulkBar);
            cb.addEventListener('change', updateBulkBar);
        });
        // Select-all per tab
        document.querySelectorAll('.select-all-results').forEach(sa => {
            sa.addEventListener('change', () => {
                const visibleChecks = document.querySelectorAll('.ale-panel.active .bulk-check');
                visibleChecks.forEach(c => c.checked = sa.checked);
                updateBulkBar();
            });
        });
    }

    document.getElementById('bulk-clear')?.addEventListener('click', () => {
        document.querySelectorAll('.bulk-check').forEach(c => c.checked = false);
        document.querySelectorAll('.select-all-results').forEach(c => c.checked = false);
        updateBulkBar();
    });

    // Bulk modal open
    document.getElementById('bulk-import-btn')?.addEventListener('click', () => {
        const selected = Array.from(document.querySelectorAll('.bulk-check:checked'));
        if (selected.length === 0) { alert('En az 1 öğe seç.'); return; }

        // Source type kontrolü — hepsi aynı tipten olmalı
        const types = new Set(selected.map(c => c.dataset.type));
        if (types.size > 1) {
            alert('Farklı tipte öğeler seçtin (Wikipedia/RSS/Web karışık). Aynı anda sadece bir tipi toplu ekleyebilirsin.');
            return;
        }

        const srcType = selected[0].dataset.type;
        document.getElementById('bulk-source-type').value = srcType;
        document.getElementById('bulk-modal-count').textContent = selected.length;

        // Hidden inputs — her item için
        const container = document.getElementById('bulk-items-container');
        container.innerHTML = selected.map((c, i) => `
            <input type="hidden" name="items[${i}][title]" value="${esc(c.dataset.title || '')}">
            <input type="hidden" name="items[${i}][url]" value="${esc(c.dataset.url || '')}">
            <input type="hidden" name="items[${i}][wiki_title]" value="${esc(c.dataset.wikiTitle || '')}">
            <input type="hidden" name="items[${i}][wiki_lang]" value="${esc(c.dataset.wikiLang || 'tr')}">
        `).join('');

        // Default kategori
        document.getElementById('bulk-category').value = srcType === 'wikipedia' ? 'wikipedia' : '';

        document.getElementById('bulk-modal').classList.add('active');
    });

    document.getElementById('bulk-modal-cancel')?.addEventListener('click', () => {
        document.getElementById('bulk-modal').classList.remove('active');
    });
    document.getElementById('bulk-modal')?.addEventListener('click', (e) => {
        if (e.target.id === 'bulk-modal') document.getElementById('bulk-modal').classList.remove('active');
    });

    // Submit loading state
    document.getElementById('bulk-form')?.addEventListener('submit', (e) => {
        const btn = document.getElementById('bulk-submit');
        btn.disabled = true;
        btn.textContent = '⏳ İşleniyor... (lütfen bekle)';
    });
})();
</script>
@endsection
