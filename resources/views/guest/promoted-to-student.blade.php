<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tebrikler — {{ config('brand.name', 'MentorDE') }}</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
    font-family:system-ui,-apple-system,'Segoe UI',sans-serif;
    min-height:100vh;
    background:#030712;
    display:flex;align-items:center;justify-content:center;
    padding:32px 16px;overflow-x:hidden;
}

/* ── Background ── */
.bg{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-base{
    position:absolute;inset:0;
    background:radial-gradient(ellipse 140% 60% at 50% -5%,#0f1e4a 0%,#030712 55%);
}
.orb{position:absolute;border-radius:50%;filter:blur(120px);}
.orb-1{width:700px;height:700px;left:-250px;top:-250px;background:#1e3a8a;opacity:.3;}
.orb-2{width:600px;height:600px;right:-200px;bottom:-200px;background:#0c4a6e;opacity:.25;}
.orb-3{width:400px;height:400px;left:40%;top:35%;transform:translate(-50%,-50%);background:#4c1d95;opacity:.18;}
.orb-4{width:250px;height:250px;right:15%;top:20%;background:#1d4ed8;opacity:.15;filter:blur(80px);}

/* Noise grain */
.noise{
    position:absolute;inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
    opacity:.5;pointer-events:none;
}

/* Stars */
.stars{position:absolute;inset:0;}
.star{position:absolute;background:#fff;border-radius:50%;animation:twinkle var(--d,3s) ease-in-out infinite var(--delay,0s);}
@keyframes twinkle{0%,100%{opacity:.06;transform:scale(1);}50%{opacity:.7;transform:scale(1.5);}}

/* ── Wrap & Card ── */
.wrap{position:relative;z-index:1;max-width:600px;width:100%;}
.card{
    background:rgba(255,255,255,.028);
    border:1px solid rgba(255,255,255,.08);
    border-radius:28px;
    backdrop-filter:blur(32px) saturate(180%);
    overflow:hidden;
    animation:rise .65s cubic-bezier(.22,.68,0,1.2) both;
    box-shadow:
        0 0 0 1px rgba(255,255,255,.04) inset,
        0 32px 80px rgba(0,0,0,.7),
        0 0 120px rgba(29,78,216,.1);
}
@keyframes rise{from{opacity:0;transform:translateY(40px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}

/* ── Hero ── */
.hero{
    background:linear-gradient(160deg,#0a1628 0%,#0f2447 40%,#0a1e3c 100%);
    padding:52px 44px 44px;
    text-align:center;
    position:relative;overflow:hidden;
    border-bottom:1px solid rgba(255,255,255,.06);
}
.hero::before{
    content:'';position:absolute;inset:0;
    background:
        radial-gradient(ellipse 80% 50% at 50% 0%,rgba(37,99,235,.25) 0%,transparent 70%),
        url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Ccircle cx='20' cy='20' r='1'/%3E%3C/g%3E%3C/svg%3E");
}
.hero-ring{
    position:absolute;border-radius:50%;border:1px solid rgba(255,255,255,.04);
}
.hero-ring-1{width:500px;height:500px;top:-200px;right:-150px;}
.hero-ring-2{width:300px;height:300px;bottom:-120px;left:-80px;}

/* Medal */
.medal-wrap{
    position:relative;z-index:1;
    display:inline-flex;align-items:center;justify-content:center;
    margin-bottom:28px;
}
.medal-halo{
    position:absolute;
    width:140px;height:140px;border-radius:50%;
    background:radial-gradient(circle,rgba(251,191,36,.2) 0%,transparent 70%);
    animation:halo-pulse 3s ease-in-out infinite;
}
@keyframes halo-pulse{0%,100%{transform:scale(1);opacity:.7;}50%{transform:scale(1.3);opacity:1;}}
.medal{
    position:relative;z-index:1;
    width:96px;height:96px;border-radius:50%;
    background:linear-gradient(135deg,#fcd34d 0%,#f59e0b 50%,#d97706 100%);
    box-shadow:
        0 0 0 12px rgba(251,191,36,.08),
        0 0 0 24px rgba(251,191,36,.04),
        0 20px 60px rgba(0,0,0,.6),
        inset 0 1px 0 rgba(255,255,255,.3);
    display:flex;align-items:center;justify-content:center;
    font-size:46px;
    animation:medal-pop .9s cubic-bezier(.34,1.56,.64,1) .2s both;
}
@keyframes medal-pop{from{opacity:0;transform:scale(.3) rotate(-30deg);}to{opacity:1;transform:scale(1) rotate(0deg);}}

.hero-title{
    position:relative;z-index:1;
    font-size:36px;font-weight:900;color:#fff;
    margin-bottom:10px;letter-spacing:-.03em;
    line-height:1.1;
}
.hero-title em{
    font-style:normal;
    background:linear-gradient(90deg,#fde68a,#fbbf24,#f59e0b);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.hero-sub{
    position:relative;z-index:1;
    font-size:15px;color:rgba(255,255,255,.55);line-height:1.6;
}
.hero-sub strong{color:rgba(255,255,255,.9);font-weight:700;}

/* ── Student ID Band ── */
.id-band{
    background:rgba(0,0,0,.2);
    border-top:1px solid rgba(255,255,255,.05);
    border-bottom:1px solid rgba(255,255,255,.05);
    padding:18px 44px;
    display:flex;align-items:center;justify-content:center;gap:16px;
}
.id-icon{
    width:44px;height:44px;border-radius:12px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    display:flex;align-items:center;justify-content:center;
    font-size:20px;flex-shrink:0;
    box-shadow:0 8px 24px rgba(34,197,94,.3);
}
.id-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.12em;margin-bottom:4px;}
.id-value{font-size:24px;font-weight:900;color:#fff;letter-spacing:.08em;font-variant-numeric:tabular-nums;}

/* ── Body ── */
.body{padding:32px 44px 36px;}

/* Date chip */
.date-chip{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.18);
    border-radius:999px;padding:5px 14px;
    font-size:11px;font-weight:700;color:#4ade80;letter-spacing:.02em;
    margin-bottom:28px;
}

/* Journey */
.journey-title{
    font-size:10px;font-weight:800;color:rgba(255,255,255,.25);
    text-transform:uppercase;letter-spacing:.12em;margin-bottom:18px;
}
.journey{position:relative;display:flex;flex-direction:column;gap:0;margin-bottom:32px;}
.journey-line{
    position:absolute;left:17px;top:18px;bottom:18px;width:2px;
    background:linear-gradient(to bottom,
        #22c55e 0%, #22c55e 55%,
        rgba(255,255,255,.06) 55%, rgba(255,255,255,.06) 100%
    );
}
.jstep{display:flex;align-items:flex-start;gap:18px;padding:8px 0;position:relative;}
.jdot{
    width:36px;height:36px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:700;flex-shrink:0;
    position:relative;z-index:1;
}
.jdot.done{
    background:linear-gradient(135deg,#22c55e,#16a34a);
    box-shadow:0 0 0 4px rgba(34,197,94,.1),0 4px 16px rgba(34,197,94,.3);
    color:#fff;
}
.jdot.now{
    background:linear-gradient(135deg,#3b82f6,#6366f1);
    box-shadow:0 0 0 4px rgba(99,102,241,.12),0 4px 20px rgba(59,130,246,.35);
    color:#fff;font-size:14px;
    animation:now-pulse 2.5s ease-in-out infinite;
}
@keyframes now-pulse{
    0%,100%{box-shadow:0 0 0 4px rgba(99,102,241,.12),0 4px 20px rgba(59,130,246,.3);}
    50%{box-shadow:0 0 0 8px rgba(99,102,241,.08),0 4px 32px rgba(59,130,246,.5);}
}
.jdot.soon{
    background:rgba(255,255,255,.04);
    border:1.5px solid rgba(255,255,255,.1);
    color:rgba(255,255,255,.25);font-size:14px;
}
.jbody{flex:1;padding-top:7px;}
.jlabel{font-size:14px;font-weight:700;color:#f1f5f9;margin-bottom:3px;line-height:1.3;}
.jlabel.dim{color:rgba(255,255,255,.25);}
.jdesc{font-size:12px;color:rgba(255,255,255,.38);line-height:1.55;}
.jdesc a,.jdesc strong{color:rgba(255,255,255,.65);font-weight:600;}

/* Notice */
.notice{
    background:linear-gradient(135deg,rgba(59,130,246,.07) 0%,rgba(99,102,241,.05) 100%);
    border:1px solid rgba(99,102,241,.2);
    border-radius:16px;padding:20px 24px;margin-bottom:28px;
    position:relative;overflow:hidden;
}
.notice::before{
    content:'';position:absolute;top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(99,102,241,.5),transparent);
}
.notice-head{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.notice-icon{
    width:30px;height:30px;border-radius:8px;
    background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.25);
    display:flex;align-items:center;justify-content:center;font-size:14px;
    flex-shrink:0;
}
.notice-title{font-size:11px;font-weight:800;color:#a5b4fc;text-transform:uppercase;letter-spacing:.08em;}
.notice-body{font-size:13px;color:rgba(255,255,255,.5);line-height:1.65;}
.notice-body strong{color:#c7d2fe;font-weight:700;}

/* Actions */
.actions{display:flex;flex-direction:column;gap:10px;}
.btn-main{
    display:flex;align-items:center;justify-content:center;gap:10px;
    background:linear-gradient(135deg,#1d4ed8 0%,#4f46e5 60%,#7c3aed 100%);
    border:none;border-radius:14px;
    padding:17px 32px;
    font-size:15px;font-weight:700;color:#fff;
    cursor:pointer;text-decoration:none;
    transition:all .25s;
    box-shadow:
        0 1px 0 rgba(255,255,255,.12) inset,
        0 12px 32px rgba(79,70,229,.35),
        0 2px 0 rgba(0,0,0,.3);
    letter-spacing:-.01em;
    position:relative;overflow:hidden;
}
.btn-main::before{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%);
    border-radius:14px;
}
.btn-main:hover{
    transform:translateY(-2px);
    box-shadow:0 1px 0 rgba(255,255,255,.12) inset,0 20px 48px rgba(79,70,229,.5),0 2px 0 rgba(0,0,0,.3);
}
.btn-main:active{transform:translateY(0);}
.btn-arrow{
    background:rgba(255,255,255,.15);border-radius:7px;
    padding:3px 9px;font-size:12px;font-weight:900;letter-spacing:0;
}
.btn-sec{
    display:flex;align-items:center;justify-content:center;gap:8px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.07);
    border-radius:14px;padding:14px 32px;
    font-size:13px;font-weight:600;color:rgba(255,255,255,.35);
    text-decoration:none;transition:all .2s;
}
.btn-sec:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);border-color:rgba(255,255,255,.12);}

/* Footer */
.footer{
    padding:18px 44px;
    border-top:1px solid rgba(255,255,255,.04);
    text-align:center;font-size:11px;color:rgba(255,255,255,.18);
    letter-spacing:.02em;
}

@media(max-width:500px){
    .hero{padding:40px 28px 36px;}
    .body{padding:28px 28px 30px;}
    .id-band{padding:16px 28px;}
    .footer{padding:16px 28px;}
    .hero-title{font-size:28px;}
}
</style>
</head>
<body>

<div class="bg">
    <div class="bg-base"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
    <div class="noise"></div>
    <div class="stars" id="stars"></div>
</div>

<div class="wrap">
<div class="card">

    {{-- Hero --}}
    <div class="hero">
        <div class="hero-ring hero-ring-1"></div>
        <div class="hero-ring hero-ring-2"></div>

        <div class="medal-wrap">
            <div class="medal-halo"></div>
            <div class="medal">🎓</div>
        </div>

        <div class="hero-title">
            Tebrikler<em>{{ $firstName ? ', '.$firstName : '' }}</em>!
        </div>
        <div class="hero-sub">
            Sözleşmeniz onaylandı — artık resmi bir<br>
            <strong>{{ config('brand.name', 'MentorDE') }} Öğrencisisiniz 🇩🇪</strong>
        </div>
    </div>

    {{-- Student ID --}}
    @if($studentId)
    <div class="id-band">
        <div class="id-icon">🪪</div>
        <div>
            <div class="id-label">Öğrenci Kimlik Numaranız</div>
            <div class="id-value">{{ $studentId }}</div>
        </div>
    </div>
    @endif

    {{-- Body --}}
    <div class="body">

        @if($approvedAt)
        <div class="date-chip">
            ✓ {{ \Carbon\Carbon::parse($approvedAt)->format('d.m.Y') }} tarihinde onaylandı
        </div>
        @endif

        <div class="journey-title">Başvuru Yolculuğunuz</div>
        <div class="journey">
            <div class="journey-line"></div>

            <div class="jstep">
                <div class="jdot done">✓</div>
                <div class="jbody">
                    <div class="jlabel">Başvuru Formu</div>
                    <div class="jdesc">Kişisel bilgiler ve belgeler eksiksiz tamamlandı.</div>
                </div>
            </div>
            <div class="jstep">
                <div class="jdot done">✓</div>
                <div class="jbody">
                    <div class="jlabel">Ön Değerlendirme & Servis Seçimi</div>
                    <div class="jdesc">Danışman değerlendirmesi ve paket seçimi onaylandı.</div>
                </div>
            </div>
            <div class="jstep">
                <div class="jdot done">✓</div>
                <div class="jbody">
                    <div class="jlabel">Sözleşme İmzalandı ve Onaylandı</div>
                    <div class="jdesc">{{ config('brand.name', 'MentorDE') }} danışmanlık sözleşmesi geçerlilik kazandı.</div>
                </div>
            </div>
            <div class="jstep">
                <div class="jdot now">→</div>
                <div class="jbody">
                    <div class="jlabel">Öğrenci Portala Giriş</div>
                    <div class="jdesc">Şimdi çıkış yapıp aynı bilgilerle giriş yapmanız yeterli — <strong>Öğrenci Portali</strong>'ne otomatik yönlendirileceksiniz.</div>
                </div>
            </div>
            <div class="jstep">
                <div class="jdot soon">🏛</div>
                <div class="jbody">
                    <div class="jlabel dim">Almanya Üniversite Süreci</div>
                    <div class="jdesc">Atanan danışmanınız rehberliğinde üniversite başvuru süreciniz başlayacak.</div>
                </div>
            </div>
        </div>

        <div class="notice">
            <div class="notice-head">
                <div class="notice-icon">⚡</div>
                <div class="notice-title">Bir Sonraki Adım</div>
            </div>
            <div class="notice-body">
                Aşağıdaki butona tıklayın, çıkış yapıldıktan sonra
                <strong>{{ optional($user)->email }}</strong>
                e-posta adresiniz ve mevcut şifrenizle giriş yapın.
                Öğrenci Portalı'nıza otomatik olarak yönlendirileceksiniz.
            </div>
        </div>

        <div class="actions">
            <a href="/logout"
               onclick="event.preventDefault();document.getElementById('logout-form').submit();"
               class="btn-main">
                🚀 Çıkış Yap ve Öğrenci Olarak Giriş Yap
                <span class="btn-arrow">→</span>
            </a>
            <form id="logout-form" method="POST" action="/logout" style="display:none;">
                @csrf
            </form>
            <a href="/" class="btn-sec">← Ana Sayfaya Dön</a>
        </div>
    </div>

    <div class="footer">Sorun yaşarsanız danışmanınızla veya destek ekibiyle iletişime geçin.</div>

</div>
</div>

<script>
(function(){
    var c = document.getElementById('stars');
    if (!c) return;
    for (var i = 0; i < 120; i++) {
        var s = document.createElement('div');
        var size = Math.random() < .85 ? Math.random() * 1.5 + .4 : Math.random() * 2.5 + 1.5;
        s.className = 'star';
        s.style.cssText = [
            'width:' + size + 'px',
            'height:' + size + 'px',
            'left:' + Math.random() * 100 + '%',
            'top:' + Math.random() * 100 + '%',
            '--d:' + (Math.random() * 5 + 2) + 's',
            '--delay:-' + (Math.random() * 8) + 's'
        ].join(';');
        c.appendChild(s);
    }
})();
</script>

</body>
</html>
