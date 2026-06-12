<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $brand = config('brand.name', 'MentorDE');
    $tiers = config('subscription_tiers');
    // Modül meta — pricing'de kullanıcı dostu label'lar
    $moduleMeta = \App\Support\ModuleAccess::MODULE_META;

    // Tier sırası (display order) — featured Gold ortada
    $tierOrder = ['trial', 'basic', 'gold', 'premium'];
@endphp
<title>Fiyatlandırma — {{ $brand }} SaaS Platformu</title>
@include('partials.favicon')
<meta name="description" content="MentorDE eğitim danışmanlığı SaaS platformu fiyatlandırması. 14 gün ücretsiz dene. €49'dan başlayan aylık planlar — Trial, Basic, Gold ve Premium.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $brand }} Fiyatlandırma — €49/ay'dan başlayan SaaS planları">
<meta property="og:description" content="6 portal · 28+ modül · AI asistan · 14 gün ücretsiz deneme. Yurt dışı eğitim danışmanlığı firmaları için profesyonel SaaS.">
<meta property="og:type" content="website">

<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#7e58bf;
    --primary-dark:#6c47a8;
    --primary-deep:#5a3a8d;
    --primary-mid:#a07ed9;
    --primary-light:#b79ae9;
    --primary-soft:#efe9fb;
    --neutral:#e9e7e2;
    --neutral-soft:#faf9f5;
    --success:#16a34a;
    --warn:#f59e0b;
    --danger:#dc2626;
    --text:#1a1325;
    --muted:#6b6377;
    --line:#e3dcec;
    --surface:#ffffff;
    --bg:#faf9f5;
    --gradient-purple:linear-gradient(140deg, #7e58bf 0%, #5a3a8d 100%);
    --gradient-soft:linear-gradient(180deg, #efe9fb 0%, #faf9f5 100%);
    --font-base:"Space Grotesk", "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; scroll-behavior:smooth; }
body {
    font-family:var(--font-base); color:var(--text);
    background:linear-gradient(180deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%);
    line-height:1.6; font-size:15px;
    -webkit-font-smoothing:antialiased;
}
a { color:var(--primary); text-decoration:none; }

/* === NAV === */
.p-nav { position:sticky; top:0; z-index:50; background:rgba(255,255,255,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--line); }
.p-nav-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
.p-logo { font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; font-weight:700; }
.p-logo span { color:var(--primary-mid); font-style:italic; font-weight:600; }
.p-nav-links { display:flex; gap:28px; font-size:14px; font-weight:600; }
.p-nav-links a { color:var(--muted); }
.p-nav-links a:hover { color:var(--primary); }
.p-nav-cta { padding:10px 20px; background:var(--primary); color:#fff !important; border-radius:10px; font-size:13px; font-weight:700; }
.p-nav-cta:hover { background:var(--primary-dark); }
@media(max-width:820px) { .p-nav-links { display:none; } }

/* === HERO === */
.hero { padding:80px 22px 50px; text-align:center; max-width:900px; margin:0 auto; }
.hero .label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.18em; font-size:12px; font-weight:800; margin-bottom:18px;
    background:var(--primary-soft); padding:6px 14px; border-radius:20px;
}
.hero h1 {
    font-family:var(--font-base); font-style:italic; font-weight:600;
    font-size:clamp(36px, 5vw, 56px); line-height:1.1; color:var(--primary-deep);
    letter-spacing:-1.5px; margin:0 0 18px;
}
.hero p { font-size:18px; color:var(--muted); max-width:680px; margin:0 auto; line-height:1.55; }
.hero .trial-pill {
    display:inline-flex; align-items:center; gap:8px;
    margin-top:24px; padding:10px 20px; background:var(--surface);
    border:2px solid var(--primary); color:var(--primary); border-radius:999px;
    font-weight:700; font-size:14px;
}

/* === TIER GRID === */
.tier-section { padding:30px 22px 90px; }
.tier-grid {
    max-width:1200px; margin:0 auto;
    display:grid; grid-template-columns:repeat(4, 1fr); gap:18px;
}
@media(max-width:980px) { .tier-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:580px) { .tier-grid { grid-template-columns:1fr; } }

.tier-card {
    background:var(--surface); border:1px solid var(--line); border-radius:18px;
    padding:30px 24px 26px; display:flex; flex-direction:column;
    transition:all .25s; position:relative;
}
.tier-card:hover { transform:translateY(-4px); box-shadow:0 14px 40px rgba(126,88,191,.12); }
.tier-card.featured {
    border:2px solid var(--primary);
    box-shadow:0 20px 60px rgba(126,88,191,.18);
    background:linear-gradient(180deg, #fff 0%, var(--primary-soft) 100%);
}
.tier-card.featured::before {
    content:"⭐ EN POPÜLER";
    position:absolute; top:-13px; left:50%; transform:translateX(-50%);
    background:var(--gradient-purple); color:#fff;
    padding:6px 14px; border-radius:999px; font-size:11px; font-weight:800;
    letter-spacing:.08em; white-space:nowrap;
}

.tier-name {
    font-size:18px; font-weight:700; color:var(--primary-deep);
    margin-bottom:6px; letter-spacing:-.3px;
}
.tier-tagline {
    font-size:12.5px; color:var(--muted); margin-bottom:18px; min-height:36px;
}
.tier-price {
    display:flex; align-items:baseline; gap:6px; margin-bottom:4px;
}
.tier-price .amount { font-size:42px; font-weight:700; color:var(--text); letter-spacing:-1px; }
.tier-price .period { font-size:13px; color:var(--muted); }
.tier-price.zero .amount { color:var(--success); }
.tier-billing { font-size:11.5px; color:var(--muted); margin-bottom:22px; }

.tier-cta {
    display:block; width:100%; padding:13px 16px; text-align:center;
    background:var(--primary); color:#fff !important; border-radius:11px;
    font-size:14px; font-weight:700; border:none; cursor:pointer; transition:all .15s;
    text-decoration:none; margin-bottom:22px;
}
.tier-cta:hover { background:var(--primary-dark); transform:translateY(-1px); }
.tier-card.featured .tier-cta {
    background:var(--gradient-purple);
    box-shadow:0 8px 22px rgba(126,88,191,.32);
}
.tier-card.tier-trial .tier-cta { background:var(--success); }
.tier-card.tier-trial .tier-cta:hover { background:#13863d; }

.tier-features {
    list-style:none; padding:0; margin:0;
    display:flex; flex-direction:column; gap:10px;
    flex:1; font-size:13px; color:var(--text);
}
.tier-features li {
    display:flex; align-items:flex-start; gap:8px; line-height:1.4;
}
.tier-features li::before {
    content:"✓"; color:var(--primary); font-weight:800; flex:0 0 14px;
    font-size:14px;
}
.tier-features li.muted { color:var(--muted); }
.tier-features li.muted::before { color:var(--muted); content:"·"; }
.tier-features .limit {
    font-weight:600; padding:8px 10px; background:var(--neutral-soft);
    border:1px solid var(--line); border-radius:8px; font-size:12px;
    margin-top:6px;
}

/* === FAQ === */
.faq-section {
    background:var(--surface); padding:70px 22px; border-top:1px solid var(--line);
}
.faq-container { max-width:780px; margin:0 auto; }
.faq-title {
    text-align:center; font-style:italic; font-weight:600;
    font-size:clamp(28px, 4vw, 40px); color:var(--primary-deep);
    letter-spacing:-1px; margin:0 0 36px;
}
.faq-item {
    border-bottom:1px solid var(--line); padding:18px 0;
}
.faq-q {
    font-weight:700; font-size:16px; color:var(--text);
    cursor:pointer; display:flex; justify-content:space-between; align-items:center;
    list-style:none;
}
.faq-q::after {
    content:"+"; color:var(--primary); font-size:24px; font-weight:300;
    transition:transform .2s;
}
.faq-item[open] .faq-q::after { content:"−"; }
.faq-a {
    color:var(--muted); font-size:14.5px; line-height:1.6; margin-top:10px;
    padding-right:32px;
}

/* === FINAL CTA === */
.final-cta {
    background:var(--gradient-purple); color:#fff;
    padding:80px 22px; text-align:center;
}
.final-cta h2 {
    font-style:italic; font-weight:600;
    font-size:clamp(28px, 4vw, 42px); margin:0 0 14px; letter-spacing:-1px;
}
.final-cta p { font-size:16px; opacity:.92; max-width:600px; margin:0 auto 28px; }
.final-cta .ctabtn {
    display:inline-block; padding:16px 36px; background:#fff;
    color:var(--primary-deep) !important; border-radius:12px;
    font-size:15px; font-weight:800; letter-spacing:-.2px;
    transition:transform .15s, box-shadow .15s;
}
.final-cta .ctabtn:hover { transform:translateY(-2px); box-shadow:0 16px 40px rgba(0,0,0,.2); }

/* === FOOT === */
.p-foot { background:var(--text); color:#cdc5d8; padding:30px 22px; text-align:center; font-size:13px; }
.p-foot a { color:var(--primary-light); }
</style>
</head>
<body>

{{-- NAV --}}
<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">{{ $brand }}<span>.</span></a>
        <div class="p-nav-links">
            <a href="/platform">Platform</a>
            <a href="/fiyatlar">Fiyatlar</a>
            <a href="/satis-ortagi">Satış Ortakları</a>
            <a href="/sss">SSS</a>
        </div>
        <a href="/kayit" class="p-nav-cta">14 Gün Ücretsiz Başla</a>
    </div>
</nav>

{{-- HERO --}}
<section class="hero">
    <span class="label">FİYATLANDIRMA</span>
    <h1>İhtiyacına uyan paketi seç</h1>
    <p>Tüm planlarda 14 gün ücretsiz deneme. Kredi kartı gerekmez. İstediğin zaman iptal edebilirsin. Almanya eğitim danışmanlığı firmaları için tasarlandı.</p>
    <div class="trial-pill">
        🎁 14 gün boyunca ücretsiz tüm Gold özelliklerini dene
    </div>
</section>

{{-- TIER GRID --}}
<section class="tier-section">
    <div class="tier-grid">
        @foreach($tierOrder as $tierKey)
            @php
                $tier = $tiers[$tierKey];
                $isFeatured = $tierKey === 'gold';
                $isTrial = $tierKey === 'trial';
                $isPremium = $tierKey === 'premium';
                $modules = $tier['modules'] === '*' ? array_keys($moduleMeta) : $tier['modules'];
                $limits = $tier['limits'] ?? [];
                $studentsMax = $limits['students_max'] ?? null;
                $docMonthly = $limits['doc_request_monthly'] ?? null;

                // Tagline per tier
                $taglines = [
                    'trial'   => 'Tüm Gold özellikleri 14 gün ücretsiz',
                    'basic'   => 'Küçük danışmanlıklar için temel set',
                    'gold'    => 'Profesyonel danışmanlıklar için tam paket',
                    'premium' => 'Sınırsız ölçek + Bayi modülü',
                ];

                // CTA target
                $ctaLabel = $isTrial ? '14 Gün Ücretsiz Başla' : 'Bu Planı Seç';
                $ctaUrl = '/kayit?tier=' . $tierKey;
            @endphp
            <div class="tier-card {{ $isFeatured ? 'featured' : '' }} {{ $isTrial ? 'tier-trial' : '' }}">
                <div class="tier-name">{{ $tier['label'] }}</div>
                <div class="tier-tagline">{{ $taglines[$tierKey] ?? '' }}</div>

                <div class="tier-price {{ $tier['mrr_eur'] == 0 ? 'zero' : '' }}">
                    @if($tier['mrr_eur'] == 0)
                        <span class="amount">Ücretsiz</span>
                    @else
                        <span class="amount">€{{ number_format($tier['mrr_eur'], 0, ',', '.') }}</span>
                        <span class="period">/ ay</span>
                    @endif
                </div>
                <div class="tier-billing">
                    @if($isTrial)
                        14 gün boyunca, sonra plan seçimi
                    @elseif($isPremium)
                        Yıllık ödeme ile %15 indirim
                    @else
                        Faturalama: aylık · KDV hariç
                    @endif
                </div>

                <a href="{{ $ctaUrl }}" class="tier-cta">{{ $ctaLabel }}</a>

                <ul class="tier-features">
                    {{-- Limits --}}
                    @if($studentsMax)
                        <li class="limit">📚 {{ number_format($studentsMax) }} aktif öğrenciye kadar</li>
                    @else
                        <li class="limit">📚 Sınırsız öğrenci</li>
                    @endif

                    @if($docMonthly)
                        <li class="limit">📲 Aylık {{ number_format($docMonthly) }} belge talep linki</li>
                    @else
                        <li class="limit">📲 Sınırsız belge talep linki</li>
                    @endif

                    {{-- Modules — kısa liste --}}
                    @if($isTrial)
                        <li>Tüm Gold özelliklerini test et</li>
                        <li>Sınırsız kullanıcı + tam destek</li>
                        <li class="muted">14 gün sonra plan seçimi gerekli</li>
                    @else
                        @php
                            // Trial olmayan tier'larda modülleri label'a çevir, max 8 göster
                            $shown = 0;
                            $maxShow = 8;
                        @endphp
                        @foreach($modules as $mod)
                            @if($shown >= $maxShow) @break @endif
                            @php $meta = $moduleMeta[$mod] ?? null; @endphp
                            @if($meta && empty($meta['locked']))
                                <li>{{ $meta['label'] }}</li>
                                @php $shown++; @endphp
                            @endif
                        @endforeach
                        @if(count($modules) - 1 > $maxShow)
                            <li class="muted">+ {{ count($modules) - 1 - $maxShow }} özellik daha</li>
                        @endif
                    @endif
                </ul>
            </div>
        @endforeach
    </div>
</section>

{{-- FAQ --}}
<section class="faq-section">
    <div class="faq-container">
        <h2 class="faq-title">Sıkça Sorulanlar</h2>

        <details class="faq-item">
            <summary class="faq-q">14 günlük deneme süresinde tüm özellikler açık mı?</summary>
            <div class="faq-a">Evet — Trial planında Gold paketin tüm özellikleri açıktır: Booking, DAM, Content Hub, çoklu AI provider, AI Labs, Doküman AI ve daha fazlası. Kredi kartı eklemeden başlayabilirsin.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">14 günün sonunda ne oluyor?</summary>
            <div class="faq-a">Hesabın yalnızca trial planı sürdüğü süre boyunca premium modüllere erişebilir. 14 günün sonunda hesabını kalıcı olarak açık tutmak için Basic, Gold veya Premium planlarından birini seçmen gerek. Veri kaybı olmaz; ödemeyi yapınca kaldığın yerden devam edersin.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">Plan değişikliği yapabilir miyim?</summary>
            <div class="faq-a">Evet — Basic'ten Gold'a veya Gold'tan Premium'a istediğin zaman yükseltebilirsin. Yükseltme anında etkili olur, faturalama oransal hesaplanır. Düşürme sonraki fatura döneminde uygulanır.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">Ödeme nasıl yapılıyor?</summary>
            <div class="faq-a">Stripe üzerinden kredi kartı veya SEPA Direct Debit ile aylık veya yıllık otomatik ödeme. Yıllık planlarda %15 indirim var. KDV faturanız sistemde otomatik üretilir.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">Verilerim güvende mi? KVKK + GDPR uyumlu mu?</summary>
            <div class="faq-a">Evet — sunucular Almanya'da (Frankfurt), tam KVKK + GDPR uyumlu. Veriler AES-256 ile şifreli, otomatik günlük yedek alınır. AVV (Auftragsverarbeitung) sözleşmesi standart olarak imzalanır.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">Birden fazla danışman ekleyebilir miyim?</summary>
            <div class="faq-a">Tabii — tüm planlarda sınırsız danışman hesabı oluşturabilirsin. Kişi başına ücret yok; sadece aktif öğrenci sayısı plan limitine girer.</div>
        </details>

        <details class="faq-item">
            <summary class="faq-q">İptal etmek isterseniz ne olur?</summary>
            <div class="faq-a">Herhangi bir an iptal edebilirsin — sözleşme bağlayıcılığı yok. İptal sonrası mevcut faturalama dönemi sonuna kadar sistem açık kalır. İstediğin zaman verilerini Excel/CSV ile dışa aktarabilirsin.</div>
        </details>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="final-cta">
    <h2>Hemen başla, hiçbir şey ödemeden</h2>
    <p>14 gün boyunca tüm Gold özelliklerini ücretsiz dene. Beğenmezsen yapacağın tek şey hesabını silmek — başka yükümlülük yok.</p>
    <a href="/kayit" class="ctabtn">🚀 14 Gün Ücretsiz Başla</a>
</section>

{{-- FOOT --}}
<footer class="p-foot">
    <p>© {{ date('Y') }} {{ $brand }} · <a href="/platform">Platform</a> · <a href="/sss">SSS</a> · <a href="/legal/privacy">Gizlilik</a> · <a href="/legal/imprint">Künye</a></p>
</footer>

</body>
</html>
