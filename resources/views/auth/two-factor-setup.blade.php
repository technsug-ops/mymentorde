<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2FA Kurulumu — MentorDE</title>
    <style>
        :root{--bg:#eef3fb;--panel:#ffffff;--line:#d8e2f0;--ink:#11243d;--muted:#5f7392;--primary:#1f66d1;--ok:#16a34a;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px;}
        .panel{background:var(--panel);border-radius:16px;padding:44px 40px;max-width:480px;width:100%;box-shadow:0 8px 40px rgba(31,102,209,.10);}
        h1{font-size:1.35rem;font-weight:700;color:var(--ink);margin-bottom:6px;}
        .subtitle{font-size:.9rem;color:var(--muted);margin-bottom:28px;line-height:1.6;}
        .step{display:flex;gap:14px;align-items:flex-start;margin-bottom:22px;}
        .step-num{width:28px;height:28px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;flex-shrink:0;margin-top:2px;}
        .step-body h3{font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:4px;}
        .step-body p{font-size:.87rem;color:var(--muted);line-height:1.55;}
        .qr-area{background:#f8faff;border:1.5px solid var(--line);border-radius:12px;padding:16px;text-align:center;margin:10px 0 6px;}
        .qr-placeholder{font-size:.8rem;color:var(--muted);margin-bottom:8px;}
        .secret-box{background:#f0f4ff;border:1px solid var(--line);border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.95rem;letter-spacing:.08em;color:var(--ink);word-break:break-all;margin-top:10px;}
        .code-input{width:100%;padding:14px;font-size:1.8rem;font-weight:700;letter-spacing:.45rem;text-align:center;border:2px solid var(--line);border-radius:12px;color:var(--ink);outline:none;transition:border-color .2s;}
        .code-input:focus{border-color:var(--primary);}
        .btn{display:block;width:100%;padding:13px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:.97rem;font-weight:600;cursor:pointer;margin-top:14px;}
        .btn:hover{background:#1854b4;}
        .error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:.87rem;color:#dc2626;margin-bottom:14px;}
        .warning-box{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;font-size:.85rem;color:#92400e;margin-bottom:22px;line-height:1.55;}
    </style>
</head>
<body>
<div class="panel">
    <h1>🔐 İki Faktörlü Doğrulama Kurulumu</h1>
    <p class="subtitle">Hesabınız için 2FA zorunludur. Aşağıdaki adımları tamamlayın.</p>

    <div class="warning-box">
        ⚠️ Bu işlemi bir kez yapmanız yeterli. Google Authenticator, Microsoft Authenticator veya Authy uygulamasını kullanabilirsiniz.
    </div>

    <div class="step">
        <div class="step-num">1</div>
        <div class="step-body">
            <h3>Authenticator Uygulamasını Açın</h3>
            <p>Telefonunuzda Google Authenticator veya benzer bir uygulama açın, "+" veya "Hesap ekle" butonuna basın.</p>
        </div>
    </div>

    <div class="step">
        <div class="step-num">2</div>
        <div class="step-body">
            <h3>QR Kodu Tarayın veya Kodu Girin</h3>
            <p>Aşağıdaki QR kodu kameranızla tarayın. Tarayamıyorsanız kodu manuel girin.</p>
            <div class="qr-area">
                @php
                    $qrSvg = rescue(function() use ($qrUrl) {
                        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                        );
                        return (new \BaconQrCode\Writer($renderer))->writeString($qrUrl);
                    }, null);
                @endphp
                @if($qrSvg)
                    <div style="display:inline-block;background:#fff;padding:10px;border-radius:8px;border:1px solid var(--line);">
                        {!! $qrSvg !!}
                    </div>
                @endif
                <div style="font-size:.8rem;color:var(--muted);margin-top:10px;margin-bottom:4px;">veya bu kodu uygulamanıza manuel girin:</div>
                <div class="secret-box">{{ chunk_split($secret, 4, ' ') }}</div>
            </div>
        </div>
    </div>

    <div class="step">
        <div class="step-num">3</div>
        <div class="step-body">
            <h3>Kodu Doğrulayın</h3>
            <p>Uygulamada görünen 6 haneli kodu aşağıya girin.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('2fa.setup.confirm') }}">
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
        <button type="submit" class="btn">Kurulumu Tamamla →</button>
    </form>
</div>
<script>
document.querySelector('.code-input').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});
</script>
</body>
</html>
