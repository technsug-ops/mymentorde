<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} Login</title>
    @php $pt = $publicTheme ?? \App\Support\PublicTheme::resolve(); @endphp
    <style>
        :root {
            --bg: {{ $pt['body_bg_lin1'] }};
            --panel: #ffffff;
            --line: {{ $pt['line'] }};
            --line-strong: {{ $pt['line_strong'] }};
            --ink: {{ $pt['text'] }};
            --muted: {{ $pt['muted'] }};
            --primary: {{ $pt['primary'] }};
            --primary-2: {{ $pt['primary_dark'] }};
            --navy: {{ $pt['primary_deep'] }};
            --danger-bg: #fff0f0;
            --danger-line: #efb0b0;
            --danger-text: #a32323;
            --ok-bg: #eefaf1;
            --ok-line: #bfe7c8;
            --ok-text: #22643a;
            --shadow: 0 18px 40px rgba({{ $pt['focus_shadow_rgb'] }}, .14);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 10%, {{ $pt['body_bg_r1'] }} 0, transparent 38%),
                radial-gradient(circle at 90% 15%, {{ $pt['body_bg_r2'] }} 0, transparent 35%),
                linear-gradient(160deg, {{ $pt['body_bg_lin1'] }} 0%, {{ $pt['body_bg_lin2'] }} 100%);
            padding: 24px;
            display: grid;
            place-items: center;
        }
        .shell {
            width: 100%;
            max-width: 1040px;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 18px;
            align-items: stretch;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        .brand {
            padding: 24px;
            background:
                linear-gradient(180deg, {{ $pt['brand_gradient_1'] }}, {{ $pt['brand_gradient_2'] }}),
                {{ $pt['brand_fallback'] }};
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .brand::before,
        .brand::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            pointer-events: none;
        }
        .brand::before { width: 260px; height: 260px; right: -70px; top: -90px; }
        .brand::after { width: 180px; height: 180px; left: -60px; bottom: -70px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,.22);
            background: rgba(255,255,255,.08);
            color: {{ $pt['brand_text_soft'] }};
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            margin-bottom: 16px;
        }
        .brand h1 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.15;
            letter-spacing: -.3px;
        }
        .brand p {
            margin: 0 0 18px;
            color: {{ $pt['brand_text_soft'] }};
            line-height: 1.45;
            max-width: 42ch;
        }
        .feature-list {
            margin-top: 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .feature-icon {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .feature-text .ft {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .feature-text .fd {
            color: #bdd3f7;
            font-size: 12.5px;
            line-height: 1.4;
        }
        .brand-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.12);
            color: #8aaad8;
            font-size: 12px;
        }
        .auth {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth h2 {
            margin: 0 0 6px;
            font-size: 28px;
            letter-spacing: -.3px;
        }
        .sub {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.4;
        }
        .flash, .error {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .flash {
            background: var(--ok-bg);
            border: 1px solid var(--ok-line);
            color: var(--ok-text);
        }
        .error {
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            color: var(--danger-text);
        }
        form { margin: 0; }
        .field {
            margin-bottom: 12px;
        }
        label {
            display: block;
            font-size: 13px;
            color: #324a6a;
            margin-bottom: 6px;
            font-weight: 600;
        }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            border: 1px solid var(--line-strong);
            border-radius: 10px;
            padding: 12px 13px;
            font-size: 14px;
            background: #fbfdff;
            color: var(--ink);
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .pwd-wrap { position: relative; }
        .pwd-wrap input { padding-right: 42px; }
        .pwd-toggle {
            position: absolute; top: 50%; right: 8px; transform: translateY(-50%);
            background: transparent; border: none; cursor: pointer;
            width: 32px; height: 32px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: #6b7e99; padding: 0;
            transition: background .15s, color .15s;
        }
        .pwd-toggle:hover { background: rgba({{ $pt['focus_shadow_rgb'] }},.08); color: var(--primary); }
        .pwd-toggle:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba({{ $pt['focus_shadow_rgb'] }},.14);
        }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 4px 0 14px;
        }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .remember input {
            margin: 0;
        }
        .ghost-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .ghost-link:hover { text-decoration: underline; }
        .primary-btn {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(180deg, var(--primary), var(--primary-2));
            color: #fff;
            cursor: pointer;
            transition: transform .05s ease, box-shadow .15s ease, filter .15s ease;
            box-shadow: 0 8px 18px {{ $pt['submit_shadow'] }};
        }
        .primary-btn:hover { filter: brightness(1.03); }
        .primary-btn:active { transform: translateY(1px); }
        @media (max-width: 900px) {
            body { padding: 14px; }
            .shell {
                grid-template-columns: 1fr;
                max-width: 560px;
            }
            .brand-grid { grid-template-columns: 1fr; }
            .auth, .brand { padding: 18px; }
            .brand h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="panel brand" aria-label="Platform bilgisi">
            <div class="badge">{{ config('brand.name', 'MentorDE') }} — {{ config('brand.tagline', 'Almanya Üniversite Danışmanlığı') }}</div>
            <h1>Almanya'da üniversite hayalinizi gerçeğe dönüştürün</h1>
            <p>
                Doğru üniversite seçiminden vize başvurusuna, barınmadan burs rehberine kadar her adımda yanınızdayız.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">🏛</div>
                    <div class="feature-text">
                        <div class="ft">400+ Alman Üniversitesi</div>
                        <div class="fd">TU Berlin, LMU, TUM ve daha yüzlerce üniversiteye uzman destekli başvuru.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📋</div>
                    <div class="feature-text">
                        <div class="ft">Uçtan Uca Başvuru Takibi</div>
                        <div class="fd">Başvurunuzun her aşamasını — kabul, vize, konaklama — tek ekrandan yönetin.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🗣</div>
                    <div class="feature-text">
                        <div class="ft">Kişisel Danışmanınız</div>
                        <div class="fd">Size özel atanan Almanya mezunu danışmanınız, sürecin her anında yanınızda.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✅</div>
                    <div class="feature-text">
                        <div class="ft">Yüzlerce Başarılı Öğrenci</div>
                        <div class="fd">{{ config('brand.name', 'MentorDE') }} ile Almanya'ya yerleşen öğrenciler arasına siz de katılın.</div>
                    </div>
                </div>
            </div>
            {{-- CTA kartı - apply'dan gelmemiş kullanıcılara --}}
            @unless(request()->query('from_apply'))
            <a href="/apply" class="cta-card" style="display:block;margin-top:20px;padding:18px 20px;background:linear-gradient(135deg,{{ $pt['primary'] }},{{ $pt['primary_dark'] }});border-radius:14px;text-decoration:none;color:#fff;box-shadow:0 8px 24px rgba({{ $pt['focus_shadow_rgb'] }},.35);transition:transform .15s,box-shadow .15s;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="font-size:32px;flex-shrink:0;">🎓</div>
                    <div style="flex:1;">
                        <div style="font-size:16px;font-weight:800;margin-bottom:3px;">Ücretsiz Başvuru</div>
                        <div style="font-size:12px;opacity:.95;line-height:1.4;">3 dakikada başvurunu tamamla, danışmanınla hemen tanış!</div>
                    </div>
                    <div style="font-size:22px;flex-shrink:0;">→</div>
                </div>
            </a>
            @endunless
        </section>

        <section class="panel auth" aria-label="Giriş formu">
            <h2>{{ config('brand.name', 'MentorDE') }} Login</h2>
            <p class="sub">Hesabınla giriş yap. Sistem seni rolüne göre ilgili panele yönlendirir.</p>

            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            @php
                $prefillEmail = old('email') ?: request()->query('email', '');
                $cameFromApply = (bool) request()->query('from_apply');
            @endphp
            <form method="POST" action="/login" autocomplete="off">
                @csrf

                <div class="field">
                    <label for="email">E-posta</label>
                    <input id="email" type="email" name="email" placeholder="ornek@example.com" value="{{ $prefillEmail }}" required autofocus autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Şifre</label>
                    <div class="pwd-wrap">
                        <input id="password" type="password" name="password" placeholder="Şifrenizi girin" required autocomplete="off">
                        <button type="button" class="pwd-toggle" aria-label="Şifreyi göster" onclick="
                            var i=document.getElementById('password');
                            var show = i.type === 'password';
                            i.type = show ? 'text' : 'password';
                            this.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
                            this.querySelector('.eye-open').style.display = show ? 'none' : 'block';
                            this.querySelector('.eye-closed').style.display = show ? 'block' : 'none';
                        ">
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <label class="remember"><input type="checkbox" name="remember" value="1"> Beni hatırla</label>
                    @if (Route::has('password.request'))
                        <a class="ghost-link" href="{{ route('password.request') }}">Şifremi unuttum</a>
                    @endif
                </div>

                <button class="primary-btn" type="submit">Giriş Yap</button>
            </form>

            {{-- Google ile Giriş --}}
            <div style="display:flex;align-items:center;gap:10px;margin:18px 0 12px;color:#9ca3af;font-size:12px;">
                <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                <span>veya</span>
                <div style="flex:1;height:1px;background:#e5e7eb;"></div>
            </div>
            <a href="{{ route('auth.google.redirect') }}"
               style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:11px 14px;border:1.5px solid #dadce0;border-radius:10px;background:#fff;color:#3c4043;font-size:14px;font-weight:600;text-decoration:none;transition:all .15s;"
               onmouseover="this.style.borderColor='#b8bdc4';this.style.boxShadow='0 2px 6px rgba(0,0,0,.08)';"
               onmouseout="this.style.borderColor='#dadce0';this.style.boxShadow='none';">
                <svg width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                <span>Google ile Devam Et</span>
            </a>

            @unless(request()->query('from_apply'))
            {{-- Ayırıcı --}}
            <div style="display:flex;align-items:center;gap:10px;margin:18px 0 12px;color:#9ca3af;font-size:12px;">
                <div style="flex:1;height:1px;background:#e5e7eb;"></div>
                <span>veya</span>
                <div style="flex:1;height:1px;background:#e5e7eb;"></div>
            </div>

            {{-- Apply CTA --}}
            <a href="/apply" class="apply-cta-btn" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;border:2px solid {{ $pt['primary'] }};border-radius:10px;background:{{ $pt['primary_soft'] }};color:{{ $pt['primary'] }};font-size:14px;font-weight:700;text-decoration:none;transition:all .15s;"
                data-hover-bg="{{ $pt['primary'] }}"
                data-base-bg="{{ $pt['primary_soft'] }}"
                data-base-color="{{ $pt['primary'] }}"
                onmouseover="this.style.background=this.dataset.hoverBg;this.style.color='#fff';"
                onmouseout="this.style.background=this.dataset.baseBg;this.style.color=this.dataset.baseColor;">
                ✨ Ücretsiz Hesap Oluştur
            </a>
            <div style="text-align:center;margin-top:8px;font-size:11px;color:#9ca3af;">
                Henüz hesabın yok mu? 3 dakikada başvur!
            </div>
            @endunless

            {{-- Yasal Linkler --}}
            <div style="margin-top:22px;padding-top:14px;border-top:1px solid #eef2f7;text-align:center;font-size:12px;color:#9ca3af;">
                <a href="{{ route('legal.privacy') }}" style="color:#6b7280;text-decoration:none;margin:0 6px;">Gizlilik Politikası</a>
                ·
                <a href="{{ route('legal.terms') }}" style="color:#6b7280;text-decoration:none;margin:0 6px;">Kullanım Koşulları</a>
            </div>

        </section>
    </div>

</body>
</html>
