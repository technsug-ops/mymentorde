<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Şifren sıfırlandı</title>
<style>
  body { margin:0; padding:0; background:#f4f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:#1a3c6b; padding:24px 32px; }
  .header h1 { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.5px; }
  .header p { margin:4px 0 0; color:#a8c4e8; font-size:13px; }
  .body { padding:32px; color:#1e293b; font-size:15px; line-height:1.7; }
  .body h2 { margin:0 0 16px; font-size:18px; color:#1a3c6b; }
  .pwd-box { background:#fffbeb; border:2px solid #f59e0b; border-radius:8px; padding:16px 20px; margin:18px 0; text-align:center; }
  .pwd-box .lbl { font-size:11px; color:#92400e; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; }
  .pwd-box .pwd { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size:22px; font-weight:700; color:#92400e; letter-spacing:1px; }
  .warning { background:#fef2f2; border-left:4px solid #dc2626; padding:12px 16px; margin:16px 0; color:#991b1b; font-size:13px; border-radius:4px; }
  .cta { display:inline-block; margin:20px 0 0; padding:12px 28px; background:#1a3c6b; color:#fff !important; border-radius:6px; text-decoration:none; font-weight:700; font-size:14px; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 32px; color:#94a3b8; font-size:12px; text-align:center; }
  .footer a { color:#64748b; text-decoration:none; }
  .small { font-size:13px; color:#64748b; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ config('brand.name', 'MentorDE') }}</h1>
    <p>Şifre Sıfırlama Bildirimi</p>
  </div>
  <div class="body">
    <h2>Merhaba {{ $name }},</h2>
    <p>{{ config('brand.name', 'MentorDE') }} hesabının şifresi yönetici tarafından sıfırlandı.</p>

    <p><strong>Aşağıdaki geçici şifreyle giriş yap:</strong></p>

    <div class="pwd-box">
      <div class="lbl">Geçici Şifre (tek kullanımlık)</div>
      <div class="pwd">{{ $tempPassword }}</div>
    </div>

    <p class="small">
      <strong>E-posta:</strong> {{ $email }}
    </p>

    <div class="warning">
      ⚠ Bu şifre <strong>tek kullanımlıktır</strong>. Giriş yaptıktan sonra
      sistem seni hemen yeni bir şifre belirleme ekranına yönlendirecek.
    </div>

    <a class="cta" href="{{ $loginUrl }}">Giriş Yap →</a>

    <p class="small" style="margin-top:24px;">
      Eğer bu işlemi sen talep etmediysen, lütfen hemen <a href="mailto:info@panel.mentorde.com">info@panel.mentorde.com</a>
      adresinden bizimle iletişime geç.
    </p>
  </div>
  <div class="footer">
    Bu e-posta {{ config('brand.name', 'MentorDE') }} platformu tarafından otomatik olarak gönderilmiştir.<br>
    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
  </div>
</div>
</body>
</html>
