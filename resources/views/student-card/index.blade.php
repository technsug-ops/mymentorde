<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Card</title>
    <style>
        :root { --bg:#f4f7fb; --card:#fff; --line:#d8e0ea; --text:#132238; --muted:#52657d; --accent:#0f6bdc; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI",Tahoma,sans-serif; background:var(--bg); color:var(--text); }
        .wrap { max-width: 1000px; margin: 28px auto; padding: 0 16px; }
        .head { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .search { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; margin-bottom:12px; }
        .row { display:flex; gap:8px; }
        input { flex:1; border:1px solid var(--line); border-radius:8px; padding:10px; }
        button { border:0; border-radius:8px; background:var(--accent); color:#fff; padding:10px 14px; cursor:pointer; }
        .status { color:var(--muted); font-size:13px; margin-top:8px; min-height:18px; }
        .list { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; }
        .muted { color:var(--muted); }
        .detail { margin-top:12px; background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; }
        .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .mini-list { max-height:220px; overflow:auto; border:1px solid var(--line); border-radius:8px; padding:8px; }
        .mini-item { padding:6px 0; border-bottom:1px solid #edf2f8; }
        .mini-item:last-child { border-bottom:none; }
        @media (max-width: 900px){ .list{grid-template-columns:1fr;} .head{flex-direction:column;align-items:flex-start;gap:8px;} }
        @media (max-width: 900px){ .detail-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1 style="margin:0;">Student Card Arama</h1>
        <a href="/config">/config'a don</a>
    </div>

    <section class="search">
        <div class="row">
            <input id="q" list="studentCardSuggestions" placeholder="{{ config('brand.name', 'MentorDE') }} ID / e-posta / isim (min 2 karakter)">
            <button onclick="runSearch()">Ara</button>
        </div>
        <datalist id="studentCardSuggestions"></datalist>
        <div id="status" class="status"></div>
    </section>

    <section id="result" class="list"></section>
    <section id="detail" class="detail" style="display:none;"></section>
</div>

<script defer src="{{ Vite::asset('resources/js/student-card.js') }}"></script>
</body>
</html>
