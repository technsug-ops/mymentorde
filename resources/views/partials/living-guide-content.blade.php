{{-- ══════════════════════════════════════════════════════════════════════════
  Shared Living Guide partial — Guest + Student portals.
═══════════════════════════════════════════════════════════════════════════ --}}

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
.jm-minimalist .lg-hero { background: #e2e5ec !important; color: var(--u-text,#1a1a1a) !important; border: 1px solid rgba(0,0,0,.10) !important; }
.jm-minimalist .lg-hero::before { display: none !important; }
.jm-minimalist .lg-hero * { color: inherit !important; opacity: 1 !important; }

/* ══════ Hero ══════ */
.lg-hero {
    color:#fff; border-radius:14px; margin-bottom:20px; overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.14); position:relative;
    background:#2563eb url('https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=1400&q=80') center/cover;
}
.lg-hero::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, rgba(37,99,235,.92) 0%, rgba(124,58,237,.82) 100%);
}
.lg-hero-body { position:relative; display:flex; align-items:center; gap:24px; padding:26px 28px; }
.lg-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
.lg-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.82; }
.lg-hero-marker { display:inline-block; width:5px; height:16px; background:rgba(255,255,255,.75); border-radius:3px; }
.lg-hero-title { font-size:32px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.5px; }
.lg-hero-overview { font-size:14px; opacity:.92; line-height:1.55; max-width:600px; margin-top:2px; }
.lg-hero-stats { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.lg-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; background:rgba(255,255,255,.18); font-size:12px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.lg-hero-stat-ico { font-size:13px; }
.lg-hero-icon { font-size:64px; line-height:1; flex-shrink:0; opacity:.9; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
.lg-hero-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
.lg-hero-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 11px; border-radius:20px; background:rgba(255,255,255,.92);
    color:#2563eb; font-size:11px; font-weight:700; text-decoration:none;
    box-shadow:0 2px 6px rgba(0,0,0,.15); transition:transform .12s, box-shadow .12s;
}
.lg-hero-chip:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.2); text-decoration:none; }

@media (max-width:720px){
    .lg-hero{border-radius:12px;}
    .lg-hero-body{gap:14px; padding:18px; align-items:flex-start;}
    .lg-hero-title{font-size:22px; letter-spacing:-.3px;}
    .lg-hero-overview{font-size:12.5px; line-height:1.45; max-width:none;}
    .lg-hero-stats{gap:6px; margin-top:10px; padding-top:10px;}
    .lg-hero-stat{padding:4px 9px; font-size:11px;}
    .lg-hero-icon{font-size:40px; align-self:flex-start; margin-top:2px;}
    .lg-hero-label{font-size:10px; letter-spacing:.5px;}
    .lg-hero-marker{height:12px; width:3px;}
    .lg-hero-chip{font-size:10px; padding:4px 9px;}
}

