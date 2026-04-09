<!doctype html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f7fb;color:#1a2233;padding:40px 20px;line-height:1.75;}
.wrap{max-width:900px;margin:0 auto;background:#fff;border-radius:12px;padding:48px 56px;box-shadow:0 4px 32px rgba(0,0,0,.08);}
.cover{text-align:center;padding:48px 0 40px;border-bottom:2px solid #e2e8f0;margin-bottom:40px;}
.cover h1{font-size:2rem;font-weight:800;color:#1f66d1;margin-bottom:8px;}
.cover .meta{font-size:.9rem;color:#6b7280;}
h1{font-size:1.55rem;font-weight:800;color:#1f66d1;margin:2.5rem 0 .6rem;padding-bottom:.4rem;border-bottom:2px solid #e2e8f0;}
h2{font-size:1.3rem;font-weight:800;color:#1f66d1;margin:2.5rem 0 .6rem;padding-bottom:.4rem;border-bottom:2px solid #e2e8f0;}
h3{font-size:1.05rem;font-weight:700;color:#1a2233;margin:1.8rem 0 .5rem;}
h4{font-size:.95rem;font-weight:600;color:#4b5563;margin:1.3rem 0 .4rem;}
p{margin-bottom:.8rem;}
ul,ol{margin:.4rem 0 1rem 1.6rem;}
li{margin-bottom:.3rem;}
table{width:100%;border-collapse:collapse;margin:1rem 0;font-size:.9rem;}
th{background:#1f66d1;color:#fff;padding:9px 12px;text-align:left;font-weight:600;}
td{padding:8px 12px;border-bottom:1px solid #e2e8f0;}
tr:nth-child(even) td{background:#f8faff;}
code{background:#f1f5f9;border:1px solid #e2e8f0;padding:2px 6px;border-radius:4px;font-size:.85rem;font-family:monospace;}
pre{background:#f1f5f9;border:1px solid #e2e8f0;padding:16px;border-radius:8px;overflow-x:auto;margin:1rem 0;}
pre code{border:none;padding:0;background:none;}
blockquote{border-left:3px solid #1f66d1;padding-left:16px;color:#6b7280;margin:1rem 0;font-style:italic;}
hr{border:none;border-top:1px solid #e2e8f0;margin:2rem 0;}
strong{color:#1a2233;}
.footer{text-align:center;margin-top:48px;padding-top:24px;border-top:1px solid #e2e8f0;font-size:.82rem;color:#9ca3af;}
@media print{body{background:#fff;padding:0;}  .wrap{box-shadow:none;border-radius:0;padding:24px;}}
</style>
</head>
<body>
<div class="wrap">
    <div class="cover">
        <h1>📖 {{ $title }}</h1>
        <div class="meta">{{ config('brand.name', 'MentorDE') }} ERP &nbsp;·&nbsp; {{ date('Y') }} &nbsp;·&nbsp; {{ strtoupper($lang) }}</div>
    </div>

    {!! $html !!}

    <div class="footer">
        {{ config('brand.name', 'MentorDE') }} — {{ date('Y') }} — {{ $lang === 'en' ? 'Generated automatically' : 'Otomatik oluşturuldu' }}
    </div>
</div>
</body>
</html>
