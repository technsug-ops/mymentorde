<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $brand = $brandName ?? config('brand.name', 'MentorDE');
    $selectedTierData = $tiers[$selectedTier] ?? $tiers['gold'];
@endphp
<title>Hesap Oluştur — {{ $brand }} (14 Gün Ücretsiz)</title>
@include('partials.favicon')
<meta name="robots" content="noindex,nofollow">

<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#7e58bf;
    --primary-dark:#6c47a8;
    --primary-deep:#5a3a8d;
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
    --gradient-purple:linear-gradient(140deg, #7e58bf 0%, #5a3a8d 100%);
    --font-base:"Space Grotesk", "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; }
body {
    font-family:var(--font-base); color:var(--text);
    background:linear-gradient(180deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%);
    line-height:1.6; font-size:15px;
    -webkit-font-smoothing:antialiased;
    min-height:100vh;
}
a { color:var(--primary); text-decoration:none; }

/* NAV */
.p-nav { position:sticky; top:0; z-index:50; background:rgba(255,255,255,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--line); }
.p-nav-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; }
.p-logo { font-size:28px; color:var(--primary); font-weight:700; letter-spacing:-.5px; }
.p-logo span { color:var(--primary-light); font-style:italic; font-weight:600; }
.nav-link { font-size:13px; color:var(--muted); font-weight:600; }
.nav-link:hover { color:var(--primary); }

/* LAYOUT — 2 column */
.signup-wrap {
    max-width:1100px; margin:0 auto; padding:40px 22px 60px;
    display:grid; grid-template-columns:1.2fr 1fr; gap:32px; align-items:flex-start;
}
@media(max-width:880px) { .signup-wrap { grid-template-columns:1fr; padding:24px 16px 40px; } }

/* FORM CARD */
.form-card {
    background:var(--surface); border-radius:18px;
    box-shadow:0 14px 40px rgba(126,88,191,.10); border:1px solid var(--line);
    padding:36px 36px;
}
@media(max-width:600px) { .form-card { padding:24px 22px; } }

.form-head { margin-bottom:24px; }
.form-head .label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.18em; font-size:11px; font-weight:800; margin-bottom:12px;
    background:var(--primary-soft); padding:5px 12px; border-radius:20px;
}
.form-head h1 {
    font-family:var(--font-base); font-style:italic; font-weight:600;
    font-size:30px; line-height:1.15; color:var(--primary-deep);
    letter-spacing:-1px; margin:0 0 8px;
}
.form-head p { color:var(--muted); font-size:14px; margin:0; }

.form-group { margin-bottom:18px; }
.form-group label {
    display:block; font-size:12px; font-weight:700; color:var(--text);
    text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;
}
.form-group label .req { color:var(--danger); font-weight:800; }
.form-group label .hint { color:var(--muted); font-weight:500; text-transform:none; letter-spacing:0; font-size:11px; }
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="tel"] {
    width:100%; padding:12px 14px; border-radius:10px;
    border:1.5px solid var(--line); background:#fff;
    font-size:14px; font-family:inherit; color:var(--text);
    transition:border-color .15s, box-shadow .15s;
}
.form-group input:focus {
    outline:none; border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(126,88,191,.12);
}
.form-group .err {
    color:var(--danger); font-size:12px; margin-top:4px; font-weight:600;
}
.form-row {
    display:grid; grid-template-columns:1fr 1fr; gap:14px;
}
@media(max-width:520px) { .form-row { grid-template-columns:1fr; } }

.password-hint { font-size:11px; color:var(--muted); margin-top:4px; line-height:1.4; }

.checkbox-group { margin-bottom:14px; }
.checkbox-group label {
    display:flex; align-items:flex-start; gap:10px;
    font-size:13px; font-weight:500; color:var(--text); cursor:pointer;
    text-transform:none; letter-spacing:0; line-height:1.5;
}
.checkbox-group input[type="checkbox"] {
    width:18px; height:18px; margin-top:2px; cursor:pointer; accent-color:var(--primary);
    flex:0 0 18px;
}
.checkbox-group a { color:var(--primary); font-weight:600; text-decoration:underline; }

.submit-btn {
    display:block; width:100%; padding:16px 24px;
    background:var(--gradient-purple); color:#fff; border:none;
    border-radius:12px; font-size:15px; font-weight:800;
    cursor:pointer; transition:transform .15s, box-shadow .15s;
    font-family:inherit; margin-top:14px;
    box-shadow:0 10px 28px rgba(126,88,191,.28);
}
.submit-btn:hover { transform:translateY(-2px); box-shadow:0 16px 40px rgba(126,88,191,.36); }
.submit-btn:disabled { opacity:.55; cursor:wait; transform:none; }

