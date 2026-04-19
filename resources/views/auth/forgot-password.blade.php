<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} — Şifremi Unuttum</title>
    <style>
        :root {
            --bg: #eef3fb; --panel: #ffffff; --line: #d8e2f0;
            --ink: #11243d; --muted: #5f7392;
            --primary: #1f66d1; --primary-2: #1149a8;
            --ok-bg: #eefaf1; --ok-line: #bfe7c8; --ok-text: #22643a;
            --danger-bg: #fff0f0; --danger-line: #efb0b0; --danger-text: #a32323;
            --shadow: 0 18px 40px rgba(15,30,60,.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--ink); background: linear-gradient(160deg, #ecf2fb 0%, #f7faff 100%);
            padding: 24px; display: grid; place-items: center;
        }
        .card {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 14px; box-shadow: var(--shadow);
            padding: 40px 44px; width: 100%; max-width: 440px;
        }
        .logo { font-size: 22px; font-weight: 800; color: #1a3c6b; margin-bottom: 6px; }
        .logo span { color: var(--primary); }
        h1 { margin: 0 0 8px; font-size: 20px; font-weight: 700; }
        p.desc { margin: 0 0 24px; color: var(--muted); font-size: 14px; line-height: 1.6; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input[type=email] {
            width: 100%; padding: 10px 14px; border: 1.5px solid var(--line);
            border-radius: 8px; font-size: 15px; color: var(--ink); outline: none;
            transition: border-color .15s;
        }
        input[type=email]:focus { border-color: var(--primary); }
        .btn-submit {
            display: block; width: 100%; margin-top: 18px; padding: 12px;
            background: var(--primary); color: #fff; border: none; border-radius: 8px;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-submit:hover { background: var(--primary-2); }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .alert.ok { background: var(--ok-bg); border: 1px solid var(--ok-line); color: var(--ok-text); }
        .alert.err { background: var(--danger-bg); border: 1px solid var(--danger-line); color: var(--danger-text); }
        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }
        .back-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    @php
        $fpBrand   = $brandName ?? config('brand.name', 'MentorDE');
        $fpLogoUrl = $brandLogoUrl ?? '';
        $fpLogoBg  = $brandLogoBg ?? 'light';
        $fpLogoBgStyle = match($fpLogoBg) {
            'dark'        => 'background:#1a1a2e;padding:8px 12px;border-radius:8px;',
            'transparent' => '',
            default       => 'background:#fff;padding:8px 12px;border-radius:8px;',
        };
    @endphp
    <div class="logo" style="margin-bottom:16px;">
        @if($fpLogoUrl)
            <div style="display:inline-block;{{ $fpLogoBgStyle }}">
                <img src="{{ $fpLogoUrl }}" alt="{{ $fpBrand }}" style="height:40px;width:auto;max-width:160px;object-fit:contain;display:block;">
            </div>
        @else
            Mentor<span>DE</span>
        @endif
    </div>
    <h1>Şifremi Unuttum</h1>
    <p class="desc">E-posta adresinizi girin. Şifre sıfırlama bağlantısı göndereceğiz.</p>

    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert err">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">E-posta Adresi</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               placeholder="ornek@email.com" required autofocus>
        <button type="submit" class="btn-submit">Bağlantı Gönder</button>
    </form>

    <div class="back-link">
        <a href="{{ route('login') }}">&larr; Giriş sayfasına dön</a>
    </div>
</div>
</body>
</html>
