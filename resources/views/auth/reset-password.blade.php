<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} — Şifre Sıfırla</title>
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
        .pwd-wrap { position: relative; }
        .pwd-wrap input { padding-right: 42px; }
        .pwd-toggle {
            position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
            background: transparent; border: none; cursor: pointer;
            width: 32px; height: 32px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: #6b7e99; padding: 0;
            transition: background .15s, color .15s;
        }
        .pwd-toggle:hover { background: rgba(31,102,209,.08); color: var(--primary); }
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
            <div class="pwd-wrap">
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <button type="button" class="pwd-toggle" aria-label="Şifreyi göster" onclick="
                    var i=document.getElementById('password');
                    var show = i.type === 'password';
                    i.type = show ? 'text' : 'password';
                    this.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
                    this.querySelector('.eye-open').style.display = show ? 'none' : 'block';
                    this.querySelector('.eye-closed').style.display = show ? 'block' : 'none';
                ">
                    <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <div class="hint">En az 8 karakter; büyük/küçük harf, rakam ve özel karakter içermeli.</div>
        </div>

        <div class="field">
            <label for="password_confirmation">Şifre Tekrar</label>
            <div class="pwd-wrap">
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                <button type="button" class="pwd-toggle" aria-label="Şifreyi göster" onclick="
                    var i=document.getElementById('password_confirmation');
                    var show = i.type === 'password';
                    i.type = show ? 'text' : 'password';
                    this.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
                    this.querySelector('.eye-open').style.display = show ? 'none' : 'block';
                    this.querySelector('.eye-closed').style.display = show ? 'block' : 'none';
                ">
                    <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">Şifremi Güncelle</button>
    </form>

    <div class="back-link">
        <a href="{{ route('login') }}">&larr; Giriş sayfasına dön</a>
    </div>
</div>
</body>
</html>
