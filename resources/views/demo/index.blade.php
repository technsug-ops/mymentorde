<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'MentorDE') }} Demo</title>
    <style>
        :root {
            --bg: #eef3f9;
            --card: #ffffff;
            --line: #d8e0ea;
            --text: #132238;
            --muted: #52657d;
            --accent: #0f6bdc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #eaf1f8 0%, #f8fbff 100%);
            color: var(--text);
        }
        .wrap {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px 30px;
        }
        h1 {
            margin: 0 0 8px;
        }
        .meta {
            color: var(--muted);
            margin-bottom: 14px;
        }
        .top {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .top a {
            text-decoration: none;
            background: #fff;
            color: var(--text);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 12px;
        }
        .grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }
        .card h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .card p {
            margin: 0 0 10px;
            color: var(--muted);
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 11px;
            color: var(--text);
            background: #fff;
        }
        .btn.primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ config('brand.name', 'MentorDE') }} Demo Akisi</h1>
    <div class="meta">Master v5.0 demo stratejisine uygun 4 ekranlik hizli gecis.</div>

    <div class="top">
        <a href="/manager/dashboard">Manager Dashboard</a>
        <a href="/config">Config Panel</a>
        <a href="/student-card">Student Card</a>
        <a href="/demo/checklist">MVP Checklist</a>
    </div>

    <div class="grid">
        <section class="card">
            <h2>1. Guest Kayit</h2>
            <p>Aday ogrencinin sisteme ilk girisi ve temel bilgiler.</p>
            <div class="actions">
                <a class="btn primary" href="/apply">Guest Kayit Formu (Public)</a>
                <a class="btn" href="/demo/guest">Demo Simulasyon</a>
            </div>
        </section>

        <section class="card">
            <h2>2. Student Dashboard</h2>
            <p>Ogrenci karti, surec arama ve durum gorunumu.</p>
            <div class="actions">
                <a class="btn primary" href="/student-card">Student Card</a>
            </div>
        </section>

        <section class="card">
            <h2>3. Senior Paneli</h2>
            <p>Senior atama, kapasite, devretme ve sahiplik yonetimi.</p>
            <div class="actions">
                <a class="btn primary" href="/config">Config > Senior + Student Ownership</a>
            </div>
        </section>

        <section class="card">
            <h2>4. Manager Dashboard</h2>
            <p>KPI, funnel, approvals, rapor snapshot ve PDF.</p>
            <div class="actions">
                <a class="btn primary" href="/manager/dashboard">Manager Dashboard</a>
                <a class="btn" href="/manager/dashboard/report-print" target="_blank">PDF Yazdir</a>
            </div>
        </section>
    </div>
</div>
</body>
</html>
