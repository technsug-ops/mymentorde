<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sözleşmeniz İmzalandı</title>
<style>
  body { margin:0; padding:0; background:#f4f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:#1a3c6b; padding:24px 32px; }
  .header h1 { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.5px; }
  .header p { margin:4px 0 0; color:#a8c4e8; font-size:13px; }
  .body { padding:32px; color:#1e293b; font-size:15px; line-height:1.7; }
  .body h2 { margin:0 0 16px; font-size:18px; color:#1a3c6b; }
  .badge { display:inline-block; padding:6px 14px; background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; border-radius:6px; font-weight:700; font-size:13px; margin:12px 0; }
  .cta { display:inline-block; margin:20px 0 0; padding:12px 28px; background:#1a3c6b; color:#fff; border-radius:6px; text-decoration:none; font-weight:700; font-size:14px; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 32px; color:#94a3b8; font-size:12px; text-align:center; }
  .footer a { color:#64748b; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>MentorDE</h1>
    <p>Almanya Danışmanlık Platformu</p>
  </div>
  <div class="body">
    <h2>Sözleşmeniz Başarıyla İmzalandı ✅</h2>
    <p>Sayın {{ $recipientName }},</p>
    <p><strong>{{ $contractTitle }}</strong> başlıklı sözleşmeniz başarıyla imzalanmış ve sisteme kaydedilmiştir.</p>
    <div class="badge">✓ Sözleşme Onaylandı</div>
    <p>Sözleşmenizin bir kopyasına portal üzerinden istediğiniz zaman ulaşabilirsiniz. Herhangi bir sorunuz olursa danışmanınızla iletişime geçebilirsiniz.</p>
    <a class="cta" href="{{ config('app.url') }}/student/contract">Sözleşmemi Görüntüle</a>
  </div>
  <div class="footer">
    Bu e-posta MentorDE platformu tarafından otomatik olarak gönderilmiştir.<br>
    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
  </div>
</div>
</body>
</html>
