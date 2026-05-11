<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $title = $topicData['title'] ?? $topicKey;
        $desc = $topicData['description'] ?? '';
        $questions = $topicData['questions'] ?? [];
        $count = count($questions);
    @endphp
    <title>{{ $title }} — Topluluk Soru Arşivi | {{ config('brand.name', 'MentorDE') }}</title>
@include('partials.favicon')
    <meta name="description" content="{{ $title }}: {{ Str::limit($desc, 140) }} — toplulukta sık sorulan {{ $count }} soru.">
    <link rel="canonical" href="{{ url('/sss/topluluk/' . $topicKey) }}">

    @php
        $primary = '#7e58bf';
        $primaryDark = '#5b3a8f';
        $accent = '#e8b931';
        $bgSoft = '#faf7ff';
        $textMain = '#1a0f2e';
        $textMuted = '#5b4a7a';
    @endphp

    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">

    {{-- Schema.org FAQPage (sorular yanıtsız ama "Question" markup ile yine de listelenebilir) --}}
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'CollectionPage',
            'name'     => $title . ' — Topluluk Soru Arşivi',
            'description' => $desc,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $count,
                'itemListElement' => array_map(function ($q, $i) {
                    return [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'item'     => [
                            '@type' => 'Question',
                            'name'  => $q['text'],
                            'dateCreated' => $q['date'] ?? null,
                        ],
                    ];
                }, $questions, array_keys($questions)),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: {{ $textMain }}; line-height: 1.6; background: {{ $bgSoft }}; }
        a { color: {{ $primary }}; text-decoration: none; }
        a:hover { text-decoration: underline; }

        .topbar { background: #fff; border-bottom: 1px solid rgba(126,88,191,0.12); padding: 16px 24px; position: sticky; top: 0; z-index: 10; }
        .topbar-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 22px; font-weight: 700; color: {{ $textMain }}; letter-spacing: -0.02em; }
        .logo span { color: {{ $primary }}; }
        .topbar-nav { display: flex; gap: 24px; font-size: 14px; font-weight: 500; }
        .topbar-nav a { color: {{ $textMuted }}; }
        .topbar-nav a:hover { color: {{ $primary }}; }

        .hero { background: linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%); color: #fff; padding: 60px 24px 70px; text-align: left; }
        .hero-inner { max-width: 1100px; margin: 0 auto; }
        .breadcrumb { font-size: 13px; opacity: 0.78; margin-bottom: 18px; }
        .breadcrumb a { color: #fff; }
        .hero h1 { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: clamp(28px, 4.4vw, 38px); margin-bottom: 12px; line-height: 1.18; letter-spacing: -0.025em; }
        .hero .lead { font-size: 16px; opacity: 0.92; max-width: 720px; }
        .hero-meta { margin-top: 18px; font-size: 13px; opacity: 0.85; }

        .wrap { max-width: 1100px; margin: -40px auto 80px; padding: 0 24px; display: grid; grid-template-columns: 260px 1fr; gap: 30px; }

        /* Sidebar */
        .sidebar { background: #fff; border-radius: 14px; padding: 22px; height: fit-content; box-shadow: 0 2px 12px rgba(15,23,42,0.05); position: sticky; top: 90px; }
        .sidebar h3 { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 13px; text-transform: uppercase; letter-spacing: 0.06em; color: {{ $textMuted }}; margin-bottom: 14px; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a { display: block; padding: 8px 12px; border-radius: 8px; font-size: 13px; color: {{ $textMain }}; transition: background 0.15s, color 0.15s; }
        .sidebar-nav a:hover { background: {{ $bgSoft }}; text-decoration: none; }
        .sidebar-nav a.active { background: {{ $primary }}; color: #fff; font-weight: 600; }
        .sidebar-nav .topic-count { float: right; opacity: 0.7; font-size: 12px; }

        /* Main */
        .main { background: #fff; border-radius: 16px; padding: 32px 36px; box-shadow: 0 2px 14px rgba(15,23,42,0.04); }
        .search-bar { display: flex; align-items: center; gap: 10px; background: {{ $bgSoft }}; border: 1px solid rgba(126,88,191,0.18); padding: 12px 18px; border-radius: 12px; margin-bottom: 24px; }
        .search-bar input { flex: 1; border: none; outline: none; font-size: 14px; color: {{ $textMain }}; font-family: inherit; background: transparent; }

        .question-list { list-style: none; counter-reset: q; }
        .question-item { counter-increment: q; position: relative; padding: 16px 16px 16px 56px; margin-bottom: 8px; border-radius: 10px; background: {{ $bgSoft }}; border: 1px solid transparent; transition: border-color 0.15s; }
        .question-item:hover { border-color: rgba(126,88,191,0.18); }
        .question-item:before { content: counter(q); position: absolute; left: 16px; top: 16px; width: 28px; height: 28px; border-radius: 50%; background: {{ $primary }}; color: #fff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .question-text { font-size: 15px; color: {{ $textMain }}; line-height: 1.55; margin-bottom: 8px; }
        .question-meta { display: flex; align-items: center; gap: 14px; font-size: 12px; color: {{ $textMuted }}; }
        .question-date { display: inline-flex; align-items: center; gap: 4px; }
        .question-cta { color: {{ $primary }}; font-weight: 600; }
        .question-cta:hover { text-decoration: underline; }

        .empty { text-align: center; padding: 50px; color: {{ $textMuted }}; }

        /* Disclaimer + CTA */
        .info-card { background: linear-gradient(135deg, #fef9e7, #fff); border-left: 4px solid {{ $accent }}; border-radius: 10px; padding: 16px 20px; margin-bottom: 26px; font-size: 13px; color: {{ $textMuted }}; line-height: 1.6; }
        .info-card strong { color: {{ $textMain }}; }
        .bottom-cta { background: linear-gradient(135deg, {{ $primary }} 0%, {{ $primaryDark }} 100%); color: #fff; padding: 32px; border-radius: 14px; text-align: center; margin-top: 36px; }
        .bottom-cta h3 { font-family: 'Space Grotesk', system-ui, sans-serif; font-size: 22px; margin-bottom: 8px; }
        .bottom-cta p { opacity: 0.92; font-size: 14px; margin-bottom: 18px; }
        .bottom-cta .cta-btn { background: #fff; color: {{ $primary }}; padding: 11px 24px; border-radius: 9px; font-weight: 700; font-size: 14px; display: inline-block; }
        .bottom-cta .cta-btn:hover { text-decoration: none; opacity: 0.93; }

        .footer { background: #1a0f2e; color: #b9a5d9; padding: 30px 24px; text-align: center; font-size: 13px; }
        .footer a { color: #d6c5f0; }

        @media (max-width: 880px) {
            .wrap { grid-template-columns: 1fr; }
            .sidebar { position: static; order: 2; }
            .main { padding: 24px 22px; order: 1; }
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
    <div class="hero-inner">
        <div class="breadcrumb">
            <a href="/">Anasayfa</a> &nbsp;›&nbsp;
            <a href="{{ route('public.faq') }}">SSS</a> &nbsp;›&nbsp;
            <a href="{{ route('public.community-faq') }}">Topluluk Arşivi</a> &nbsp;›&nbsp;
            {{ $title }}
        </div>
        <h1>{{ $title }}</h1>
        <p class="lead">{{ $desc }}</p>
        <div class="hero-meta">📚 {{ $count }} anonim soru · 🛡️ Kişisel bilgi temizliği uygulandı</div>
    </div>
</section>

<div class="wrap">

    <aside class="sidebar">
        <h3>Diğer Konular</h3>
        <ul class="sidebar-nav">
            @foreach ($allTopics as $key => $t)
                <li>
                    <a href="{{ route('public.community-faq.topic', ['topic' => $key]) }}"
                       class="{{ $key === $topicKey ? 'active' : '' }}">
                        {{ $t['title'] ?? $key }}
                        <span class="topic-count">{{ count($t['questions'] ?? []) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    <main class="main">

        <div class="info-card">
            <strong>Bu sorular nasıl derlendi?</strong> Türkiye-Almanya eğitim ve vize topluluklarındaki yıllık binlerce mesajdan, en sık tekrarlanan ve bilgi yoğunluğu yüksek sorular seçildi. Tüm gönderim sahibi bilgileri (isim, telefon, e-posta, kullanıcı adı) tamamen temizlendi. Yanıtlar burada değil — uzman onaylı cevaplar için <a href="{{ route('public.faq') }}">resmi SSS</a>'a bakabilir veya <a href="/randevu">ücretsiz randevu</a> alabilirsin.
        </div>

        <div class="search-bar">
            <span style="color: {{ $primary }};">🔍</span>
            <input type="text" id="q-search" placeholder="Bu konudaki soruları filtrele...">
        </div>

        <ol class="question-list" id="q-list">
            @forelse ($questions as $q)
                <li class="question-item" data-q="{{ mb_strtolower($q['text']) }}">
                    <p class="question-text">{{ $q['text'] }}</p>
                    <div class="question-meta">
                        <span class="question-date">📅 {{ $q['date'] ?? '—' }}</span>
                        <a href="/randevu" class="question-cta">Bu konuda danışmanlık →</a>
                    </div>
                </li>
            @empty
                <li class="empty">Bu konuda henüz soru derlenmedi.</li>
            @endforelse
        </ol>

        <div class="bottom-cta">
            <h3>Sorduğun soruya çok benzeyen bir başlık var mı?</h3>
            <p>Sürecin senin durumuna nasıl uygulanacağı kişiseldir. Ücretsiz danışmanlık randevusunda spesifik cevap alabilirsin.</p>
            <a href="/randevu" class="cta-btn">📅 Ücretsiz Randevu Al</a>
        </div>

    </main>
</div>

<footer class="footer">
    © {{ date('Y') }} {{ config('brand.name', 'MentorDE') }} —
    <a href="/">Anasayfa</a> ·
    <a href="{{ route('public.community-faq') }}">Topluluk Arşivi</a> ·
    <a href="{{ route('public.faq') }}">SSS</a> ·
    <a href="/iletisim">İletişim</a>
</footer>

<script>
(function () {
    var input = document.getElementById('q-search');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('.question-item').forEach(function (item) {
            var text = item.getAttribute('data-q') || '';
            item.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>

</body>
</html>
