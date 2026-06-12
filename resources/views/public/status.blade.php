<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Durumu — {{ $brandName }}</title>
@include('partials.favicon')
<meta name="description" content="{{ $brandName }} platformunun anlık servis durumu — veritabanı, cache, depolama, e-posta ve anlık bildirimler.">
<meta name="robots" content="index, follow">

<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#7e58bf;
    --primary-dark:#6c47a8;
    --primary-deep:#5a3a8d;
    --primary-soft:#efe9fb;
    --success:#16a34a;
    --warn:#f59e0b;
    --danger:#dc2626;
    --text:#1a1325;
    --muted:#6b6377;
    --line:#e3dcec;
    --surface:#ffffff;
    --neutral-soft:#faf9f5;
    --font-base:"Space Grotesk", "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; }
body {
    font-family:var(--font-base); color:var(--text);
    background:linear-gradient(180deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%);
    line-height:1.6; font-size:15px;
    -webkit-font-smoothing:antialiased;
    min-height:100vh;
}
a { color:var(--primary); text-decoration:none; }

.p-nav { background:rgba(255,255,255,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--line); }
.p-nav-inner { max-width:980px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; }
.p-logo { font-size:24px; color:var(--primary); font-weight:700; letter-spacing:-.5px; }
.p-logo span { color:#b79ae9; font-style:italic; font-weight:600; }

.wrap { max-width:980px; margin:0 auto; padding:48px 22px 80px; }

.hero { text-align:center; margin-bottom:36px; }
.hero .label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.18em; font-size:11px; font-weight:800; margin-bottom:14px;
    background:var(--primary-soft); padding:5px 14px; border-radius:20px;
}
.hero h1 {
    font-family:var(--font-base); font-style:italic; font-weight:600;
    font-size:clamp(30px, 4vw, 44px); line-height:1.1; color:var(--primary-deep);
    letter-spacing:-1.2px; margin:0 0 14px;
}

