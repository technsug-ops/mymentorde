@php
    $brandName = config('brand.name', 'MentorDE');
    $applyUrl  = url('/apply');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bu Kupon Artık Geçerli Değil — {{ $brandName }}</title>
    @include('partials.favicon')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body {
            min-height: 100vh; padding: 24px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .card {
            max-width: 500px; width: 100%; background: white; border-radius: 20px;
            padding: 40px 32px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.1);
        }
        .icon { font-size: 64px; margin-bottom: 16px; }
        h1 { font-size: 24px; font-weight: 800; margin: 0 0 8px 0; color: #0f172a; }
        p { font-size: 15px; line-height: 1.6; color: #475569; margin: 0 0 20px 0; }
        .code { font-family: monospace; font-size: 14px; padding: 4px 10px;
            background: #f1f5f9; border-radius: 6px; color: #475569; }
        .btn {
            display: inline-block; padding: 14px 28px; background: #2563eb;
            color: white; text-decoration: none; border-radius: 10px; font-weight: 700;
            margin-top: 8px;
        }
        .btn:hover { background: #1d4ed8; }
        .small { font-size: 12px; color: #94a3b8; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏳</div>
        <h1>Bu Kupon Artık Geçerli Değil</h1>
        <p>
            Aradığın kupon (<span class="code">{{ $code }}</span>) süresi dolmuş veya devre dışı bırakılmış olabilir.
            Ama merak etme — yine de sürecini başlatabilirsin.
        </p>
        <a class="btn" href="{{ $applyUrl }}">Yine de Başvur →</a>
        <div class="small">{{ $brandName }} olarak yolculuğunda yanındayız.</div>
    </div>
</body>
</html>
