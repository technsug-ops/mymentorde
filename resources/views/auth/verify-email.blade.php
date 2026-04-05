<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-posta Doğrulama — MentorDE</title>
    <style>
        :root{--bg:#eef3fb;--panel:#ffffff;--line:#d8e2f0;--ink:#11243d;--muted:#5f7392;--primary:#1f66d1;--ok:#16a34a;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px;}
        .panel{background:var(--panel);border-radius:16px;padding:44px 40px;max-width:460px;width:100%;box-shadow:0 8px 40px rgba(31,102,209,.10);text-align:center;}
        .icon{font-size:3rem;margin-bottom:20px;}
        h1{font-size:1.4rem;font-weight:700;color:var(--ink);margin-bottom:10px;}
        p{font-size:.93rem;color:var(--muted);line-height:1.65;margin-bottom:20px;}
        .email-badge{display:inline-block;background:#eef3fb;border:1px solid var(--line);border-radius:8px;padding:6px 14px;font-size:.88rem;color:var(--ink);font-weight:600;margin-bottom:24px;}
        .btn{display:block;width:100%;padding:13px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:.97rem;font-weight:600;cursor:pointer;text-decoration:none;margin-bottom:12px;}
        .btn:hover{background:#1854b4;}
        .btn.secondary{background:transparent;border:1.5px solid var(--line);color:var(--ink);}
        .btn.secondary:hover{background:var(--bg);}
        .alert{padding:10px 14px;border-radius:8px;font-size:.87rem;margin-bottom:16px;}
        .alert.ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
        .divider{border:none;border-top:1px solid var(--line);margin:20px 0;}
        .footer{font-size:.8rem;color:var(--muted);margin-top:20px;}
    </style>
</head>
<body>
<div class="panel">
    <div class="icon">✉️</div>
    <h1>E-posta Adresinizi Doğrulayın</h1>
    <p>Hesabınıza erişmeden önce e-posta adresinizi doğrulamanız gerekiyor. Az önce bir doğrulama bağlantısı gönderdik.</p>

    <div class="email-badge">{{ $user->email }}</div>

    @if(session('status') === 'verification-link-sent')
        <div class="alert ok">Doğrulama e-postası yeniden gönderildi. Gelen kutunuzu kontrol edin.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn">Doğrulama Bağlantısını Yeniden Gönder</button>
    </form>

    <hr class="divider">

    <form method="POST" action="/logout">
        @csrf
        <button type="submit" class="btn secondary">Çıkış Yap</button>
    </form>

    <p class="footer">E-posta gelmiyorsa spam klasörünüzü kontrol edin.<br>Sorun devam ederse destek ekibimizle iletişime geçin.</p>
</div>
</body>
</html>
