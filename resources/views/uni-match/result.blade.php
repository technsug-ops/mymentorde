@extends('uni-match.layout')

@section('og_title', 'UniMatch ile sana özel ' . count($recommendations) . ' Almanya programı seçtim')
@section('og_description', config('brand.name') . ' UniMatch sihirbazı, 13.000+ program arasından profil ve hedeflerime en uygun olanları sıraladı. Sen de dene → /uni-match')

@push('scripts')
<style>
/* Favorite + toast (mevcut) */
.fav-btn:hover { color:#a07ed9 !important; transform:scale(1.15); }
.fav-btn.is-fav { color:#f59e0b !important; }
.fav-btn.is-fav:hover { color:#d97706 !important; }
.fav-toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#1a1a1a; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; z-index:9999; box-shadow:0 8px 24px rgba(0,0,0,.25); opacity:0; transition:opacity .3s; pointer-events:none; }
.fav-toast.show { opacity:1; }
.fav-toast.error { background:#dc2626; }

/* ── Bachelorsportal tarzi grid layout ─────────────────────────── */
.rec-wide { margin: 0 calc(-1 * (50vw - 50%)); padding: 0 20px; }
.rec-inner { max-width: 1180px; margin: 0 auto; }

.rec-utility {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    padding: 14px 18px; margin-bottom: 18px;
    background: #fff; border-radius: 12px;
    border: 1px solid #ede5f7;
    box-shadow: 0 2px 8px rgba(126,88,191,.04);
    position: sticky; top: 8px; z-index: 5;
    backdrop-filter: blur(8px);
}
.rec-utility-count { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.rec-utility-count strong { color: #7e58bf; }
.rec-utility-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.rec-sort-select {
    font-family: inherit; font-size: 12.5px; font-weight: 600;
    padding: 7px 12px; border-radius: 8px; border: 1px solid #d4c5e8;
    background: #fff; color: #6b5894; cursor: pointer;
    transition: border-color .2s cubic-bezier(.4,0,.2,1);
}
.rec-sort-select:hover { border-color: #b79ae9; }

.rec-layout {
    display: grid; grid-template-columns: 240px 1fr; gap: 20px;
    align-items: start;
}
@media (max-width: 880px) {
    .rec-layout { grid-template-columns: 1fr; }
    .rec-sidebar { position: relative !important; top: 0 !important; }
}

.rec-sidebar {
    position: sticky; top: 80px;
    background: #fff; border-radius: 12px;
    padding: 18px 16px;
    border: 1px solid #ede5f7;
    box-shadow: 0 2px 8px rgba(126,88,191,.04);
}
.rec-sidebar-section { margin-bottom: 18px; }
.rec-sidebar-section:last-child { margin-bottom: 0; }
.rec-sidebar-label {
    font-size: 11px; font-weight: 700; color: #6b5894;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 10px;
}
.rec-filter-list { display: flex; flex-direction: column; gap: 6px; }
.rec-filter-btn {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; border: 1px solid transparent;
    border-radius: 8px; background: transparent;
    font-family: inherit; font-size: 13px; font-weight: 600;
    color: #6b5894; cursor: pointer; text-align: left;
    transition: all .2s cubic-bezier(.4,0,.2,1);
    width: 100%;
}
.rec-filter-btn:hover { background: #f9f6fc; color: #1a1a1a; }
.rec-filter-btn.active {
    background: linear-gradient(135deg, #7e58bf, #6a47a8);
    color: #fff;
    box-shadow: 0 4px 12px rgba(126,88,191,.25);
}
.rec-filter-btn-count {
    font-size: 11px; padding: 2px 7px; border-radius: 999px;
    background: rgba(126,88,191,.12); color: #7e58bf; font-weight: 700;
}
.rec-filter-btn.active .rec-filter-btn-count {
    background: rgba(255,255,255,.22); color: #fff;
}

/* Grid */
.rec-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 16px;
}

.rec-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #ede5f7;
    overflow: hidden;
    display: flex; flex-direction: column;
    transition: transform .25s cubic-bezier(.4,0,.2,1),
                box-shadow .25s cubic-bezier(.4,0,.2,1),
                border-color .2s cubic-bezier(.4,0,.2,1);
    will-change: transform;
}
.rec-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 36px rgba(126,88,191,.18);
    border-color: rgba(126,88,191,.25);
}

.rec-card-header {
    position: relative;
    height: 88px;
    background: linear-gradient(135deg, #7e58bf 0%, #a07ed9 100%);
    display: flex; align-items: flex-end; padding: 12px 16px;
    overflow: hidden;
}
.rec-card-header::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,.12), transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,.08), transparent 50%);
}
.rec-card-rank {
    position: absolute; top: 12px; left: 16px;
    background: rgba(255,255,255,.95); color: #7e58bf;
    font-size: 11px; font-weight: 700;
    padding: 4px 10px; border-radius: 999px;
    letter-spacing: .3px;
}
.rec-card-fav-btn {
    position: absolute; top: 10px; right: 12px;
    background: rgba(255,255,255,.95); border: none;
    width: 32px; height: 32px; border-radius: 50%;
    cursor: pointer; font-size: 16px; line-height: 1;
    color: #d4c5e8;
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s cubic-bezier(.4,0,.2,1), color .2s;
    box-shadow: 0 2px 6px rgba(0,0,0,.08);
}
.rec-card-fav-btn:hover { transform: scale(1.12); color: #a07ed9; }
.rec-card-fav-btn.is-fav { color: #f59e0b; }
.rec-card-fav-btn.is-fav:hover { color: #d97706; }
.rec-card-score {
    position: relative;
    background: rgba(255,255,255,.95);
    padding: 6px 12px; border-radius: 8px;
    display: inline-flex; align-items: baseline; gap: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,.08);
}
.rec-card-score-num { font-size: 18px; font-weight: 700; color: #7e58bf; line-height: 1; }
.rec-card-score-label { font-size: 9.5px; font-weight: 600; color: #6b5894; letter-spacing: .5px; }

.rec-card-body { padding: 14px 16px 16px; flex: 1; display: flex; flex-direction: column; }
.rec-card-uni {
    font-size: 12px; color: #6b5894; font-weight: 600;
    margin-bottom: 6px; line-height: 1.4;
}
.rec-card-uni-loc { color: #8a7baf; font-weight: 500; }
.rec-card-name {
    font-size: 15.5px; font-weight: 700; color: #1a1a1a;
    line-height: 1.35; margin-bottom: 12px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.rec-card-chips {
    display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px;
}
.rec-chip {
    font-size: 10.5px; font-weight: 600;
    padding: 3px 9px; border-radius: 999px;
    background: #f4f2ee; color: #1a1a1a;
}
.rec-chip-degree { background: rgba(126,88,191,.1); color: #6c47a8; }
.rec-chip-lang { background: #ede9fe; color: #6d28d9; }
.rec-chip-free { background: rgba(22,163,74,.12); color: #15803d; }
.rec-chip-paid { background: #fef9c3; color: #854d0e; }
.rec-chip-uniassist { background: rgba(217,119,6,.12); color: #92400e; }
.rec-chip-direkt { background: rgba(5,150,105,.12); color: #065f46; }

.rec-card-reasons {
    font-size: 11.5px; color: #6b5894; line-height: 1.55;
    padding-top: 10px; margin-top: auto;
    border-top: 1px solid #f0ecf6;
}
.rec-card-reasons div { padding: 1px 0; }

.rec-card-cta {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 12px;
    padding: 9px 14px;
    background: rgba(126,88,191,.08); color: #7e58bf;
    border-radius: 8px; font-size: 12.5px; font-weight: 700;
    text-decoration: none;
    align-self: flex-start;
    transition: all .2s cubic-bezier(.4,0,.2,1);
}
.rec-card-cta:hover { background: #7e58bf; color: #fff; transform: translateX(2px); }

@media (prefers-reduced-motion: no-preference) {
    .rec-card { animation: rec-card-in .42s cubic-bezier(.4,0,.2,1) both; }
    .rec-card:nth-child(1) { animation-delay: 60ms; }
    .rec-card:nth-child(2) { animation-delay: 100ms; }
    .rec-card:nth-child(3) { animation-delay: 140ms; }
    .rec-card:nth-child(4) { animation-delay: 180ms; }
    .rec-card:nth-child(5) { animation-delay: 220ms; }
    .rec-card:nth-child(6) { animation-delay: 260ms; }
    .rec-card:nth-child(n+7) { animation-delay: 300ms; }
    @keyframes rec-card-in {
        0% { opacity: 0; transform: translateY(12px); }
        100% { opacity: 1; transform: translateY(0); }
    }
}

.rec-empty {
    grid-column: 1 / -1;
    text-align: center; padding: 60px 20px;
    color: #8a7baf; font-size: 14px;
}
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

    var favSet = new Set({!! json_encode(array_map('strval', (array) ($response->favorite_program_ids ?? []))) !!});

    // Favorite toggle (.rec-card-fav-btn ve .fav-btn ikisini de yakalar)
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
                // Tüm aynı program_id butonlarını senkronla
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
                // Aktif filter favorilerse re-apply
                var activeBtn = document.querySelector('[data-filter].active');
                if (activeBtn && activeBtn.dataset.filter === 'favorite') applyFilter('favorite');
            }).catch(function(){ toast('Bir hata oldu, tekrar dene', true); });
        });
    });

    // ── Filter buttons (sidebar)
    function applyFilter(f){
        var visibleCount = 0;
        document.querySelectorAll('.rec-card').forEach(function(card){
            var show = true;
            if (f === 'uni-assist') show = card.dataset.sourceType === 'uni-assist';
            else if (f === 'direkt') show = card.dataset.sourceType === 'direkt';
            else if (f === 'favorite') show = favSet.has(String(card.dataset.programId));
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        var countEl = document.querySelector('[data-result-count]');
        if (countEl) countEl.textContent = visibleCount;
        return visibleCount;
    }
    document.querySelectorAll('[data-filter]').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('[data-filter]').forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            var f = btn.dataset.filter;
            var visible = applyFilter(f);
            if (visible === 0 && f === 'favorite') {
                toast('Henüz favorin yok — bir programa yıldız ekle', true);
                document.querySelector('[data-filter="all"]').click();
            }
        });
    });

    // ── Sort dropdown
    var sortSelect = document.querySelector('[data-sort]');
    if (sortSelect) {
        sortSelect.addEventListener('change', function(){
            var grid = document.querySelector('[data-rec-grid]');
            if (! grid) return;
            var cards = Array.from(grid.querySelectorAll('.rec-card'));
            var mode = sortSelect.value;
            cards.sort(function(a, b){
                if (mode === 'tuition_asc') {
                    return (parseInt(a.dataset.tuition || '0', 10)) - (parseInt(b.dataset.tuition || '0', 10));
                }
                if (mode === 'tuition_desc') {
                    return (parseInt(b.dataset.tuition || '0', 10)) - (parseInt(a.dataset.tuition || '0', 10));
                }
                // default: match score desc (sayfaya geliş sırası)
                return (parseInt(a.dataset.rank || '99', 10)) - (parseInt(b.dataset.rank || '99', 10));
            });
            cards.forEach(function(c){ grid.appendChild(c); });
        });
    }
})();
</script>
@endpush

@section('title', 'Sana özel program önerileri — UniMatch')

@section('content')
<div class="sb-progress-wrap">
    <div class="sb-progress-meta">
        <span>✓ Tamamlandı</span>
        <span>%100</span>
    </div>
    <div class="sb-progress-bar">
        <div class="sb-progress-fill" style="width: 100%;"></div>
    </div>
</div>

<div class="sb-card" style="text-align: center; margin-bottom: 16px;">
    <div style="font-size: 48px; margin-bottom: 8px;">🎯</div>
    <h1 class="sb-title">Senin için {{ count($recommendations) }} program seçtik</h1>
    <p class="sb-subtitle">Cevaplarına göre 13.000+ program arasından en uyumlu olanları sıraladık.</p>
</div>

@if(count($recommendations) === 0)
    <div class="sb-card" style="text-align: center;">
        <p style="color: #6b5894; font-size: 14px;">Cevaplarına tam uyan program bulunamadı. Filtreleri biraz genişletmek için cevaplarını tekrar gözden geçirelim.</p>
        <div style="margin-top: 20px;">
            <a href="{{ route('uni-match.start') }}" class="sb-btn sb-btn-primary">Yeniden Başla</a>
        </div>
    </div>
@else
    {{-- ═══ Bachelorsportal-tarzı geniş layout: sticky filter sidebar + grid ═══ --}}
    @php
        $uaCount  = collect($recommendations)->where('is_uni_assist_member', true)->count();
        $dirCount = count($recommendations) - $uaCount;
        $favCountList = count((array) ($response->favorite_program_ids ?? []));
    @endphp

    <div class="rec-wide">
        <div class="rec-inner">
            <div class="rec-utility">
                <div class="rec-utility-count">
                    <span data-result-count>{{ count($recommendations) }}</span> program · sana özel sıralandı
                </div>
                <div class="rec-utility-actions">
                    <label style="font-size:12px;color:#6b5894;font-weight:600;">Sırala:</label>
                    <select class="rec-sort-select" data-sort>
                        <option value="match">Eşleşme skoru (varsayılan)</option>
                        <option value="tuition_asc">Ücret (düşükten yükseğe)</option>
                        <option value="tuition_desc">Ücret (yüksekten düşüğe)</option>
                    </select>
                </div>
            </div>

            <div class="rec-layout">
                {{-- ─── Sol sidebar: filtreler ─── --}}
                <aside class="rec-sidebar" aria-label="Programları filtrele">
                    <div class="rec-sidebar-section">
                        <div class="rec-sidebar-label">Başvuru tipi</div>
                        <div class="rec-filter-list">
                            <button type="button" class="rec-filter-btn active" data-filter="all">
                                <span>Tümü</span>
                                <span class="rec-filter-btn-count">{{ count($recommendations) }}</span>
                            </button>
                            <button type="button" class="rec-filter-btn" data-filter="uni-assist">
                                <span>📨 uni-assist</span>
                                <span class="rec-filter-btn-count">{{ $uaCount }}</span>
                            </button>
                            <button type="button" class="rec-filter-btn" data-filter="direkt">
                                <span>✅ Direkt</span>
                                <span class="rec-filter-btn-count">{{ $dirCount }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="rec-sidebar-section">
                        <div class="rec-sidebar-label">Listelerim</div>
                        <div class="rec-filter-list">
                            <button type="button" class="rec-filter-btn" data-filter="favorite">
                                <span>⭐ Favorilerim</span>
                                <span class="rec-filter-btn-count">{{ $favCountList }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="rec-sidebar-section">
                        <div class="rec-sidebar-label">Hızlı eylemler</div>
                        <div class="rec-filter-list">
                            <a href="{{ route('uni-match.result.pdf') }}"
                               class="rec-filter-btn" style="text-decoration:none;">
                                <span>📄 PDF indir</span>
                            </a>
                            <a href="{{ route('uni-match.start') }}"
                               class="rec-filter-btn" style="text-decoration:none;">
                                <span>🔁 Yeniden başla</span>
                            </a>
                        </div>
                    </div>
                </aside>

                {{-- ─── Sağ: program grid ─── --}}
                <div class="rec-grid" data-rec-grid>
                    @foreach($recommendations as $i => $rec)
                        @php
                            $sourceType = ! empty($rec['is_uni_assist_member']) ? 'uni-assist' : 'direkt';
                            $tuitionVal = isset($rec['tuition_eur']) && $rec['tuition_eur'] !== null ? (int) $rec['tuition_eur'] : 0;
                            $isFav      = in_array($rec['program_id'], (array) ($response->favorite_program_ids ?? []), true);
                        @endphp
                        <article class="rec-card"
                                 data-source-type="{{ $sourceType }}"
                                 data-program-id="{{ $rec['program_id'] }}"
                                 data-tuition="{{ $tuitionVal }}"
                                 data-rank="{{ $i + 1 }}">
                            <div class="rec-card-header">
                                <span class="rec-card-rank">#{{ $i + 1 }} ÖNERİ</span>
                                <button type="button"
                                        data-favorite-toggle
                                        data-program-id="{{ $rec['program_id'] }}"
                                        class="rec-card-fav-btn {{ $isFav ? 'is-fav' : '' }}"
                                        title="Favorile (max 3)"
                                        aria-label="Favorile">★</button>
                                <div class="rec-card-score">
                                    <span class="rec-card-score-num">{{ $rec['match_score'] }}</span>
                                    <span class="rec-card-score-label">/100</span>
                                </div>
                            </div>

                            <div class="rec-card-body">
                                <div class="rec-card-uni">
                                    {{ $rec['university_name'] ?? '—' }}
                                    @if(! empty($rec['location']))
                                        <span class="rec-card-uni-loc">· {{ $rec['location'] }}</span>
                                    @endif
                                </div>
                                <h3 class="rec-card-name">{{ $rec['course_name'] ?? '—' }}</h3>

                                <div class="rec-card-chips">
                                    @if(! empty($rec['degree_specification']))
                                        <span class="rec-chip rec-chip-degree">{{ $rec['degree_specification'] }}</span>
                                    @endif
                                    @foreach(($rec['languages_raw'] ?? []) as $lang)
                                        <span class="rec-chip rec-chip-lang">{{ $lang }}</span>
                                    @endforeach
                                    @if(($rec['tuition_eur'] ?? null) !== null)
                                        @if($tuitionVal === 0)
                                            <span class="rec-chip rec-chip-free">✓ Ücretsiz</span>
                                        @else
                                            <span class="rec-chip rec-chip-paid">{{ $tuitionVal }} €/sem</span>
                                        @endif
                                    @endif
                                    @if(! empty($rec['duration_semesters']))
                                        <span class="rec-chip">{{ $rec['duration_semesters'] }} sem</span>
                                    @endif
                                    @if($sourceType === 'uni-assist')
                                        <span class="rec-chip rec-chip-uniassist" title="uni-assist üzerinden başvuru">📨 uni-assist</span>
                                    @else
                                        <span class="rec-chip rec-chip-direkt" title="Üniversite kendi portali">✅ Direkt</span>
                                    @endif
                                </div>

                                @if(! empty($rec['reasons']))
                                    <div class="rec-card-reasons">
                                        @foreach(array_slice($rec['reasons'], 0, 3) as $reason)
                                            <div>· {{ $reason }}</div>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('program.show', ['program' => $rec['program_id']]) }}"
                                   target="_blank"
                                   class="rec-card-cta">
                                    Detayları gör <span style="font-size:14px;">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Sosyal proof (son 7 gün) --}}
    @if(($socialProof ?? 0) >= 5)
    <div style="text-align:center;margin:20px 0;padding:12px 18px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;">
        <div style="font-size:13px;color:#166534;font-weight:600;">
            <span style="display:inline-block;width:8px;height:8px;background:#16a34a;border-radius:50%;animation:pulse 1.5s infinite;margin-right:6px;vertical-align:middle;"></span>
            Son 7 günde <strong style="color:#15803d;">{{ number_format($socialProof) }}</strong> öğrenci UniMatch'ı tamamladı
        </div>
    </div>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
    @endif

    {{-- Sosyal paylaşım --}}
    @php
        $shareText = "🎯 " . config('brand.name') . " UniMatch sihirbazı bana özel " . count($recommendations) . " Almanya programı seçti! Sen de dene:";
        $shareUrl = url('/uni-match');
    @endphp
    <div style="margin: 20px 0; padding: 14px 18px; background: #f9f6fc; border-radius: 10px; border-left: 4px solid #7e58bf;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div style="flex:1;min-width:200px;">
                <div style="font-size: 14px; font-weight: 700; color: #6b5894;">📢 Bunu paylaş</div>
                <div style="font-size: 12px; color: #8a7baf; margin-top: 2px;">Almanya'ya gitmek isteyen arkadaşların da denemeli</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="https://wa.me/?text={{ urlencode($shareText . ' ' . $shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#25d366;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    💬 WhatsApp
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#000;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    𝕏 Twitter
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                   target="_blank" rel="noopener"
                   style="background:#0a66c2;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    💼 LinkedIn
                </a>
                <a href="mailto:?subject={{ urlencode('UniMatch — Almanya programı bul') }}&body={{ urlencode($shareText . ' ' . $shareUrl) }}"
                   style="background:#7e58bf;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    ✉️ E-posta
                </a>
            </div>
        </div>
    </div>

    {{-- PDF indirme bandı --}}
    @php $favCount = count((array) ($response->favorite_program_ids ?? [])); @endphp
    <div style="margin: 20px 0; padding: 14px 18px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 10px; border-left: 4px solid #d97706;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="font-size: 26px;">📄</div>
            <div style="flex: 1; min-width: 200px;">
                <div style="font-size: 14px; font-weight: 700; color: #78350f;">Sonuçlarımı PDF olarak indir</div>
                <div style="font-size: 12px; color: #92400e; margin-top: 2px;">Tüm {{ count($recommendations) }} program + profilin — paylaşıma hazır</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('uni-match.result.pdf') }}"
                   style="background: #92400e; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
                    Tümünü İndir →
                </a>
                @if($favCount > 0)
                <a href="{{ route('uni-match.result.pdf') }}?favorites=1"
                   style="background: #f59e0b; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
                    ⭐ {{ $favCount }} Favorimi İndir
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="sb-card" style="margin-top: 24px; text-align: center; background: linear-gradient(135deg, rgba(126, 88, 191, 0.06), rgba(167, 126, 217, 0.03));">
        <div style="font-size: 32px; margin-bottom: 8px;">🚀</div>
        <h2 class="sb-title">Hadi adım atalım</h2>
        <p class="sb-subtitle">{{ config('brand.name') }}'ye kayıt ol, danışmanın bu programlardan hangisinin sana en uygun olduğunu birlikte değerlendirin. Cevapların form'a otomatik aktarılacak — sadece kalan bilgileri tamamlarsın.</p>
        <form method="POST" action="{{ route('uni-match.convert') }}">
            @csrf
            <button type="submit" class="sb-btn sb-btn-primary" style="padding: 16px 36px; font-size: 16px; font-weight: 700;">
                Şimdi Kayıt Ol & Danışmanla Görüş
                <span style="font-size: 18px;">→</span>
            </button>
        </form>
        <div style="margin-top: 14px; font-size: 12px; color: #8a7baf;">
            Wizard cevapların kaydedildi — istediğin zaman bu sayfaya geri dönebilirsin.
        </div>
    </div>
@endif
@endsection
