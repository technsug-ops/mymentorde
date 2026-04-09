<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Destek Talebiniz Çözüldü</title>
<style>
  body { margin:0; padding:0; background:#f4f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; }
  .header { background:#1a3c6b; padding:24px 32px; }
  .header h1 { margin:0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.5px; }
  .header p { margin:4px 0 0; color:#a8c4e8; font-size:13px; }
  .body { padding:32px; color:#1e293b; font-size:15px; line-height:1.7; }
  .body h2 { margin:0 0 16px; font-size:18px; color:#1a3c6b; }
  .ticket-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px 20px; margin:16px 0; }
  .ticket-box p { margin:4px 0; font-size:14px; }
  .badge-ok { display:inline-block; padding:4px 12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; border-radius:999px; font-weight:700; font-size:12px; }
  .cta { display:inline-block; margin:20px 0 0; padding:12px 28px; background:#1a3c6b; color:#fff; border-radius:6px; text-decoration:none; font-weight:700; font-size:14px; }
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
    <h2>Destek Talebiniz Çözüldü</h2>
    <p>Gönderdiğiniz destek talebi başarıyla tamamlanmıştır.</p>
    <div class="ticket-box">
      <p><strong>Ticket #{{ $ticket->id }}</strong> <span class="badge-ok">Çözüldü</span></p>
      <p><strong>Konu:</strong> {{ $ticket->subject ?? '-' }}</p>
      @if($ticket->closed_at)
      <p><strong>Kapanış Tarihi:</strong> {{ \Carbon\Carbon::parse($ticket->closed_at)->format('d.m.Y H:i') }}</p>
      @endif
    </div>
    <p>Talebiniz çözülmüş olsa da, sorun devam ederse yeni bir destek talebi oluşturabilirsiniz.</p>
    <a class="cta" href="{{ config('app.url') }}/student/tickets">Ticketlarıma Git</a>
  </div>
  <div class="footer">
    Bu e-posta {{ config('brand.name', 'MentorDE') }} platformu tarafından otomatik olarak gönderilmiştir.<br>
    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
  </div>
</div>
</body>
</html>