.alt-login {
    text-align:center; font-size:13px; color:var(--muted); margin-top:18px;
}

.errors-block {
    background:#fef2f2; border:1px solid #fecaca; color:#991b1b;
    padding:12px 14px; border-radius:10px; margin-bottom:18px;
    font-size:13px; line-height:1.5;
}

/* SIDE — Tier preview */
.tier-side { position:sticky; top:90px; }
.tier-side .preview-card {
    background:var(--surface); border-radius:18px; padding:28px;
    border:2px solid var(--primary);
    box-shadow:0 14px 40px rgba(126,88,191,.18);
}
.tier-side .preview-card .label-pill {
    display:inline-block; background:var(--gradient-purple); color:#fff;
    padding:5px 12px; border-radius:999px; font-size:11px; font-weight:800;
    letter-spacing:.08em; margin-bottom:14px;
}
.tier-side .preview-card .tier-name {
    font-size:24px; font-weight:700; color:var(--primary-deep);
    margin-bottom:6px; letter-spacing:-.5px;
}
.tier-side .preview-card .price-block {
    display:flex; align-items:baseline; gap:6px; margin:14px 0 4px;
}
.tier-side .price-block .amount { font-size:36px; font-weight:700; color:var(--text); }
.tier-side .price-block .period { font-size:13px; color:var(--muted); }
.tier-side .price-block.zero .amount { color:var(--success); }
.tier-side .billing-info { font-size:12px; color:var(--muted); margin-bottom:18px; }

