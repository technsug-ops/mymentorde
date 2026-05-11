<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Link Geçersiz — MentorDE</title>
@include('partials.favicon')
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%);
    min-height: 100vh; padding: 16px; display: flex; align-items: center; justify-content: center;
}
.card {
    background: #fff; border-radius: 18px; max-width: 420px; width: 100%;
    padding: 36px 28px; box-shadow: 0 20px 60px rgba(0,0,0,.25);
    text-align: center;
}
.icon {
    width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 18px;
    background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 42px;
}
h1 { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
p { font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 14px; }
.reason {
    background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
    padding: 12px 14px; font-size: 12.5px; color: #991b1b; line-height: 1.5;
    margin-bottom: 18px;
}
.foot {
    font-size: 11px; color: #94a3b8; line-height: 1.5;
    padding-top: 18px; border-top: 1px solid #f1f5f9;
}
</style>
</head>
<body>
<div class="card">
    <div class="icon">⏱️</div>
    <h1>Bu Link Artık Geçersiz</h1>
    <p>Belge yükleme linki kullanılamıyor.</p>
    <div class="reason">
        @if($isExpired && $isUsed)
            Link hem süresi dolmuş hem de daha önce kullanılmış.
        @elseif($isExpired)
            Bu link süresinin sonuna geldi. Lütfen danışmanınızdan yeni bir link talep edin.
        @elseif($isUsed)
            Bu link daha önce kullanılarak belge yüklenmiş. Eğer yenisini yüklemen gerekiyorsa danışmanına yaz.
        @endif
    </div>
    <div class="foot">
        Sorularınız için danışmanınız ile iletişime geçin.<br><br>
        <strong style="color:#1e40af;">MentorDE</strong>
    </div>
</div>
</body>
</html>
