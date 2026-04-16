@extends('manager.layouts.app')
@section('title', $lang === 'en' ? 'System Handbook' : 'Sistem El Kitabı')

@push('styles')
    @include('handbook._style')
@endpush

@push('head')
<style>
/* ─── Handbook: sidebar ToC + content + minimap ─── */
.hb-layout { position:relative; }
.hb-content-area { margin-left:246px; margin-right:60px; }
@media(max-width:1100px){ .hb-content-area { margin-right:0; } .hb-minimap-wrap { display:none !important; } }
@media(max-width:800px){ .hb-content-area { margin-left:0; } .hb-toc-sidebar { display:none !important; } }

/* ToC sidebar — fixed position, takip eder */
.hb-toc-sidebar { position:fixed; top:80px; left:var(--sidebar-w,260px); width:220px; max-height:calc(100vh - 100px); overflow-y:auto; scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:12px 10px; z-index:50; }
@media(max-width:800px){ .hb-toc-sidebar { position:static; width:100%; max-height:none; margin-bottom:12px; } }
.hb-toc-title { font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--u-line,#e5e9f0); }
.hb-toc-list { list-style:none; margin:0; padding:0; }
.hb-toc-list li { margin:0; }
.hb-toc-list a { display:block; padding:4px 8px; font-size:11px; color:var(--u-muted,#64748b); text-decoration:none; border-radius:4px; transition:all .1s; line-height:1.4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; border-left:2px solid transparent; }
.hb-toc-list a:hover { background:var(--u-bg,#f5f7fa); color:var(--u-text,#0f172a); }
.hb-toc-list a.active { background:#dbeafe; color:#1e40af; font-weight:600; border-left-color:#1e40af; }
.hb-toc-list a[data-level="1"] { font-weight:600; color:var(--u-text,#0f172a); margin-top:6px; }
.hb-toc-list a[data-level="2"] { padding-left:18px; font-size:10px; }
.hb-toc-list a[data-level="3"] { padding-left:28px; font-size:10px; opacity:.75; }

/* Minimap */
.hb-minimap-wrap { position:fixed; top:80px; right:24px; height:calc(100vh - 100px); display:flex; flex-direction:column; align-items:center; z-index:50; }
.hb-minimap-bar { width:6px; flex:1; background:var(--u-line,#e5e9f0); border-radius:999px; overflow:hidden; position:relative; }
.hb-minimap-fill { width:100%; background:linear-gradient(180deg,#1e40af,#3b82f6); border-radius:999px; transition:height .15s; position:absolute; top:0; left:0; }
.hb-minimap-pct { font-size:9px; font-weight:700; color:var(--u-muted,#64748b); margin-top:4px; text-align:center; }

/* Home button */
.hb-home-btn { position:fixed; bottom:80px; right:24px; z-index:100; width:42px; height:42px; border-radius:50%; background:#1e40af; color:#fff; border:none; box-shadow:0 4px 16px rgba(30,64,175,.3); cursor:pointer; font-size:18px; display:none; align-items:center; justify-content:center; transition:all .2s; }
.hb-home-btn:hover { transform:scale(1.1); background:#1d4ed8; }
.hb-home-btn.visible { display:flex; }

/* Heading scroll offset */
.handbook-body h1, .handbook-body h2, .handbook-body h3 { scroll-margin-top:20px; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
    <div>
        <h1 class="page-title">📖 {{ $lang === 'en' ? 'System Handbook' : 'Sistem El Kitabı' }}</h1>
        <p class="page-subtitle" style="margin:0;">{{ $lang === 'en' ? 'Full reference for all portals, roles, and modules.' : 'Tüm portaller, roller ve modüller için tam referans.' }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <div class="handbook-lang">
            <a href="?lang=tr" class="{{ $lang === 'tr' ? 'active' : '' }}">TR</a>
            <a href="?lang=en" class="{{ $lang === 'en' ? 'active' : '' }}">EN</a>
        </div>
        <a href="{{ route('manager.handbook.download') }}?lang={{ $lang }}" class="btn alt" style="padding:7px 16px;font-size:.85rem;">
            ⬇ HTML {{ $lang === 'en' ? 'Download' : 'İndir' }}
        </a>
    </div>
</div>

<div class="hb-layout">

    {{-- Sol: İçindekiler (fixed sidebar) --}}
    <nav class="hb-toc-sidebar" id="hbTocSidebar">
        <div class="hb-toc-title">📑 {{ $lang === 'en' ? 'Contents' : 'İçindekiler' }}</div>
        <ul class="hb-toc-list" id="hbTocList"></ul>
    </nav>

    {{-- Sağ: Minimap (fixed) --}}
    <div class="hb-minimap-wrap">
        <div class="hb-minimap-bar">
            <div class="hb-minimap-fill" id="hbMinimapFill" style="height:0%;"></div>
        </div>
        <div class="hb-minimap-pct" id="hbMinimapPct">0%</div>
    </div>

    {{-- Orta: İçerik (normal document flow — scroll çalışır) --}}
    <div class="hb-content-area">
        <div class="card handbook-wrap" id="hbContent" style="max-width:none;">
            <div class="handbook-body" id="hbBody">
                {!! $html !!}
            </div>
        </div>
    </div>

</div>

{{-- Home button --}}
<button type="button" class="hb-home-btn" id="hbHomeBtn" title="{{ $lang === 'en' ? 'Back to top' : 'Başa dön' }}">⬆</button>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var body     = document.getElementById('hbBody');
    var tocList  = document.getElementById('hbTocList');
    var sidebar  = document.getElementById('hbTocSidebar');
    var fill     = document.getElementById('hbMinimapFill');
    var pctLabel = document.getElementById('hbMinimapPct');
    var homeBtn  = document.getElementById('hbHomeBtn');
    if (!body || !tocList) return;

    // ── 1) Heading'lere id ekle + ToC oluştur ──
    var headings = body.querySelectorAll('h1, h2, h3');
    var tocItems = [];
    headings.forEach(function(h, idx) {
        var text = (h.textContent || '').trim();
        if (!text) return;
        var id = 'hb-s-' + idx;
        h.id = id;

        var level = parseInt(h.tagName.substring(1), 10);
        var li = document.createElement('li');
        var a  = document.createElement('a');
        a.href = '#' + id;
        a.textContent = text.replace(/^[\d.]+\s*/, '').substring(0, 50);
        a.title = text;
        a.setAttribute('data-level', String(level));
        a.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        li.appendChild(a);
        tocList.appendChild(li);
        tocItems.push({ id: id, el: h, link: a });
    });

    // ── 2) Scroll spy + minimap + home ──
    var ticking = false;
    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function() {
            ticking = false;
            var scrollTop = window.scrollY || document.documentElement.scrollTop;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            var pct = docHeight > 0 ? Math.min(100, Math.round(scrollTop / docHeight * 100)) : 0;

            if (fill) fill.style.height = pct + '%';
            if (pctLabel) pctLabel.textContent = pct + '%';
            if (homeBtn) homeBtn.classList.toggle('visible', scrollTop > 400);

            // Aktif bölümü bul
            var activeId = null;
            for (var i = tocItems.length - 1; i >= 0; i--) {
                if (tocItems[i].el.getBoundingClientRect().top <= 80) {
                    activeId = tocItems[i].id;
                    break;
                }
            }

            // ToC'ta highlight + sidebar içinde aktif item'ı görünür yap
            tocItems.forEach(function(item) {
                var isActive = item.id === activeId;
                item.link.classList.toggle('active', isActive);
            });

            // Sidebar'ı aktif item'a scroll et (doğal takip hissi)
            var activeLink = tocList.querySelector('a.active');
            if (activeLink && sidebar) {
                var sRect = sidebar.getBoundingClientRect();
                var lRect = activeLink.getBoundingClientRect();
                if (lRect.top < sRect.top + 50 || lRect.bottom > sRect.bottom - 50) {
                    activeLink.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // ── 3) Home button ──
    if (homeBtn) {
        homeBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
</script>
@endpush
