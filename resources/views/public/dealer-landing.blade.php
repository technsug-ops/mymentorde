<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Satış Ortaklığı Programı — {{ $brandName ?? 'MentorDE' }} · Birlikte Kazanalım</title>
<meta name="description" content="MentorDE Satış Ortaklığı Programı 2026. Almanya eğitim sürecine yönlendirdiğiniz her başarılı kayıt için €200-€750 komisyon kazanın. 100€ hoş geldin bonusu + vize reddi güvencesi.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="MentorDE Satış Ortaklığı Programı — Birlikte Kazanalım">
<meta property="og:description" content="Sıfır yatırım, Euro bazlı yüksek komisyon, operasyonel destek. Öğrenci başına €200-€750 kazanç.">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#5b2e91;
    --primary-dark:#4a2377;
    --primary-deep:#3d1c67;
    --primary-soft:#f1e8fb;
    --accent:#e8b931;
    --accent-dark:#c99c26;
    --success:#16a34a;
    --success-bg:#dcfce7;
    --text:#12233a;
    --muted:#5e7187;
    --line:#d9e2ee;
    --surface:#ffffff;
    --bg:#f9fafd;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; scroll-behavior:smooth; }
body {
    font-family:"Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
    color:var(--text);
    background:linear-gradient(140deg, #f7f3ff 0%, #f9fafd 42%, #fff8e8 100%);
    line-height:1.6;
    font-size:15px;
    -webkit-font-smoothing:antialiased;
}
.serif { font-family:"DM Serif Display", Georgia, serif; font-weight:normal; }
a { color:var(--primary); text-decoration:none; }
a:hover { text-decoration:underline; }

/* === NAV === */
.d-nav {
    position:sticky; top:0; z-index:50;
    background:rgba(255,255,255,.92); backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
}
.d-nav-inner { max-width:1180px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
.d-logo { font-family:"DM Serif Display", serif; font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; display:inline-flex; align-items:center; gap:2px; }
.d-logo span { color:var(--primary-dark); font-style:italic; }
.d-nav-links { display:flex; gap:24px; font-size:14px; font-weight:600; }
.d-nav-links a { color:var(--muted); }
.d-nav-links a:hover { color:var(--primary); text-decoration:none; }
.d-nav-cta {
    padding:10px 18px; background:var(--primary); color:#fff !important;
    border-radius:10px; font-size:13px; font-weight:700;
}
.d-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
@media(max-width:720px) { .d-nav-links { display:none; } }

/* === LAYOUT === */
.container { max-width:1180px; margin:0 auto; padding:0 22px; }

/* === HERO === */
.hero { position:relative; overflow:hidden; padding:80px 0 100px; }
.hero::before {
    content:''; position:absolute; inset:0; z-index:-1;
    background:
        radial-gradient(80% 60% at 70% 20%, rgba(91,46,145,.18), transparent 70%),
        radial-gradient(60% 50% at 20% 80%, rgba(232,185,49,.15), transparent 70%);
}
.hero-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:48px; align-items:center; }
@media(max-width:920px) { .hero-grid { grid-template-columns:1fr; gap:32px; } }
.hero-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:var(--primary-soft); color:var(--primary-dark);
    padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;
    text-transform:uppercase; letter-spacing:.08em; margin-bottom:20px;
}
.hero h1 {
    font-family:"DM Serif Display", Georgia, serif;
    font-size:clamp(36px, 5vw, 58px); line-height:1.08; letter-spacing:-1.5px;
    margin:0 0 20px; color:var(--primary-deep);
}
.hero h1 em { color:var(--accent-dark); font-style:italic; }
.hero-lead { font-size:18px; color:var(--muted); margin:0 0 32px; max-width:560px; }
.hero-ctas { display:flex; gap:14px; flex-wrap:wrap; }
.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    padding:15px 30px; background:var(--primary); color:#fff !important;
    border-radius:12px; font-size:15px; font-weight:700; border:none; cursor:pointer;
    box-shadow:0 4px 14px rgba(91,46,145,.32);
    transition:all .18s;
}
.btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); text-decoration:none !important; box-shadow:0 8px 24px rgba(91,46,145,.4); }
.btn-ghost {
    display:inline-flex; align-items:center; gap:8px;
    padding:15px 28px; border:2px solid var(--primary); color:var(--primary) !important;
    border-radius:12px; font-size:15px; font-weight:700; background:#fff;
    transition:all .18s;
}
.btn-ghost:hover { background:var(--primary-soft); text-decoration:none !important; }

