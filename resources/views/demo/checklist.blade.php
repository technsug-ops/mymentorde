<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MVP Checklist</title>
    <style>
        :root { --line:#d8e0ea; --text:#132238; --muted:#52657d; --ok:#1a8f4b; --accent:#0f6bdc; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI",Tahoma,sans-serif; background:#f4f7fb; color:var(--text); }
        .wrap { max-width:1000px; margin:28px auto; padding:0 14px; }
        .top { display:flex; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
        .top a { text-decoration:none; border:1px solid var(--line); background:#fff; color:var(--text); border-radius:8px; padding:8px 11px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:14px; margin-bottom:12px; }
        h1 { margin:0 0 8px; }
        h2 { margin:0 0 8px; font-size:18px; }
        .meta { color:var(--muted); margin-bottom:8px; }
        .list { margin:0; padding-left:18px; }
        .list li { margin:7px 0; }
        .ok { color:var(--ok); font-weight:600; }
        code { background:#eef3fa; border-radius:6px; padding:2px 6px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <a href="/demo">Demo Akisi</a>
        <a href="/apply">Public Apply</a>
        <a href="/config">Config</a>
        <a href="/manager/dashboard">Manager Dashboard</a>
    </div>

    <div class="card">
        <h1>MVP Uctan Uca Checklist</h1>
        <div class="meta">Amac: sistemi adaydan manager'a kadar tek turda dogrulamak.</div>
    </div>

    <div class="card">
        <h2>1. Public Basvuru</h2>
        <ol class="list">
            <li><code>/apply</code> ac, formu doldur, gonder.</li>
            <li><code>/apply/success</code> ekraninda token ve durum linki gor.</li>
            <li><code>/apply/status/{token}</code> ekraninda adim bari gor.</li>
        </ol>
    </div>

    <div class="card">
        <h2>2. Admin Donusum</h2>
        <ol class="list">
            <li><code>/config</code> -> <strong>Aday Öğrenci Başvuruları</strong> kartinda kaydi bul.</li>
            <li>Sec ve <strong>Studenta Donustur</strong> yap.</li>
            <li>Durum mesajinda yeni <code>student_id</code> gorundugunu kontrol et.</li>
        </ol>
    </div>

    <div class="card">
        <h2>3. Otomatik Tetikler</h2>
        <ol class="list">
            <li>Config'te <strong>Öğrenci Sahipliği</strong> listesinde yeni ogrenciyi gor.</li>
            <li><strong>Internal Notes</strong> ve <strong>Process Outcomes</strong kayitlari olusmus olmali.</li>
            <li><strong>Notification Queue</strong kartinda <code>queued/sent/failed</code> kayitlari gor.</li>
        </ol>
    </div>

    <div class="card">
        <h2>4. Queue ve Scheduler</h2>
        <ol class="list">
            <li>Terminal: <code>C:\tools\php84\php.exe artisan schedule:work</code> calisiyor olmali.</li>
            <li>Config queue kartindan <strong>Dispatch Simdi</strong> ile manuel tetik test et.</li>
            <li>Failed kayit varsa <strong>Failed -> Queue</strong> sonra tekrar dispatch et.</li>
        </ol>
    </div>

    <div class="card">
        <h2>5. Manager Dogrulama</h2>
        <ol class="list">
            <li><code>/manager/dashboard</code> ac.</li>
            <li><strong>Notification Queue Sagligi</strong> KPI kutusunu kontrol et.</li>
            <li>Risk/approval/funnel ve queue metrikleri tutarliysa tur tamamdir.</li>
        </ol>
        <div class="meta ok">Bu 5 adim geciyorsa MVP temel akisi calisir durumda.</div>
    </div>
</div>
</body>
</html>

