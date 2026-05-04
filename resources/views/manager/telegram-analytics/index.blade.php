@extends('manager.layouts.app')
@section('title', 'Telegram Analytics')
@section('page_title', 'Telegram Analytics')

@section('content')

@include('partials.manager-hero', [
    'label' => 'Anonim chat analiz arayüzü',
    'title' => 'Telegram Analytics',
    'sub'   => 'Topluluk forumlarından alınan mesajları konu, zaman, soru tipi ve kaynak gruba göre filtreleyip görselleştir. Tüm veri PII temizlenmiş ve anonim.',
    'icon'  => '📊',
    'bg'    => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1400&q=80',
    'tone'  => 'purple',
    'stats' => [
        ['icon' => '💬', 'text' => number_format($totalCount, 0, ',', '.') . ' mesaj'],
        ['icon' => '🗂️', 'text' => count($sources) . ' grup'],
        ['icon' => '🏷️', 'text' => count($topics) . ' konu kategorisi'],
        ['icon' => '🛡️', 'text' => 'Anonim + PII temiz'],
    ],
])

<style>
    .ta-wrap { max-width: 1400px; margin: 0 auto; padding: 0 24px 60px; }
    .ta-filter-card {
        background: #fff;
        border-radius: 14px;
        padding: 22px 26px;
        box-shadow: 0 2px 14px rgba(15,23,42,0.05);
        margin-bottom: 24px;
    }
    .ta-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        align-items: end;
    }
    .ta-filter-grid label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #5b4a7a;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .ta-filter-grid input,
    .ta-filter-grid select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid rgba(126,88,191,0.20);
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        background: #fff;
    }
    .ta-filter-grid input:focus,
    .ta-filter-grid select:focus {
        border-color: #7e58bf;
        box-shadow: 0 0 0 3px rgba(126,88,191,0.1);
    }
    .ta-filter-actions {
        display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;
    }
    .ta-btn {
        padding: 10px 22px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: opacity 0.15s, transform 0.15s;
    }
    .ta-btn:hover { opacity: 0.92; }
    .ta-btn-primary { background: #7e58bf; color: #fff; }
    .ta-btn-secondary { background: #f1edf7; color: #5b3a8f; }
    .ta-btn-upload { background: #fff; color: #7e58bf; border: 2px solid #7e58bf; }

    /* Stat cards */
    .ta-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 26px;
    }
    .ta-stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px 22px;
        box-shadow: 0 2px 12px rgba(15,23,42,0.05);
        border-left: 4px solid #7e58bf;
    }
    .ta-stat-num {
        font-family: 'Space Grotesk', system-ui, sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #5b3a8f;
        letter-spacing: -0.02em;
    }
    .ta-stat-label {
        font-size: 11px;
        color: #5b4a7a;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
    }

    /* Tabs */
    .ta-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid rgba(126,88,191,0.15);
        margin-bottom: 0;
        flex-wrap: wrap;
    }
    .ta-tab {
        padding: 12px 22px;
        font-size: 14px;
        font-weight: 600;
        color: #5b4a7a;
        background: transparent;
        border: none;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.15s, border-color 0.15s;
    }
    .ta-tab:hover { color: #7e58bf; }
    .ta-tab.active { color: #7e58bf; border-bottom-color: #7e58bf; }

    .ta-tab-panel {
        background: #fff;
        border-radius: 0 14px 14px 14px;
        padding: 28px;
        box-shadow: 0 2px 14px rgba(15,23,42,0.05);
        display: none;
    }
    .ta-tab-panel.active { display: block; }

    .ta-chart-wrap { position: relative; height: 380px; margin-bottom: 18px; }
    .ta-chart-wrap.tall { height: 480px; }

    /* Heatmap */
    .ta-heatmap {
        display: grid;
        grid-template-columns: 60px repeat(24, 1fr);
        gap: 2px;
        font-size: 11px;
    }
    .ta-heatmap-cell {
        aspect-ratio: 1;
        border-radius: 3px;
        background: #f3edfa;
        position: relative;
    }
    .ta-heatmap-label {
        font-size: 11px; color: #5b4a7a; padding: 4px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600;
    }

    /* Search results */
    .ta-search-row {
        padding: 14px 16px;
        border-bottom: 1px solid #f1edf7;
        font-size: 13px;
    }
    .ta-search-meta {
        display: flex; gap: 12px; align-items: center;
        font-size: 11px; color: #5b4a7a; margin-bottom: 6px;
    }
    .ta-pill {
        display: inline-block; background: #f1edf7; color: #5b3a8f;
        padding: 2px 9px; border-radius: 10px; font-size: 11px; font-weight: 600;
    }
    .ta-pill.q { background: #fef3c7; color: #92400e; }

    .ta-empty {
        text-align: center; padding: 40px; color: #5b4a7a;
    }
    .ta-loading {
        text-align: center; padding: 30px; color: #7e58bf; font-weight: 600;
    }

    /* Upload box */
    .ta-upload-box {
        border: 2px dashed rgba(126,88,191,0.30);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        background: #faf7ff;
        margin-top: 20px;
    }
    .ta-upload-box.dragover { background: #f1edf7; border-color: #7e58bf; }
</style>

<div class="ta-wrap">

    {{-- Filter Card --}}
    <div class="ta-filter-card">
        <h3 style="font-family:'Space Grotesk',system-ui,sans-serif;font-size:18px;color:#1a0f2e;margin:0 0 16px 0;letter-spacing:-0.02em;">🔧 Filtreler</h3>
        <div class="ta-filter-grid">
            <div>
                <label>Başlangıç</label>
                <input type="date" id="ta-from" value="{{ $minDate }}" min="{{ $minDate }}" max="{{ $maxDate }}">
            </div>
            <div>
                <label>Bitiş</label>
                <input type="date" id="ta-to" value="{{ $maxDate }}" min="{{ $minDate }}" max="{{ $maxDate }}">
            </div>
            <div>
                <label>Kaynak Grup</label>
                <select id="ta-source">
                    <option value="">Tüm gruplar</option>
                    @foreach ($sources as $s)
                        <option value="{{ $s['source'] }}">{{ $s['source'] }} ({{ number_format($s['count'], 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Konu</label>
                <select id="ta-topic">
                    <option value="all">Tüm konular</option>
                    @foreach ($topics as $key => [$title, $icon])
                        <option value="{{ $key }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="ta-filter-actions">
            <button class="ta-btn ta-btn-primary" id="ta-apply">Filtreyi Uygula</button>
            <button class="ta-btn ta-btn-secondary" id="ta-reset">Sıfırla</button>
            <button class="ta-btn ta-btn-upload" id="ta-upload-toggle" type="button">📤 Yeni Telegram Export Yükle</button>
        </div>
        <div id="ta-upload-area" style="display:none;">
            <div class="ta-upload-box" id="ta-upload-zone">
                <p style="margin:0 0 10px 0;font-weight:600;color:#5b3a8f;">Telegram ChatExport ZIP dosyalarını buraya sürükle veya seç</p>
                <p style="margin:0 0 14px 0;font-size:12px;color:#5b4a7a;">Birden fazla ZIP yükleyebilirsin. Otomatik anonim parse + DB'ye ekle.</p>
                <input type="file" id="ta-file" accept=".zip" multiple style="margin-bottom:10px;">
                <p style="font-size:11px;color:#5b4a7a;margin:0;">⚠ Yükleme manager-only. Tüm gönderici isimleri SHA1 hash, mention/email/telefon temizlenir.</p>
            </div>
        </div>
    </div>

    {{-- Stat cards (filtreye göre güncellenir) --}}
    <div class="ta-stats" id="ta-stats">
        <div class="ta-stat-card"><div class="ta-stat-num" data-stat="messages">—</div><div class="ta-stat-label">Mesaj</div></div>
        <div class="ta-stat-card"><div class="ta-stat-num" data-stat="questions">—</div><div class="ta-stat-label">Soru</div></div>
        <div class="ta-stat-card"><div class="ta-stat-num" data-stat="unique_senders">—</div><div class="ta-stat-label">Anonim Sender</div></div>
        <div class="ta-stat-card"><div class="ta-stat-num" data-stat="sources">—</div><div class="ta-stat-label">Grup</div></div>
        <div class="ta-stat-card"><div class="ta-stat-num" data-stat="avg_length">—</div><div class="ta-stat-label">Ort. Uzunluk</div></div>
    </div>

    {{-- Tabs --}}
    <div class="ta-tabs">
        <button class="ta-tab active" data-tab="overview">📈 Özet</button>
        <button class="ta-tab" data-tab="topics">🏷 Konular</button>
        <button class="ta-tab" data-tab="heatmap">🔥 Heatmap</button>
        <button class="ta-tab" data-tab="sources">🗂 Gruplar</button>
        <button class="ta-tab" data-tab="search">🔍 Soru Arama</button>
    </div>

    <div class="ta-tab-panel active" data-tab="overview">
        <h3 style="margin:0 0 14px 0;font-family:'Space Grotesk',system-ui,sans-serif;color:#1a0f2e;">Aylık mesaj hacmi</h3>
        <div class="ta-chart-wrap"><canvas id="chart-monthly"></canvas></div>
    </div>

    <div class="ta-tab-panel" data-tab="topics">
        <h3 style="margin:0 0 14px 0;font-family:'Space Grotesk',system-ui,sans-serif;color:#1a0f2e;">Konu frekansı</h3>
        <div class="ta-chart-wrap tall"><canvas id="chart-topics"></canvas></div>
    </div>

    <div class="ta-tab-panel" data-tab="heatmap">
        <h3 style="margin:0 0 14px 0;font-family:'Space Grotesk',system-ui,sans-serif;color:#1a0f2e;">Etkileşim — gün × saat</h3>
        <p style="font-size:13px;color:#5b4a7a;margin:0 0 14px 0;">Koyulaşan kareler en aktif zaman dilimi.</p>
        <div id="ta-heatmap-grid"></div>
    </div>

    <div class="ta-tab-panel" data-tab="sources">
        <h3 style="margin:0 0 14px 0;font-family:'Space Grotesk',system-ui,sans-serif;color:#1a0f2e;">Kaynak grup dağılımı</h3>
        <div class="ta-chart-wrap"><canvas id="chart-sources"></canvas></div>
    </div>

    <div class="ta-tab-panel" data-tab="search">
        <div style="display:grid;grid-template-columns:1fr 200px 200px auto;gap:10px;align-items:end;margin-bottom:18px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#5b4a7a;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.05em;">Anahtar kelime</label>
                <input id="ta-search-term" placeholder="örn: sperrkonto, fsp, randevu" style="width:100%;padding:9px 12px;border:1px solid rgba(126,88,191,0.20);border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#5b4a7a;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.05em;">Min uzunluk</label>
                <input type="number" id="ta-search-minlen" value="30" min="0" style="width:100%;padding:9px 12px;border:1px solid rgba(126,88,191,0.20);border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#5b4a7a;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.05em;">Sayfa boyutu</label>
                <select id="ta-search-perpage" style="width:100%;padding:9px 12px;border:1px solid rgba(126,88,191,0.20);border-radius:8px;font-size:14px;">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <button class="ta-btn ta-btn-primary" id="ta-search-go">Ara</button>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#5b4a7a;margin-bottom:14px;">
            <input type="checkbox" id="ta-search-onlyq" checked> Sadece sorular (?)
        </label>
        <div id="ta-search-meta" style="font-size:13px;color:#5b4a7a;margin-bottom:10px;">Sonuç bekleniyor...</div>
        <div id="ta-search-results"></div>
        <div id="ta-search-pagination" style="margin-top:18px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;"></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" nonce="{{ $cspNonce ?? '' }}"></script>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    const URL_STATS  = "{{ route('manager.telegram-analytics.stats') }}";
    const URL_SEARCH = "{{ route('manager.telegram-analytics.search') }}";
    const URL_UPLOAD = "{{ route('manager.telegram-analytics.upload') }}";
    const CSRF = "{{ csrf_token() }}";
    const TOPIC_LABELS = @json(array_map(fn ($t) => $t[0], $topics));

    let charts = { monthly: null, topics: null, sources: null };
    let currentSearchPage = 1;

    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    function fmtNum(n) {
        return Number(n || 0).toLocaleString('tr-TR');
    }

    function getFilters() {
        return {
            from: $('#ta-from').value || '',
            to:   $('#ta-to').value || '',
            sources: $('#ta-source').value ? [$('#ta-source').value] : [],
            topic: $('#ta-topic').value || 'all',
        };
    }

    function buildQuery(obj) {
        const p = new URLSearchParams();
        for (const k in obj) {
            if (Array.isArray(obj[k])) {
                obj[k].forEach(v => p.append(k + '[]', v));
            } else if (obj[k] !== '' && obj[k] !== null && obj[k] !== undefined) {
                p.append(k, obj[k]);
            }
        }
        return p.toString();
    }

    async function loadStats() {
        const qs = buildQuery(getFilters());
        const res = await fetch(URL_STATS + '?' + qs, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        // Stat cards
        for (const k in data.totals) {
            const el = document.querySelector(`[data-stat="${k}"]`);
            if (el) el.textContent = fmtNum(data.totals[k]);
        }

        // Monthly chart
        if (charts.monthly) charts.monthly.destroy();
        charts.monthly = new Chart($('#chart-monthly').getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.monthly.map(m => m.month),
                datasets: [{
                    label: 'Mesaj',
                    data: data.monthly.map(m => m.count),
                    backgroundColor: '#7e58bf',
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { maxRotation: 90, minRotation: 90, font: { size: 9 } } } },
            },
        });

        // Topics chart (horizontal bar)
        const topicEntries = Object.entries(data.topics)
            .filter(([_, c]) => c > 0)
            .sort(([, a], [, b]) => b - a);
        if (charts.topics) charts.topics.destroy();
        charts.topics = new Chart($('#chart-topics').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topicEntries.map(([k]) => TOPIC_LABELS[k] || k),
                datasets: [{
                    label: 'Mesaj',
                    data: topicEntries.map(([, c]) => c),
                    backgroundColor: '#7e58bf',
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });

        // Sources chart
        if (charts.sources) charts.sources.destroy();
        charts.sources = new Chart($('#chart-sources').getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.source_breakdown.map(s => s.source),
                datasets: [{
                    label: 'Mesaj',
                    data: data.source_breakdown.map(s => s.count),
                    backgroundColor: '#7e58bf',
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });

        // Heatmap render
        renderHeatmap(data.heatmap);
    }

    function renderHeatmap(grid) {
        const dows = ['Pzt','Sal','Çar','Per','Cum','Cmt','Pzr'];
        let max = 0;
        grid.forEach(row => row.forEach(v => { if (v > max) max = v; }));
        let html = '<div class="ta-heatmap"><div></div>';
        for (let h = 0; h < 24; h++) html += `<div class="ta-heatmap-label">${h}</div>`;
        for (let d = 0; d < 7; d++) {
            html += `<div class="ta-heatmap-label">${dows[d]}</div>`;
            for (let h = 0; h < 24; h++) {
                const v = grid[d][h];
                const intensity = max ? v / max : 0;
                const opacity = (0.05 + intensity * 0.95).toFixed(2);
                html += `<div class="ta-heatmap-cell" style="background:rgba(126,88,191,${opacity})" title="${dows[d]} ${h}:00 — ${v} mesaj"></div>`;
            }
        }
        html += '</div>';
        $('#ta-heatmap-grid').innerHTML = html;
    }

    async function loadSearch(page = 1) {
        currentSearchPage = page;
        const filters = getFilters();
        const qs = buildQuery({
            ...filters,
            term: $('#ta-search-term').value,
            min_length: $('#ta-search-minlen').value,
            per_page: $('#ta-search-perpage').value,
            only_questions: $('#ta-search-onlyq').checked ? 1 : 0,
            page: page,
        });
        $('#ta-search-meta').textContent = 'Aranıyor...';
        const res = await fetch(URL_SEARCH + '?' + qs, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        $('#ta-search-meta').textContent = `📌 ${fmtNum(data.total)} eşleşme — sayfa ${data.page}/${data.pages}`;

        if (!data.rows.length) {
            $('#ta-search-results').innerHTML = '<div class="ta-empty">Eşleşme yok. Filtreleri gevşet.</div>';
            $('#ta-search-pagination').innerHTML = '';
            return;
        }

        let html = '';
        data.rows.forEach(r => {
            const topicPills = (r.topics || []).map(t =>
                `<span class="ta-pill">${TOPIC_LABELS[t] || t}</span>`).join(' ');
            html += `
                <div class="ta-search-row">
                    <div class="ta-search-meta">
                        <span>📅 ${r.sent_at || '—'}</span>
                        <span>📁 ${r.source}</span>
                        <span>👤 ${r.sender}</span>
                        ${r.is_question ? '<span class="ta-pill q">SORU</span>' : ''}
                        ${topicPills}
                    </div>
                    <div>${escapeHtml(r.text)}</div>
                </div>
            `;
        });
        $('#ta-search-results').innerHTML = html;

        let pgHtml = '';
        const maxPages = Math.min(data.pages, 20);
        if (data.page > 1) pgHtml += `<button class="ta-btn ta-btn-secondary" data-pg="${data.page - 1}">← Önceki</button>`;
        for (let i = 1; i <= maxPages; i++) {
            const active = i === data.page ? 'ta-btn-primary' : 'ta-btn-secondary';
            pgHtml += `<button class="ta-btn ${active}" data-pg="${i}">${i}</button>`;
        }
        if (data.page < data.pages) pgHtml += `<button class="ta-btn ta-btn-secondary" data-pg="${data.page + 1}">Sonraki →</button>`;
        $('#ta-search-pagination').innerHTML = pgHtml;
        $$('#ta-search-pagination button').forEach(b => b.addEventListener('click', () => loadSearch(parseInt(b.dataset.pg))));
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    // Tab switching
    $$('.ta-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.ta-tab').forEach(b => b.classList.remove('active'));
            $$('.ta-tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.querySelector(`.ta-tab-panel[data-tab="${btn.dataset.tab}"]`)?.classList.add('active');
        });
    });

    // Filter buttons
    $('#ta-apply').addEventListener('click', () => { loadStats(); if ($('.ta-tab-panel[data-tab="search"]').classList.contains('active')) loadSearch(1); });
    $('#ta-reset').addEventListener('click', () => {
        $('#ta-from').value = "{{ $minDate }}";
        $('#ta-to').value = "{{ $maxDate }}";
        $('#ta-source').value = '';
        $('#ta-topic').value = 'all';
        loadStats();
    });

    // Search
    $('#ta-search-go').addEventListener('click', () => loadSearch(1));
    $('#ta-search-term').addEventListener('keydown', (e) => { if (e.key === 'Enter') loadSearch(1); });

    // Upload toggle
    $('#ta-upload-toggle').addEventListener('click', () => {
        const a = $('#ta-upload-area');
        a.style.display = a.style.display === 'none' ? 'block' : 'none';
    });

    // File upload
    $('#ta-file').addEventListener('change', async (e) => {
        const files = e.target.files;
        if (!files.length) return;
        const fd = new FormData();
        for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);
        const zone = $('#ta-upload-zone');
        zone.innerHTML = '<p class="ta-loading">📤 Yükleniyor + parse ediliyor — bu birkaç dakika sürebilir...</p>';
        try {
            const res = await fetch(URL_UPLOAD, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (data.success) {
                zone.innerHTML = `<p style="color:#16a34a;font-weight:600;">✅ ${fmtNum(data.inserted)} mesaj eklendi (${data.batch}). Sayfayı yenileyince filtre listesinde görünür.</p>`;
                setTimeout(() => location.reload(), 2000);
            } else {
                zone.innerHTML = `<p style="color:#dc2626;font-weight:600;">❌ Hata: ${data.error || 'Bilinmiyor'}</p>`;
            }
        } catch (err) {
            zone.innerHTML = `<p style="color:#dc2626;font-weight:600;">❌ Network hatası: ${err.message}</p>`;
        }
    });

    // İlk yükleme
    loadStats();
})();
</script>

@endsection
