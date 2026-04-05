<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Marketing Admin Panel</title>
    <link rel="stylesheet" href="{{ Vite::asset('resources/css/portal-unified-v2.css') }}">
    {{-- Eski stiller devre disi
    <style>
        :root {
            --bg: #f2f6fb;
            --card: #ffffff;
            --line: #d8e1ec;
            --text: #11263d;
            --muted: #58718e;
            --accent: #0b66d7;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, sans-serif; background: var(--bg); color: var(--text); }
        .shell { display: grid; grid-template-columns: 230px 1fr; min-height: 100vh; }
        .sidebar { background: #10253e; color: #d7e4f6; padding: 18px 14px; }
        .sidebar h1 { margin: 0 0 16px; font-size: 18px; }
        .menu-item { padding: 8px 0; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .content { padding: 18px; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 12px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 12px; }
        .card h3 { margin: 0 0 8px; font-size: 15px; }
        .big { font-size: 24px; font-weight: 700; color: var(--accent); }
        .two { display: grid; grid-template-columns: 1.3fr 1fr; gap: 12px; }
        .list { max-height: 360px; overflow: auto; border: 1px solid var(--line); border-radius: 8px; }
        .item { padding: 8px; border-bottom: 1px solid #e6edf6; font-size: 14px; }
        .item:last-child { border-bottom: none; }
        .row { display: flex; gap: 8px; margin-bottom: 8px; }
        input, select { width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px; }
        button { border: 0; border-radius: 8px; background: var(--accent); color: #fff; padding: 8px 10px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border-bottom: 1px solid #e8eef6; padding: 7px 6px; text-align: left; }
        .status { color: var(--muted); font-size: 13px; min-height: 18px; margin-top: 6px; }
        @media (max-width: 1100px) {
            .shell { grid-template-columns: 1fr; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .two { grid-template-columns: 1fr; }
        }
    </style>
    --}}
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <h1>Marketing+Sales Admin</h1>
        <div class="menu-item">🏠 Dashboard</div>
        <div class="menu-item">📊 Kampanya Yönetimi</div>
        <div class="menu-item">📝 İçerik Yönetimi</div>
        <div class="menu-item">📧 E-posta Marketing</div>
        <div class="menu-item">📱 Sosyal Medya Takibi</div>
        <div class="menu-item">🎯 Lead Kaynağı Analizi</div>
        <div class="menu-item">📅 Etkinlik Yönetimi</div>
        <div class="menu-item">📈 Marketing KPI</div>
        <div class="menu-item">👥 Ekip Yönetimi</div>
        <a href="/logout" style="margin-top:14px;display:block;">Çıkış</a>
    </aside>

    <main class="content">
        <div class="top">
            <h2 style="margin:0;">Marketing Dashboard</h2>
            <small>{{ auth()->user()->email }}</small>
        </div>

        <section class="grid">
            <div class="card"><h3>Guest Kayıt</h3><div id="kpiGuests" class="big">-</div></div>
            <div class="card"><h3>Doğrulanmış Kaynak</h3><div id="kpiVerified" class="big">-</div></div>
            <div class="card"><h3>Kampanya Sayısı</h3><div id="kpiCampaigns" class="big">-</div></div>
            <div class="card"><h3>CPA</h3><div id="kpiCpa" class="big">-</div></div>
        </section>

        <section class="two">
            <div class="card">
                <h3>Kampanya Yönetimi</h3>
                <div class="row">
                    <input id="cmpName" list="campaignNameSuggestions" placeholder="Kampanya adı">
                    <select id="cmpChannel">
                        <option value="instagram">instagram</option>
                        <option value="google_ads">google_ads</option>
                        <option value="tiktok">tiktok</option>
                        <option value="email">email</option>
                        <option value="fair">fair</option>
                        <option value="other">other</option>
                    </select>
                    <input id="cmpBudget" type="number" placeholder="Bütçe" value="1000">
                </div>
                <div class="row">
                    <input id="cmpTargetAudience" list="marketingSearchSuggestions" placeholder="Hedef kitle (onerili)" value="">
                </div>
                <div class="row">
                    <select id="cmpTargetAudiencePick">
                        <option value="">Oneriden secin</option>
                    </select>
                    <button type="button" onclick="appendTargetAudience()">Ekle</button>
                </div>
                <button onclick="createCampaign()">Kampanya Ekle</button>
                <div id="campaignStatus" class="status"></div>
                <div id="campaignList" class="list" style="margin-top:8px;"></div>
            </div>

            <div class="card">
                <h3>Lead Kaynağı Analizi</h3>
                <table>
                    <thead><tr><th>Kaynak</th><th>Guest</th><th>Student</th><th>Dönüşüm</th></tr></thead>
                    <tbody id="sourceTable"></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<datalist id="marketingSearchSuggestions"></datalist>
<datalist id="campaignNameSuggestions"></datalist>

<script defer src="{{ Vite::asset('resources/js/marketing-admin-dashboard.js') }}"></script>
</body>
</html>