.hero-visual {
    background:linear-gradient(140deg, var(--primary), var(--primary-deep));
    border-radius:24px; padding:36px; color:#fff;
    box-shadow:0 24px 48px rgba(61,28,103,.35);
    position:relative; overflow:hidden;
}
.hero-visual::after {
    content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px;
    background:radial-gradient(circle, rgba(232,185,49,.3), transparent 70%);
    border-radius:50%;
}
.hero-visual-title { font-size:13px; text-transform:uppercase; letter-spacing:.1em; opacity:.8; margin-bottom:12px; }
.hero-visual-amount { font-family:"DM Serif Display", serif; font-size:56px; line-height:1; margin:0 0 8px; color:var(--accent); }
.hero-visual-sub { font-size:14px; opacity:.9; margin-bottom:24px; }
.hero-visual-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; font-size:13px; }
.hero-visual-list li { display:flex; align-items:center; gap:10px; }
.hero-visual-list li::before { content:'✓'; color:var(--accent); font-weight:900; font-size:16px; }

/* === SECTIONS === */
section { padding:70px 0; }
.sec-label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.15em; font-size:12px; font-weight:800; margin-bottom:14px;
}
.sec-title {
    font-family:"DM Serif Display", serif;
    font-size:clamp(28px, 3.5vw, 40px); line-height:1.15; color:var(--primary-deep);
    letter-spacing:-1px; margin:0 0 16px; max-width:800px;
}
.sec-lead { font-size:17px; color:var(--muted); max-width:680px; margin:0 0 44px; }
.sec-bg-white { background:#fff; }
.sec-bg-soft  { background:linear-gradient(180deg, rgba(91,46,145,.04), transparent); }

/* === STEPS === */
.steps { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; position:relative; }
@media(max-width:900px) { .steps { grid-template-columns:1fr; } }
.step {
    background:#fff; border-radius:16px; padding:32px 24px;
    border:1px solid var(--line); position:relative;
    transition:all .2s;
}
.step:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 12px 32px rgba(91,46,145,.12); }
.step-num {
    position:absolute; top:-18px; left:24px;
    background:var(--primary); color:#fff; width:36px; height:36px;
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:14px;
}
.step-icon { font-size:32px; margin-bottom:10px; }
.step h3 { font-size:18px; margin:0 0 8px; color:var(--primary-deep); font-weight:700; }
.step p { font-size:14px; color:var(--muted); margin:0; }
.step strong { color:var(--accent-dark); }

/* === BENEFIT CARDS === */
.benefits { display:grid; grid-template-columns:repeat(2, 1fr); gap:18px; }
@media(max-width:720px) { .benefits { grid-template-columns:1fr; } }
.benefit {
    background:#fff; padding:28px; border-radius:16px;
    border-left:4px solid var(--primary); display:flex; gap:18px; align-items:flex-start;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}
