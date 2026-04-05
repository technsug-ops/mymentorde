<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MentorDE — Şifre Sıfırla</title>
    <style>
        :root {
            --bg: #eef3fb; --panel: #ffffff; --line: #d8e2f0;
            --ink: #11243d; --muted: #5f7392;
            --primary: #1f66d1; --primary-2: #1149a8;
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
        h1 { margin: 0 0 24px; font-size: 20px; font-weight: 700; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .field { margin-bottom: 16px; }
        input[type=email], input[type=password] {
            width: 100%; padding: 10px 14px; border: 1.5px solid var(--line);
            border-radius: 8px; font-size: 15px; color: var(--ink); outline: none;
            transition: border-color .15s;
        }
        input[type=email]:focus, input[type=password]:focus { border-color: var(--primary); }
        .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .btn-submit {
            display: block; width: 100%; margin-top: 8px; padding: 12px;
            background: var(--primary); color: #fff; border: none; border-radius: 8px;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-submit:hover { background: var(--primary-2); }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .alert.err { background: var(--danger-bg); border: 1px solid var(--danger-line); color: var(--danger-text); }
        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }
        .back-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">Mentor<span>DE</span></div>
    <h1>Yeni Şifre Belirle</h1>

    @if ($errors->any())
        <div class="alert err">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" value="{{ old('email', request()->query('email', '')) }}"
                   placeholder="ornek@email.com" required>
        </div>

        <div class="field">
            <label for="password">Yeni Şifre</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <div class="hint">En az 8 karakter; büyük/küçük harf, rakam ve özel karakter içermeli.</div>
        </div>

        <div class="field">
            <label for="password_confirmation">Şifre Tekrar</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-submit">Şifremi Güncelle</button>
    </form>

    <div class="back-link">
        <a href="{{ route('login') }}">&larr; Giriş sayfasına dön</a>
    </div>
</div>
</body>
</html>
