<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paylaşım Linki — {{ config('brand.name', 'MentorDE') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            background: #fff;
            padding: 36px 30px;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,.08);
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        h1 { font-size: 18px; margin: 0 0 6px; color: #0f172a; }
        p { font-size: 13px; color: #64748b; margin: 0 0 20px; }
        input {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            outline: none;
        }
        input:focus { border-color: #1e40af; }
        button {
            width: 100%;
            padding: 12px;
            background: #1e40af;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #1e3a8a; }
        .err { color: #dc2626; font-size: 13px; margin-bottom: 10px; font-weight: 500; }
    </style>
</head>
<body>
<div class="box">
    <div style="font-size:36px;margin-bottom:10px">🔒</div>
    <h1>Paylaşım Linki Şifre Korumalı</h1>
    <p>Bu içeriğe erişmek için gönderilen şifreyi girin.</p>
    @if(!empty($error))
        <div class="err">{{ $error }}</div>
    @endif
    <form method="GET" action="{{ route('dam.share.public', $token) }}">
        <input type="password" name="pw" placeholder="Şifre" autofocus required minlength="4">
        <button type="submit">Erişimi Aç</button>
    </form>
</div>
</body>
</html>
