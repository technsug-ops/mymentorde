<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} — Yeni Şifre Belirle</title>
    @include('partials.favicon')
    <style>
        :root {
            --bg: #eef3fb; --panel: #ffffff; --line: #d8e2f0;
            --ink: #11243d; --muted: #5f7392;
            --primary: #1f66d1; --primary-2: #1149a8;
            --danger-bg: #fff0f0; --danger-line: #efb0b0; --danger-text: #a32323;
            --warning-bg: #fffbeb; --warning-line: #fde68a; --warning-text: #92400e;
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
            padding: 40px 44px; width: 100%; max-width: 460px;
        }
        .logo { font-size: 22px; font-weight: 800; color: #1a3c6b; margin-bottom: 6px; }
        h1 { margin: 0 0 8px; font-size: 20px; font-weight: 700; }
        .sub { font-size: 14px; color: var(--muted); margin-bottom: 22px; line-height:1.5; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .field { margin-bottom: 16px; }
        input[type=password], input[type=email] {
            width: 100%; padding: 10px 14px; border: 1.5px solid var(--line);
            border-radius: 8px; font-size: 15px; color: var(--ink); outline: none;
            transition: border-color .15s;
        }
        input[type=password]:focus, input[type=email]:focus { border-color: var(--primary); }
        .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .btn-submit {
            display: block; width: 100%; margin-top: 8px; padding: 12px;
            background: var(--primary); color: #fff; border: none; border-radius: 8px;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-submit:hover { background: var(--primary-2); }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .alert.err { background: var(--danger-bg); border: 1px solid var(--danger-line); color: var(--danger-text); }
        .alert.warn { background: var(--warning-bg); border: 1px solid var(--warning-line); color: var(--warning-text); }
        .logout-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }
        .logout-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .logout-link a:hover { text-decoration: underline; }
        ul.errors { margin: 6px 0 0; padding-left: 20px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">{{ config('brand.name', 'MentorDE') }}</div>
    <h1>🔐 Yeni Şifre Belirle</h1>
    <p class="sub">
        Şifren yönetici tarafından sıfırlandı. Devam etmeden önce kendi belirleyeceğin
        yeni bir şifre seçmelisin. Bu şifre sadece sende olacak.
    </p>

    @if($errors->any())
        <div class="alert err">
            <strong>Hata:</strong>
            <ul class="errors">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert warn">{{ session('warning') }}</div>
    @endif

    <form method="POST" action="{{ route('password.change-required.update') }}" autocomplete="off">
        @csrf

        <div class="field">
            <label for="email">E-posta</label>
            <input type="email" id="email" value="{{ auth()->user()->email }}" readonly disabled
                   style="background:#f5f7fb;color:var(--muted);cursor:not-allowed;">
        </div>

        <div class="field">
            <label for="password">Yeni Şifre</label>
            <input type="password" id="password" name="password" required
                   minlength="8" autocomplete="new-password"
                   placeholder="En az 8 karakter, harf + rakam">
            <div class="hint">En az 8 karakter, harf ve rakam içermeli.</div>
        </div>

        <div class="field">
            <label for="password_confirmation">Yeni Şifre (tekrar)</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   minlength="8" autocomplete="new-password"
                   placeholder="Aynı şifreyi tekrar yaz">
        </div>

        <button type="submit" class="btn-submit">Şifreyi Güncelle ve Devam Et</button>
    </form>

    <p class="logout-link">
        Yanlış hesap mı? <a href="{{ url('/logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Çıkış Yap</a>
    </p>
    <form id="logout-form" method="POST" action="{{ url('/logout') }}" style="display:none;">
        @csrf
    </form>
</div>
</body>
</html>
