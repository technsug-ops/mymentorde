<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Belge Yüklendi — MentorDE</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: linear-gradient(135deg, #065f46 0%, #16a34a 50%, #4ade80 100%);
    min-height: 100vh; padding: 16px; display: flex; align-items: center; justify-content: center;
}
.card {
    background: #fff; border-radius: 18px; max-width: 420px; width: 100%;
    padding: 36px 28px; box-shadow: 0 20px 60px rgba(0,0,0,.25);
    text-align: center;
}
.icon {
    width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 18px;
    background: linear-gradient(135deg, #16a34a, #4ade80); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 42px;
    box-shadow: 0 6px 20px rgba(22,163,74,.35);
    animation: pop .5s cubic-bezier(.34,1.56,.64,1);
}
@keyframes pop { 0% { transform: scale(.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
p { font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 18px; }
.doc-name {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; font-weight: 700; color: #166534;
    margin-bottom: 22px;
}
.foot {
    font-size: 11px; color: #94a3b8; line-height: 1.5;
    padding-top: 18px; border-top: 1px solid #f1f5f9;
}
</style>
</head>
<body>
<div class="card">
    <div class="icon">✓</div>
    <h1>Belge Yüklendi!</h1>
    <p>Belgeniz danışmanınıza ulaştı. İnceleme sonrası size haber verilecek.</p>
    <div class="doc-name">📄 {{ $docName }}</div>
    <div class="foot">
        🔒 Bu link tek-kullanımlık olduğu için artık geçersiz.<br>
        Tarayıcıyı kapatabilirsiniz.<br><br>
        <strong style="color:#1e40af;">MentorDE</strong>
    </div>
</div>
</body>
</html>
