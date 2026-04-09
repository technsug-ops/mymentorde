<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('brand.name', 'MentorDE') }} — Çevrimdışı</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafd; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 16px; padding: 40px 32px; max-width: 440px; width: 100%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 8px; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background: #2563eb; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; cursor: pointer; border: none; }
        .contact { margin-top: 24px; padding: 16px; background: #f1f5f9; border-radius: 10px; font-size: 13px; color: #475569; }
        .contact strong { color: #1e293b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📡</div>
        <h1>İnternet Bağlantısı Yok</h1>
        <p>Şu an çevrimdışı görünüyorsunuz.</p>
        <p>Bağlantı geldiğinde portal otomatik olarak yüklenecektir.</p>

        <button class="btn" onclick="window.location.reload()">Tekrar Dene</button>

        <div class="contact">
            <strong>Danışmanınıza ulaşmak için:</strong><br>
            Bağlantınız geldiğinde mesajlaşma bölümünden iletişime geçebilirsiniz.<br><br>
            <strong>Destek:</strong> destek@mentorde.de
        </div>
    </div>

    <script>
        window.addEventListener('online', () => {
            window.location.href = '/student/dashboard';
        });
    </script>
</body>
</html>
