<!DOCTYPE html>
<html lang="tr" data-theme="{{ session('mentorde_theme_v2', 'light') }}">
<head>
    <script nonce="{{ $cspNonce ?? '' }}">
    !function(){
        var t=localStorage.getItem('mentorde_dark');
        if(t==='true'){document.documentElement.setAttribute('data-theme','dark');document.documentElement.classList.add('dark');}
        var d=localStorage.getItem('mentorde_design');
        if(d==='minimalist'){var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#111111;--c-accent2:#333333;--accent-soft:rgba(0,0,0,.04);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#111111;}';document.head.appendChild(s);var ml=document.createElement('link');ml.rel='stylesheet';ml.id='minimalist-css-pre';ml.href='{{ Vite::asset('resources/css/minimalist.css') }}';document.head.appendChild(ml);document.addEventListener('DOMContentLoaded',function(){var l=document.getElementById('mentorde-theme-css');if(l)l.disabled=true;});}
    }();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('brand.name', 'MentorDE') . ' Staff')</title>

    <link id="mentorde-theme-css" rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Font scale — manager/senior ile aynı pattern. Renk değerleri body @php'den sonra dinamik override edilir --}}
    <style>
        :root{
            --tx-xs:15px;--tx-sm:17px;--tx-base:18px;
            --tx-lg:20px;--tx-xl:22px;--tx-2xl:26px;
            /* Koyu sidebar için beyaz metin — premium.css'in --theme-sidebar-text fallback'i */
            --theme-sidebar-text: rgba(255,255,255,.90);
        }
        html{ font-size:18px; font-family:-apple-system,BlinkMacSystemFont,'Inter','Segoe UI',Roboto,sans-serif; }
        html:not(.jm-minimalist) .sidebar{ background:linear-gradient(180deg,#0f172a,#1e293b); }
        /* Sidebar kontrast + font — koyu bg üzerinde açık metin */
        .sidebar .nav-link        { font-size:17px !important; color:rgba(255,255,255,.80) !important; }
        .sidebar .nav-link:hover  { color:#fff !important; background:rgba(255,255,255,.10) !important; }
        .sidebar .nav-link.active { color:#fff !important; background:rgba(255,255,255,.18) !important; font-weight:700 !important; }
        .sidebar .nav-section-label { font-size:13px !important; color:rgba(255,255,255,.45) !important; }
        .sidebar .brand-name      { font-size:18px !important; color:#fff !important; }
        .sidebar .brand-sub       { font-size:13px !important; color:rgba(255,255,255,.55) !important; }
        .sidebar .user-name       { font-size:16px !important; color:rgba(255,255,255,.90) !important; }
        .sidebar .user-role       { font-size:13px !important; color:rgba(255,255,255,.50) !important; }
        .sidebar .nav-link.logout { color:rgba(255,100,100,.75) !important; }
    </style>
    <style>
        :root {
            --c-accent:      #1e40af;
            --c-accent2:     #1e293b;
            --hero-gradient: linear-gradient(to right,#0f172a,#1e40af);
            /* Bridge: --u-* → premium.css */
            --u-card:      var(--surface, #ffffff);
            --u-line:      var(--border,  #e2e8f0);
            --u-text:      var(--text,    #0f172a);
            --u-muted:     var(--muted,   #64748b);
            --u-brand:     var(--c-accent, #1e40af);
            --u-bg:        var(--bg,      #f1f5f9);
            --u-ok:        var(--c-ok,    #16a34a);
            --u-warn:      var(--c-warn,  #d97706);
            --u-danger:    var(--c-danger,#dc2626);
            --u-info:      var(--c-info,  #0891b2);
            --u-subtle:    var(--bg,      #f1f5f9);
            --u-shadow:    0 1px 3px rgba(0,0,0,.08);
            --u-shadow-md: 0 4px 12px rgba(0,0,0,.12);
        }
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
        .btn.alt { background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text,#0f172a); }
        .btn.alt:hover { border-color:var(--c-accent,#1e40af);color:var(--c-accent,#1e40af); }
        .btn.ok  { background:var(--c-ok,#16a34a);color:#fff; }
        .btn.warn{ background:var(--c-danger,#dc2626);color:#fff; }
        /* Form alanı kontrast düzeltmesi */
        input:not([type=checkbox]):not([type=radio]):not([type=range]),
        select,
        textarea {
            background: var(--surface, #ffffff) !important;
            border: 1.5px solid var(--border, #cbd5e1) !important;
            color: var(--text, #0f172a) !important;
            border-radius: 7px;
        }
        input:not([type=checkbox]):not([type=radio]):not([type=range]):focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--c-accent,#1e40af) !important;
            box-shadow: 0 0 0 3px rgba(30,64,175,.15);
        }
        /* Active nav link */
        .nav-link.active, .sidebar-nav a.active { color:#fff !important; background:rgba(255,255,255,.16) !important; font-weight:700; }
        .brand-logo { background:linear-gradient(135deg,#0f172a,#1e40af) !important; }
        .avatar     { background:linear-gradient(135deg,#0f172a,#1e40af) !important; }
    </style>

    @stack('head')
</head>
<body>

<div class="app">
    {{-- Sidebar --}}
    <aside class="sidebar" id="premium-sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo" style="overflow:hidden;">
                @if(!empty($brandLogoUrl ?? ''))
                    <img src="{{ $brandLogoUrl }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <span style="display:none;align-items:center;justify-content:center;width:100%;height:100%;">{{ $brandInitial ?? 'M' }}</span>
                @else
                    {{ $brandInitial ?? strtoupper(mb_substr(config('brand.name', 'MentorDE'), 0, 1)) }}
                @endif
            </div>
            <div>
                <div class="brand-name">{{ $brandName ?? config('brand.name', 'MentorDE') }}</div>
                <div class="brand-sub">Staff Panel</div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="avatar" style="width:44px;height:44px;font-size:16px;flex-shrink:0;">{{ $staffInitials }}</div>
            <div style="min-width:0;">
                <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()?->name ?? 'Staff' }}</div>
                <div class="user-role">{{ auth()->user()?->email ?? '' }}</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="{{ $dashboardUrl }}" class="nav-link">
                    <span class="nav-icon">⬅️</span> Panom
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-label">İş Araçları</div>
                <a href="/bulletins" class="nav-link {{ request()->is('bulletins*') ? 'active' : '' }}"
                   style="justify-content:space-between;">
                    <span><span class="nav-icon">📢</span> Duyurular</span>
                    @if(($bulletinUnread ?? 0) > 0)
                    <span class="sidebar-bulletin-badge" style="background:#dc2626;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;min-width:18px;text-align:center;line-height:16px;">{{ $bulletinUnread }}</span>
                    @endif
                </a>
                <a href="/tasks" class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> Görev Panosu
                </a>
                @if(auth()->user()?->role !== \App\Models\User::ROLE_SALES_STAFF)
                <a href="/tickets-center" class="nav-link {{ request()->is('tickets-center*') ? 'active' : '' }}">
                    <span class="nav-icon">🎫</span> Ticket Merkezi
                </a>
                @endif
                <a href="/im" class="nav-link {{ request()->is('im*') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> İletişim Merkezi
                </a>
                <a href="/manager/requests" class="nav-link {{ request()->is('manager/requests*') ? 'active' : '' }}">
                    <span class="nav-icon">📤</span> Manager'a Talep
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-label">Kişisel</div>
                <a href="/hr/my/leaves" class="nav-link {{ request()->is('hr/my/leaves*') ? 'active' : '' }}">
                    <span class="nav-icon">🏖️</span> İzin Taleplerim
                </a>
                <a href="/hr/my/attendance" class="nav-link {{ request()->is('hr/my/attendance') ? 'active' : '' }}">
                    <span class="nav-icon">⏰</span> Devam Takibi
                </a>
                <a href="/hr/my/certifications" class="nav-link {{ request()->is('hr/my/certifications*') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Sertifikalarım
                </a>
                <a href="/hr/my/onboarding" class="nav-link {{ request()->is('hr/my/onboarding*') ? 'active' : '' }}">
                    <span class="nav-icon">✅</span> Onboarding
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout" class="nav-link logout">
                <span class="nav-icon">🚪</span> Çıkış Yap
            </a>
        </div>
    </aside>

    {{-- Main --}}
    <div class="main">
        <header class="topbar">
            <div class="topbar-left" style="flex:0 1 auto;">
                <button class="icon-btn" id="premium-menu-btn" style="display:none;">☰</button>
                <div>
                    <div class="topbar-title">@yield('page_title', 'Staff Panel')</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            {{-- Global search (topbar center) --}}
            <div style="position:relative;flex:1 1 300px;max-width:520px;margin:0 20px;" id="gs-wrap">
                <input type="text" id="gs-input" placeholder="🔍 Ara..." autocomplete="off" minlength="2"
                       style="width:100%;padding:9px 16px;border:1px solid var(--border,#d1d5db);border-radius:10px;font-size:14px;background:var(--surface,#f9fafb);color:var(--text,#111);outline:none;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <div id="gs-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;min-width:min(400px,calc(100vw - 32px));background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9000;max-height:400px;overflow-y:auto;"></div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                <button class="icon-btn" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" id="design-btn" title="Tasarım Teması">🎨</button>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;">{{ $staffInitials }}</div>
            </div>
        </header>

        @if(!empty($urgentBulletins) && $urgentBulletins->isNotEmpty())
        <div style="background:#dc2626;color:#fff;padding:9px 22px;font-size:13px;font-weight:600;display:flex;gap:12px;align-items:center;flex-shrink:0;">
            <span>🚨</span>
            <span>{{ $urgentBulletins->first()->title }}</span>
            <a href="/bulletins" style="color:#fff;text-decoration:underline;margin-left:auto;font-size:12px;">Tümünü Gör →</a>
        </div>
        @endif
        <div class="content">
            @if (session('status'))
                <div class="card" style="border-left:4px solid var(--c-ok);margin-bottom:16px;">
                    <div class="card-body" style="padding:12px 16px;color:var(--c-ok);font-weight:500;">{{ session('status') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="card" style="border-left:4px solid var(--c-danger);margin-bottom:16px;">
                    <div class="card-body" style="padding:12px 16px;color:var(--c-danger);">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>

<div id="premium-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"></div>
<style>
    #premium-overlay.active{display:block !important;}
    @media(max-width:900px){#premium-menu-btn{display:flex!important;}}
</style>

<div class="dark-toggle" title="Tema Değiştir" id="theme-toggle"><span id="theme-icon">🌙</span></div>
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:min(360px, calc(100vw - 40px));"></div>


<script nonce="{{ $cspNonce ?? '' }}">
function __designToggle(){
    var link=document.getElementById('mentorde-theme-css');
    if(!link)return;
    var isMin=!!document.getElementById('minimalist-css-pre')||link.disabled;
    var next=isMin?'premium':'minimalist';
    localStorage.setItem('mentorde_design',next);if(window.__iconSwitcher)window.__iconSwitcher.apply(next);
    if(next==='minimalist'){
        if(!document.getElementById('minimalist-css-pre')){var ml=document.createElement('link');ml.rel='stylesheet';ml.id='minimalist-css-pre';ml.href='{{ Vite::asset('resources/css/minimalist.css') }}';document.head.appendChild(ml);ml.onload=function(){link.disabled=true;};}else{link.disabled=true;}
        if(!document.getElementById('design-override')){var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#111111;--c-accent2:#333333;--accent-soft:rgba(0,0,0,.04);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#111111;}';document.head.appendChild(s);}
    } else {
        link.disabled=false;
        var ml2=document.getElementById('minimalist-css-pre');if(ml2)ml2.remove();
        var ov=document.getElementById('design-override');if(ov)ov.remove();
    }
    var btn=document.getElementById('design-btn');
    if(btn)btn.textContent=next==='minimalist'?'◎':'🎨';
}
(function(){
    if(localStorage.getItem('mentorde_design')==='minimalist'){
        document.addEventListener('DOMContentLoaded',function(){var btn=document.getElementById('design-btn');if(btn)btn.textContent='◎';});
    }
})();
function __dmToggle(){
    var html=document.documentElement;
    var isDark=html.getAttribute('data-theme')==='dark';
    var next=isDark?'light':'dark';
    html.setAttribute('data-theme',next);
    html.classList.toggle('dark',next==='dark');
    localStorage.setItem('mentorde_dark',next==='dark');
    var icon=next==='dark'?'☀️':'🌙';
    ['dm-btn','theme-icon'].forEach(function(id){var el=document.getElementById(id);if(el)el.textContent=icon;});
}
(function(){
    var saved=localStorage.getItem('mentorde_dark');
    if(saved==='true'){
        document.documentElement.setAttribute('data-theme','dark');
        document.documentElement.classList.add('dark');
        document.addEventListener('DOMContentLoaded',function(){
            ['dm-btn','theme-icon'].forEach(function(id){var el=document.getElementById(id);if(el)el.textContent='☀️';});
        });
    }
})();
// ── Mobile hamburger + overlay + theme buttons (CSP-safe addEventListener) ──
(function(){
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb){_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});}
    if(_ov){_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});}
    document.getElementById('dm-btn')?.addEventListener('click', __dmToggle);
    document.getElementById('theme-toggle')?.addEventListener('click', __dmToggle);
    document.getElementById('design-btn')?.addEventListener('click', __designToggle);
})();
document.addEventListener('alpine:init',function(){
    Alpine.effect(function(){
        var items=Alpine.store('toast').items;
        var c=document.getElementById('toast-container');
        if(!c)return;
        c.innerHTML=items.map(function(i){
            var bg=i.type==='ok'?'var(--c-ok)':i.type==='danger'?'var(--c-danger)':i.type==='warn'?'var(--c-warn)':'#1e40af';
            return '<div style="background:'+bg+';color:#fff;padding:12px 18px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.2);font-size:13px;font-weight:500;animation:slideIn .3s ease">'+i.message+'</div>';
        }).join('');
    });
});
</script>
<style>@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}</style>
<style>
.nav-section-label{cursor:pointer!important;pointer-events:auto!important;user-select:none;display:flex!important;align-items:center;justify-content:space-between;opacity:1!important;}
.nav-section-label::after{content:'▾';font-size:.7rem;opacity:.6;transition:transform .2s;}
.nav-section.collapsed .nav-section-label::after{transform:rotate(-90deg);}
.nav-section.collapsed .nav-link{display:none!important;}
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var STORE_KEY='staff_sidebar_collapsed';
    var VER='v2';
    if(localStorage.getItem(STORE_KEY+'_ver')!==VER){localStorage.removeItem(STORE_KEY);localStorage.setItem(STORE_KEY+'_ver',VER);}
    function getC(){try{return JSON.parse(localStorage.getItem(STORE_KEY)||'[]');}catch(e){return[];}}
    function saveC(a){localStorage.setItem(STORE_KEY,JSON.stringify(a));}
    var labels=document.querySelectorAll('.sidebar-nav .nav-section-label');
    var collapsed=getC();
    labels.forEach(function(label){
        var section=label.closest('.nav-section');
        if(!section)return;
        var key=label.textContent.trim();
        if(collapsed.indexOf(key)>-1)section.classList.add('collapsed');
        label.addEventListener('click',function(){
            var isC=section.classList.toggle('collapsed');
            var a=getC();
            if(isC){if(a.indexOf(key)<0)a.push(key);}else{a=a.filter(function(k){return k!==key;});}
            saveC(a);
        });
    });
}());
</script>

<script defer src="{{ Vite::asset('resources/js/icon-switcher.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">window.__giphyKey={{ Js::from(config('services.giphy.key','')) }};</script>

{{-- Global search JS --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var gsTimer;
    var inp=document.getElementById('gs-input');
    var box=document.getElementById('gs-results');
    if(!inp)return;
    inp.addEventListener('input',function(){
        clearTimeout(gsTimer);
        var q=this.value.trim();
        if(q.length<2){box.style.display='none';return;}
        gsTimer=setTimeout(function(){
            var csrf=document.querySelector('meta[name="csrf-token"]');
            fetch('/mktg-admin/search?q='+encodeURIComponent(q),{
                credentials:'same-origin',
                headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf?csrf.content:''}
            }).then(function(r){
                if(!r.ok) throw new Error('HTTP '+r.status);
                return r.json();
            }).then(function(d){
                box.innerHTML=(d.results&&d.results.length)
                    ?d.results.map(function(x){return '<a href="'+x.url+'" style="display:flex;gap:10px;align-items:flex-start;padding:9px 12px;border-bottom:1px solid var(--border,#f3f4f6);text-decoration:none;color:var(--text,#111827);">'
                        +'<span style="font-size:16px;flex-shrink:0;">'+x.icon+'</span>'
                        +'<div><div style="font-size:13px;font-weight:600;">'+x.title+'</div><div style="font-size:11px;color:var(--muted,#9ca3af);">'+x.sub+' &mdash; '+(x.date||'')+'</div></div></a>';}).join('')
                    :'<div style="padding:12px;font-size:13px;color:var(--muted,#9ca3af);text-align:center;">Sonuç bulunamadı.</div>';
                box.style.display='block';
            }).catch(function(e){
                box.innerHTML='<div style="padding:12px;font-size:12px;color:var(--muted,#9ca3af);text-align:center;">Arama bu modülde henüz aktif değil.</div>';
                box.style.display='block';
            });
        },300);
    });
    document.addEventListener('click',function(e){
        var wrap=document.getElementById('gs-wrap');
        if(wrap&&!wrap.contains(e.target))box.style.display='none';
    });
}());
</script>

@stack('scripts')
</body>
</html>
