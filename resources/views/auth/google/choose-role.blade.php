<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rol Seç — {{ config('brand.name', 'MentorDE') }}</title>
    @php $pt = $publicTheme ?? \App\Support\PublicTheme::resolve(); @endphp
    <style>
        :root {
            --bg: {{ $pt['body_bg_lin1'] }};
            --panel: #ffffff;
            --line: {{ $pt['line'] }};
            --ink: {{ $pt['text'] }};
            --muted: {{ $pt['muted'] }};
            --primary: {{ $pt['primary'] }};
            --primary-2: {{ $pt['primary_dark'] }};
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
            padding: 32px 16px;
            display: grid;
            place-items: center;
        }
        .shell { width: 100%; max-width: 880px; }
        .header {
            text-align: center;
            margin-bottom: 28px;
        }
        .header h1 {
            margin: 0 0 8px;
            font-size: 26px;
            color: var(--ink);
        }
        .header p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
        }
        .header .ident {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 12px;
            background: rgba({{ $pt['focus_shadow_rgb'] }}, .08);
            border-radius: 999px;
            font-size: 13px;
            color: var(--ink);
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba({{ $pt['focus_shadow_rgb'] }}, .1);
            display: grid;
            place-items: center;
            font-size: 24px;
        }
        .card h2 {
            margin: 0;
            font-size: 19px;
            color: var(--ink);
        }
        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
            flex: 1;
        }
        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: transform .15s ease, box-shadow .2s ease;
            width: 100%;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-2);
        }
        .btn-secondary {
            background: #fff;
            color: var(--primary);
            border-color: var(--primary);
        }
        .btn-secondary:hover {
            background: rgba({{ $pt['focus_shadow_rgb'] }}, .08);
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted);
        }
        .footer a {
            color: var(--primary);
            text-decoration: none;
        }
        .footer a:hover { text-decoration: underline; }
        form { margin: 0; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="header">
            <h1>Hangi rolde devam etmek istersin?</h1>
            <p>Google hesabınla ilk kez giriş yapıyorsun. Aşağıdan kendine uygun olanı seç.</p>
            <span class="ident">{{ $email }}</span>
        </div>

        <div class="grid">
            <form method="POST" action="{{ route('auth.google.choose-role.submit') }}">
                @csrf
                <input type="hidden" name="role" value="guest">
                <div class="card">
                    <div class="icon">🎓</div>
                    <h2>Aday Öğrenci</h2>
                    <p>Almanya'da üniversite okumayı düşünüyorum. Programları keşfetmek, başvuru sürecini yönetmek ve mentorluk almak istiyorum.</p>
                    <button type="submit" class="btn btn-primary">Aday Öğrenci olarak devam et</button>
                </div>
            </form>

            <form method="POST" action="{{ route('auth.google.choose-role.submit') }}">
                @csrf
                <input type="hidden" name="role" value="dealer">
                <div class="card">
                    <div class="icon">🤝</div>
                    <h2>Partner Bayi</h2>
                    <p>MentorDE satış ortağı/bayi olarak öğrenci yönlendirmek istiyorum. Başvuru formunu doldurup onay sürecine başlayacağım.</p>
                    <button type="submit" class="btn btn-secondary">Partner olarak başvur</button>
                </div>
            </form>
        </div>

        <div class="footer">
            Yanlış mı oldu? <a href="/login">Giriş ekranına dön</a>
        </div>
    </div>
</body>
</html>
