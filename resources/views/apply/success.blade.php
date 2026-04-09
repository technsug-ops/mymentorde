<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Başvuru Alındı — {{ config('brand.name', 'MentorDE') }}</title>
    <style>
        :root {
            --bg:      #eef3fb;
            --panel:   #ffffff;
            --line:    #d8e2f0;
            --line-s:  #c6d5ea;
            --ink:     #11243d;
            --muted:   #5f7392;
            --primary: #1f66d1;
            --primary2:#1149a8;
            --navy:    #132f59;
            --shadow:  0 18px 48px rgba(15,30,60,.13);
            --ok-bg:   #eefaf1; --ok-line:#bfe7c8; --ok-text:#22643a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 12%, #dce9ff 0, transparent 36%),
                radial-gradient(circle at 92% 18%, #e6f2ff 0, transparent 32%),
                linear-gradient(160deg, #ecf2fb 0%, #f7faff 100%);
            padding: 24px;
            display: grid;
            place-items: center;
        }
        .shell {
            width: 100%;
            max-width: 1080px;
            display: grid;
            grid-template-columns: .88fr 1.12fr;
            gap: 18px;
            align-items: stretch;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        /* ── SOL: MARKA PANELİ ───────────────────────────── */
        .brand-panel {
            padding: 32px 28px;
            background:
                linear-gradient(180deg, rgba(19,47,89,.98), rgba(14,32,64,.97)),
                #0e2040;
            color: #fff;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255,255,255,.055);
            pointer-events: none;
        }
        .brand-panel::before { width: 280px; height: 280px; right: -80px; top: -100px; }
        .brand-panel::after  { width: 200px; height: 200px; left: -70px; bottom: -80px; }

        .brand-logo { margin-bottom: 20px; position: relative; z-index: 1; }
        .brand-logo img { height: 44px; width: auto; display: block; }
        .brand-logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.5px;
        }
        .brand-logo-text span { color: #f59e0b; }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.07);
            color: #cde0ff;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 18px;
            width: fit-content;
            position: relative; z-index: 1;
        }
        .brand-panel h1 {
            margin: 0 0 12px;
            font-size: 26px;
            line-height: 1.2;
            letter-spacing: -.4px;
            position: relative; z-index: 1;
        }
        .brand-panel p {
            margin: 0 0 24px;
            color: #d2e4fa;
            line-height: 1.5;
            font-size: 14px;
            position: relative; z-index: 1;
        }
        .steps {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative; z-index: 1;
            margin-top: auto;
        }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: 1px solid rgba(255,255,255,.11);
            background: rgba(255,255,255,.05);
            border-radius: 12px;
            padding: 12px;
        }
        .step-icon {
            flex-shrink: 0;
            width: 28px; height: 28px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }
        .step.done .step-icon { background: rgba(34,100,58,.55); border-color: rgba(100,200,130,.3); }
        .step-txt .t { font-size: 13px; font-weight: 600; color: #e8f2ff; margin-bottom: 2px; }
        .step-txt .s { font-size: 12px; color: #9db8da; }
        .step.done .step-txt .t { color: #a5e8bf; }

        /* ── SAĞ: İÇERİK PANELİ ───────────────────────────── */
        .content-panel {
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--ok-bg);
            border: 1px solid var(--ok-line);
            color: var(--ok-text);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
            width: fit-content;
        }
        .content-panel h2 {
            margin: 0 0 6px;
            font-size: 28px;
            letter-spacing: -.4px;
        }
        .greeting {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Token kutusu */
        .token-block {
            background: #f4f8ff;
            border: 1px solid var(--line-s);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .token-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-weight: 700;
            color: #8aa0be;
            margin-bottom: 8px;
        }
        .token-code {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .08em;
            color: var(--primary);
            font-family: "Courier New", monospace;
        }
        .token-hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Buton */
        .primary-btn {
            display: inline-block;
            text-decoration: none;
            border: 0;
            border-radius: 10px;
            padding: 13px 20px;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(180deg, var(--primary), var(--primary2));
            color: #fff;
            cursor: pointer;
            transition: filter .15s, box-shadow .15s, transform .05s;
            box-shadow: 0 8px 20px rgba(31,102,209,.22);
            font-family: inherit;
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .primary-btn:hover  { filter: brightness(1.04); }
        .primary-btn:active { transform: translateY(1px); }

        /* Bilgi satırları */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-top: 1px dashed var(--line);
            padding-top: 18px;
        }
        .info-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: #f8fbff;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 14px;
        }
        .info-row-icon {
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .info-row-body .lbl {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 700;
            color: #8aa0be;
            margin-bottom: 2px;
        }
        .info-row-body .val {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            word-break: break-all;
        }
        .info-row-body .note {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        @media (max-width: 820px) {
            body { padding: 14px; }
            .shell { grid-template-columns: 1fr; max-width: 540px; }
            .brand-panel { display: none; }
            .content-panel { padding: 24px 20px; }
        }
    </style>
</head>
<body>
<div class="shell">

    {{-- ── SOL MARKA PANELİ ─────────────────────────── --}}
    @php
        $brandName   = config('brand.name', 'MentorDE');
        $brandAccent = config('brand.accent', 'DE');
        $logoUrl     = config('brand.logo_url', '');
        $logoPath    = config('brand.logo_path', '');
        $logoHeight  = (int) config('brand.logo_height', 40);
        $resolvedLogoSrc = $logoUrl !== '' ? $logoUrl
            : ($logoPath !== '' ? asset('storage/' . $logoPath) : '');
    @endphp
    <section class="brand-panel" aria-label="Marka bilgisi">
        <div class="brand-logo">
            @if($resolvedLogoSrc !== '')
                <img src="{{ $resolvedLogoSrc }}" alt="{{ $brandName }}"
                     style="height:{{ $logoHeight }}px;width:auto;filter:brightness(0) invert(1);">
            @else
                <div class="brand-logo-text">
                    @if($brandAccent !== '')
                        {{ str_replace($brandAccent, '', $brandName) }}<span>{{ $brandAccent }}</span>
                    @else
                        {{ $brandName }}
                    @endif
                </div>
            @endif
        </div>

        <div class="brand-badge">Öğrenci Danışmanlık Platformu</div>

        <h1>Başvurunuz başarıyla teslim alındı</h1>
        <p>Ekibimiz en kısa sürede sizinle iletişime geçecek. Sürecin her adımını portal üzerinden takip edebilirsiniz.</p>

        <div class="steps">
            <div class="step done">
                <div class="step-icon">✓</div>
                <div class="step-txt">
                    <div class="t">Başvuru Tamamlandı</div>
                    <div class="s">Form başarıyla iletildi</div>
                </div>
            </div>
            <div class="step">
                <div class="step-icon">2</div>
                <div class="step-txt">
                    <div class="t">Danışman Ataması</div>
                    <div class="s">Profiline uygun danışman atanıyor</div>
                </div>
            </div>
            <div class="step">
                <div class="step-icon">3</div>
                <div class="step-txt">
                    <div class="t">İlk Görüşme</div>
                    <div class="s">Danışmanın seninle iletişime geçecek</div>
                </div>
            </div>
            <div class="step">
                <div class="step-icon">4</div>
                <div class="step-txt">
                    <div class="t">Süreç Başlıyor</div>
                    <div class="s">Üniversite başvurusu, vize ve yerleşim</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SAĞ İÇERİK PANELİ ──────────────────────────── --}}
    <section class="panel content-panel" aria-label="Başvuru sonucu">
        <div class="success-badge">&#10003; Başvuru başarıyla alındı</div>

        <h2>Başvurunuz Alındı</h2>
        <p class="greeting">Teşekkürler <strong>{{ $row->first_name }} {{ $row->last_name }}</strong>. Danışman atama süreci başlatıldı.</p>

        <div class="token-block">
            <div class="token-label">Takip Kodunuz</div>
            <div class="token-code">{{ $row->tracking_token }}</div>
            <div class="token-hint">Bu kodu saklayın — başvuru durumunuzu sorgulamak için kullanılır.</div>
        </div>

        <a class="primary-btn" href="{{ route('login') }}">
            Portala Giriş Yap →
        </a>

        <div class="info-list">
            @if(session('assigned_senior_email'))
            <div class="info-row">
                <div class="info-row-icon">&#128100;</div>
                <div class="info-row-body">
                    <div class="lbl">Atanan Danışman</div>
                    <div class="val">{{ session('assigned_senior_email') }}</div>
                </div>
            </div>
            @endif

            @if(session('portal_email'))
            <div class="info-row">
                <div class="info-row-icon">&#128274;</div>
                <div class="info-row-body">
                    <div class="lbl">Portal Girişi</div>
                    <div class="val">{{ session('portal_email') }}</div>
                    <div class="note">Adres: <a href="/login" style="color:var(--primary);font-weight:600;">/login</a></div>
                </div>
            </div>
            @endif

            @if(session('portal_password'))
            <div class="info-row">
                <div class="info-row-icon">&#128273;</div>
                <div class="info-row-body">
                    <div class="lbl">İlk Giriş Şifresi</div>
                    <div class="val" style="font-family:'Courier New',monospace;letter-spacing:.05em;">{{ session('portal_password') }}</div>
                    <div class="note">İlk girişten sonra Ayarlar'dan şifrenizi değiştirin.</div>
                </div>
            </div>
            @else
            <div class="info-row">
                <div class="info-row-icon">&#128273;</div>
                <div class="info-row-body">
                    <div class="lbl">Giriş Şifresi</div>
                    <div class="val">Mevcut şifrenizle giriş yapabilirsiniz.</div>
                    <div class="note">Şifrenizi unuttuysanız <a href="{{ route('password.request') }}" style="color:var(--primary);font-weight:600;">şifre sıfırlama</a> sayfasını kullanın.</div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>
</body>
</html>
