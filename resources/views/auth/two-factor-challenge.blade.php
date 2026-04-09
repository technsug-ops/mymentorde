<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İki Faktörlü Doğrulama — {{ config('brand.name', 'MentorDE') }}</title>
    <style>
        :root{--bg:#eef3fb;--panel:#ffffff;--line:#d8e2f0;--ink:#11243d;--muted:#5f7392;--primary:#1f66d1;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px;}
        .panel{background:var(--panel);border-radius:16px;padding:44px 40px;max-width:420px;width:100%;box-shadow:0 8px 40px rgba(31,102,209,.10);text-align:center;}
        .icon{font-size:3rem;margin-bottom:20px;}
        h1{font-size:1.35rem;font-weight:700;color:var(--ink);margin-bottom:8px;}
        p{font-size:.92rem;color:var(--muted);line-height:1.6;margin-bottom:28px;}
        .code-input{width:100%;padding:16px;font-size:2rem;font-weight:700;letter-spacing:.5rem;text-align:center;border:2px solid var(--line);border-radius:12px;color:var(--ink);outline:none;transition:border-color .2s;margin-bottom:16px;}
        .code-input:focus{border-color:var(--primary);}
        .btn{display:block;width:100%;padding:13px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:.97rem;font-weight:600;cursor:pointer;}
        .btn:hover{background:#1854b4;}
        .error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:.87rem;color:#dc2626;margin-bottom:16px;text-align:left;}
        .divider{border:none;border-top:1px solid var(--line);margin:20px 0;}
        .logout-link{font-size:.83rem;color:var(--muted);text-decoration:none;}
        .logout-link:hover{color:var(--ink);}
    </style>
</head>
<body>
<div class="panel">
    <div class="icon">🔐</div>
    <h1>İki Faktörlü Doğrulama</h1>
    <p>Authenticator uygulamanızdan 6 haneli kodu girin.</p>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('2fa.challenge.verify') }}">
        @csrf
        <input
            type="text"
            name="code"
            class="code-input"
            placeholder="000000"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            autofocus
        >
        <button type="submit" class="btn">Doğrula</button>
    </form>

    <hr class="divider">

    <form method="POST" action="/logout">
        @csrf
        <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;">Farklı hesapla giriş yap</button>
    </form>
</div>
<script>
// Otomatik sayı formatı: sadece rakam kabul et
document.querySelector('.code-input').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length === 6) this.form.submit();
});
</script>
</body>
</html>
