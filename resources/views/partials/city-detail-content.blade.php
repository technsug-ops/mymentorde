@php
// Portal-aware routes
$cdIsStudent = request()->is('student/*');
$cdCityDetail = function ($slug) use ($cdIsStudent) {
    return $cdIsStudent ? route('student.info.city-detail', $slug) : route('guest.city-detail', $slug);
};
$cdDiscover = function ($params = []) use ($cdIsStudent) {
    return $cdIsStudent ? route('student.discover', $params) : route('guest.discover', $params);
};
$cdContentDetail = function ($slug) use ($cdIsStudent) {
    return $cdIsStudent ? route('student.content-detail', $slug) : route('guest.content-detail', $slug);
};
$cdUniGuide = $cdIsStudent ? route('student.info.university-guide') : route('guest.university-guide');
$cdCostCalc = $cdIsStudent
    ? (\Illuminate\Support\Facades\Route::has('student.cost-calculator') ? route('student.cost-calculator') : '#')
    : route('guest.cost-calculator');
@endphp

@push('head')
<script nonce="{{ $cspNonce ?? '' }}">if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist [style*="gradient"] {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
}
.cms-card-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,.1);
}

/* ── Hero (Option B: compact & data-forward) ── */
.city-hero {
    color: #fff; border-radius: 14px; margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden;
}
.city-hero-body {
    display: flex; align-items: stretch; gap: 24px; padding: 26px 28px;
}
.city-hero-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
.city-hero-header { display: flex; flex-direction: column; gap: 6px; }
.city-hero-label {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
    opacity: .82; margin-bottom: 2px;
}
.city-hero-marker {
    display: inline-block; width: 5px; height: 16px; background: rgba(255,255,255,.75); border-radius: 3px;
}
.city-hero-title {
    font-size: 34px; font-weight: 800; line-height: 1.1; margin: 0;
    letter-spacing: -.5px;
}
.city-hero-tagline { font-size: 14px; opacity: .88; line-height: 1.4; margin-bottom: 4px; }

.city-hero-stats {
    display: flex; gap: 8px; flex-wrap: wrap; margin: 6px 0 4px;
}
.city-hero-stat {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    background: rgba(255,255,255,.18); backdrop-filter: blur(6px);
    font-size: 12px; font-weight: 600; line-height: 1;
    border: 1px solid rgba(255,255,255,.12);
}
.city-hero-stat-ico { font-size: 13px; }
.city-hero-overview {
    font-size: 13px; opacity: .92; line-height: 1.55; margin-top: 10px;
    padding-top: 12px; border-top: 1px solid rgba(255,255,255,.18);
    max-width: 620px;
}

.city-hero-video {
    position: relative; width: 260px; aspect-ratio: 16/9; border-radius: 12px;
    overflow: hidden; border: 2px solid rgba(255,255,255,.3); cursor: pointer;
    padding: 0; flex-shrink: 0; background: #000; align-self: center;
    transition: transform .15s, border-color .15s, box-shadow .2s;
    box-shadow: 0 6px 22px rgba(0,0,0,.25);
}
.city-hero-video:hover { transform: scale(1.03); border-color: rgba(255,255,255,.6); box-shadow: 0 8px 28px rgba(0,0,0,.35); }
.city-hero-play {
    width: 46px; height: 46px; background: rgba(220,38,38,.95); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,.5);
}
.city-hero-play svg { width: 18px; height: 18px; }
@media (max-width: 720px) {
    /* Mobil: Video banner üstte (full-width), altta kompakt metin */
    .city-hero { border-radius: 12px; }
    .city-hero-body {
        display: flex; flex-direction: column; gap: 0; padding: 0;
    }
    .city-hero-video { order: -1; }
    .city-hero-main  { order: 0; }
    .city-hero-video {
        width: 100%; aspect-ratio: 16/9; border-radius: 12px 12px 0 0;
        border: none; box-shadow: none; display: block;
    }
    .city-hero-video::after {
        /* Alt kısımda gradient fade — metin alanıyla yumuşak geçiş */
        content: ''; position: absolute; inset: auto 0 0 0; height: 40%;
        background: linear-gradient(to bottom, transparent, rgba(0,0,0,.25));
        pointer-events: none;
    }
    .city-hero-video:hover { transform: none; }
    .city-hero-play { width: 50px; height: 50px; }
    .city-hero-play svg { width: 20px; height: 20px; }
    .city-hero-video span { font-size: .72rem !important; margin-top: 4px; }

    .city-hero-main {
        padding: 14px 16px 16px;
    }
    .city-hero-header { gap: 3px; margin-bottom: 10px; }
    .city-hero-label { font-size: 10px; letter-spacing: .7px; opacity: .85; }
    .city-hero-marker { height: 11px; width: 3px; }
    .city-hero-title { font-size: 24px; letter-spacing: -.3px; line-height: 1.1; }
    .city-hero-tagline { font-size: 12.5px; line-height: 1.35; opacity: .85; }

    /* Stats: inline ayraçlı tek satır (pill yerine) */
    .city-hero-stats {
        gap: 0; margin: 0; padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,.2);
        display: flex; align-items: center; flex-wrap: wrap; row-gap: 4px;
    }
    .city-hero-stat {
        background: none; border: none; padding: 0 10px 0 0; border-radius: 0;
        font-size: 12px; opacity: .92; position: relative;
    }
    .city-hero-stat + .city-hero-stat { padding-left: 10px; }
    .city-hero-stat + .city-hero-stat::before {
        content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
        width: 1px; background: rgba(255,255,255,.3);
    }
    .city-hero-stat-ico { font-size: 12px; }

    .city-hero-overview { display: none; }
}
@media (max-width: 420px) {
    .city-hero-title { font-size: 22px; }
    .city-hero-main { padding: 12px 14px 14px; }
}

