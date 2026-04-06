<!DOCTYPE html>
<html lang="tr" data-theme="{{ session('mentorde_theme_v2', 'light') }}">
<head>
    {{-- Dark restore (before render) --}}
    <script nonce="{{ $cspNonce ?? '' }}">
    !function(){
        var t=localStorage.getItem('mentorde_dark');
        if(t==='true'){document.documentElement.setAttribute('data-theme','dark');document.documentElement.classList.add('dark');}
        var d=localStorage.getItem('mentorde_design');
        if(d==='minimalist'){document.documentElement.classList.add('jm-minimalist');var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#111111;--c-accent2:#333333;--accent-soft:rgba(0,0,0,.04);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#111111;--bg:#e8e8e8;--border:#c0c0c0;--u-line:#c0c0c0;--u-shadow:0 2px 6px rgba(0,0,0,.09);--u-shadow-md:0 4px 12px rgba(0,0,0,.11);--shadow:0 2px 6px rgba(0,0,0,.09);--shadow-md:0 4px 12px rgba(0,0,0,.11);}';document.head.appendChild(s);var ml=document.createElement('link');ml.rel='stylesheet';ml.id='minimalist-css-pre';ml.href='{{ Vite::asset('resources/css/minimalist.css') }}';document.head.appendChild(ml);document.addEventListener('DOMContentLoaded',function(){var l=document.getElementById('mentorde-theme-css');if(l)l.disabled=true;});}
    }();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MentorDE — Bayi Portalı')</title>

    {{-- Premium Design System --}}
    <link id="mentorde-theme-css" rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Dealer portal accent (yeşil) + backwards-compat bridge --}}
    <style>
        :root {
            /* Dealer accent */
            --c-accent:  var(--theme-accent-dealer, #16a34a);
            --c-accent2: var(--theme-accent-dealer, #15803d);
            --accent-soft: rgba(22,163,74,.09);
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-dealer, #16a34a), var(--theme-hero-to-dealer, #0891b2));

            /* Bridge: legacy --u-* → premium.css vars */
            --u-card:      var(--surface, #ffffff);
            --u-line:      var(--border,  #e2e8f0);
            --u-text:      var(--text,    #0f172a);
            --u-muted:     var(--muted,   #64748b);
            --u-brand:     var(--c-accent,  #16a34a);
            --u-brand-2:   var(--c-accent2, #15803d);
            --u-bg:        var(--bg,      #f1f5f9);
            --u-ok:        var(--c-ok,    #16a34a);
            --u-warn:      var(--c-warn,  #d97706);
            --u-danger:    var(--c-danger,#dc2626);
            --u-info:      var(--c-info,  #0891b2);
            --u-subtle:    var(--bg,      #f1f5f9);
            --u-shadow:    0 1px 3px rgba(0,0,0,.08);
            --u-shadow-md: 0 4px 12px rgba(0,0,0,.12);

            /* Badge/btn compat */
            --badge-ok-bg:     rgba(22,163,74,.12);
            --badge-ok-fg:     #15803d;
            --badge-warn-bg:   rgba(217,119,6,.12);
            --badge-warn-fg:   #b45309;
            --badge-danger-bg: rgba(220,38,38,.12);
            --badge-danger-fg: #b91c1c;
            --badge-info-bg:   rgba(8,145,178,.12);
            --badge-info-fg:   #0e7490;
        }

        /* Btn compat */
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:var(--tx-sm,13px);font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
        .btn.alt     { background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text,#0f172a); }
        .btn.alt:hover { border-color:var(--c-accent);color:var(--c-accent); }
        .btn.ok      { background:var(--c-ok,#16a34a);color:#fff;border-color:transparent; }
        .btn.ok:hover{ opacity:.9; }
        .btn.warn    { background:var(--c-danger,#dc2626);color:#fff;border-color:transparent; }
        /* Grid layout */
        .grid2  { display:grid; grid-template-columns:1fr 1fr;       gap:18px; margin-bottom:16px; }
        .grid3  { display:grid; grid-template-columns:1fr 1fr 1fr;   gap:18px; margin-bottom:16px; }
        .grid3-1{ display:grid; grid-template-columns:3fr 2fr;       gap:18px; margin-bottom:16px; }
        @media(max-width:900px){ .grid3 { grid-template-columns:1fr 1fr; } }
        @media(max-width:700px){ .grid2,.grid3,.grid3-1 { grid-template-columns:1fr; } }
        /* Panel card */
        .panel { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:18px 20px; transition:box-shadow .15s; }
        .panel:hover { box-shadow:var(--shadow-md,0 4px 12px rgba(0,0,0,.08)); }
        .panel h1,.panel h2,.panel h3 { font-size:var(--tx-base,15px); font-weight:700; color:var(--text,#0f172a); margin:0 0 12px; }
    </style>

    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} --tx-xs:calc(var(--theme-font-size-dealer,15px)*.733);--tx-sm:calc(var(--theme-font-size-dealer,15px)*.867);--tx-base:var(--theme-font-size-dealer,15px);--tx-lg:calc(var(--theme-font-size-dealer,15px)*1.2);--tx-xl:calc(var(--theme-font-size-dealer,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-dealer,15px)*1.867); }html{font-size:var(--theme-font-size-dealer,15px);font-family:var(--theme-font-family-dealer,inherit);}</style>
    @endif

    <style>html:not(.jm-minimalist) .sidebar{background:linear-gradient(180deg,var(--theme-sidebar-from-dealer,#162C4A),var(--theme-sidebar-to-dealer,#1E3D6B));}</style>
    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Sidebar Header (merged brand + user) --}}
        @php
            $dealerUser     = auth()->user();
            $dealerInitials = strtoupper(substr(preg_replace('/\s+/', '', ($dealerUser?->name ?? 'DE')), 0, 2));
            // $tierPerms layout'ta her zaman hazır olsun
            if (!isset($tierPerms)) {
                $__sidebarDealer = \App\Models\Dealer::where('code', strtoupper(trim((string)($dealerUser?->dealer_code ?? ''))))->first();
                $tierPerms = \App\Support\DealerTierPermissions::for($__sidebarDealer);
            }
        @endphp
        <div style="padding:14px 14px 0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:46px;height:46px;font-size:17px;flex-shrink:0;">{{ $dealerInitials }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $dealerUser?->name ?? 'Bayi' }}</div>
                    <div class="user-role">
                        <span style="display:inline-block;padding:1px 7px;border-radius:4px;font-size:9px;font-weight:800;background:{{ $tierPerms->tierColor() }};color:#fff;letter-spacing:.04em;">
                            T{{ $tierPerms->tier() }}
                        </span>
                        {{ $tierPerms->tierLabel() }} · {{ $dealerUser?->dealer_code ?: '-' }}
                    </div>
                </div>
                <div class="brand-logo" style="width:28px;height:28px;font-size:11px;flex-shrink:0;" title="MentorDE">M</div>
            </div>
            <div style="font-size:10px;color:var(--muted);font-weight:600;letter-spacing:.03em;margin-top:6px;padding-left:56px;">MentorDE · Bayi Portalı</div>
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            {{-- Dashboard — tüm tierlar --}}
            <div class="nav-section">
                <a href="/dealer/dashboard"
                   class="nav-link {{ request()->is('dealer/dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Dashboard
                </a>
            </div>

            {{-- Öğrenci İşleri — tüm tierlar --}}
            <div class="nav-section">
                <div class="nav-section-label">Öğrenci İşleri</div>
                <a href="/dealer/lead-create"
                   class="nav-link {{ request()->is('dealer/lead-create') ? 'active' : '' }}">
                    <span class="nav-icon">➕</span> Öğrenci Yönlendir
                </a>
                <a href="/dealer/leads"
                   class="nav-link {{ request()->is('dealer/leads') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span> Yönlendirmelerim
                </a>
                {{-- Tier 2+ : Süreç takibi görünür --}}
                @if($tierPerms->can('canViewProcessDetails'))
                <a href="/dealer/leads?view=process"
                   class="nav-link {{ request()->is('dealer/leads') && request('view')==='process' ? 'active' : '' }}"
                   style="font-size:11px;padding-left:28px;">
                    <span class="nav-icon" style="font-size:13px;">🔄</span> Süreç Takibi
                </a>
                @endif
            </div>

            {{-- Finans — tüm tierlar --}}
            <div class="nav-section">
                <div class="nav-section-label">Finans</div>
                <a href="/dealer/earnings"
                   class="nav-link {{ request()->is('dealer/earnings') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Kazancım
                </a>
                @if($tierPerms->can('canAccessCalculator'))
                <a href="/dealer/calculator"
                   class="nav-link {{ request()->is('dealer/calculator') ? 'active' : '' }}">
                    <span class="nav-icon">🧮</span> Komisyon Hesapla
                </a>
                @endif
                <a href="/dealer/payments"
                   class="nav-link {{ request()->is('dealer/payments') ? 'active' : '' }}">
                    <span class="nav-icon">💳</span> Ödemeler
                </a>
                <a href="/dealer/contracts"
                   class="nav-link {{ request()->is('dealer/contracts*') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> Sözleşmelerim
                </a>
            </div>

            {{-- Araçlar --}}
            <div class="nav-section">
                <div class="nav-section-label">Araçlar</div>
                @if($tierPerms->can('canAccessSupport'))
                <a href="/dealer/advisor"
                   class="nav-link {{ request()->is('dealer/advisor') ? 'active' : '' }}">
                    <span class="nav-icon">{{ $tierPerms->isBasic() ? '🎫' : '👨‍💼' }}</span>
                    {{ $tierPerms->isBasic() ? 'Destek Talebi' : 'Danışmanım' }}
                </a>
                @endif
                @if($tierPerms->can('canAccessTraining'))
                <a href="/dealer/training"
                   class="nav-link {{ request()->is('dealer/training') ? 'active' : '' }}">
                    <span class="nav-icon">📚</span> Eğitim Merkezi
                </a>
                @endif
                {{-- Tier 2+ --}}
                @if($tierPerms->isStandard())
                <a href="/dealer/referral-links"
                   class="nav-link {{ request()->is('dealer/referral-links') ? 'active' : '' }}">
                    <span class="nav-icon">🔗</span> Referans Linklerim
                </a>
                <a href="/dealer/performance"
                   class="nav-link {{ request()->is('dealer/performance') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Performans Raporu
                </a>
                @endif
                {{-- Tier 3 --}}
                @if($tierPerms->isAdvanced())
                <a href="/dealer/calendar"
                   class="nav-link {{ request()->is('dealer/calendar') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span> Takvimim
                </a>
                @endif
            </div>

            {{-- Hesap — tüm tierlar --}}
            <div class="nav-section">
                <div class="nav-section-label">Hesap</div>
                @php
                    $dealerNotifUnread = \App\Models\NotificationDispatch::where('recipient_email', auth()->user()?->email ?? '')
                        ->where('is_read', false)->where('status', 'sent')->count();
                @endphp
                <a href="/dealer/notifications"
                   class="nav-link {{ request()->is('dealer/notifications') ? 'active' : '' }}">
                    <span class="nav-icon">🔔</span> Bildirimlerim
                    @if($dealerNotifUnread > 0)<span class="nav-badge">{{ $dealerNotifUnread }}</span>@endif
                </a>
                <a href="/dealer/profile"
                   class="nav-link {{ request()->is('dealer/profile') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Profilim
                </a>
                <a href="/dealer/settings"
                   class="nav-link {{ request()->is('dealer/settings') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Ayarlar
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('dealer.handbook') }}" class="nav-link {{ request()->routeIs('dealer.handbook') ? 'active' : '' }}" style="margin-bottom:6px;">
                <span class="nav-icon">📖</span> Bayi Kılavuzu
            </a>
            <a href="/logout" class="nav-link logout">
                <span class="nav-icon">🚪</span> Çıkış Yap
            </a>
        </div>
    </aside>

    {{-- ─── Main ─── --}}
    <div class="main">

        {{-- Topbar --}}
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn" id="premium-menu-btn"
                        onclick="document.getElementById('premium-sidebar').classList.toggle('mobile-open');document.getElementById('premium-overlay').classList.toggle('active');"
                        style="display:none;">☰</button>
                <button class="icon-btn" onclick="history.back()" title="Geri dön" style="font-size:18px;line-height:1;">&#8592;</button>
                <div>
                    <div class="topbar-title">@yield('page_title', 'Bayi Portalı')</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                <button class="icon-btn" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" id="design-btn" title="Tasarım Teması">🎨</button>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ $dealerUser?->name ?? 'Bayi' }}">
                    {{ $dealerInitials }}
                </div>
            </div>
        </header>

        {{-- Content --}}
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
    </div>{{-- /main --}}
</div>{{-- /app --}}

{{-- Mobile overlay --}}
<div id="premium-overlay"
     onclick="document.getElementById('premium-sidebar').classList.remove('mobile-open');this.classList.remove('active');"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"></div>
<style>
    #premium-overlay.active { display:block; }
    @media(max-width:900px){
        #premium-menu-btn{display:flex!important;}
    }
</style>

{{-- Dark mode toggle FAB --}}
<div class="dark-toggle" id="theme-toggle" title="Tema Değiştir">
    <span id="theme-icon">🌙</span>
</div>

{{-- Toast container --}}
<div id="toast-container" style="position:fixed;bottom:20px;right:80px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:360px;"></div>

{{-- Alpine.js --}}
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs  = $__manifest['resources/js/app.js']['file'] ?? null;
    $__appCss = $__manifest['resources/css/app.css']['file'] ?? null;
@endphp
@if($__appCss)<link rel="stylesheet" href="/build/{{ $__appCss }}">@endif
@if($__appJs)<script type="module" src="/build/{{ $__appJs }}"></script>@endif

{{-- Theme + Toast + Dark Mode --}}
<script nonce="{{ $cspNonce ?? '' }}">
function __designToggle(){
    var link=document.getElementById('mentorde-theme-css');
    if(!link)return;
    var isMin=!!document.getElementById('minimalist-css-pre')||link.disabled;
    var next=isMin?'premium':'minimalist';
    localStorage.setItem('mentorde_design',next);document.documentElement.classList.toggle('jm-minimalist',next==='minimalist');if(window.__iconSwitcher)window.__iconSwitcher.apply(next);
    if(next==='minimalist'){
        if(!document.getElementById('minimalist-css-pre')){var ml=document.createElement('link');ml.rel='stylesheet';ml.id='minimalist-css-pre';ml.href='{{ Vite::asset('resources/css/minimalist.css') }}';document.head.appendChild(ml);ml.onload=function(){link.disabled=true;};}else{link.disabled=true;}
        if(!document.getElementById('design-override')){var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#111111;--c-accent2:#333333;--accent-soft:rgba(0,0,0,.04);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#111111;--bg:#e8e8e8;--border:#c0c0c0;--u-line:#c0c0c0;--u-shadow:0 2px 6px rgba(0,0,0,.09);--u-shadow-md:0 4px 12px rgba(0,0,0,.11);--shadow:0 2px 6px rgba(0,0,0,.09);--shadow-md:0 4px 12px rgba(0,0,0,.11);}';document.head.appendChild(s);}
    } else {
        link.disabled=false;
        var ml2=document.getElementById('minimalist-css-pre');if(ml2)ml2.remove();
        var ov=document.getElementById('design-override');if(ov)ov.remove();
    }
    var btn=document.getElementById('design-btn');
    if(btn)btn.textContent=next==='minimalist'?'◎':'🎨';
}
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
// ── İkon başlangıç değerleri (DOMContentLoaded sonrası) ──
document.addEventListener('DOMContentLoaded',function(){
    if(localStorage.getItem('mentorde_design')==='minimalist'){var b=document.getElementById('design-btn');if(b)b.textContent='◎';}
    if(localStorage.getItem('mentorde_dark')==='true'){['dm-btn','theme-icon'].forEach(function(id){var el=document.getElementById(id);if(el)el.textContent='☀️';});}
});

document.addEventListener('alpine:init',function(){
    Alpine.effect(function(){
        var items=Alpine.store('toast').items;
        var c=document.getElementById('toast-container');
        if(!c)return;
        c.innerHTML=items.map(function(i){
            var bg=i.type==='ok'?'var(--c-ok)':i.type==='danger'?'var(--c-danger)':i.type==='warn'?'var(--c-warn)':'var(--c-accent)';
            return '<div style="background:'+bg+';color:#fff;padding:12px 18px;border-radius:var(--r-sm,8px);box-shadow:0 4px 16px rgba(0,0,0,.2);font-size:13px;font-weight:500;animation:slideIn .3s ease">'+i.message+'</div>';
        }).join('');
    });
});
// ── Tema butonları (CSP-safe, aynı nonce bloğu) ──
(function(){
    var _dm=document.getElementById('dm-btn');
    var _dt=document.getElementById('theme-toggle');
    var _dg=document.getElementById('design-btn');
    if(_dm)_dm.addEventListener('click',__dmToggle);
    if(_dt)_dt.addEventListener('click',__dmToggle);
    if(_dg)_dg.addEventListener('click',__designToggle);
})();
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
    var STORE_KEY='dealer_sidebar_collapsed';
    var VER='v2';
    if(localStorage.getItem(STORE_KEY+'_ver')!==VER){localStorage.removeItem(STORE_KEY);localStorage.setItem(STORE_KEY+'_ver',VER);}
    function getC(){try{return JSON.parse(localStorage.getItem(STORE_KEY)||'[]');}catch(e){return[];}}
    function saveC(a){localStorage.setItem(STORE_KEY,JSON.stringify(a));}
    // ── Mobile hamburger (CSP-safe) ──
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb)_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});
    if(_ov)_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});
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
<script nonce="{{ $cspNonce ?? '' }}">window.__tenorKey={{ Js::from(config('services.tenor.key','')) }};</script>
@stack('scripts')

@include('partials.welcome-video-modal', ['wvPortal' => 'dealer'])
</body>
</html>
