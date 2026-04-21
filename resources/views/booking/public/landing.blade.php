<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al — Yurt Dışı Eğitim Danışmanları · {{ $brandName ?? 'MentorDE' }}</title>
    <meta name="description" content="Almanya yurt dışı eğitim başvurunuz için uzman danışmanlarla birebir görüşme planlayın. Üniversite seçimi, belge süreci, vize ve daha fazlası.">
    @vite(['resources/css/premium.css'])
    <style>
        :root {
            --brand:#1e40af; --brand-dark:#1e3a8a; --brand-light:#dbeafe; --accent:#f59e0b;
            --text:#0f172a; --muted:#64748b; --border:#e2e8f0;
            --bg:#f8fafc; --card:#ffffff;
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); line-height:1.55; }
        a { color:var(--brand); text-decoration:none; }
        a:hover { text-decoration:underline; }

        /* === HEADER / NAV === */
        .l-nav { position:sticky; top:0; z-index:50; background:rgba(255,255,255,.95); backdrop-filter:blur(8px); border-bottom:1px solid var(--border); }
        .l-nav-inner { max-width:1180px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:12px 20px; gap:14px; }
        .l-logo { display:flex; align-items:center; gap:8px; font-weight:800; font-size:18px; color:var(--text); }
        .l-logo-icon { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg, var(--brand), var(--brand-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; }
        .l-nav-links { display:flex; gap:20px; font-size:14px; font-weight:600; }
        .l-nav-links a { color:var(--muted); }
        .l-nav-links a:hover { color:var(--brand); text-decoration:none; }
        .l-nav-cta { padding:8px 16px; background:var(--brand); color:#fff !important; border-radius:8px; font-size:13px; font-weight:700; }
        .l-nav-cta:hover { background:var(--brand-dark); text-decoration:none !important; }
        @media(max-width:720px){ .l-nav-links { display:none; } }

        /* === HERO === */
        .l-hero { position:relative; background:linear-gradient(135deg,#1e3a8a 0%, #1e40af 40%, #3b82f6 100%); color:#fff; padding:80px 20px 100px; overflow:hidden; }
        .l-hero::before { content:''; position:absolute; top:-40%; right:-10%; width:500px; height:500px; border-radius:50%; background:radial-gradient(circle, rgba(245,158,11,.25) 0%, transparent 70%); }
        .l-hero-inner { max-width:1180px; margin:0 auto; position:relative; z-index:1; }
        .l-hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.15); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-bottom:18px; border:1px solid rgba(255,255,255,.25); }
        .l-hero h1 { margin:0 0 16px; font-size:42px; font-weight:800; line-height:1.1; max-width:720px; }
        .l-hero h1 .accent { color:#fbbf24; }
        .l-hero-sub { font-size:17px; opacity:.92; max-width:620px; margin:0 0 28px; line-height:1.65; }
        .l-hero-ctas { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:40px; }
        .l-btn { display:inline-flex; align-items:center; gap:8px; padding:14px 26px; border-radius:10px; font-weight:700; font-size:14px; transition:all .2s; cursor:pointer; border:none; }
        .l-btn-primary { background:#fbbf24; color:#1e3a8a !important; }
        .l-btn-primary:hover { background:#f59e0b; text-decoration:none !important; transform:translateY(-2px); }
        .l-btn-ghost { background:rgba(255,255,255,.12); color:#fff !important; border:1.5px solid rgba(255,255,255,.35); }
        .l-btn-ghost:hover { background:rgba(255,255,255,.2); text-decoration:none !important; }
        .l-hero-trust { display:flex; gap:40px; flex-wrap:wrap; padding-top:28px; border-top:1px solid rgba(255,255,255,.15); }
        .l-trust-num { font-size:30px; font-weight:800; color:#fbbf24; }
        .l-trust-lbl { font-size:12px; opacity:.85; text-transform:uppercase; letter-spacing:.04em; }
        @media(max-width:720px){ .l-hero { padding:50px 18px 70px; } .l-hero h1 { font-size:30px; } .l-hero-sub { font-size:15px; } }

        /* === SECTION === */
        .l-sec { padding:70px 20px; }
        .l-sec-inner { max-width:1180px; margin:0 auto; }
        .l-sec-head { text-align:center; max-width:680px; margin:0 auto 48px; }
        .l-sec-eyebrow { display:inline-block; color:var(--brand); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; margin-bottom:10px; }
        .l-sec-title { font-size:32px; font-weight:800; color:var(--text); margin:0 0 12px; line-height:1.2; }
        .l-sec-sub { font-size:16px; color:var(--muted); line-height:1.65; margin:0; }

        /* === BENEFITS GRID === */
        .l-benefits { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; }
        .l-benefit { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:24px; transition:all .2s; }
        .l-benefit:hover { border-color:var(--brand); transform:translateY(-3px); box-shadow:0 12px 24px rgba(30,64,175,.08); }
        .l-benefit-icon { width:48px; height:48px; border-radius:12px; background:var(--brand-light); color:var(--brand); display:flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:14px; }
        .l-benefit h3 { margin:0 0 8px; font-size:16px; color:var(--text); }
        .l-benefit p { margin:0; font-size:13px; color:var(--muted); line-height:1.65; }

        /* === HOW IT WORKS === */
        .l-steps { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:24px; position:relative; }
        .l-step { background:var(--card); border:2px solid var(--border); border-radius:14px; padding:28px 22px; position:relative; text-align:center; transition:all .2s; }
        .l-step:hover { border-color:var(--brand); }
        .l-step-num { position:absolute; top:-16px; left:50%; transform:translateX(-50%); width:40px; height:40px; border-radius:50%; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px; }
        .l-step h3 { margin:16px 0 10px; font-size:18px; color:var(--text); }
        .l-step p { margin:0; font-size:13px; color:var(--muted); line-height:1.65; }
        .l-step-icon { font-size:44px; display:block; margin-top:12px; }

        /* === SENIORS GRID === */
        .l-seniors-bar { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px; margin-bottom:24px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; box-shadow:0 1px 3px rgba(0,0,0,.03); }
        .l-seniors-bar input { flex:1; min-width:240px; padding:11px 15px; border:1px solid var(--border); border-radius:8px; font-size:14px; outline:none; transition:border-color .15s; }
        .l-seniors-bar input:focus { border-color:var(--brand); }
        .l-seniors-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; }
        .l-senior-card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:22px; transition:all .2s; display:flex; flex-direction:column; }
        .l-senior-card:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.08); }
        .l-senior-avatar { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg, var(--brand-light), #c7d2fe); display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; color:var(--brand); margin-bottom:14px; overflow:hidden; border:3px solid #fff; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .l-senior-avatar img { width:100%; height:100%; object-fit:cover; }
        .l-senior-card h3 { margin:0 0 4px; font-size:16px; }
        .l-senior-sub { color:var(--muted); font-size:12px; margin-bottom:12px; }
        .l-senior-bio { font-size:13px; color:#475569; line-height:1.6; margin-bottom:14px; flex:1; }
        .l-senior-tags { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:14px; }
        .l-senior-tag { background:var(--brand-light); color:var(--brand); padding:3px 9px; border-radius:10px; font-size:11px; font-weight:600; }
        .l-senior-meta { display:flex; gap:12px; font-size:12px; color:var(--muted); margin-bottom:14px; }
        .l-senior-btn { display:block; text-align:center; padding:11px 16px; background:var(--brand); color:#fff !important; border-radius:8px; font-weight:700; font-size:13px; transition:background .15s; }
        .l-senior-btn:hover { background:var(--brand-dark); text-decoration:none !important; }
        .l-empty { padding:60px 30px; text-align:center; background:var(--card); border-radius:12px; border:1px dashed var(--border); }
        .l-empty-icon { font-size:48px; margin-bottom:14px; }
        .l-empty h3 { margin:0 0 8px; font-size:18px; color:var(--text); }
        .l-empty p { margin:0 0 18px; color:var(--muted); font-size:14px; max-width:420px; margin-left:auto; margin-right:auto; }

        /* === CTA SECTION === */
        .l-cta { background:linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color:#fff; padding:70px 20px; text-align:center; }
        .l-cta h2 { margin:0 0 14px; font-size:30px; font-weight:800; }
        .l-cta p { margin:0 0 28px; font-size:16px; opacity:.9; max-width:620px; margin-left:auto; margin-right:auto; }
        .l-cta .l-btn { margin:0 6px; }

        /* === FAQ === */
        .l-faq { max-width:760px; margin:0 auto; }
        .l-faq details { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:0; margin-bottom:10px; overflow:hidden; transition:all .2s; }
        .l-faq details[open] { border-color:var(--brand); box-shadow:0 4px 12px rgba(30,64,175,.08); }
        .l-faq summary { cursor:pointer; padding:18px 22px; font-weight:700; font-size:15px; display:flex; align-items:center; justify-content:space-between; }
        .l-faq summary::after { content:'+'; font-size:22px; color:var(--brand); transition:transform .2s; }
        .l-faq details[open] summary::after { content:'−'; }
        .l-faq summary::-webkit-details-marker { display:none; }
        .l-faq-body { padding:0 22px 18px; color:var(--muted); font-size:14px; line-height:1.7; }

        /* === FOOTER === */
        .l-foot { background:#0f172a; color:#94a3b8; padding:40px 20px 24px; }
        .l-foot-inner { max-width:1180px; margin:0 auto; }
        .l-foot-cols { display:grid; grid-template-columns:2fr 1fr 1fr; gap:30px; margin-bottom:30px; }
        @media(max-width:720px){ .l-foot-cols { grid-template-columns:1fr; } }
        .l-foot h4 { color:#fff; margin:0 0 12px; font-size:14px; font-weight:700; }
        .l-foot a { color:#94a3b8; display:block; margin-bottom:6px; font-size:13px; }
        .l-foot a:hover { color:#fff; text-decoration:none; }
        .l-foot-bottom { border-top:1px solid #1e293b; padding-top:20px; font-size:12px; text-align:center; }
    </style>
</head>
<body>

{{-- ══════════════ NAV ══════════════ --}}
<nav class="l-nav">
    <div class="l-nav-inner">
        <a href="/" class="l-logo">
            <span class="l-logo-icon">M</span>
            {{ $brandName ?? 'MentorDE' }}
        </a>
        <div class="l-nav-links">
            <a href="#nasil-calisir">Nasıl Çalışır</a>
            <a href="#danismanlar">Danışmanlar</a>
            <a href="#sss">SSS</a>
        </div>
        <a href="{{ route('apply.create') }}" class="l-nav-cta">Başvur</a>
    </div>
</nav>

{{-- ══════════════ HERO ══════════════ --}}
<section class="l-hero">
    <div class="l-hero-inner">
        <div class="l-hero-badge">✨ Almanya'da 106+ mezun</div>
        <h1>Almanya'da eğitim yolculuğun <span class="accent">bir görüşme ötende</span></h1>
        <p class="l-hero-sub">
            Üniversite seçimi, belge hazırlığı, vize süreci — uzman eğitim danışmanlarımızla birebir görüş,
            kafandaki soruları net cevaplara dönüştür. Dakikalar içinde müsait saatini bul, tek tıkla randevunu al.
        </p>
        <div class="l-hero-ctas">
            <a href="#danismanlar" class="l-btn l-btn-primary">📅 Hemen Randevu Al</a>
            <a href="{{ route('apply.create') }}" class="l-btn l-btn-ghost">📝 Tam Başvuru Başlat</a>
        </div>
        <div class="l-hero-trust">
            <div>
                <div class="l-trust-num">106+</div>
                <div class="l-trust-lbl">Öğrenci Almanya'da</div>
            </div>
            <div>
                <div class="l-trust-num">50+</div>
                <div class="l-trust-lbl">Üniversite Kabulü</div>
            </div>
            <div>
                <div class="l-trust-num">%95</div>
                <div class="l-trust-lbl">Memnuniyet</div>
            </div>
            <div>
                <div class="l-trust-num">7+</div>
                <div class="l-trust-lbl">Yıllık Deneyim</div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ BENEFITS ══════════════ --}}
<section class="l-sec" style="background:#fff;">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Neden {{ $brandName ?? 'MentorDE' }}</div>
            <h2 class="l-sec-title">Almanya hedefin için bütün süreç tek platformda</h2>
            <p class="l-sec-sub">Sadece bir randevu sistemi değiliz — üniversite seçiminden vize alımına kadar her adımda yanındayız.</p>
        </div>
        <div class="l-benefits">
            <div class="l-benefit">
                <div class="l-benefit-icon">🎯</div>
                <h3>Uzman Danışmanlık</h3>
                <p>Almanya eğitim sistemini yıllardır tanıyan danışmanlarla birebir görüşme. Her biri Alman üniversitelerini bizzat deneyimlemiş.</p>
            </div>
            <div class="l-benefit">
                <div class="l-benefit-icon">📄</div>
                <h3>Belge Süreci Yönetimi</h3>
                <p>Uni-Assist başvurusundan Sperrkonto açılımına, vize evrakından Anmeldung'a kadar tüm belgeler dijital havuzda.</p>
            </div>
            <div class="l-benefit">
                <div class="l-benefit-icon">🎓</div>
                <h3>Üniversite Eşleştirme</h3>
                <p>AI destekli program önerileri + gerçek kabul verileri. Seni hangi üniversitenin alabileceğini bilimsel olarak analiz ediyoruz.</p>
            </div>
            <div class="l-benefit">
                <div class="l-benefit-icon">📅</div>
                <h3>Takvim Senkronizasyonu</h3>
                <p>Google Takvim + Zoom otomatik. Randevun onaylanır onaylanmaz takvimine düşer, link hazır.</p>
            </div>
            <div class="l-benefit">
                <div class="l-benefit-icon">🇩🇪</div>
                <h3>Vize & Blocked Account</h3>
                <p>Vize randevusu, bloke hesap, sağlık sigortası — resmî süreçlerin her birini adım adım takip ediyoruz.</p>
            </div>
            <div class="l-benefit">
                <div class="l-benefit-icon">🤖</div>
                <h3>AI Asistan 7/24</h3>
                <p>Danışmanın müsait olmadığı zamanlarda AI asistanımıza sor — belgelerin, süreç sorularını anında yanıtlar.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ HOW IT WORKS ══════════════ --}}
<section class="l-sec" id="nasil-calisir">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Nasıl Çalışır</div>
            <h2 class="l-sec-title">3 adımda danışmanınla tanış</h2>
            <p class="l-sec-sub">Kafanda karışık soru varsa, cevaplamak 30 dakika sürer. Süreci 3 basit adıma indirdik.</p>
        </div>
        <div class="l-steps">
            <div class="l-step">
                <div class="l-step-num">1</div>
                <div class="l-step-icon">🔍</div>
                <h3>Danışman Seç</h3>
                <p>Aşağıdan senin için uygun uzmanı bul. Uzmanlık alanı (vize / üniversite / burs) veya dile göre filtrele.</p>
            </div>
            <div class="l-step">
                <div class="l-step-num">2</div>
                <div class="l-step-icon">📆</div>
                <h3>Müsait Saati Belirle</h3>
                <p>Gerçek zamanlı takvim — 30 gün içinde uygun olan saatleri gör. Tek tıkla seç, dakikalar içinde onaylansın.</p>
            </div>
            <div class="l-step">
                <div class="l-step-num">3</div>
                <div class="l-step-icon">💬</div>
                <h3>Görüş & Yol Haritanı Al</h3>
                <p>Online görüşme linki mailine gelir. 30-60 dakikalık birebir görüşmeden sonra kişisel yol haritan hazır.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ SENIORS LIST ══════════════ --}}
<section class="l-sec" id="danismanlar" style="background:#fff;">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Danışmanlarımız</div>
            <h2 class="l-sec-title">Müsait uzmanlar</h2>
            <p class="l-sec-sub">Her biri Almanya'da eğitim görmüş veya hâlâ aktif çalışan profesyoneller. Deneyimlerini senin adımlarına dönüştürüyorlar.</p>
        </div>

        @if ($seniors->isNotEmpty())
            <div class="l-seniors-bar">
                <input type="text" id="l-search" placeholder="🔍 Danışman adı veya uzmanlık alanı ara...">
            </div>
            <div class="l-seniors-grid" id="l-grid">
                @foreach ($seniors as $s)
                    <div class="l-senior-card" data-search="{{ strtolower(($s['name'] ?? '').' '.($s['display_name'] ?? '').' '.implode(' ', (array)$s['expertise'])) }}">
                        <div class="l-senior-avatar">
                            @if (!empty($s['photo_url']))
                                <img src="{{ $s['photo_url'] }}" alt="{{ $s['name'] }}">
                            @else
                                {{ strtoupper(mb_substr($s['name'] ?? 'D', 0, 1)) }}
                            @endif
                        </div>
                        <h3>{{ $s['name'] ?? $s['display_name'] }}</h3>
                        <div class="l-senior-sub">{{ $s['display_name'] }}</div>

                        @if (!empty($s['bio']))
                            <div class="l-senior-bio">{{ \Illuminate\Support\Str::limit($s['bio'], 120) }}</div>
                        @else
                            <div class="l-senior-bio" style="color:#94a3b8;font-style:italic;">Uzman tanıtımı yakında eklenecek.</div>
                        @endif

                        @if (!empty($s['expertise']))
                            <div class="l-senior-tags">
                                @foreach (array_slice($s['expertise'], 0, 5) as $tag)
                                    <span class="l-senior-tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="l-senior-meta">
                            <span>⏱ {{ $s['slot_duration'] }} dk</span>
                            <span>🌍 {{ $s['timezone'] }}</span>
                        </div>

                        <a href="{{ route('booking.public.show', ['slug' => $s['slug']]) }}" class="l-senior-btn">Müsait Saatleri Gör →</a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="l-empty">
                <div class="l-empty-icon">🧑‍🏫</div>
                <h3>Danışmanlarımız Hazırlanıyor</h3>
                <p>Şu anda public randevu veren danışman yok. Hemen başvuru formumuzu doldurup kayıtlı öğrenci olarak bekleme listesine katılabilirsin — sıra sana geldiğinde sana özel danışman atanacak.</p>
                <a href="{{ route('apply.create') }}" class="l-btn l-btn-primary" style="display:inline-flex;">📝 Başvuru Formunu Doldur</a>
            </div>
        @endif
    </div>
</section>

{{-- ══════════════ CTA ══════════════ --}}
<section class="l-cta">
    <div class="l-sec-inner">
        <h2>Almanya hedefine bugün başla</h2>
        <p>Bir görüşme yeterli — kafandaki soru işaretleri netleşir, hedefin adım adım yol haritasına dönüşür. Randevu almak ücretsiz, sana herhangi bir taahhüt yok.</p>
        <a href="#danismanlar" class="l-btn l-btn-primary">📅 Hemen Randevu Al</a>
        <a href="{{ route('apply.create') }}" class="l-btn l-btn-ghost">📝 Tam Başvuru Başlat</a>
    </div>
</section>

{{-- ══════════════ FAQ ══════════════ --}}
<section class="l-sec" id="sss">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Sık Sorulan Sorular</div>
            <h2 class="l-sec-title">Her şey net ve şeffaf</h2>
        </div>
        <div class="l-faq">
            <details>
                <summary>Randevu ücretli mi?</summary>
                <div class="l-faq-body">
                    Şu an randevular <strong>ücretsiz</strong>. Danışmanlarımızla tanışma ve hedef analizi görüşmeleri için tek kuruş alınmıyor. Eğer tam danışmanlık paketine geçersen oradan itibaren paket ücretlendirmesi başlar.
                </div>
            </details>
            <details>
                <summary>Kimler randevu alabilir?</summary>
                <div class="l-faq-body">
                    Almanya'da üniversite okumak isteyen veya yurt dışı eğitim süreciyle ilgili tavsiye arayan herkes. Lise son sınıf, üniversite öğrencisi veya mezun olabilirsin. Çalışan veya yüksek lisans planlayan profesyoneller de başvurabilir.
                </div>
            </details>
            <details>
                <summary>Görüşme ne kadar sürer?</summary>
                <div class="l-faq-body">
                    Danışmanın tanımladığı slot süresine göre 15, 30, 45 veya 60 dakika. Çoğu tanışma görüşmesi 30 dakikadır. Görüşmeden önce hangi konulara odaklanmak istediğini belirtebilirsin, danışmanın hazırlıklı gelir.
                </div>
            </details>
            <details>
                <summary>Görüşme nasıl gerçekleşir?</summary>
                <div class="l-faq-body">
                    Online — Google Meet veya Zoom üzerinden. Randevu onaylandığında linki mailine gelir, aynı link Google Takvim davetiyesinde de yer alır. Kamera ve mikrofonun açık olsun yeterli.
                </div>
            </details>
            <details>
                <summary>Randevumu iptal veya ertele yapabilir miyim?</summary>
                <div class="l-faq-body">
                    Evet. Onay mailinde gelen iptal linki üzerinden randevundan en az <strong>24 saat önce</strong> ücretsiz iptal edebilirsin. Sonraki süreç için yeni bir randevu alabilirsin.
                </div>
            </details>
            <details>
                <summary>Danışmanımı nasıl seçerim?</summary>
                <div class="l-faq-body">
                    Yukarıdaki listede her danışmanın uzmanlık alanını (üniversite başvurusu, vize süreci, burs, dil sınavı vb.) gör. Eğer tam başvuru sürecine girersen, profiline en uygun danışman otomatik olarak sana atanır.
                </div>
            </details>
            <details>
                <summary>Sonrasında danışmanlık süreci nasıl devam eder?</summary>
                <div class="l-faq-body">
                    İlk görüşmeden sonra durumuna uygun paketler gösterilir (sadece belge yönetimi / tam başvuru paketi / premium). Paket seçersen sözleşme imzalanır, Uni-Assist'ten Immatrikulation'a kadar tüm süreç platformda ilerler.
                </div>
            </details>
        </div>
    </div>
</section>

{{-- ══════════════ FOOTER ══════════════ --}}
<footer class="l-foot">
    <div class="l-foot-inner">
        <div class="l-foot-cols">
            <div>
                <h4>{{ $brandName ?? 'MentorDE' }}</h4>
                <p style="color:#94a3b8;font-size:13px;line-height:1.65;margin:0 0 12px;max-width:320px;">
                    Türkiye'den Almanya'ya uzanan yol haritanı kolaylaştıran yurt dışı eğitim danışmanlık platformu.
                </p>
            </div>
            <div>
                <h4>Platform</h4>
                <a href="#nasil-calisir">Nasıl Çalışır</a>
                <a href="#danismanlar">Danışmanlar</a>
                <a href="{{ route('apply.create') }}">Başvur</a>
                <a href="/login">Giriş Yap</a>
            </div>
            <div>
                <h4>Yasal</h4>
                <a href="{{ route('legal.privacy') }}">Gizlilik Politikası</a>
                <a href="{{ route('legal.terms') }}">Kullanım Koşulları</a>
                <a href="mailto:support@mentorde.com">support@mentorde.com</a>
            </div>
        </div>
        <div class="l-foot-bottom">
            © {{ date('Y') }} {{ $brandName ?? 'MentorDE' }} · Tüm hakları saklıdır
        </div>
    </div>
</footer>

<script>
(function(){
    var search = document.getElementById('l-search');
    if (!search) return;
    search.addEventListener('input', function(){
        var q = search.value.toLowerCase().trim();
        document.querySelectorAll('.l-senior-card').forEach(function(card){
            var haystack = card.getAttribute('data-search') || '';
            card.style.display = (q === '' || haystack.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>

</body>
</html>
