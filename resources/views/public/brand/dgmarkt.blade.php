<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DGmarkt — Brand Kit</title>
<link rel="icon" type="image/svg+xml" href="/brand/dgmarkt/favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --indigo:#3b3098;
    --indigo-dark:#2a2270;
    --violet:#4338ca;
    --cyan:#0ea5e9;
    --slate-900:#0f172a;
    --slate-700:#334155;
    --slate-500:#64748b;
    --slate-200:#e2e8f0;
    --slate-50:#f8fafc;
    --font:"Space Grotesk", system-ui, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; font-family:var(--font); color:var(--slate-900); }
body { background:#fff; }

.nav { padding:18px 28px; border-bottom:1px solid var(--slate-200); display:flex; align-items:center; justify-content:space-between; }
.nav-mark { width:32px; height:32px; }
.nav-title { font-weight:800; font-size:16px; letter-spacing:-.3px; margin-left:10px; }
.nav-meta { color:var(--slate-500); font-size:12.5px; }

.hero { padding:80px 28px 60px; max-width:980px; margin:0 auto; }
.hero .kicker { color:var(--indigo); font-weight:800; font-size:12px; letter-spacing:.2em; text-transform:uppercase; }
.hero h1 { font-size:clamp(36px, 5vw, 56px); font-weight:800; line-height:1.05; letter-spacing:-2px; margin:14px 0 16px; }
.hero p { font-size:17px; color:var(--slate-500); max-width:640px; line-height:1.55; margin:0; }

.section { padding:48px 28px; max-width:1100px; margin:0 auto; }
.section h2 { font-size:24px; font-weight:700; letter-spacing:-.6px; margin:0 0 6px; }
.section .sub { color:var(--slate-500); font-size:14px; margin:0 0 24px; }

.tiles { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px; }
.tile {
    background:#fff; border:1px solid var(--slate-200); border-radius:16px;
    padding:26px 22px; display:flex; flex-direction:column; gap:14px;
    transition:border-color .2s, box-shadow .2s;
}
.tile:hover { border-color:#cbd5e1; box-shadow:0 8px 28px rgba(15,23,42,.06); }
.tile.dark { background:linear-gradient(180deg, #0f172a, #1e293b); border-color:#1e293b; }
.tile.dark .tile-label { color:#94a3b8; }
.tile.dark .tile-note { color:#cbd5e1; }
.tile-art { display:flex; align-items:center; justify-content:center; min-height:140px; }
.tile-art img { max-width:100%; height:auto; }
.tile-label { font-weight:700; font-size:13px; color:var(--slate-500); letter-spacing:.05em; text-transform:uppercase; }
.tile-note { font-size:13px; color:var(--slate-700); line-height:1.5; }

.tile-art.compact { min-height:88px; }
.tile-art.compact img { width:64px; height:64px; }

.colors { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; }
.color { border-radius:14px; overflow:hidden; border:1px solid var(--slate-200); }
.color-swatch { height:110px; }
.color-body { padding:14px 16px; background:#fff; }
.color-name { font-weight:700; font-size:13.5px; }
.color-hex { font-family:'JetBrains Mono', 'Monaco', monospace; font-size:12px; color:var(--slate-500); margin-top:2px; }

.usage-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px; }
.usage {
    background:var(--slate-50); border:1px solid var(--slate-200); border-radius:12px;
    padding:18px 20px; font-size:14px; line-height:1.55; color:var(--slate-700);
}
.usage strong { color:var(--slate-900); display:block; margin-bottom:4px; font-weight:700; }
.usage code { background:#fff; padding:2px 6px; border-radius:4px; border:1px solid var(--slate-200); font-size:12px; font-family:'JetBrains Mono', 'Monaco', monospace; }

.services { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-top:16px; }
.service {
    padding:18px 20px; border-radius:12px;
    background:linear-gradient(135deg, rgba(59,48,152,.06), rgba(14,165,233,.05));
    border:1px solid var(--slate-200);
}
.service .ico { font-size:20px; margin-bottom:8px; }
.service strong { display:block; font-size:14px; margin-bottom:4px; }
.service span { font-size:12.5px; color:var(--slate-500); }

footer { padding:36px 28px; text-align:center; color:var(--slate-500); font-size:13px; border-top:1px solid var(--slate-200); }
</style>
</head>
<body>

<nav class="nav">
    <div style="display:flex;align-items:center;">
        <img src="/brand/dgmarkt/favicon.svg" alt="" class="nav-mark">
        <span class="nav-title">DGmarkt</span>
    </div>
    <div class="nav-meta">Brand Kit · v1</div>
</nav>

<header class="hero">
    <div class="kicker">BRAND</div>
    <h1>DGmarkt görsel kimliği</h1>
    <p>Online satış operasyonu, sosyal medya yönetimi, SEO, web & app development ve SaaS — beş alanı tek çatı altında birleştiren dijital operasyon şirketi için tasarlanmış kimlik seti.</p>
</header>

<section class="section">
    <h2>Logo</h2>
    <p class="sub">Yatay logo (mark + wordmark + tagline). Açık ve koyu zemin varyantları.</p>
    <div class="tiles">
        <div class="tile">
            <div class="tile-label">Açık zemin · Light variant</div>
            <div class="tile-art"><img src="/brand/dgmarkt/logo.svg" alt="DGmarkt logo light"></div>
            <div class="tile-note">Web header, fatura, e-posta imzası, sunum kapağı — beyaz/açık arka planlar için.</div>
        </div>
        <div class="tile dark">
            <div class="tile-label">Koyu zemin · Dark variant</div>
            <div class="tile-art"><img src="/brand/dgmarkt/logo-dark.svg" alt="DGmarkt logo dark"></div>
            <div class="tile-note">Platform Owner paneli sidebar, koyu hero bölümleri, fotoğraf overlay.</div>
        </div>
    </div>
</section>

<section class="section">
    <h2>İkon (Mark)</h2>
    <p class="sub">Yatay logonun kullanılamadığı yerler — uygulama ikonu, sosyal medya avatar, favicon.</p>
    <div class="tiles">
        <div class="tile">
            <div class="tile-label">Tam mark · 256×256</div>
            <div class="tile-art"><img src="/brand/dgmarkt/mark.svg" alt="DGmarkt mark" style="width:160px;height:160px;"></div>
            <div class="tile-note">Sosyal medya profil resmi, app store ikonu, PWA installer.</div>
        </div>
        <div class="tile">
            <div class="tile-label">Favicon · 32×32 sade</div>
            <div class="tile-art compact"><img src="/brand/dgmarkt/favicon.svg" alt="DGmarkt favicon" style="width:64px;height:64px;"></div>
            <div class="tile-note">Browser tab, küçük ölçekli durumlarda. Accent dot kaldırıldı, sadece DG monogram.</div>
        </div>
    </div>
</section>

<section class="section">
    <h2>Renk paleti</h2>
    <p class="sub">Gradient indigo → cyan: "klasik güven" ile "tech enerjisi" karışımı. Mentorde'nin saf mor'undan ayrıştırılmış — DGmarkt ana çatı, Mentorde alt marka.</p>
    <div class="colors">
        <div class="color">
            <div class="color-swatch" style="background:#3b3098;"></div>
            <div class="color-body">
                <div class="color-name">Indigo (Primary)</div>
                <div class="color-hex">#3b3098</div>
            </div>
        </div>
        <div class="color">
            <div class="color-swatch" style="background:#4338ca;"></div>
            <div class="color-body">
                <div class="color-name">Violet (Mid)</div>
                <div class="color-hex">#4338ca</div>
            </div>
        </div>
        <div class="color">
            <div class="color-swatch" style="background:#0ea5e9;"></div>
            <div class="color-body">
                <div class="color-name">Cyan (Accent)</div>
                <div class="color-hex">#0ea5e9</div>
            </div>
        </div>
        <div class="color">
            <div class="color-swatch" style="background:#0f172a;"></div>
            <div class="color-body">
                <div class="color-name">Slate (Text)</div>
                <div class="color-hex">#0f172a</div>
            </div>
        </div>
        <div class="color">
            <div class="color-swatch" style="background:linear-gradient(135deg,#3b3098,#4338ca 48%,#0ea5e9);"></div>
            <div class="color-body">
                <div class="color-name">Brand Gradient</div>
                <div class="color-hex">3b3098 → 4338ca → 0ea5e9</div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <h2>Tipografi</h2>
    <p class="sub">Tek font: <strong>Space Grotesk</strong>. Wordmark 800 bold, gövde 400-500 regular/medium, başlıklar 700 bold.</p>
    <div style="border:1px solid var(--slate-200);border-radius:14px;padding:32px 28px;">
        <div style="font-family:'Space Grotesk';font-weight:800;font-size:48px;letter-spacing:-1.5px;line-height:1;">dgmarkt</div>
        <div style="font-family:'Space Grotesk';font-weight:500;color:var(--slate-500);margin-top:6px;font-size:15px;">Aa Bb Cc Dd Ee Ff Gg · 0123456789</div>
    </div>
</section>

<section class="section">
    <h2>Hizmet alanları</h2>
    <p class="sub">DGmarkt çatısı altında 5 alan — her biri kendi ekip + iş akışı, ortak operasyon altyapısı.</p>
    <div class="services">
        <div class="service">
            <div class="ico">🛒</div>
            <strong>Online Satış Ops</strong>
            <span>Baştan sona e-ticaret operasyonu — listeleme, fiyatlama, fulfillment.</span>
        </div>
        <div class="service">
            <div class="ico">📱</div>
            <strong>Sosyal Medya</strong>
            <span>İçerik üretimi, topluluk yönetimi, reklam kampanyaları.</span>
        </div>
        <div class="service">
            <div class="ico">🔍</div>
            <strong>SEO Optimization</strong>
            <span>Organik trafik, teknik SEO, içerik stratejisi.</span>
        </div>
        <div class="service">
            <div class="ico">💻</div>
            <strong>Web & App Dev</strong>
            <span>Custom web/mobile uygulamalar, modern stack.</span>
        </div>
        <div class="service">
            <div class="ico">🚀</div>
            <strong>SaaS</strong>
            <span>Kendi SaaS ürünleri — örn. Mentorde (Almanya eğitim danışmanlığı).</span>
        </div>
    </div>
</section>

<section class="section">
    <h2>Kullanım kuralları</h2>
    <p class="sub">Tutarlılık için ipuçları.</p>
    <div class="usage-grid">
        <div class="usage">
            <strong>✓ Yapılır</strong>
            Logo etrafında en az <code>mark height × 0.4</code> kadar boş alan bırak. Beyaz veya çok açık zeminde light variant, koyu zemin (gradient, fotoğraf) üzerinde dark variant kullan.
        </div>
        <div class="usage">
            <strong>✗ Yapılmaz</strong>
            Logo rengini değiştirme. Gradient'i kaldırma. Wordmark'ı tek başına ortalama; ya yanına mark ekle ya yatay sola hizala. Çok küçük boyutta (mark &lt; 24px) ikon yerine sadece favicon kullan.
        </div>
        <div class="usage">
            <strong>Embed yolu</strong>
            <code>&lt;img src="/brand/dgmarkt/logo.svg"&gt;</code> · <code>/logo-dark.svg</code> · <code>/mark.svg</code> · <code>/favicon.svg</code>
        </div>
    </div>
</section>

<footer>
    DGmarkt · Brand Kit v1 · 2026
</footer>

</body>
</html>