.benefit-icon {
    font-size:32px; background:var(--primary-soft); width:56px; height:56px;
    border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.benefit h3 { margin:0 0 6px; font-size:17px; color:var(--primary-deep); }
.benefit p { margin:0; color:var(--muted); font-size:14px; line-height:1.55; }

/* === PLAN COMPARE === */
.plans { display:grid; grid-template-columns:1fr 1fr; gap:28px; }
@media(max-width:900px) { .plans { grid-template-columns:1fr; } }
.plan {
    background:#fff; border:2px solid var(--line); border-radius:20px;
    padding:36px 28px; position:relative;
    transition:all .2s;
}
.plan:hover { border-color:var(--primary); transform:translateY(-4px); box-shadow:0 20px 40px rgba(91,46,145,.12); }
.plan.featured { border-color:var(--primary); background:linear-gradient(180deg, #fff, var(--primary-soft)); }
.plan-badge {
    position:absolute; top:-14px; right:24px;
    background:var(--accent); color:#fff; padding:4px 12px; border-radius:20px;
    font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em;
}
.plan-title { font-family:"DM Serif Display", serif; font-size:26px; color:var(--primary-deep); margin:0 0 8px; }
.plan-sub { color:var(--muted); font-size:14px; font-style:italic; margin:0 0 20px; }
.plan-hook { font-size:15px; color:var(--text); font-weight:600; margin:0 0 20px; padding:14px 16px; background:var(--primary-soft); border-radius:10px; border-left:3px solid var(--primary); }
.plan h4 { font-size:13px; text-transform:uppercase; letter-spacing:.08em; color:var(--primary); margin:20px 0 10px; }
.plan ul { padding-left:20px; margin:0 0 16px; color:var(--muted); font-size:14px; }
.plan ul li { margin-bottom:6px; }

/* === TABLE === */
.ctable { width:100%; border-collapse:collapse; margin-top:14px; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04); }
.ctable th { background:var(--primary); color:#fff; padding:12px 14px; font-size:12px; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.04em; }
.ctable td { padding:12px 14px; border-bottom:1px solid var(--line); font-size:13px; }
.ctable td.amount { color:var(--primary); font-weight:800; font-size:15px; white-space:nowrap; }
.ctable tr:last-child td { border-bottom:0; }
.ctable tr:hover { background:var(--primary-soft); }

/* === PROGRAM CARDS === */
.programs { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
@media(max-width:900px) { .programs { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:540px) { .programs { grid-template-columns:1fr; } }
.program {
    background:#fff; border:1px solid var(--line); border-radius:16px;
    padding:28px 22px; text-align:center;
    transition:all .2s;
}
.program:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 12px 24px rgba(91,46,145,.10); }
.program-icon {
    font-size:36px; width:72px; height:72px; background:var(--primary);
    color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center;
    margin:0 auto 18px;
}
.program h3 { margin:0 0 8px; font-size:16px; color:var(--primary-deep); font-weight:700; }
.program p { font-size:13px; color:var(--muted); margin:0; line-height:1.5; }

/* === PANEL FEATURES === */
.features { display:grid; grid-template-columns:1fr 1fr; gap:28px; align-items:center; }
@media(max-width:900px) { .features { grid-template-columns:1fr; } }
.feature-list { list-style:none; padding:0; margin:0; }
.feature-list li { padding:18px 0; border-bottom:1px solid var(--line); display:flex; gap:14px; align-items:flex-start; }
.feature-list li:last-child { border-bottom:0; }
.feature-icon {
    background:var(--primary-soft); color:var(--primary); font-size:22px;
    width:44px; height:44px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.feature-list strong { display:block; color:var(--primary-deep); margin-bottom:4px; font-size:15px; }
.feature-list span { color:var(--muted); font-size:14px; }
.feature-visual {
    background:linear-gradient(140deg, var(--primary-soft), #fff);
    border:1px solid var(--line); border-radius:20px; padding:36px;
    text-align:center;
}

/* === GUARANTEES === */
.guarantees { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media(max-width:720px) { .guarantees { grid-template-columns:1fr; } }
.guarantee {
    background:#fff; border-radius:18px; padding:32px;
    border:1px solid var(--line); display:flex; gap:20px; align-items:flex-start;
}
.guarantee-icon {
    background:linear-gradient(140deg, var(--primary), var(--primary-deep));
    color:#fff; font-size:26px; width:64px; height:64px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.guarantee h3 { margin:0 0 8px; font-size:17px; color:var(--primary-deep); }
.guarantee p { margin:0; color:var(--muted); font-size:14px; line-height:1.6; }

/* === CTA === */
.cta-section {
    background:linear-gradient(140deg, var(--primary-deep), var(--primary));
    color:#fff; padding:90px 0; text-align:center; position:relative; overflow:hidden;
}
.cta-section::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(60% 50% at 30% 20%, rgba(232,185,49,.2), transparent 70%);
    z-index:0;
}
.cta-section .container { position:relative; z-index:1; }
.cta-section h2 { font-family:"DM Serif Display", serif; font-size:clamp(32px, 4vw, 48px); margin:0 0 16px; line-height:1.1; }
.cta-section p { font-size:18px; opacity:.9; margin:0 0 36px; max-width:640px; margin-left:auto; margin-right:auto; }
.cta-section .btn-primary { background:var(--accent); color:var(--primary-deep) !important; font-size:17px; padding:18px 36px; box-shadow:0 8px 24px rgba(232,185,49,.4); }
.cta-section .btn-primary:hover { background:var(--accent-dark); }
.cta-contacts { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; margin-top:48px; color:#fff; font-size:14px; }
.cta-contact { display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.1); padding:10px 18px; border-radius:10px; }
.cta-contact a { color:#fff !important; text-decoration:underline; text-decoration-color:rgba(255,255,255,.4); }

/* === FOOTER === */
footer { background:#1a0f2e; color:rgba(255,255,255,.7); padding:36px 0; font-size:13px; text-align:center; }
footer a { color:var(--accent); }
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav class="d-nav">
    <div class="d-nav-inner">
        <a href="/" class="d-logo">
            mentor<span>de</span>
        </a>
        <div class="d-nav-links">
            <a href="#nasil-calisir">Nasıl Çalışır</a>
            <a href="#kazanc-planlari">Kazanç Planları</a>
            <a href="#komisyon">Komisyon</a>
            <a href="#programlar">Programlar</a>
            <a href="#iletisim">İletişim</a>
        </div>
        <a href="https://panel.mentorde.com/register"
           class="d-nav-cta"
           data-track="cta_clicked"
           data-ph-cta-name="nav_register"
           data-ph-location="dealer_landing_nav">Hemen Başla →</a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-badge">🤝 Satış Ortaklığı Programı 2026</span>
            <h1>Satış Ortağımız Olun,<br><em>Birlikte Kazanalım</em></h1>
            <p class="hero-lead">
                Almanya eğitim hayalini olan her aday için €200–€750 arası komisyon kazanın.
                Sıfır yatırım, sıfır risk. Yönlendirmeyi siz yapın — vize, belge ve okul sürecini biz yönetelim.
            </p>
            <div class="hero-ctas">
                <a href="https://panel.mentorde.com/register"
                   class="btn-primary"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_register"
                   data-ph-location="dealer_landing_hero">
                    🚀 Hemen Hesap Oluştur — 100€ Bonus
                </a>
                <a href="#nasil-calisir"
                   class="btn-ghost"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_learn"
                   data-ph-location="dealer_landing_hero">
                    Nasıl Çalışır?
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-visual-title">💰 Öğrenci Başına</div>
            <div class="hero-visual-amount">€200—€750</div>
            <div class="hero-visual-sub">KDV hariç, kademenize göre artan komisyon</div>
            <ul class="hero-visual-list">
                <li>100€ Hoş Geldin Bonusu</li>
                <li>15 gün içinde hızlı ödeme</li>
                <li>Vize reddi güvencesi (teselli payı)</li>
                <li>Özel müşteri temsilcisi desteği</li>
                <li>Dealer Paneli ile şeffaf takip</li>
            </ul>
        </div>
    </div>
</section>

{{-- ═══ BİZ KİMİZ ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Kimiz</span>
        <h2 class="sec-title">MentorDE — Almanya Eğitim Danışmanlığında Uzman Platform</h2>
        <p class="sec-lead">
            Türk öğrencilerin Almanya eğitim yolculuğunda uzman rehberlik sağlıyoruz.
            Başvuru, vize, konaklama ve yerleşim süreçlerinin tamamını profesyonel ekibimizle
            sorunsuz yönetiyoruz — siz sadece adayınızı tanıtın, biz sürecin tamamını üstlenelim.
        </p>
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">🎓</div>
                <div><h3>Üniversite & Dil Okulu Başvuruları</h3>
                <p>Almanya devlet/özel üniversite + dil okulu + şartlı kabul başvuru süreçleri.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🛂</div>
                <div><h3>Profesyonel Vize Danışmanlığı</h3>
                <p>Randevu, dosya hazırlama, mülakat hazırlığı ve süreç takibinin tamamı.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">💳</div>
                <div><h3>Bloke Hesap & Sağlık Sigortası</h3>
                <p>Sperrkonto ve Krankenversicherung işlemlerinin resmi partnerler üzerinden kurulumu.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🏠</div>
                <div><h3>Konaklama & Yerleşim Desteği</h3>
                <p>Wohnung/Wohnheim araştırma, Anmeldung ve günlük yaşam rehberliği.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ 3 ADIM ═══ --}}
<section id="nasil-calisir" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Nasıl Çalışır</span>
        <h2 class="sec-title">Adım Adım Kazanma Yolculuğunuz</h2>
        <p class="sec-lead">Hemen başlayın, 100€ bonus kazanın — sonrası kendiliğinden gelişir.</p>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-icon">📝</div>
                <h3>Hesabınızı Oluşturun</h3>
                <p><a href="https://panel.mentorde.com/register" data-track="cta_clicked" data-ph-cta-name="step_register">panel.mentorde.com</a> adresinden ücretsiz kaydınızı tamamlayın. <strong>100€ Hoş Geldin Bonusu</strong> anında hesabınıza tanımlansın.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-icon">👥</div>
                <h3>İlk Adayınızı Ekleyin</h3>
                <p>Almanya hedefi olan potansiyel öğrencinizin iletişim bilgilerini panele girin. Hepsi bu kadar.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-icon">💸</div>
                <h3>Satış Gerçekleşsin, Kazanın</h3>
                <p>Adayın satışı ve ödemesi tamamlandığında hem <strong>komisyonunuzu</strong> hem de aktifleşen <strong>100€ bonusunuzu</strong> nakit olarak alın.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ NEDEN ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Avantajlar</span>
        <h2 class="sec-title">Neden Satış Ortağımız Olmalısınız?</h2>
        <p class="sec-lead">Geleneksel iş modellerinin hiçbir yükünü üstlenmeden, sadece tanıdıklarınızı yönlendirerek gelir elde edin.</p>
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">💼</div>
                <div><h3>Sıfır Risk, Sıfır Yatırım</h3>
                <p>Hiçbir sermaye koymadan, sadece çevrenizdeki potansiyeli değerlendirerek gelir elde edin.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">💶</div>
                <div><h3>Euro (€) ile Kazanç</h3>
                <p>Yönlendirdiğiniz ve başarılı kayıt olan her aday için döviz bazlı yüksek komisyon.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">⚙️</div>
                <div><h3>Operasyonel Rahatlık</h3>
                <p>Evrak, başvuru ve vize stresi yok — tüm zorlu süreci profesyonel destek ekibimiz yönetir.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🎧</div>
                <div><h3>Size Özel Kesintisiz Destek</h3>
                <p>Size özel atanan müşteri temsilciniz ile tüm sorularınıza anında yanıt, operasyon ortak yönetilir.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ KAZANÇ PLANLARI ═══ --}}
<section id="kazanc-planlari" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">2 Ayrı Model</span>
        <h2 class="sec-title">Kendi Kazanç Planınızı Seçin</h2>
        <p class="sec-lead">
            Zamanınıza, network'ünüze ve uzmanlığınıza en uygun yolu seçin.
            İster sadece yönlendirin, ister sürecin tam kalbinde yer alın.
        </p>
        <div class="plans">
            <div class="plan">
                <div class="plan-title">🤝 Lead Generation</div>
                <div class="plan-sub">Hızlı ve Kolay Kazanç</div>
                <div class="plan-hook">"Siz sadece tavsiye edin, satışı bize bırakın."</div>

                <h4>Kimler İçin?</h4>
                <ul>
                    <li>Ek gelir isteyenler</li>
                    <li>Sosyal medya influencer'ları</li>
                    <li>Geniş çevresi olan herkes</li>
                </ul>

                <h4>Nasıl Çalışır?</h4>
                <ul>
                    <li>Öğrenci iletişim bilgilerini panele girersiniz</li>
                    <li>MentorDE adayı arar, teknik bilgi verir ve satışı kapatır</li>
                    <li>Operasyonel hiçbir sürece karışmadan hak edişinizi alırsınız</li>
                </ul>

                <a href="#komisyon-lead" class="btn-ghost" style="margin-top:8px; padding:10px 18px; font-size:13px;">Komisyon Tablosunu Gör →</a>
            </div>

            <div class="plan featured">
                <span class="plan-badge">Yüksek Gelir</span>
                <div class="plan-title">🎯 Freelance Danışmanlık</div>
                <div class="plan-sub">Yüksek Gelir Odaklı</div>
                <div class="plan-hook">"Süreci siz başlatın, kazancınızı katlayın."</div>

                <h4>Kimler İçin?</h4>
                <ul>
                    <li>Eğitim sektöründe tecrübeli çözüm ortakları</li>
                    <li>Adaylarla ön görüşme yapabilenler</li>
                    <li>Süreci başlatıp ortak yönetmek isteyenler</li>
                </ul>

                <h4>Nasıl Çalışır?</h4>
                <ul>
                    <li>Adaya okul sunumları + maliyet analizini siz yaparsınız</li>
                    <li>Karar aşamasında MentorDE ile ortak toplantı düzenlersiniz</li>
                    <li>Resmi kayıt sonrası vize/okul başvuru süreçlerini biz devralırız</li>
                </ul>

                <a href="#komisyon-freelance" class="btn-ghost" style="margin-top:8px; padding:10px 18px; font-size:13px;">Komisyon Tablosunu Gör →</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ KOMİSYON TABLOLARI ═══ --}}
<section id="komisyon" class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Şeffaf Komisyon</span>
        <h2 class="sec-title">Üniversite Başvuruları için Komisyon</h2>
        <p class="sec-lead">
            Yıllık kayıt sayınız arttıkça kademeniz yükselir, komisyonunuz katlanır.
            Her program türü (dil okulu, vize, Ausbildung) için ayrı tarife uygulanır.
        </p>

        {{-- Lead Generation Table --}}
        <div id="komisyon-lead" style="margin-bottom:56px;">
            <h3 style="color:var(--primary-deep); font-size:22px; margin:0 0 16px; font-family:'DM Serif Display', serif;">
                🤝 Lead Generation — Komisyon Kademeleri
            </h3>
            <table class="ctable">
                <thead>
                    <tr>
                        <th>Seviye</th>
                        <th>Yıllık Kayıt</th>
                        <th>Komisyon / Öğrenci (KDV Hariç)</th>
                        <th>Avantajlar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🥉 <strong>Bronz</strong></td>
                        <td>1 — 10</td>
                        <td class="amount">€200</td>
                        <td>Standart komisyon + Dealer Paneli erişimi</td>
                    </tr>
                    <tr>
                        <td>🥈 <strong>Gümüş</strong></td>
                        <td>11 — 25</td>
                        <td class="amount">€250</td>
                        <td>Artırılmış komisyon + Öncelikli destek</td>
                    </tr>
                    <tr>
                        <td>🥇 <strong>Altın</strong></td>
                        <td>26 — 50</td>
                        <td class="amount">€300</td>
                        <td>Yüksek komisyon + Ortak pazarlama desteği</td>
                    </tr>
                    <tr>
                        <td>💎 <strong>Platin</strong></td>
                        <td>51 — 100</td>
                        <td class="amount">€320</td>
                        <td>Premium komisyon + Özel müşteri temsilcisi</td>
                    </tr>
                    <tr>
                        <td>👑 <strong>Elmas</strong></td>
                        <td>101+</td>
                        <td class="amount">€400</td>
                        <td>En yüksek komisyon + Stratejik ortaklık toplantıları</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Freelance Table --}}
        <div id="komisyon-freelance">
            <h3 style="color:var(--primary-deep); font-size:22px; margin:0 0 16px; font-family:'DM Serif Display', serif;">
                🎯 Freelance Danışmanlık — Komisyon Kademeleri
            </h3>
            <table class="ctable">
                <thead>
                    <tr>
                        <th>Seviye</th>
                        <th>Yıllık Kayıt</th>
                        <th>Komisyon / Öğrenci (KDV Hariç)</th>
                        <th>Avantajlar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🚀 <strong>Aktif</strong></td>
                        <td>1 — 15</td>
                        <td class="amount">€500</td>
                        <td>Başlangıç komisyonu + Dealer Paneli + Temel süreç eğitimi</td>
                    </tr>
                    <tr>
                        <td>⭐ <strong>Uzman</strong></td>
                        <td>16 — 30</td>
                        <td class="amount">€600</td>
                        <td>Artırılmış komisyon + Öncelikli operasyon/vize inceleme desteği</td>
                    </tr>
                    <tr>
                        <td>🏆 <strong>Elit</strong></td>
                        <td>31+</td>
                        <td class="amount">€750</td>
                        <td>Yüksek komisyon + Co-branding ortak pazarlama desteği</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="font-size:13px; color:var(--muted); margin-top:24px; padding:14px 18px; background:var(--primary-soft); border-radius:10px; border-left:3px solid var(--primary);">
            <strong>Not:</strong> Diğer yönlendirebileceğiniz programların (dil okulu, vize danışmanlığı, Ausbildung vb.) hak edişleri seçilen program türüne göre değişiklik göstermektedir. Detaylar için temsilcinize ulaşın.
        </p>
    </div>
</section>

{{-- ═══ PROGRAMLAR ═══ --}}
<section id="programlar" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Yönlendirebileceğiniz</span>
        <h2 class="sec-title">6 Ayrı Program — Sınırsız Kazanç Fırsatı</h2>
        <p class="sec-lead">Öğrenci profiline göre en uygun programa yönlendirin, her biri için ayrı komisyon kazanın.</p>
        <div class="programs">
            <div class="program">
                <div class="program-icon">🎓</div>
                <h3>Üniversite Başvuruları</h3>
                <p>Almanya devlet ve özel üniversitelerine lisans/yüksek lisans başvuruları.</p>
            </div>
            <div class="program">
                <div class="program-icon">🗣️</div>
                <h3>Dil Okulları</h3>
                <p>Almanya'da İngilizce ve Almanca dil eğitimleri (A1—C2).</p>
            </div>
            <div class="program">
                <div class="program-icon">🛂</div>
                <h3>Vize Danışmanlığı</h3>
                <p>Profesyonel vize başvuru süreçleri — randevu, dosya, mülakat.</p>
            </div>
            <div class="program">
                <div class="program-icon">☀️</div>
                <h3>Yaz Okulları</h3>
                <p>Gençler için Almanya yaz programları ve kültür deneyimi.</p>
            </div>
            <div class="program">
                <div class="program-icon">🛠️</div>
                <h3>Ausbildung</h3>
                <p>Mesleki eğitim ve staj programları — maaşlı öğrenim modeli.</p>
            </div>
            <div class="program">
                <div class="program-icon">📚</div>
                <h3>Studienkolleg</h3>
                <p>Üniversite hazırlık ve denklik eğitimleri.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PANEL ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Dealer Paneli</span>
        <h2 class="sec-title">Tüm Süreç ve Kazancınız Tek Ekranda</h2>
        <p class="sec-lead">
            Yönlendirdiğiniz her aday için anlık süreç takibi, şeffaf kazanç ekranı ve ücretsiz pazarlama materyalleri — MentorDE Dealer Paneli.
        </p>
        <div class="features">
            <ul class="feature-list">
                <li>
                    <div class="feature-icon">📊</div>
                    <div>
                        <strong>Anlık Süreç Takibi</strong>
                        <span>Yönlendirdiğiniz öğrencinin hangi aşamada (kabul bekliyor, vize onaylandı vb.) olduğunu canlı izleyin.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">💰</div>
                    <div>
                        <strong>Şeffaf Kazanç Ekranı</strong>
                        <span>Hak ettiğiniz, bekleyen ve ödenen komisyon tutarlarınız tek ekranda görünür.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">📦</div>
                    <div>
                        <strong>Ücretsiz Materyal Desteği</strong>
                        <span>Satışı kolaylaştıracak güncel katalog, fiyat listesi ve sosyal medya görselleri tek tıkla.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">✨</div>
                    <div>
                        <strong>Kullanıcı Dostu Arayüz</strong>
                        <span>Hiçbir teknik bilgi gerektirmeyen, anlaşılır menülerle saniyeler içinde işlem.</span>
                    </div>
                </li>
            </ul>
            <div class="feature-visual">
                <div style="font-size:56px; margin-bottom:18px;">🖥️📱</div>
                <h3 style="color:var(--primary-deep); margin:0 0 10px;">Web & Mobil Uyumlu</h3>
                <p style="color:var(--muted); font-size:14px; margin:0 0 20px;">Masaüstü, tablet, telefon — her cihazda eksiksiz çalışır. İstediğiniz yerden adaylarınızı takip edin.</p>
                <a href="https://panel.mentorde.com/register"
                   class="btn-primary"
                   style="font-size:14px; padding:12px 24px;"
                   data-track="cta_clicked"
                   data-ph-cta-name="panel_register"
                   data-ph-location="dealer_landing_panel">
                    Panel'i İnceleyin →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ GÜVENCELER ═══ --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Ödeme & Güvence</span>
        <h2 class="sec-title">Emeğinizin Karşılığı Garanti</h2>
        <p class="sec-lead">Süreç olumsuz sonuçlansa bile çabanız boşa gitmez. Size iki güvenli sözümüz var.</p>
        <div class="guarantees">
            <div class="guarantee">
                <div class="guarantee-icon">💳</div>
                <div>
                    <h3>Hızlı ve Esnek Ödeme Sistemi</h3>
                    <p>Öğrenci kayıt işlemini tamamladığında komisyonunuz kesinleşir, <strong>en geç 15 gün içinde</strong> hesabınıza yatar. Şirketiniz varsa fatura keserek, bireysel çalışıyorsanız basit yasal süreçlerle anında tahsilat.</p>
                </div>
            </div>
            <div class="guarantee">
                <div class="guarantee-icon">🛡️</div>
                <div>
                    <h3>Vize Reddi Güvencesi</h3>
                    <p>Süreç olumsuz sonuçlansa bile harcadığınız zaman değerlidir. Öğrenci vize reddi alsa dahi emekleriniz boşa gitmez — <strong>kademenize göre belirlenen teselli payı</strong> anında hesabınıza yatar.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CTA ═══ --}}
<section id="iletisim" class="cta-section">
    <div class="container">
        <h2>Hemen Hesabınızı Oluşturun<br>ve Kazanmaya Başlayın</h2>
        <p>Almanya eğitim fırsatlarını çevrenizle buluşturun, birlikte kazanalım.</p>
        <a href="https://panel.mentorde.com/register"
           class="btn-primary"
           data-track="cta_clicked"
           data-ph-cta-name="footer_register"
           data-ph-location="dealer_landing_cta">
            🎯 Ücretsiz Kayıt Ol — 100€ Bonus
        </a>

        <div class="cta-contacts">
            <div class="cta-contact">
                🌐 <a href="https://panel.mentorde.com" target="_blank" rel="noopener"
                      data-track="cta_clicked" data-ph-cta-name="contact_panel" data-ph-location="dealer_landing_contact">panel.mentorde.com</a>
            </div>
            <div class="cta-contact">
                ✉️ <a href="mailto:info@mentorde.com"
                      data-track="cta_clicked" data-ph-cta-name="contact_email" data-ph-location="dealer_landing_contact">info@mentorde.com</a>
            </div>
            <div class="cta-contact">
                💬 <a href="https://wa.me/4915203253691?text=Merhaba%2C%20Sat%C4%B1%C5%9F%20Orta%C4%9Fl%C4%B1%C4%9F%C4%B1%20Program%C4%B1%20hakk%C4%B1nda%20bilgi%20almak%20istiyorum."
                      target="_blank" rel="noopener"
                      data-track="cta_clicked" data-ph-cta-name="contact_whatsapp" data-ph-location="dealer_landing_contact">WhatsApp: +49 1520 325 3691</a>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        © {{ date('Y') }} {{ $brandName ?? 'MentorDE' }} · Almanya eğitim danışmanlığında uzman platform ·
        <a href="/legal/terms">Kullanım Koşulları</a> ·
        <a href="/legal/privacy">Gizlilik</a>
    </div>
</footer>

{{-- Analytics: PostHog snippet (consent varsa) + Consent banner --}}
<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>