/* ══════ Cost Table ══════ */
.lg-cost-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; margin-bottom:24px; overflow:hidden; }
.lg-cost-table { width:100%; border-collapse:collapse; font-size:13px; }
.lg-cost-table thead th {
    background:color-mix(in srgb, var(--u-brand,#2563eb) 5%, var(--u-bg));
    padding:13px 16px; text-align:left; font-weight:700; font-size:12px;
    color:var(--u-text); letter-spacing:.2px;
    border-bottom:1px solid var(--u-line);
}
.lg-cost-table thead th.right { text-align:right; }
.lg-cost-table thead th.total { color:var(--u-brand); }
.lg-cost-table tbody tr { border-bottom:1px solid var(--u-line); transition:background .12s; }
.lg-cost-table tbody tr:hover { background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, transparent); }
.lg-cost-table tbody tr:last-child { border-bottom:none; }
.lg-cost-table td { padding:13px 16px; vertical-align:middle; }
.lg-cost-table td.right { text-align:right; }
.lg-cost-table .lg-city-cell { display:flex; align-items:center; gap:10px; font-weight:600; }
.lg-cost-table .lg-city-emoji {
    width:30px; height:30px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; background:color-mix(in srgb, var(--u-brand,#2563eb) 10%, var(--u-bg)); flex-shrink:0;
}
.lg-cost-total { font-weight:800; color:var(--u-brand,#2563eb); }
.lg-cost-try { color:var(--u-muted); font-size:11.5px; }
.lg-cost-foot { padding:11px 16px; font-size:11.5px; color:var(--u-muted); background:color-mix(in srgb, var(--u-brand,#2563eb) 3%, transparent); }

@media (max-width:720px){
    .lg-cost-card { overflow-x:auto; }
    .lg-cost-table { min-width:620px; font-size:12px; }
    .lg-cost-table td, .lg-cost-table thead th { padding:10px 12px; }
}

/* ══════ Housing Cards ══════ */
.lg-housing-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.lg-housing-card {
    background:var(--u-card); border:1px solid var(--u-line); border-radius:14px;
    overflow:hidden; display:flex; flex-direction:column;
    transition:transform .18s, box-shadow .18s, border-color .18s;
    --h-color: #6366f1;
}
.lg-housing-card:hover {
    transform:translateY(-3px);
    box-shadow:0 10px 26px rgba(0,0,0,.1);
    border-color:color-mix(in srgb, var(--h-color) 35%, var(--u-line));
}
.lg-housing-photo {
    position:relative; height:140px; overflow:hidden;
    background:color-mix(in srgb, var(--h-color) 10%, var(--u-bg));
}
.lg-housing-photo img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
.lg-housing-card:hover .lg-housing-photo img { transform:scale(1.06); }
.lg-housing-photo::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,0) 40%, rgba(0,0,0,.55));
    pointer-events:none;
}
.lg-housing-price {
    position:absolute; top:10px; right:10px;
    padding:4px 11px; border-radius:20px;
    background:rgba(255,255,255,.96); color:var(--h-color);
    font-size:11px; font-weight:800;
    box-shadow:0 2px 8px rgba(0,0,0,.15);
}
.lg-housing-body { padding:14px 16px 16px; flex:1; display:flex; flex-direction:column; }
.lg-housing-title { font-weight:700; font-size:14px; margin-bottom:4px; color:var(--u-text); }
.lg-housing-desc { font-size:12px; color:var(--u-muted); line-height:1.55; }

@media (max-width:900px){ .lg-housing-grid{grid-template-columns:repeat(2,1fr);} }
@media (max-width:560px){ .lg-housing-grid{grid-template-columns:1fr; gap:12px;} .lg-housing-photo{height:160px;} }

/* ══════ Arrival Steps ══════ */
.lg-arrival-card { background:var(--u-card); border:1px solid var(--u-line); border-radius:14px; padding:18px 20px; margin-bottom:24px; }
.lg-arrival-title { font-weight:700; font-size:14px; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.lg-arrival-title::before {
    content:''; display:inline-block; width:4px; height:16px;
    background:var(--u-brand,#2563eb); border-radius:2px;
}
.lg-arrival-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
.lg-arrival-item {
    display:flex; gap:11px; align-items:flex-start;
    padding:11px 12px; border-radius:10px;
    transition:background .12s;
}
.lg-arrival-item:hover { background:color-mix(in srgb, var(--u-brand,#2563eb) 4%, transparent); }
.lg-arrival-num {
    flex-shrink:0; width:26px; height:26px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:800;
    color:var(--u-brand,#2563eb);
    background:color-mix(in srgb, var(--u-brand,#2563eb) 12%, #fff);
    border:1.5px solid color-mix(in srgb, var(--u-brand,#2563eb) 28%, transparent);
    margin-top:2px;
}
.lg-arrival-name { font-weight:700; font-size:13px; color:var(--u-text); line-height:1.3; margin-bottom:2px; }
.lg-arrival-desc { font-size:11.5px; color:var(--u-muted); line-height:1.5; }

@media (max-width:720px){ .lg-arrival-grid{grid-template-columns:1fr;} }

/* ══════ Tips Grid ══════ */
.lg-tips-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
.lg-tip {
    background:var(--u-card); border:1px solid var(--u-line);
    border-radius:12px; padding:16px 16px 14px;
    position:relative; overflow:hidden;
    --t-color: var(--u-brand,#2563eb);
    transition:transform .15s, border-color .15s;
}
.lg-tip::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--t-color);
}
.lg-tip:hover { transform:translateY(-2px); border-color:color-mix(in srgb, var(--t-color) 35%, var(--u-line)); }
.lg-tip-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.lg-tip-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
    background:color-mix(in srgb, var(--t-color) 12%, #fff);
    border:1px solid color-mix(in srgb, var(--t-color) 22%, transparent);
}
.lg-tip-title { font-weight:700; font-size:13.5px; color:var(--u-text); line-height:1.2; }
.lg-tip-desc { font-size:12px; color:var(--u-muted); line-height:1.55; }

@media (max-width:900px){ .lg-tips-grid{grid-template-columns:repeat(2,1fr);} }
@media (max-width:560px){ .lg-tips-grid{grid-template-columns:1fr;} }

/* ══════ Section Title ══════ */
.lg-section-title { font-weight:700; font-size:var(--tx-base); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.lg-section-title::before {
    content:''; display:inline-block; width:4px; height:16px;
    background:var(--u-brand,#2563eb); border-radius:2px;
}
</style>
@endpush

@php
$lgIsStudent = request()->is('student/*');
$lgDashboardRoute = $lgIsStudent ? route('student.dashboard') : route('guest.dashboard');
$lgCostRoute = $lgIsStudent
    ? (\Illuminate\Support\Facades\Route::has('student.cost-calculator') ? route('student.cost-calculator') : null)
    : route('guest.cost-calculator');
$lgDiscover = function ($cat = null) use ($lgIsStudent) {
    $name = $lgIsStudent ? 'student.discover' : 'guest.discover';
    return $cat ? route($name, ['cat' => $cat]) : route($name);
};

$cityList = $cities ?? [];
$rate     = $eurTryRate ?? null;
$cityEmojiMap = [
    'berlin' => '🐻', 'munich' => '🏔', 'hamburg' => '⚓', 'frankfurt' => '🏦',
    'cologne' => '⛪', 'stuttgart' => '🚗', 'dusseldorf' => '🎭',
    'dresden' => '🏰', 'hannover' => '🌿', 'nurnberg' => '🏛',
];
@endphp

{{-- ══════ Hero ══════ --}}
<div class="lg-hero">
    <div class="lg-hero-body">
        <div class="lg-hero-main">
            <div class="lg-hero-label"><span class="lg-hero-marker"></span>Almanya'da Öğrenci Hayatı</div>
            <h1 class="lg-hero-title">Yaşam Rehberi</h1>
            <div class="lg-hero-overview">
                Konut, ulaşım, sigorta, banka hesabı — Almanya'ya gelmeden önce bilmen gereken her şey.
            </div>
            <div class="lg-hero-stats">
                <span class="lg-hero-stat"><span class="lg-hero-stat-ico">💶</span>Ort. €850–1.100/ay</span>
                <span class="lg-hero-stat"><span class="lg-hero-stat-ico">🏛</span>10 popüler şehir</span>
                <span class="lg-hero-stat"><span class="lg-hero-stat-ico">🎫</span>Semesterticket ücretsiz</span>
                @if($rate)
                <span class="lg-hero-stat"><span class="lg-hero-stat-ico">💱</span>1 EUR = {{ number_format($rate, 2) }} TRY</span>
                @endif
            </div>
            <div class="lg-hero-chips">
                <a class="lg-hero-chip" href="{{ $lgDiscover() }}">🧭 Tüm İçerikler</a>
                <a class="lg-hero-chip" href="{{ $lgDiscover('city-content') }}">🏙 Şehir Rehberleri</a>
                <a class="lg-hero-chip" href="{{ $lgDiscover('tips-tricks') }}">💡 İpuçları</a>
                <a class="lg-hero-chip" href="{{ $lgDiscover('careers') }}">💼 Kariyer</a>
                <a class="lg-hero-chip" href="{{ $lgDiscover('student-life') }}">🎓 Öğrenci Hayatı</a>
            </div>
        </div>
        <div class="lg-hero-icon">🏙</div>
    </div>
</div>

{{-- ══════ Cost Table ══════ --}}
<div class="lg-section-title">Şehir Bazında Aylık Maliyet</div>
<div class="lg-cost-card">
    <table class="lg-cost-table">
        <thead>
            <tr>
                <th>Şehir</th>
                <th class="right">Kira</th>
                <th class="right">Gıda</th>
                <th class="right">Ulaşım</th>
                <th class="right">Diğer</th>
                <th class="right total">Toplam/ay</th>
                @if($rate)<th class="right">TRY</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($cityList as $key => $c)
            @php
                $monthly     = ($c['rent_avg']??0) + ($c['food_avg']??0) + ($c['transport_avg']??0) + ($c['misc_avg']??0) + 110;
                $isExpensive = $monthly > 1100;
                $emoji       = $cityEmojiMap[$key] ?? '🏙';
            @endphp
            <tr>
                <td>
                    <div class="lg-city-cell">
                        <span class="lg-city-emoji">{{ $emoji }}</span>
                        {{ $c['label'] ?? $key }}
                        @if($isExpensive)<span class="badge warn" style="font-size:10px;">Pahalı</span>@endif
                    </div>
                </td>
                <td class="right">€ {{ number_format($c['rent_avg']??0, 0, ',', '.') }}</td>
                <td class="right">€ {{ number_format($c['food_avg']??0, 0, ',', '.') }}</td>
                <td class="right">€ {{ number_format($c['transport_avg']??0, 0, ',', '.') }}</td>
                <td class="right">€ {{ number_format(($c['misc_avg']??0) + 110, 0, ',', '.') }}</td>
                <td class="right lg-cost-total">€ {{ number_format($monthly, 0, ',', '.') }}</td>
                @if($rate)
                <td class="right lg-cost-try">₺ {{ number_format($monthly * $rate, 0, ',', '.') }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="lg-cost-foot">
        * Kira: WG/yurt ortalaması · Sağlık sigortası €110/ay dahil · Kaynak: Studentenwerk 2025–2026
    </div>
</div>

{{-- ══════ Housing Options ══════ --}}
<div class="lg-section-title">🏠 Konut Seçenekleri</div>
<div class="lg-housing-grid">
    @foreach([
        ['Studentenwohnheim', 'Yurt', 'En ucuz seçenek. Studentenwerk listelerine yazılın — bekleme listesi uzun olabilir.', '€150–400', '#16a34a',
         'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80'],
        ['WG (Paylaşımlı Ev)', 'WG', 'En popüler seçenek. WG-Gesucht.de ve Immobilienscout24 kullanın.', '€300–600', '#0891b2',
         'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=80'],
        ['Tek Kişilik Daire', 'Apartment', 'En pahalı seçenek. Refah düzeyi yüksek öğrenciler için uygun.', '€600–1200', '#f59e0b',
         'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&q=80'],
    ] as [$title, $short, $desc, $price, $color, $img])
    <div class="lg-housing-card" style="--h-color:{{ $color }};">
        <div class="lg-housing-photo">
            <img src="{{ $img }}" alt="{{ $title }}" loading="lazy">
            <span class="lg-housing-price">{{ $price }}/ay</span>
        </div>
        <div class="lg-housing-body">
            <div class="lg-housing-title">{{ $title }}</div>
            <div class="lg-housing-desc">{{ $desc }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ══════ Almanya'ya Gelince Yapılacaklar ══════ --}}
<div class="lg-arrival-card">
    <div class="lg-arrival-title">📋 Almanya'ya Gelince Yapılacaklar</div>
    <div class="lg-arrival-grid">
        @foreach([
            ['1','Anmeldung (İkamet Tescili)','Gelişten itibaren 2 hafta içinde zorunlu. Einwohnermeldeamt\'a gidin.'],
            ['2','Banka Hesabı','DKB, N26 veya Deutsche Bank — öğrenci hesabı ücretsiz.'],
            ['3','Sağlık Sigortası','TK, AOK, Barmer. €110-130/ay. Üniversite kaydı için zorunlu.'],
            ['4','Üniversite Kaydı','Kabul + sigorta belgesi + Anmeldung ile kayıt tamamlanır.'],
            ['5','Öğrenci Semesterticket','Üniversite katkı payıyla birlikte toplu taşıma hakkı.'],
            ['6','Sperrkonto Serbest Bırakma','Vizeyle geldikten sonra aylık ~€934 çekme hakkı başlar.'],
        ] as [$num,$title,$desc])
        <div class="lg-arrival-item">
            <span class="lg-arrival-num">{{ $num }}</span>
            <div>
                <div class="lg-arrival-name">{{ $title }}</div>
                <div class="lg-arrival-desc">{{ $desc }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ══════ Tasarruf İpuçları ══════ --}}
<div class="lg-section-title">💡 Tasarruf İpuçları</div>
<div class="lg-tips-grid">
    @foreach([
        ['🛒','#16a34a','Gıda','ALDI, LIDL, REWE öğrencilerin favorisi. Yemek pişirerek aylık €150–200\'e düşürebilirsin.'],
        ['🚲','#0891b2','Ulaşım','Semesterticket ile toplu taşıma ücretsiz veya indirimli. Bisiklet de yaygın ve ucuz.'],
        ['💊','#e11d48','Sağlık','Devlet sigortasıyla (TK/AOK) tüm temel sağlık hizmetleri kapsanır.'],
        ['📱','#7c3aed','Telefon','Aldi Talk, Congstar gibi ön ödemeli hatlar €10–15/ay. Öğrenci tarifeleri mevcut.'],
        ['☕','#f59e0b','Sosyal Hayat','Mensa yemekleri €2–4. Kütüphaneler ve müzeler öğrenciye ücretsiz/indirimli.'],
        ['💰','#2563eb','Çalışma','Öğrenci vizesiyle yılda 120 tam/240 yarım gün çalışabilirsin. Minijob yaygın.'],
    ] as [$icon,$color,$title,$desc])
    <div class="lg-tip" style="--t-color:{{ $color }};">
        <div class="lg-tip-head">
            <div class="lg-tip-icon">{{ $icon }}</div>
            <div class="lg-tip-title">{{ $title }}</div>
        </div>
        <div class="lg-tip-desc">{{ $desc }}</div>
    </div>
    @endforeach
</div>

{{-- ══════ Footer ══════ --}}
<div style="text-align:center;padding:8px 0 16px;">
    @if($lgCostRoute)
    <a href="{{ $lgCostRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Kişisel Maliyet Hesapla →</a>
    &nbsp;·&nbsp;
    @endif
    <a href="{{ $lgDashboardRoute }}" style="color:var(--u-brand,#2563eb);font-size:var(--tx-sm);font-weight:600;text-decoration:none;">← Dashboard</a>
</div>
