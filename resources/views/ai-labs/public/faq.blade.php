<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sık Sorulan Sorular — {{ $brandName ?? 'MentorDE' }}</title>
    <meta name="description" content="Yurt dışı eğitim, Almanya üniversite başvurusu, vize, Sperrkonto ve daha fazlası hakkında sık sorulan sorular ve cevapları.">

    @php
        $theme = $publicTheme ?? null;
        $primary = $theme['primary'] ?? '#5b2e91';
        $primaryDark = $theme['primary_dark'] ?? '#4a2578';
        $accent = $theme['accent'] ?? '#e8b931';
        $bgSoft = $theme['bg_soft'] ?? '#faf7ff';
        $textMain = $theme['text_main'] ?? '#0f172a';
        $textMuted = $theme['text_muted'] ?? '#64748b';
    @endphp

    {{-- Typography --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Schema.org FAQPage structured data (SEO) — PHP'de build edip @json ile yaz (Blade @context ile çakışmasın) --}}
    @php
        $schemaFaq = [];
        if (!empty($faqs)) {
            $schemaFaq = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type' => 'Question',
                    'name'  => $f['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $f['answer'],
                    ],
                ], $faqs),
            ];
        }
    @endphp
    @if (!empty($schemaFaq))
        <script type="application/ld+json">{!! json_encode($schemaFaq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: {{ $textMain }}; line-height: 1.6; background: {{ $bgSoft }}; }
        a { color: {{ $primary }}; text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Navbar */
        .topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 16px 24px; position: sticky; top: 0; z-index: 10; }
        .topbar-inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-family: 'DM Serif Display', serif; font-size: 22px; color: {{ $textMain }}; }
        .logo span { color: {{ $primary }}; }
        .topbar-nav { display: flex; gap: 24px; font-size: 14px; font-weight: 500; }
        .topbar-nav a { color: {{ $textMuted }}; }
        .topbar-nav a:hover { color: {{ $primary }}; }

        /* Hero */
        .hero { background: linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%); color: #fff; padding: 60px 24px 80px; text-align: center; }
        .hero h1 { font-family: 'DM Serif Display', serif; font-size: clamp(32px, 5vw, 48px); margin-bottom: 14px; line-height: 1.15; }
        .hero p { font-size: 17px; opacity: 0.92; max-width: 720px; margin: 0 auto 28px; }
        .hero-stats { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; font-size: 13px; opacity: 0.9; }
        .hero-stat { background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 20px; }

        /* Search */
        .search-wrap { max-width: 640px; margin: 0 auto 0; padding: 0 24px; transform: translateY(-30px); }
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0; padding: 12px 18px; border-radius: 14px; box-shadow: 0 8px 30px rgba(91,46,145,0.15); }
        .search-box input { flex: 1; border: none; outline: none; font-size: 15px; color: {{ $textMain }}; font-family: inherit; }
        .search-box input::placeholder { color: {{ $textMuted }}; }
        .search-icon { color: {{ $textMuted }}; font-size: 18px; }

        /* Main content */
        .wrap { max-width: 900px; margin: 0 auto; padding: 0 24px 80px; }
        .topic-section { background: #fff; border-radius: 16px; padding: 28px 32px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(15,23,42,0.04); }
        .topic-title { font-family: 'DM Serif Display', serif; font-size: 22px; color: {{ $textMain }}; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid {{ $primary }}; }

        .faq-item { border-bottom: 1px solid #f1f5f9; }
        .faq-item:last-child { border-bottom: none; }
        .faq-question {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            width: 100%; text-align: left; background: transparent; border: none;
            padding: 16px 0; cursor: pointer; font: inherit; color: {{ $textMain }};
            font-weight: 600; font-size: 15px; transition: color 0.2s;
        }
        .faq-question:hover { color: {{ $primary }}; }
        .faq-question .caret { color: {{ $primary }}; font-size: 20px; transition: transform 0.2s; flex-shrink: 0; }
        .faq-item.open .faq-question .caret { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s ease; color: {{ $textMuted }}; font-size: 14px; line-height: 1.75; }
        .faq-item.open .faq-answer { max-height: 800px; padding: 0 0 18px; }
        .faq-category { display: inline-block; background: {{ $bgSoft }}; color: {{ $primary }}; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px; }

        .empty { text-align: center; padding: 60px 20px; color: {{ $textMuted }}; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }

        /* CTA */
        .cta {
            background: #fff; border: 2px solid {{ $primary }}; border-radius: 16px;
            padding: 32px; text-align: center; margin-top: 40px;
        }
        .cta h3 { font-family: 'DM Serif Display', serif; font-size: 24px; color: {{ $textMain }}; margin-bottom: 8px; }
        .cta p { color: {{ $textMuted }}; margin-bottom: 18px; }
        .cta-btn {
            display: inline-block; background: {{ $primary }}; color: #fff;
            padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px;
        }
        .cta-btn:hover { background: {{ $primaryDark }}; text-decoration: none; }

        /* Footer */
        .footer { background: #1e293b; color: #94a3b8; padding: 30px 24px; text-align: center; font-size: 13px; }
        .footer a { color: #cbd5e1; }

        /* Hidden filter state */
        .faq-item.hidden { display: none; }
    </style>
</head>
<body>

{{-- Topbar --}}
<div class="topbar">
    <div class="topbar-inner">
        @if (!empty($brandLogoUrl ?? ''))
            <a href="/" style="text-decoration:none;">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName ?? 'MentorDE' }}" style="height:36px;">
            </a>
        @else
            <a href="/" class="logo">Mentor<span>DE</span></a>
        @endif
        <nav class="topbar-nav">
            <a href="/">Anasayfa</a>
            <a href="/randevu">Randevu</a>
            <a href="/login">Giriş</a>
        </nav>
    </div>
</div>

{{-- Hero --}}
<section class="hero">
    <h1>Sık Sorulan Sorular</h1>
    <p>Almanya'da eğitim, vize, başvuru süreçleri ve daha fazlası hakkında en çok merak edilenler — yanıtlar uzmanlarımızın hazırladığı güvenilir kaynaklardan.</p>
    <div class="hero-stats">
        <span class="hero-stat">📚 {{ $totalCount }} soru/cevap</span>
        <span class="hero-stat">🎓 Yurt dışı eğitim</span>
        <span class="hero-stat">✅ Uzman onaylı</span>
    </div>
</section>

{{-- Search --}}
<div class="search-wrap">
    <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="faq-search" placeholder="Soru ara... (ör. Sperrkonto, vize, APS)">
    </div>
</div>

{{-- FAQ List --}}
<div class="wrap">
    @if ($totalCount === 0)
        <div class="topic-section empty">
            <div class="empty-icon">📭</div>
            <p>Henüz yayınlanmış soru/cevap yok.<br>Yöneticimiz yakında bu sayfayı doldurmaya başlayacak.</p>
        </div>
    @else
        @foreach ($topics as $topicTitle => $items)
            <section class="topic-section">
                <h2 class="topic-title">{{ $topicTitle }}</h2>
                @foreach ($items as $f)
                    <div class="faq-item" data-text="{{ mb_strtolower($f['question'] . ' ' . $f['answer'] . ' ' . $f['category']) }}">
                        <button type="button" class="faq-question" aria-expanded="false">
                            <span>
                                @if (!empty($f['category']) && $f['category'] !== 'Genel')
                                    <span class="faq-category">{{ $f['category'] }}</span><br>
                                @endif
                                {{ $f['question'] }}
                            </span>
                            <span class="caret">▾</span>
                        </button>
                        <div class="faq-answer">{{ $f['answer'] }}</div>
                    </div>
                @endforeach
            </section>
        @endforeach

        <div class="cta">
            <h3>Aradığın cevabı bulamadın mı?</h3>
            <p>Danışmanlarımız seninle tanışmak istiyor. Ücretsiz randevu al.</p>
            <a href="/randevu" class="cta-btn">📅 Randevu Al</a>
        </div>
    @endif
</div>

<footer class="footer">
    © {{ date('Y') }} {{ $brandName ?? 'MentorDE' }} — <a href="/">Anasayfa</a>
</footer>

<script>
// Accordion toggle
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.parentElement;
        const isOpen = item.classList.toggle('open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});

// Live search
const search = document.getElementById('faq-search');
if (search) {
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        document.querySelectorAll('.faq-item').forEach(item => {
            const text = item.getAttribute('data-text') || '';
            const match = q === '' || text.includes(q);
            item.classList.toggle('hidden', !match);
        });
        // Boş topic'leri de gizle
        document.querySelectorAll('.topic-section').forEach(sec => {
            const visible = sec.querySelectorAll('.faq-item:not(.hidden)').length;
            sec.style.display = visible === 0 && q !== '' ? 'none' : '';
        });
    });
}
</script>

</body>
</html>
