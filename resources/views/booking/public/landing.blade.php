<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al — {{ $brandName ?? 'MentorDE' }}</title>
    @vite(['resources/css/premium.css'])
    <style>
        :root { --brand:#1e40af; --brand-light:#dbeafe; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f8fafc; color:var(--text); }
        .bl-head { background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%); color:#fff; padding:60px 20px 40px; text-align:center; }
        .bl-head h1 { margin:0 0 10px; font-size:32px; font-weight:800; }
        .bl-head p { margin:0; font-size:16px; opacity:.9; max-width:620px; margin:0 auto; line-height:1.6; }
        .bl-wrap { max-width:1100px; margin:-30px auto 40px; padding:0 16px; }
        .bl-filters { background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px; margin-bottom:24px; box-shadow:0 2px 6px rgba(0,0,0,.04); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .bl-filters input { flex:1; min-width:200px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:14px; }
        .bl-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; }
        .bl-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:22px; transition:transform .15s, box-shadow .15s; display:flex; flex-direction:column; }
        .bl-card:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.08); }
        .bl-card h3 { margin:0 0 4px; font-size:16px; color:var(--text); }
        .bl-card .subtitle { color:var(--muted); font-size:12px; margin-bottom:12px; }
        .bl-card .bio { font-size:13px; color:#475569; line-height:1.55; margin-bottom:14px; flex:1; }
        .bl-card .tags { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:14px; }
        .bl-card .tag { background:var(--brand-light); color:var(--brand); padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
        .bl-card .meta { display:flex; gap:12px; font-size:12px; color:var(--muted); margin-bottom:12px; }
        .bl-btn { display:block; text-align:center; padding:11px 16px; background:var(--brand); color:#fff; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px; transition:background .15s; }
        .bl-btn:hover { background:#1e3a8a; }
        .bl-empty { padding:40px; text-align:center; color:var(--muted); background:#fff; border-radius:12px; }
        .bl-foot { text-align:center; padding:20px; color:var(--muted); font-size:11px; }
        .bl-avatar { width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700; color:var(--brand); margin-bottom:12px; overflow:hidden; }
        .bl-avatar img { width:100%; height:100%; object-fit:cover; }
    </style>
</head>
<body>

<div class="bl-head">
    <h1>📅 Uzmanla Randevu Al</h1>
    <p>Yurt dışı eğitim danışmanları ile tek tıkla görüşme planla. Müsait saatleri gör, hemen ayarla.</p>
</div>

<div class="bl-wrap">

    <div class="bl-filters">
        <input type="text" id="bl-search" placeholder="🔍 Danışman adı veya uzmanlık alanı ara...">
    </div>

    @if ($seniors->isEmpty())
        <div class="bl-empty">
            🙏 Şu anda public randevu veren danışman yok. Yakında aktif olacak.
        </div>
    @else
        <div class="bl-grid" id="bl-grid">
            @foreach ($seniors as $s)
                <div class="bl-card" data-search="{{ strtolower(($s['name'] ?? '').' '.($s['display_name'] ?? '').' '.implode(' ', (array)$s['expertise'])) }}">
                    <div class="bl-avatar">
                        @if (!empty($s['photo_url']))
                            <img src="{{ $s['photo_url'] }}" alt="{{ $s['name'] }}">
                        @else
                            {{ strtoupper(mb_substr($s['name'] ?? 'D', 0, 1)) }}
                        @endif
                    </div>
                    <h3>{{ $s['name'] ?? $s['display_name'] }}</h3>
                    <div class="subtitle">{{ $s['display_name'] }}</div>

                    @if (!empty($s['bio']))
                        <div class="bio">{{ \Illuminate\Support\Str::limit($s['bio'], 140) }}</div>
                    @endif

                    @if (!empty($s['expertise']))
                        <div class="tags">
                            @foreach (array_slice($s['expertise'], 0, 5) as $tag)
                                <span class="tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="meta">
                        <span>⏱ {{ $s['slot_duration'] }} dk</span>
                        <span>🌍 {{ $s['timezone'] }}</span>
                    </div>

                    <a href="{{ route('booking.public.show', ['slug' => $s['slug']]) }}" class="bl-btn">Randevu Al →</a>
                </div>
            @endforeach
        </div>
    @endif

</div>

<div class="bl-foot">
    Powered by <a href="/" style="color:inherit;">{{ $brandName ?? 'MentorDE' }}</a>
</div>

<script>
(function(){
    var search = document.getElementById('bl-search');
    if (!search) return;
    search.addEventListener('input', function(){
        var q = search.value.toLowerCase().trim();
        document.querySelectorAll('.bl-card').forEach(function(card){
            var haystack = card.getAttribute('data-search') || '';
            card.style.display = (q === '' || haystack.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>

</body>
</html>