/* ── Şehir Navigasyonu ── */
.city-nav-wrap { margin-bottom: 20px; position: relative; }
.city-nav {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.city-nav-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 20px;
    font-size: var(--tx-xs); font-weight: 600;
    text-decoration: none; white-space: nowrap;
    background: var(--u-card); color: var(--u-text);
    border: 1px solid var(--u-line);
    transition: border-color .15s, transform .1s;
}
.city-nav-pill:hover { border-color: var(--u-brand); text-decoration: none; }
.city-nav-pill.active {
    background: var(--u-brand, #2563eb); color: #fff; border-color: var(--u-brand, #2563eb);
}
.city-nav-ico { font-size: 14px; line-height: 1; }

/* Ok butonları (sadece mobilde görünür) */
.city-nav-arrow { display: none; }

/* Mobil: yatay scroll (single row) + ok butonları */
@media (max-width: 720px) {
    .city-nav-wrap {
        margin-left: -4px; margin-right: -4px;
        padding: 0 4px;
        -webkit-overflow-scrolling: touch;
    }
    .city-nav {
        flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none;
        padding: 2px 36px;
        scroll-snap-type: x proximity;
        mask-image: linear-gradient(to right, transparent 0, #000 28px, #000 calc(100% - 28px), transparent 100%);
    }
    .city-nav::-webkit-scrollbar { display: none; }
    .city-nav-pill { flex-shrink: 0; scroll-snap-align: start; padding: 7px 12px; }
    .city-nav-pill.active { scroll-snap-align: center; }

    /* Ok butonları */
    .city-nav-arrow {
        display: flex; align-items: center; justify-content: center;
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 30px; height: 30px; border-radius: 50%;
        background: var(--u-card); color: var(--u-text);
        border: 1px solid var(--u-line);
        font-size: 22px; font-weight: 700; line-height: 1;
        cursor: pointer; z-index: 2; padding: 0; padding-bottom: 3px;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
        transition: opacity .2s, transform .1s;
    }
    .city-nav-arrow:hover { transform: translateY(-50%) scale(1.08); }
    .city-nav-arrow:active { transform: translateY(-50%) scale(.95); }
    .city-nav-arrow.prev { left: 2px; }
    .city-nav-arrow.next { right: 2px; }
    .city-nav-arrow[data-hidden="1"] { opacity: 0; pointer-events: none; }
}
</style>
@endpush

@php
    $c = $city ?? [];
    $costLabels = [1=>'Çok Uygun', 2=>'Uygun', 3=>'Orta', 4=>'Pahalı', 5=>'Çok Pahalı'];
    $costBadge  = [1=>'ok', 2=>'ok', 3=>'info', 4=>'warn', 5=>'danger'];
    $idx = $c['cost_index'] ?? 3;
    $collarIcon = ['beyaz yaka'=>'👔', 'mavi yaka'=>'🔧', 'her ikisi'=>'⚡'];
@endphp

{{-- Hero (B: compact + data-forward) --}}
@php
    $heroVid   = $c['hero_video_id'] ?? '';
    $heroThumb = $c['hero_video_thumb'] ?? '';
    $thumbSrc  = $heroThumb ?: ($heroVid ? "https://img.youtube.com/vi/{$heroVid}/maxresdefault.jpg" : '');

    $heroOverview = trim($c['overview'] ?? '');
    if (mb_strlen($heroOverview) > 100) {
        $cut = mb_substr($heroOverview, 0, 100);
        $lastSpace = mb_strrpos($cut, ' ');
        $heroOverview = ($lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut) . '…';
    }
@endphp
<div class="city-hero" style="background:{{ $c['hero_color'] ?? 'var(--u-brand)' }};">
    <div class="city-hero-body">
        <div class="city-hero-main">
            <div class="city-hero-header">
                <div class="city-hero-label">
                    <span class="city-hero-marker"></span>
                    {{ $c['state'] ?? '' }} Eyaleti
                </div>
                <h1 class="city-hero-title">{{ $c['name'] ?? '' }}</h1>
                <div class="city-hero-tagline">{{ $c['tagline'] ?? '' }}</div>
            </div>

            <div class="city-hero-stats">
                <span class="city-hero-stat"><span class="city-hero-stat-ico">👥</span>{{ $c['population'] ?? '' }}</span>
                <span class="city-hero-stat"><span class="city-hero-stat-ico">🎓</span>{{ $c['student_pop'] ?? '' }} öğrenci</span>
                <span class="city-hero-stat"><span class="city-hero-stat-ico">💶</span>{{ $costLabels[$idx] }}</span>
            </div>

            @if($heroOverview)
            <div class="city-hero-overview">{{ $heroOverview }}</div>
            @endif
        </div>

        @if($heroVid)
        <button data-vid-open="{{ $heroVid }}" class="city-hero-video">
            <img src="{{ $thumbSrc }}"
                 alt="{{ $c['name'] }} video"
                 onerror="this.src='https://img.youtube.com/vi/{{ $heroVid }}/hqdefault.jpg';"
                 style="width:100%;height:100%;object-fit:cover;opacity:.92;">
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;">
                <div class="city-hero-play">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg>
                </div>
                <span style="font-size:.66rem;font-weight:700;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.8);">Videoyu İzle</span>
            </div>
        </button>
        @endif
    </div>
</div>

{{-- Şehir Navigasyonu — yatay scroll (mobil) / wrap (desktop) --}}
<div class="city-nav-wrap">
    <button type="button" class="city-nav-arrow prev" aria-label="Önceki şehirler" data-city-nav-dir="-1">‹</button>
    <div class="city-nav" id="cityNavTrack">
        @foreach($allCities ?? [] as $key => $ac)
        <a href="{{ $cdCityDetail($key) }}"
           class="city-nav-pill {{ $key === ($c['slug'] ?? '') ? 'active' : '' }}">
            <span class="city-nav-ico">{{ $ac['emoji'] ?? '' }}</span>
            <span class="city-nav-name">{{ $ac['name'] }}</span>
        </a>
        @endforeach
    </div>
    <button type="button" class="city-nav-arrow next" aria-label="Sonraki şehirler" data-city-nav-dir="1">›</button>
</div>

<div class="col2" style="margin-bottom:20px;">

    {{-- Konum & Ulaşım --}}
    @if(!empty($c['location']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">📍 Konum & Ulaşım</div>
            @if(!empty($c['location']['region']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">BÖLGE</div>
                <div style="font-size:var(--tx-sm);">{{ $c['location']['region'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['airport']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">HAVALİMANI</div>
                <div style="font-size:var(--tx-sm);">✈ {{ $c['location']['airport'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['train_hubs']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">TREN BAĞLANTILARI</div>
                @foreach($c['location']['train_hubs'] as $train)
                <div style="font-size:var(--tx-xs);margin-bottom:3px;">🚄 {{ $train }}</div>
                @endforeach
            </div>
            @endif
            @if(!empty($c['location']['city_transport']))
            <div style="padding:8px 0;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">ŞEHİR İÇİ ULAŞIM</div>
                <div style="font-size:var(--tx-xs);">🚇 {{ $c['location']['city_transport'] }}</div>
            </div>
            @endif
            @if(!empty($c['location']['geography']))
            <div style="margin-top:8px;padding:8px 10px;background:var(--u-bg,#f8fafc);border-radius:6px;font-size:var(--tx-xs);color:var(--u-muted);">
                {{ $c['location']['geography'] }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Kültür --}}
    @if(!empty($c['culture']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">🎭 Şehir Kültürü</div>
            @if(!empty($c['culture']['personality']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">KARAKTERİ</div>
                <div style="font-size:var(--tx-sm);font-style:italic;">{{ $c['culture']['personality'] }}</div>
            </div>
            @endif
            @if(!empty($c['culture']['notable_for']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">TANINDIĞI ÖZELLİKLER</div>
                <div style="display:flex;flex-wrap:wrap;gap:5px;">
                    @foreach($c['culture']['notable_for'] as $n)
                    <span class="badge info" style="font-size:var(--tx-xs);">{{ $n }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($c['culture']['student_life']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">ÖĞRENCİ HAYATI</div>
                <div style="font-size:var(--tx-xs);line-height:1.5;">{{ $c['culture']['student_life'] }}</div>
            </div>
            @endif
            @if(!empty($c['culture']['turkish_community']))
            <div style="padding:8px 0;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">🇹🇷 TÜRK TOPLULUĞU</div>
                <div style="font-size:var(--tx-xs);line-height:1.5;">{{ $c['culture']['turkish_community'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- Üniversiteler --}}
@if(!empty($c['universities']))
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">🏛 Üniversiteler</div>
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
    @foreach($c['universities'] as $uni)
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div style="padding:16px 18px;border-bottom:1px solid var(--u-line);">
                <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">{{ $uni['name'] }}</div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="badge info" style="font-size:var(--tx-xs);">{{ $uni['type'] ?? '' }}</span>
                            @if(!empty($uni['founded']))
                            <span class="badge" style="font-size:var(--tx-xs);">Est. {{ $uni['founded'] }}</span>
                            @endif
                            @if(!empty($uni['students']))
                            <span class="badge" style="font-size:var(--tx-xs);">👥 {{ number_format($uni['students']) }} öğrenci</span>
                            @endif
                            @if(!empty($uni['english_programs']))
                            <span class="badge ok" style="font-size:var(--tx-xs);">🌍 İngilizce program var</span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($uni['qs_ranking']))
                    <div style="text-align:center;padding:8px 14px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:10px;color:#fff;">
                        <div style="font-size:var(--tx-lg);font-weight:800;">#{{ $uni['qs_ranking'] }}</div>
                        <div style="font-size:var(--tx-xs);opacity:.8;">QS Dünya</div>
                    </div>
                    @endif
                </div>
            </div>
            <div style="padding:12px 18px;display:flex;gap:16px;flex-wrap:wrap;">
                @if(!empty($uni['strengths']))
                <div style="flex:1;min-width:180px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:6px;">GÜÇLÜ PROGRAMLAR</div>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($uni['strengths'] as $s)
                        <span class="badge ok" style="font-size:var(--tx-xs);">{{ $s }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($uni['note']))
                <div style="flex:1;min-width:180px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">NOT</div>
                    <div style="font-size:var(--tx-xs);color:var(--u-text);line-height:1.5;">{{ $uni['note'] }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="col2" style="margin-bottom:20px;">

    {{-- Yaşam Maliyeti --}}
    @if(!empty($c['cost_of_living']))
    @php $cost = $c['cost_of_living']; @endphp
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div style="font-weight:700;font-size:var(--tx-sm);">💶 Yaşam Maliyeti</div>
                <span class="badge {{ $costBadge[$idx] ?? 'info' }}">{{ $costLabels[$idx] ?? '' }}</span>
            </div>
            {{-- Pahalılık çubuğu --}}
            <div style="margin-bottom:14px;">
                <div style="display:flex;gap:4px;">
                    @for($i=1;$i<=5;$i++)
                    <div style="flex:1;height:8px;border-radius:4px;background:{{ $i<=$idx ? ($idx>=4?'#dc2626':($idx>=3?'#d97706':'#16a34a')) : 'var(--u-line)' }};"></div>
                    @endfor
                </div>
            </div>
            @if(!empty($cost['rent']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">KİRA</div>
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);margin-bottom:2px;">
                    <span>WG/Oda</span><strong>{{ $cost['rent']['wg_room'] ?? '' }}</strong>
                </div>
                @if(!empty($cost['rent']['studentenwohnheim']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Öğrenci Yurdu</span><strong style="color:var(--u-ok,#16a34a);">{{ $cost['rent']['studentenwohnheim'] }}</strong>
                </div>
                @endif
            </div>
            @endif
            @if(!empty($cost['food']))
            <div style="padding:8px 0;border-bottom:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">YEMEK</div>
                @if(!empty($cost['food']['mensa_lunch']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Mensa öğle</span><strong>{{ $cost['food']['mensa_lunch'] }}</strong>
                </div>
                @endif
                @if(!empty($cost['food']['grocery_monthly']))
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span>Market (aylık)</span><strong>{{ $cost['food']['grocery_monthly'] }}</strong>
                </div>
                @endif
            </div>
            @endif
            @if(!empty($cost['monthly_total_estimate']))
            <div style="margin-top:12px;padding:10px 12px;background:rgba(37,99,235,.06);border-radius:8px;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">TOPLAM TAHMİN (tipik öğrenci)</div>
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-brand,#2563eb);">{{ $cost['monthly_total_estimate'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Sağlık sigortası dahil, yurt hariç</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Artılar / Eksiler --}}
    @if(!empty($c['pros_cons']))
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">⚖️ Artılar & Eksiler</div>
            @if(!empty($c['pros_cons']['pros']))
            <div style="margin-bottom:12px;">
                <div style="font-size:var(--tx-xs);color:var(--u-ok,#16a34a);font-weight:700;margin-bottom:6px;">✓ ARTILARI</div>
                @foreach($c['pros_cons']['pros'] as $p)
                <div style="font-size:var(--tx-xs);padding:4px 0;border-bottom:1px solid var(--u-line);display:flex;gap:6px;align-items:flex-start;">
                    <span style="color:var(--u-ok,#16a34a);flex-shrink:0;">+</span><span>{{ $p }}</span>
                </div>
                @endforeach
            </div>
            @endif
            @if(!empty($c['pros_cons']['cons']))
            <div>
                <div style="font-size:var(--tx-xs);color:var(--u-danger,#dc2626);font-weight:700;margin-bottom:6px;">✗ EKSİLERİ</div>
                @foreach($c['pros_cons']['cons'] as $con)
                <div style="font-size:var(--tx-xs);padding:4px 0;border-bottom:1px solid var(--u-line);display:flex;gap:6px;align-items:flex-start;">
                    <span style="color:var(--u-danger,#dc2626);flex-shrink:0;">−</span><span>{{ $con }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- İş Piyasası --}}
@if(!empty($c['job_market']))
@php $jm = $c['job_market']; @endphp
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">💼 İş Piyasası & Kariyer</div>
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:20px;">
        @if(!empty($jm['overview']))
        <div style="font-size:var(--tx-sm);color:var(--u-text);margin-bottom:14px;line-height:1.6;padding:12px;background:rgba(37,99,235,.05);border-radius:8px;border-left:3px solid var(--u-brand,#2563eb);">
            {{ $jm['overview'] }}
        </div>
        @endif
        <div class="col3" style="margin-bottom:0;">
            @if(!empty($jm['avg_salary']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-ok,#16a34a);">{{ $jm['avg_salary'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">Ortalama Maaş</div>
            </div>
            @endif
            @if(!empty($jm['unemployment']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-brand,#2563eb);">{{ $jm['unemployment'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);">İşsizlik Oranı</div>
            </div>
            @endif
            @if(!empty($jm['student_jobs']))
            <div style="text-align:center;padding:14px;background:var(--u-bg,#f8fafc);border-radius:10px;border:1px solid var(--u-line);">
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:4px;">ÖĞRENCİ İŞLERİ</div>
                <div style="font-size:var(--tx-xs);color:var(--u-text);">{{ $jm['student_jobs'] }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@if(!empty($jm['dominant_sectors']))
<div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
    @foreach($jm['dominant_sectors'] as $sector)
    <div class="card">
        <div class="card-body" style="padding:16px 18px;">
            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:var(--tx-lg);">{{ $collarIcon[$sector['collar'] ?? 'beyaz yaka'] ?? '💼' }}</span>
                        <span style="font-weight:700;font-size:var(--tx-sm);">{{ $sector['name'] }}</span>
                        <span class="badge info" style="font-size:var(--tx-xs);">{{ $sector['collar'] ?? '' }}</span>
                    </div>
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;margin-bottom:8px;">{{ $sector['description'] ?? '' }}</div>
                    @if(!empty($sector['companies']))
                    <div style="display:flex;flex-wrap:wrap;gap:5px;">
                        @foreach($sector['companies'] as $co)
                        <span style="font-size:var(--tx-xs);padding:2px 8px;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line);border-radius:12px;color:var(--u-text);">{{ $co }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                {{-- Yoğunluk barı --}}
                @if(!empty($sector['intensity']))
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;min-width:60px;">
                    <div style="font-size:var(--tx-xs);color:var(--u-muted);">YOĞUNLUK</div>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        @for($i=5;$i>=1;$i--)
                        <div style="width:40px;height:6px;border-radius:3px;background:{{ $i<=$sector['intensity'] ? '#2563eb' : 'var(--u-line)' }};"></div>
                        @endfor
                    </div>
                    <div style="font-size:var(--tx-sm);font-weight:800;color:var(--u-brand,#2563eb);">{{ $sector['intensity'] }}/5</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endif

{{-- Gezilecek Yerler --}}
@if(!empty($c['attractions']))
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📸 Gezilecek Yerler</div>
<div class="col3" style="margin-bottom:20px;">
    @foreach($c['attractions'] as $att)
    <div class="card" style="margin-bottom:0;">
        <div class="card-body" style="padding:14px 16px;display:flex;align-items:center;gap:12px;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:var(--tx-sm);margin-bottom:2px;">{{ $att['name'] }}</div>
                <div style="display:flex;gap:6px;align-items:center;margin-top:4px;">
                    <span class="badge" style="font-size:var(--tx-xs);">{{ $att['type'] ?? '' }}</span>
                    <span class="badge ok" style="font-size:var(--tx-xs);">{{ $att['price'] ?? '' }}</span>
                </div>
                @if(!empty($att['note']))
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;line-height:1.4;">{{ $att['note'] }}</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Öğrenci İpuçları --}}
@if(!empty($c['student_tips']))
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">💡 Öğrenci İpuçları</div>
        <div class="col2">
            @foreach($c['student_tips'] as $tip => $desc)
            <div style="padding:10px 12px;background:var(--u-bg,#f8fafc);border-radius:8px;border:1px solid var(--u-line);">
                <div style="font-weight:600;font-size:var(--tx-xs);margin-bottom:3px;color:var(--u-brand,#2563eb);">{{ $tip }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.4;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Etkinlikler --}}
@if(!empty($c['culture']['events']))
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:20px;">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:14px;">🎪 Önemli Etkinlikler</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($c['culture']['events'] as $event => $desc)
            <div style="display:flex;gap:12px;align-items:center;padding:8px;background:var(--u-bg,#f8fafc);border-radius:8px;">
                <span style="font-weight:700;font-size:var(--tx-xs);color:var(--u-brand,#2563eb);min-width:160px;">{{ $event }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $desc }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- 📹 Video İçerikleri --}}
@php
    $videos = collect($c['videos'] ?? [])->filter(fn($v) => !empty($v['youtube_id']));
    $categoryLabels = ['şehir' => '🏙 Şehir Hayatı', 'üniversite' => '🏛 Üniversite', 'yaşam' => '🏠 Yaşam', 'kariyer' => '💼 Kariyer', 'genel' => '📌 Genel'];
    $categoryColors = ['şehir' => '#2563eb', 'üniversite' => '#7c3aed', 'yaşam' => '#16a34a', 'kariyer' => '#d97706', 'genel' => '#64748b'];
@endphp
@if($videos->isNotEmpty())
<div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">📹 Video İçerikleri</div>

{{-- Kategori filtre butonları --}}
@php $videoCategories = $videos->pluck('category')->unique()->values(); @endphp
@if($videoCategories->count() > 1)
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="vid-filters">
    <button data-cat="all"
            style="padding:5px 14px;border-radius:20px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;border:2px solid var(--u-brand,#2563eb);background:var(--u-brand,#2563eb);color:#fff;">
        Tümü ({{ $videos->count() }})
    </button>
    @foreach($videoCategories as $vcat)
    <button data-cat="{{ $vcat }}"
            style="padding:5px 14px;border-radius:20px;font-size:var(--tx-xs);font-weight:700;cursor:pointer;border:2px solid {{ $categoryColors[$vcat] ?? '#64748b' }};background:var(--u-card);color:{{ $categoryColors[$vcat] ?? '#64748b' }};">
        {{ $categoryLabels[$vcat] ?? $vcat }} ({{ $videos->where('category', $vcat)->count() }})
    </button>
    @endforeach
</div>
@endif

<div class="col2" style="margin-bottom:24px;" id="vid-grid">
    @foreach($videos as $vid)
    @php
        $embedUrl = 'https://www.youtube.com/embed/' . htmlspecialchars($vid['youtube_id'], ENT_QUOTES, 'UTF-8') . '?rel=0&modestbranding=1';
        $thumbUrl = 'https://img.youtube.com/vi/' . htmlspecialchars($vid['youtube_id'], ENT_QUOTES, 'UTF-8') . '/hqdefault.jpg';
        $vcat = $vid['category'] ?? 'genel';
        $vcolor = $categoryColors[$vcat] ?? '#64748b';
    @endphp
    <div class="card vid-card" data-cat="{{ $vcat }}" style="margin-bottom:0;overflow:hidden;">
        {{-- Thumbnail tıklama ile embed aç --}}
        <div style="position:relative;padding-bottom:56.25%;height:0;background:#0f0f0f;cursor:pointer;"
             data-vid-play="{{ $embedUrl }}"
             title="{{ $vid['title'] ?? '' }}">
            <img src="{{ $thumbUrl }}" alt="{{ $vid['title'] ?? '' }}"
                 loading="lazy"
                 style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;opacity:.85;">
            {{-- Play butonu --}}
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                        width:56px;height:56px;background:rgba(255,0,0,.85);border-radius:50%;
                        display:flex;align-items:center;justify-content:center;pointer-events:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><polygon points="8,5 19,12 8,19"/></svg>
            </div>
            @if(!empty($vid['duration']))
            <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.75);color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px;">
                {{ $vid['duration'] }}
            </div>
            @endif
            {{-- Kategori badge --}}
            <div style="position:absolute;top:8px;left:8px;background:{{ $vcolor }};color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">
                {{ $categoryLabels[$vcat] ?? $vcat }}
            </div>
        </div>
        <div style="padding:12px 14px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;line-height:1.3;">{{ $vid['title'] ?? '' }}</div>
            @if(!empty($vid['description']))
            <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.4;">{{ $vid['description'] }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    // Video oynat: data-vid-play veya data-vid-open
    function playVideoEl(el, embedUrl) {
        var iframe = document.createElement('iframe');
        iframe.src = embedUrl + '&autoplay=1';
        iframe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';
        iframe.allow = 'accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share';
        iframe.allowFullscreen = true;
        el.style.cursor = 'default';
        el.innerHTML = '';
        el.appendChild(iframe);
    }

    // Kategori filtresi
    function filterVideos(cat) {
        document.querySelectorAll('#vid-filters button').forEach(function (btn) {
            var isSel = btn.dataset.cat === cat;
            btn.style.background = isSel ? (btn.style.borderColor || 'var(--u-brand,#2563eb)') : 'var(--u-card)';
            btn.style.color      = isSel ? '#fff' : (btn.style.borderColor || 'var(--u-brand,#2563eb)');
        });
        document.querySelectorAll('.vid-card').forEach(function (card) {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    }

    // Delegation: video grid click
    document.addEventListener('click', function (e) {
        // data-vid-play (grid kartlar)
        var playEl = e.target.closest('[data-vid-play]');
        if (playEl) { playVideoEl(playEl, playEl.dataset.vidPlay); return; }

        // data-vid-open (hero butonu)
        var openEl = e.target.closest('[data-vid-open]');
        if (openEl) {
            var vid = openEl.dataset.vidOpen;
            var embedUrl = 'https://www.youtube.com/embed/' + vid + '?rel=0&modestbranding=1';
            playVideoEl(openEl, embedUrl);
            return;
        }

        // data-cat (filtre butonları)
        var catBtn = e.target.closest('#vid-filters button[data-cat]');
        if (catBtn) { filterVideos(catBtn.dataset.cat); return; }
    });
}());
</script>

{{-- CMS İçerikleri (tag eşleme) --}}
@php
use App\Models\Marketing\CmsContent;
$citySlugForCms = $city['slug'] ?? array_search($city, config('germany_cities', []));
// Try to use the route slug
$routeSlug = request()->route('slug') ?? $citySlugForCms;
$cityContents = CmsContent::where('status', 'published')
    ->whereJsonContains('tags', $routeSlug)
    ->whereIn('type', ['blog', 'experience', 'career_guide', 'tip'])
    ->where(fn($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'guests'))
    ->orderByDesc('published_at')
    ->limit(4)->get();
@endphp

@if($cityContents->isNotEmpty())
<div style="margin-top:32px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:1rem;font-weight:700;color:var(--u-text,#1a1a1a);">📚 Bu Şehir Hakkında İçerikler</div>
        <a href="{{ $cdDiscover(['cat' => 'city-content']) }}" style="font-size:.82rem;color:var(--u-brand,#2563eb);text-decoration:none;font-weight:600;">Tümünü Gör →</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
        @foreach($cityContents as $cms)
        @php
        $typeIcons = ['blog'=>'📝','video_feature'=>'▶️','podcast'=>'🎙','presentation'=>'📊','experience'=>'💬','career_guide'=>'🗺','tip'=>'💡'];
        $typeLabels = ['blog'=>'Blog','video_feature'=>'Video','podcast'=>'Podcast','presentation'=>'Sunum','experience'=>'Deneyim','career_guide'=>'Kariyer','tip'=>'İpucu'];
        $gradients = ['student-life'=>'linear-gradient(to right,#0d2748,#1f6fd9)','culture-fun'=>'linear-gradient(to right,#2e1660,#6b3fa0)','careers'=>'linear-gradient(to right,#0a2e18,#166534)','tips-tricks'=>'linear-gradient(to right,#0a2e3e,#1e607a)','city-content'=>'linear-gradient(to right,#072840,#0e6fa0)','uni-content'=>'linear-gradient(to right,#0f1d5a,#2a3fa8)','success-stories'=>'linear-gradient(to right,#0d1e52,#1a3a8a)'];
        @endphp
        <a href="{{ $cdContentDetail($cms->slug) }}" class="cms-card-link" style="display:flex;gap:12px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:10px;overflow:hidden;text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s;">
            <div style="width:72px;flex-shrink:0;background:{{ $gradients[$cms->category] ?? 'linear-gradient(to right,#0d2748,#1f6fd9)' }};display:flex;align-items:center;justify-content:center;font-size:1.8rem;">{{ $typeIcons[$cms->type] ?? '📄' }}</div>
            <div style="padding:10px 12px;flex:1;">
                <div style="font-size:.78rem;color:var(--u-muted,#888);margin-bottom:3px;">{{ $typeLabels[$cms->type] ?? $cms->type }}</div>
                <div style="font-size:.88rem;font-weight:600;color:var(--u-text,#1a1a1a);line-height:1.35;">{{ Str::limit($cms->title_tr, 65) }}</div>
                @if($cms->summary_tr)
                <div style="font-size:.78rem;color:var(--u-muted,#888);margin-top:3px;">{{ Str::limit($cms->summary_tr, 70) }}</div>
                @endif
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Navigasyon --}}
<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;flex-wrap:wrap;gap:8px;margin-top:20px;">
    <a href="{{ $cdUniGuide }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Üniversite Rehberi</a>
    <a href="{{ $cdCostCalc }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Maliyet Hesapla →</a>
</div>

{{-- Video Modal --}}
<div id="city-vid-modal"
     style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.85);align-items:center;justify-content:center;"
     onclick="if(event.target===this)cityVidClose()">
    <div style="position:relative;width:min(900px,92vw);">
        <div style="position:relative;padding-bottom:56.25%;height:0;">
            <iframe id="city-vid-iframe" src="" allow="autoplay;accelerometer;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen
                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;border-radius:12px;"></iframe>
        </div>
        <button onclick="cityVidClose()"
                style="position:absolute;top:-14px;right:-14px;background:#fff;border:none;color:#111;border-radius:50%;width:36px;height:36px;font-size:18px;cursor:pointer;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.3);">✕</button>
    </div>
</div>
<script>
function cityVidOpen(id){
    var modal = document.getElementById('city-vid-modal');
    document.getElementById('city-vid-iframe').src = 'https://www.youtube.com/embed/' + id + '?autoplay=1&rel=0';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cityVidClose(){
    document.getElementById('city-vid-modal').style.display = 'none';
    document.getElementById('city-vid-iframe').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') cityVidClose(); });

// City nav: scroll active pill into view + arrow button behavior (mobile)
(function(){
    var track = document.getElementById('cityNavTrack');
    if (!track) return;

    // Aktif şehri ortala
    var active = track.querySelector('.city-nav-pill.active');
    if (active && active.scrollIntoView) {
        active.scrollIntoView({ block: 'nearest', inline: 'center' });
    }

    // Ok butonları
    var prevBtn = document.querySelector('.city-nav-arrow.prev');
    var nextBtn = document.querySelector('.city-nav-arrow.next');

    function updateArrows(){
        if (!prevBtn || !nextBtn) return;
        var max = track.scrollWidth - track.clientWidth - 2;
        prevBtn.dataset.hidden = track.scrollLeft <= 4 ? '1' : '0';
        nextBtn.dataset.hidden = track.scrollLeft >= max ? '1' : '0';
    }

    [prevBtn, nextBtn].forEach(function(btn){
        if (!btn) return;
        btn.addEventListener('click', function(){
            var dir = parseInt(btn.dataset.cityNavDir || '1', 10);
            track.scrollBy({ left: dir * Math.max(track.clientWidth * 0.7, 180), behavior: 'smooth' });
        });
    });

    track.addEventListener('scroll', updateArrows, { passive: true });
    window.addEventListener('resize', updateArrows);
    updateArrows();
})();
</script>
