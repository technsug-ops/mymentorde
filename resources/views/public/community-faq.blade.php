<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topluluk Soru Arşivi — Almanya Eğitim & Vize | {{ config('brand.name', 'MentorDE') }}</title>
@include('partials.favicon')
    <meta name="description" content="Almanya'da eğitim, vize, dil sertifikası, denklik, Sperrkonto ve daha fazlası — toplulukta en çok sorulan {{ $totalQuestions }} soru, 15 ana konu altında derlendi.">
    <meta property="og:title" content="Topluluk Soru Arşivi — {{ config('brand.name', 'MentorDE') }}">
    <meta property="og:description" content="{{ $totalQuestions }} anonim soru — Almanya eğitim sürecinde toplulukta en çok merak edilenler.">
    <link rel="canonical" href="{{ url('/sss/topluluk') }}">

    @php
        $primary = '#7e58bf';
        $primaryDark = '#5b3a8f';
        $accent = '#e8b931';
        $bgSoft = '#faf7ff';
        $textMain = '#1a0f2e';
        $textMuted = '#5b4a7a';
    @endphp

    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: {{ $textMain }}; line-height: 1.6; background: {{ $bgSoft }}; }
        a { color: {{ $primary }}; text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Topbar */
        .topbar { background: #fff; border-bottom: 1px solid rgba(126,88,191,0.12); padding: 16px 24px; position: sticky; top: 0; z-index: 10; }
        .topbar-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 22px; font-weight: 700; color: {{ $textMain }}; letter-spacing: -0.02em; }
        .logo span { color: {{ $primary }}; }
        .topbar-nav { display: flex; gap: 24px; font-size: 14px; font-weight: 500; }
        .topbar-nav a { color: {{ $textMuted }}; }
        .topbar-nav a:hover { color: {{ $primary }}; }

        /* Hero */
        .hero { background: linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%); color: #fff; padding: 70px 24px 90px; text-align: center; }
        .hero h1 { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: clamp(28px, 5vw, 44px); margin-bottom: 14px; line-height: 1.15; letter-spacing: -0.025em; }
        .hero .lead { font-size: 17px; opacity: 0.92; max-width: 740px; margin: 0 auto 28px; }
        .breadcrumb { font-size: 13px; opacity: 0.75; margin-bottom: 18px; }
        .breadcrumb a { color: #fff; opacity: 0.9; }
        .breadcrumb a:hover { opacity: 1; }
        .hero-stats { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; font-size: 13px; }
        .hero-stat { background: rgba(255,255,255,0.18); padding: 9px 18px; border-radius: 22px; backdrop-filter: blur(4px); }

        /* Disclaimer */
        .disclaimer { max-width: 920px; margin: -50px auto 30px; padding: 0 24px; }
        .disclaimer-box { background: #fff; border-radius: 14px; padding: 18px 22px; box-shadow: 0 8px 30px rgba(126,88,191,0.10); border-left: 4px solid {{ $accent }}; font-size: 13px; color: {{ $textMuted }}; line-height: 1.65; }
        .disclaimer-box strong { color: {{ $textMain }}; }

        /* Wrap */
        .wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px 80px; }

        /* Search */
        .search-wrap { max-width: 720px; margin: 0 auto 36px; }
        .search-box { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid rgba(126,88,191,0.20); padding: 14px 20px; border-radius: 14px; box-shadow: 0 4px 14px rgba(126,88,191,0.08); }
        .search-box input { flex: 1; border: none; outline: none; font-size: 15px; color: {{ $textMain }}; font-family: inherit; background: transparent; }
        .search-box input::placeholder { color: {{ $textMuted }}; }
        .search-icon { color: {{ $primary }}; font-size: 18px; }

        /* Topic grid */
        .topic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 22px; }
        .topic-card { background: #fff; border-radius: 16px; padding: 26px 24px; box-shadow: 0 2px 14px rgba(15,23,42,0.05); transition: transform 0.18s, box-shadow 0.18s; border: 1px solid rgba(126,88,191,0.08); display: flex; flex-direction: column; }
        .topic-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(126,88,191,0.18); border-color: rgba(126,88,191,0.25); text-decoration: none; }
        .topic-icon { width: 44px; height: 44px; background: linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; color: #fff; font-size: 20px; font-weight: 700; }
        .topic-title { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 18px; font-weight: 700; color: {{ $textMain }}; margin-bottom: 8px; line-height: 1.3; }
        .topic-desc { font-size: 13px; color: {{ $textMuted }}; margin-bottom: 16px; line-height: 1.55; flex-grow: 1; }
        .topic-meta { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid rgba(126,88,191,0.08); }
        .topic-count { font-size: 12px; color: {{ $primary }}; font-weight: 700; }
        .topic-arrow { color: {{ $primary }}; font-size: 18px; transition: transform 0.18s; }
        .topic-card:hover .topic-arrow { transform: translateX(4px); }

        /* CTA section */
        .cta-section { background: #fff; border: 2px solid {{ $primary }}; border-radius: 18px; padding: 36px; text-align: center; margin-top: 48px; }
        .cta-section h3 { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 24px; color: {{ $textMain }}; margin-bottom: 8px; letter-spacing: -0.02em; }
        .cta-section p { color: {{ $textMuted }}; margin-bottom: 22px; max-width: 540px; margin-left: auto; margin-right: auto; }
        .cta-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .cta-btn { display: inline-block; padding: 12px 26px; border-radius: 10px; font-weight: 700; font-size: 14px; transition: opacity 0.18s; }
        .cta-btn:hover { opacity: 0.92; text-decoration: none; }
        .cta-btn.primary { background: {{ $primary }}; color: #fff; }
        .cta-btn.secondary { background: transparent; color: {{ $primary }}; border: 2px solid {{ $primary }}; }

        /* Footer */
        .footer { background: #1a0f2e; color: #b9a5d9; padding: 30px 24px; text-align: center; font-size: 13px; }
        .footer a { color: #d6c5f0; }

        @media (max-width: 640px) {
            .hero { padding: 50px 20px 70px; }
            .topic-grid { grid-template-columns: 1fr; }
            .cta-section { padding: 28px 20px; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="/" class="logo">{{ str_replace('MentorDE','Mentor', config('brand.name','MentorDE')) }}<span>DE</span></a>
        <nav class="topbar-nav">
            <a href="/">Anasayfa</a>
            <a href="{{ route('public.faq') }}">SSS</a>
            <a href="/randevu">Randevu</a>
            <a href="/login">Giriş</a>
        </nav>
    </div>
</div>

<section class="hero">
    <div class="breadcrumb">
        <a href="/">Anasayfa</a> &nbsp;›&nbsp; <a href="{{ route('public.faq') }}">SSS</a> &nbsp;›&nbsp; Topluluk Arşivi
    </div>
    <h1>Topluluk Soru Arşivi</h1>
    <p class="lead">Almanya eğitim ve vize sürecinde topluluk forumlarında en çok sorulan {{ $totalQuestions }} soru — {{ $topicCount }} ana konu başlığı altında derlendi.</p>
    <div class="hero-stats">
        <span class="hero-stat">📚 {{ $totalQuestions }} soru</span>
        <span class="hero-stat">🗂️ {{ $topicCount }} kategori</span>
        <span class="hero-stat">🛡️ Anonim & PII temizliği</span>
    </div>
</section>

<div class="disclaimer">
    <div class="disclaimer-box">
        <strong>Bu sayfa nedir?</strong> Türkiye-Almanya eğitim/vize/akademik kariyer toplulukluklarında <strong>sıkça karşılaşılan sorular</strong>, kişisel bilgi (isim, telefon, e-posta, mention) tamamen temizlenerek anonim olarak derlendi. Yanıtlar burada yer almıyor — bu bir <strong>soru havuzudur</strong>. Detaylı, uzman onaylı yanıtlar için <a href="{{ route('public.faq') }}">resmi SSS</a> sayfamızı veya <a href="/randevu">ücretsiz danışmanlık randevusu</a>nu kullanabilirsiniz.
    </div>
</div>

<div class="wrap">
    <div class="search-wrap">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="topic-search" placeholder="Konu ara... (ör. vize, denklik, dil)">
        </div>
    </div>

    <div class="topic-grid" id="topic-grid">
        @foreach ($topics as $topicKey => $t)
            @php
                $count = count($t['questions'] ?? []);
                $title = $t['title'] ?? $topicKey;
                $desc  = $t['description'] ?? '';
                $initial = mb_strtoupper(mb_substr($title, 0, 1));
            @endphp
            <a href="{{ route('public.community-faq.topic', ['topic' => $topicKey]) }}"
               class="topic-card"
               data-search="{{ mb_strtolower($title . ' ' . $desc . ' ' . $topicKey) }}">
                <div class="topic-icon">{{ $initial }}</div>
                <h2 class="topic-title">{{ $title }}</h2>
                <p class="topic-desc">{{ $desc }}</p>
                <div class="topic-meta">
                    <span class="topic-count">{{ $count }} soru →</span>
                    <span class="topic-arrow">→</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="cta-section">
        <h3>Soruna özel bir cevap mı arıyorsun?</h3>
        <p>Bu arşivde göremediğin sorular için danışmanlarımız ücretsiz randevu açıyor. AI asistanımıza da anlık soru sorabilirsin.</p>
        <div class="cta-buttons">
            <a href="/randevu" class="cta-btn primary">📅 Ücretsiz Randevu Al</a>
            <a href="{{ route('public.faq') }}" class="cta-btn secondary">📖 Resmi SSS</a>
        </div>
    </div>
</div>

<footer class="footer">
    © {{ date('Y') }} {{ config('brand.name', 'MentorDE') }} —
    <a href="/">Anasayfa</a> ·
    <a href="{{ route('public.faq') }}">SSS</a> ·
    <a href="/iletisim">İletişim</a>
</footer>

<script>
(function () {
    var input = document.getElementById('topic-search');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('.topic-card').forEach(function (card) {
            var s = card.getAttribute('data-search') || '';
            card.style.display = (q === '' || s.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>

</body>
</html>
