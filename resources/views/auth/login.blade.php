<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} Login</title>
    <style>
        :root {
            --bg: #eef3fb;
            --panel: #ffffff;
            --line: #d8e2f0;
            --line-strong: #c6d5ea;
            --ink: #11243d;
            --muted: #5f7392;
            --primary: #1f66d1;
            --primary-2: #1149a8;
            --navy: #132f59;
            --danger-bg: #fff0f0;
            --danger-line: #efb0b0;
            --danger-text: #a32323;
            --ok-bg: #eefaf1;
            --ok-line: #bfe7c8;
            --ok-text: #22643a;
            --shadow: 0 18px 40px rgba(15, 30, 60, .12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 10%, #dce9ff 0, transparent 38%),
                radial-gradient(circle at 90% 15%, #e6f2ff 0, transparent 35%),
                linear-gradient(160deg, #ecf2fb 0%, #f7faff 100%);
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
                linear-gradient(180deg, rgba(19,47,89,.98), rgba(16,37,72,.97)),
                #10264a;
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
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.06);
            color: #dbe8ff;
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
            color: #d9e6fa;
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
        input[type="password"] {
            width: 100%;
            border: 1px solid var(--line-strong);
            border-radius: 10px;
            padding: 12px 13px;
            font-size: 14px;
            background: #fbfdff;
            color: var(--ink);
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        input:focus {
            outline: none;
            border-color: #7faaf2;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(31,102,209,.10);
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
            box-shadow: 0 8px 18px rgba(31,102,209,.22);
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
            <div class="brand-footer">
                Hesabınız yok mu? &nbsp;·&nbsp; <a href="/apply" style="color:#7fb3f5;text-decoration:none;">Ücretsiz başvurun →</a>
            </div>
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

            <form method="POST" action="/login">
                @csrf

                <div class="field">
                    <label for="email">E-posta</label>
                    <input id="email" type="email" name="email" placeholder="ornek@example.com" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Şifre</label>
                    <input id="password" type="password" name="password" placeholder="Şifrenizi girin" required>
                </div>

                <div class="row">
                    <label class="remember"><input type="checkbox" name="remember" value="1"> Beni hatırla</label>
                    @if (Route::has('password.request'))
                        <a class="ghost-link" href="{{ route('password.request') }}">Şifremi unuttum</a>
                    @endif
                </div>

                <button class="primary-btn" type="submit">Giriş Yap</button>
            </form>

        </section>
    </div>

</body>
</html>
