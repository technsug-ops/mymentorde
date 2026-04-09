<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $mailSubject }}</title>
<style>
  body { margin:0; padding:0; background:#f4f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:#1a3c6b; padding:24px 32px; }
  .header h1 { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.5px; }
  .header p { margin:4px 0 0; color:#a8c4e8; font-size:13px; }
  .body { padding:32px; color:#1e293b; font-size:15px; line-height:1.7; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 32px; color:#94a3b8; font-size:12px; text-align:center; }
  .footer a { color:#64748b; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ config('brand.name', 'MentorDE') }}</h1>
    <p>{{ config('brand.tagline', 'Almanya Danışmanlık Platformu') }}</p>
  </div>
  <div class="body">
    {!! nl2br(e($mailBody)) !!}
  </div>
  <div class="footer">
    Bu e-posta {{ config('brand.name', 'MentorDE') }} platformu tarafından otomatik olarak gönderilmiştir.<br>
    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
  </div>
</div>
</body>
</html>