.overall-card {
    background:var(--surface); border-radius:18px;
    padding:28px 32px; margin-bottom:24px;
    border:2px solid; display:flex; align-items:center; gap:18px;
    box-shadow:0 10px 30px rgba(126,88,191,.10);
}
@media(max-width:520px) { .overall-card { flex-direction:column; text-align:center; padding:22px 18px; } }
.overall-card.operational { border-color:var(--success); background:linear-gradient(180deg, #f0fdf4, #fff 60%); }
.overall-card.degraded    { border-color:var(--warn); background:linear-gradient(180deg, #fef3c7, #fff 60%); }
.overall-card.outage      { border-color:var(--danger); background:linear-gradient(180deg, #fee2e2, #fff 60%); }
.overall-icon {
    width:64px; height:64px; border-radius:18px;
    display:flex; align-items:center; justify-content:center;
    font-size:34px; flex:0 0 auto;
}
.overall-card.operational .overall-icon { background:var(--success); color:#fff; }
.overall-card.degraded    .overall-icon { background:var(--warn); color:#fff; }
.overall-card.outage      .overall-icon { background:var(--danger); color:#fff; }
.overall-text h2 {
    margin:0 0 4px; font-size:20px; font-weight:700; letter-spacing:-.3px;
}
.overall-text p { margin:0; color:var(--muted); font-size:14px; }

.svc-list {
    background:var(--surface); border:1px solid var(--line); border-radius:18px;
    overflow:hidden;
}
.svc-row {
    display:flex; align-items:center; gap:16px;
    padding:18px 24px;
    border-bottom:1px solid var(--line);
}
.svc-row:last-child { border-bottom:none; }
@media(max-width:520px) { .svc-row { padding:14px 16px; gap:12px; flex-wrap:wrap; } }

.svc-dot {
    width:12px; height:12px; border-radius:50%; flex:0 0 auto;
    box-shadow:0 0 0 4px rgba(0,0,0,.04);
}
.svc-dot.operational { background:var(--success); box-shadow:0 0 0 4px rgba(22,163,74,.18), 0 0 0 8px rgba(22,163,74,.08); }
.svc-dot.degraded    { background:var(--warn); box-shadow:0 0 0 4px rgba(245,158,11,.18); }
.svc-dot.outage      { background:var(--danger); box-shadow:0 0 0 4px rgba(220,38,38,.18); }

.svc-info { flex:1; min-width:0; }
.svc-name { font-weight:700; font-size:15px; color:var(--text); letter-spacing:-.2px; }
.svc-note { color:var(--muted); font-size:12.5px; margin-top:2px; }

.svc-state {
    padding:5px 12px; border-radius:999px; font-size:11px; font-weight:800;
    text-transform:uppercase; letter-spacing:.05em; flex:0 0 auto;
}
.svc-state.operational { background:rgba(22,163,74,.12); color:#15803d; }
.svc-state.degraded    { background:rgba(245,158,11,.14); color:#b45309; }
.svc-state.outage      { background:rgba(220,38,38,.14); color:#b91c1c; }

.svc-latency {
    font-size:11.5px; color:var(--muted); margin-left:6px; font-variant-numeric:tabular-nums;
}

.foot-meta {
    margin-top:28px; display:flex; justify-content:space-between; align-items:center; gap:14px;
    color:var(--muted); font-size:12.5px; flex-wrap:wrap;
}
.foot-meta a { color:var(--primary); font-weight:600; }

.refresh-btn {
    margin-top:24px; display:inline-flex; align-items:center; gap:8px;
    padding:10px 18px; background:var(--primary); color:#fff;
    border-radius:10px; border:none; cursor:pointer; font-weight:700;
    font-family:inherit; font-size:13px; text-decoration:none;
}
.refresh-btn:hover { background:var(--primary-dark); }
</style>
</head>
<body>

<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">{{ $brandName }}<span>.</span></a>
        <div>
            <a href="/fiyatlar" style="color:var(--muted);font-size:13px;font-weight:600;">Fiyatlar</a>
        </div>
    </div>
</nav>

<div class="wrap">
    <div class="hero">
        <span class="label">SİSTEM DURUMU</span>
        <h1>Tüm servislerin anlık durumu</h1>
    </div>

    @php
        $overall = $overallState;
        $overallTitle = match($overall) {
            'operational' => 'Tüm sistemler çalışıyor',
            'degraded'    => 'Bazı servisler düşük performansta',
            'outage'      => 'Bazı servislerde sorun var',
            default       => 'Durum bilinmiyor',
        };
        $overallNote = match($overall) {
            'operational' => 'Tüm altyapı servislerimiz normal şekilde çalışıyor.',
            'degraded'    => 'Sistemler çalışıyor ancak bazı servislerde gecikme veya kısmi sorun yaşanıyor.',
            'outage'      => 'Bir veya birden fazla servisimiz şu an kullanılamıyor — ekip durumu inceliyor.',
            default       => '',
        };
        $overallIcon = match($overall) {
            'operational' => '✓',
            'degraded'    => '!',
            'outage'      => '✕',
            default       => '?',
        };
    @endphp

    <div class="overall-card {{ $overall }}">
        <div class="overall-icon">{{ $overallIcon }}</div>
        <div class="overall-text">
            <h2>{{ $overallTitle }}</h2>
            <p>{{ $overallNote }}</p>
        </div>
    </div>

    <div class="svc-list">
        @foreach($report as $svcKey => $svc)
            <div class="svc-row">
                <span class="svc-dot {{ $svc['state'] }}"></span>
                <div class="svc-info">
                    <div class="svc-name">{{ $svc['name'] }}</div>
                    @if(!empty($svc['note']))
                        <div class="svc-note">{{ $svc['note'] }}</div>
                    @endif
                </div>
                @if(isset($svc['latency_ms']))
                    <span class="svc-latency">{{ $svc['latency_ms'] }}ms</span>
                @endif
                <span class="svc-state {{ $svc['state'] }}">
                    @if($svc['state'] === 'operational') Aktif
                    @elseif($svc['state'] === 'degraded') Kısıtlı
                    @elseif($svc['state'] === 'outage') Hata
                    @else Bilinmiyor
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    <div style="text-align:center;">
        <a href="{{ url()->current() }}" class="refresh-btn">
            ↻ Yenile
        </a>
    </div>

    <div class="foot-meta">
        <span>Son kontrol: {{ $lastChecked->locale('tr')->isoFormat('D MMMM YYYY, HH:mm:ss') }} (UTC{{ $lastChecked->format('P') }})</span>
        <span>
            JSON: <a href="{{ url()->current() }}?format=json">/durum?format=json</a>
        </span>
    </div>

    <div style="margin-top:28px;padding:18px 22px;background:var(--neutral-soft);border:1px solid var(--line);border-radius:14px;font-size:13px;color:var(--muted);text-align:center;line-height:1.5;">
        💡 Bir sorun mu yaşıyorsun? <a href="mailto:destek@mentorde.com" style="color:var(--primary);font-weight:700;">destek@mentorde.com</a> üzerinden bize ulaş — genelde 1 saat içinde döneriz.
    </div>
</div>

</body>
</html>