.trial-bonus {
    background:linear-gradient(135deg, var(--primary-soft), #fff);
    border:1px solid var(--primary-light); border-radius:12px;
    padding:14px 16px; margin-bottom:18px;
    display:flex; gap:10px; align-items:flex-start;
}
.trial-bonus .ico { font-size:24px; }
.trial-bonus strong { color:var(--primary-deep); font-size:13px; display:block; margin-bottom:2px; }
.trial-bonus span { font-size:12px; color:var(--muted); line-height:1.4; }

.tier-side ul {
    list-style:none; padding:0; margin:0;
    display:flex; flex-direction:column; gap:8px;
}
.tier-side ul li {
    display:flex; align-items:flex-start; gap:8px;
    font-size:13px; color:var(--text); line-height:1.4;
}
.tier-side ul li::before {
    content:"✓"; color:var(--primary); font-weight:800; flex:0 0 14px;
}
.change-tier {
    display:block; margin-top:18px; text-align:center;
    color:var(--muted); font-size:12.5px; text-decoration:underline;
}

.trust-bar {
    display:flex; gap:8px; flex-wrap:wrap; justify-content:center;
    margin-top:18px; font-size:11px; color:var(--muted);
}
.trust-bar span { background:#fff; padding:4px 10px; border-radius:999px; border:1px solid var(--line); }

@media(max-width:880px) {
    .tier-side { position:static; }
}
</style>
</head>
<body>

<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">{{ $brand }}<span>.</span></a>
        <div>
            <a href="/login" class="nav-link">Zaten hesabım var → Giriş yap</a>
        </div>
    </div>
</nav>

<div class="signup-wrap">

    {{-- ===== FORM ===== --}}
    <div class="form-card">
        <div class="form-head">
            <span class="label">14 GÜN ÜCRETSİZ DENEME</span>
            <h1>Hesabını oluştur</h1>
            <p>3 dakikada başla — kredi kartı gerekmez. İstediğin zaman iptal edebilirsin.</p>
        </div>

        @if($errors->any())
            <div class="errors-block">
                @if($errors->has('general'))
                    ⚠️ {{ $errors->first('general') }}
                @else
                    ⚠️ Lütfen aşağıdaki alanları kontrol et:
                    <ul style="margin:6px 0 0 18px;padding:0;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('public.signup.store') }}" id="signup-form">
            @csrf
            <input type="hidden" name="tier" value="{{ $selectedTier }}">
            {{-- Honeypot --}}
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">

            <div class="form-group">
                <label for="company_name">Firma Adı <span class="req">*</span></label>
                <input type="text" name="company_name" id="company_name"
                       value="{{ old('company_name') }}" required maxlength="120" autofocus
                       placeholder="Örn: ACME Eğitim Danışmanlığı">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="admin_name">Adın Soyadın <span class="req">*</span></label>
                    <input type="text" name="admin_name" id="admin_name"
                           value="{{ old('admin_name') }}" required maxlength="120"
                           placeholder="Ali Yılmaz">
                </div>
                <div class="form-group">
                    <label for="admin_phone">Telefon <span class="hint">(opsiyonel)</span></label>
                    <input type="tel" name="admin_phone" id="admin_phone"
                           value="{{ old('admin_phone') }}" maxlength="30"
                           placeholder="+90 555 123 4567">
                </div>
            </div>

            <div class="form-group">
                <label for="admin_email">İş E-postası <span class="req">*</span></label>
                <input type="email" name="admin_email" id="admin_email"
                       value="{{ old('admin_email') }}" required maxlength="190"
                       placeholder="ali@firma.com">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Şifre <span class="req">*</span></label>
                    <input type="password" name="password" id="password" required minlength="8" maxlength="120">
                    <div class="password-hint">En az 8 karakter — büyük harf + sayı önerilir</div>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Şifre (tekrar) <span class="req">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" maxlength="120">
                </div>
            </div>

            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="kvkk_accept" value="1" {{ old('kvkk_accept') ? 'checked' : '' }} required>
                    <span>
                        <a href="/legal/privacy" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kişisel verilerimin işlenmesini kabul ediyorum.
                    </span>
                </label>
            </div>

            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="terms_accept" value="1" {{ old('terms_accept') ? 'checked' : '' }} required>
                    <span>
                        <a href="/legal/terms" target="_blank">Kullanım Koşulları</a> ve <a href="/legal/imprint" target="_blank">Künye</a>'yi okudum, kabul ediyorum.
                    </span>
                </label>
            </div>

            <button type="submit" class="submit-btn" id="submit-btn">
                🚀 14 Gün Ücretsiz Başlat
            </button>

            <div class="alt-login">
                Zaten hesabın var mı? <a href="/login">Giriş yap</a>
            </div>
        </form>
    </div>

    {{-- ===== TIER PREVIEW (sağ) ===== --}}
    <aside class="tier-side">
        <div class="preview-card">
            <span class="label-pill">⭐ SEÇİLEN PLAN</span>
            <div class="tier-name">{{ $selectedTierData['label'] }}</div>

            <div class="price-block {{ $selectedTierData['mrr_eur'] == 0 ? 'zero' : '' }}">
                @if($selectedTierData['mrr_eur'] == 0)
                    <span class="amount">Ücretsiz</span>
                @else
                    <span class="amount">€{{ number_format($selectedTierData['mrr_eur'], 0, ',', '.') }}</span>
                    <span class="period">/ ay</span>
                @endif
            </div>
            <div class="billing-info">
                @if($selectedTier === 'trial')
                    14 gün ücretsiz · sonra plan seçimi
                @else
                    14 gün ücretsiz deneme · sonra €{{ number_format($selectedTierData['mrr_eur'], 0, ',', '.') }}/ay
                @endif
            </div>

            <div class="trial-bonus">
                <span class="ico">🎁</span>
                <div>
                    <strong>14 gün boyunca Gold özellikleri açık</strong>
                    <span>Tier'inden bağımsız olarak ilk 14 gün tüm Gold özelliklerini test edebilirsin.</span>
                </div>
            </div>

            <ul>
                <li>
                    @if($selectedTierData['limits']['students_max'] ?? null)
                        {{ number_format($selectedTierData['limits']['students_max']) }} aktif öğrenci
                    @else
                        Sınırsız öğrenci
                    @endif
                </li>
                <li>
                    @if($selectedTierData['limits']['doc_request_monthly'] ?? null)
                        Aylık {{ number_format($selectedTierData['limits']['doc_request_monthly']) }} belge talep linki
                    @else
                        Sınırsız belge talep linki
                    @endif
                </li>
                <li>Sınırsız danışman hesabı</li>
                <li>Almanya sunucu (KVKK + GDPR)</li>
                <li>Otomatik yedekleme</li>
                <li>E-posta + ticket destek</li>
            </ul>

            <a href="/fiyatlar" class="change-tier">Planı değiştir →</a>
        </div>

        <div class="trust-bar">
            <span>🔒 256-bit SSL</span>
            <span>🇩🇪 Almanya sunucu</span>
            <span>✓ KVKK + GDPR</span>
        </div>
    </aside>

</div>

<script>
(function(){
    var form = document.getElementById('signup-form');
    var btn = document.getElementById('submit-btn');
    form.addEventListener('submit', function(){
        btn.disabled = true;
        btn.textContent = '⏳ Hesabın oluşturuluyor...';
    });
})();
</script>

</body>
</html>
