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
    {{-- Chrome View Transitions API: sayfa geçişlerinde cross-fade, beyaz flash'ı engeller --}}
    <meta name="view-transition" content="same-origin">
    <style>
        @view-transition { navigation: auto; }
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: 180ms;
            animation-timing-function: ease-out;
        }
        html, body, .app, .main, .content { background: var(--bg, #f1f5f9) !important; }
    </style>
    <title>@yield('title', config('brand.name', 'MentorDE') . ' — Öğrenci Portalı')</title>

    {{-- Preconnect: CDN (chart.js, fullcalendar) + Tenor GIF --}}
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://tenor.googleapis.com">

    {{-- Premium Design System --}}
    <link id="mentorde-theme-css" rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">
    {{-- Vite app.css — preload + onload swap: FOUC olmadan non-blocking yükleme --}}
    @php
        $__headManifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
        $__headAppCss = $__headManifest['resources/css/app.css']['file'] ?? null;
    @endphp
    @if($__headAppCss)
    <link rel="preload" href="/build/{{ $__headAppCss }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/build/{{ $__headAppCss }}"></noscript>
    @endif

    {{-- Student portal accent (mor) + backwards-compat bridge --}}
    <style>
        :root {
            /* Student accent — tema panelinden ayarlanabilir */
            --c-accent:  var(--theme-accent-student, #7c3aed);
            --c-accent2: var(--theme-accent-student, #6d28d9);
            --accent-soft: rgba(124,58,237,.09);
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-student, #7c3aed), var(--theme-hero-to-student, #2563eb));

            /* Bridge: legacy --u-* → premium.css vars */
            --u-card:      var(--surface, #ffffff);
            --u-line:      var(--border,  #e2e8f0);
            --u-text:      var(--text,    #0f172a);
            --u-muted:     var(--muted,   #64748b);
            --u-brand:     var(--c-accent,  #7c3aed);
            --u-brand-2:   var(--c-accent2, #6d28d9);
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

        /* Panel compat — premium.css'te tanımsız, student sayfaları .panel kullanıyor */
        .panel {
            background: var(--u-card);
            border: 1px solid var(--u-line);
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 12px;
            box-shadow: var(--u-shadow);
        }
        .list { border: 1px solid var(--u-line); border-radius: 10px; overflow: hidden; }
        .list .item { padding: 10px 14px; border-bottom: 1px solid var(--u-line); font-size: 13px; }
        .list .item:last-child { border-bottom: none; }

        /* Btn compat */
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:var(--tx-sm,13px);font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
        .btn.alt     { background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text,#0f172a); }
        .btn.alt:hover { border-color:var(--c-accent);color:var(--c-accent); }
        .btn.ok      { background:var(--c-ok,#16a34a);color:#fff;border-color:transparent; }
        .btn.ok:hover{ opacity:.9; }
        .btn.warn    { background:var(--c-danger,#dc2626);color:#fff;border-color:transparent; }
    </style>

    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} --tx-xs:calc(var(--theme-font-size-student,15px)*.733);--tx-sm:calc(var(--theme-font-size-student,15px)*.867);--tx-base:var(--theme-font-size-student,15px);--tx-lg:calc(var(--theme-font-size-student,15px)*1.2);--tx-xl:calc(var(--theme-font-size-student,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-student,15px)*1.867); }html{font-size:var(--theme-font-size-student,15px);font-family:var(--theme-font-family-student,inherit);}</style>
    @endif

    {{-- Student portal sidebar: per-portal tema renklerini kullan (minimalist'te dokunulmaz) --}}
    <style>
    html:not(.jm-minimalist) .sidebar {
        background: linear-gradient(180deg, var(--theme-sidebar-from-student, #162C4A), var(--theme-sidebar-to-student, #1E3D6B));
    }
    </style>

    <link rel="manifest" href="{{ asset('manifest-student.json') }}">
    <meta name="theme-color" content="#2563eb">

    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Sidebar Header (merged brand + user) --}}
        @php
            $user           = $user ?? auth()->user();
            $sidePhoto      = trim((string) ($guestApplication?->profile_photo_path ?? ''));
            $sideInitials   = strtoupper(substr((string) ($user?->name ?? 'ST'), 0, 2));
            $progressList   = collect($progressSteps ?? []);
            $progressDone   = $progressList->where('done', true)->count();
            $progressTotal  = max(1, $progressList->count());
            $sideProgressPct = (int) round(($progressDone / $progressTotal) * 100);

            $isKayit    = request()->is('student/registration*','student/process-tracking*','student/university-applications*','student/institution-documents*','student/contract*','student/appointments*','student/vault*','student/services*','student/checklist*','student/calendar*','student/onboarding*','student/visa*','student/housing*');
            $isIletisim = request()->is('student/messages*','student/tickets*');
            $isAraclar  = request()->is('student/document-builder*','student/materials*');
            $isHesap    = request()->is('student/notifications*','student/payments*','student/profile*','student/settings*','student/feedback*');
        @endphp
        <div style="padding:14px 14px 0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:46px;height:46px;font-size:17px;flex-shrink:0;">
                    @if ($sidePhoto !== '')
                        <img src="{{ asset('storage/'.$sidePhoto) }}" alt="Profil" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    @else
                        {{ $sideInitials }}
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user?->name ?? 'Öğrenci' }}</div>
                    <div class="user-role">Öğrenci</div>
                </div>
                @php $slBrandName = config('brand.name', 'MentorDE'); $slBrandLogo = config('brand.logo_url') ?: config('brand.logo_path'); @endphp
                @if($slBrandLogo)
                    <div class="brand-logo" style="width:28px;height:28px;flex-shrink:0;background:transparent;padding:0;" title="{{ $slBrandName }}"><img src="{{ $slBrandLogo }}" alt="{{ $slBrandName }}" style="max-height:28px;max-width:28px;object-fit:contain;"></div>
                @else
                    <div class="brand-logo" style="width:28px;height:28px;font-size:11px;flex-shrink:0;" title="{{ $slBrandName }}">{{ strtoupper(mb_substr($slBrandName, 0, 1)) }}</div>
                @endif
            </div>
            <div style="font-size:10px;color:var(--muted);font-weight:600;letter-spacing:.03em;margin-top:6px;padding-left:56px;">{{ $slBrandName }} · Öğrenci Portalı</div>
            @if($sideProgressPct > 0)
            <div style="margin-top:8px;">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-bottom:3px;">
                    <span>Süreç</span><span>%{{ $sideProgressPct }}</span>
                </div>
                <div class="progress-bar"><div class="progress-fill ok" style="width:{{ $sideProgressPct }}%;"></div></div>
            </div>
            @endif
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="/student/dashboard"
                   class="nav-link {{ request()->is('student/dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Ana Sayfa
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Kayit Sureci</div>
                <a href="/student/registration"
                   class="nav-link {{ request()->is('student/registration') ? 'active' : '' }}">
                    <span class="nav-icon">📝</span> Kayit Formu
                    @if(!empty($guestApplication?->registration_form_submitted_at))
                        <span class="nav-badge" style="background:rgba(22,163,74,.15);color:#16a34a;font-size:9px;padding:1px 6px;border-radius:6px;margin-left:auto;">✓</span>
                    @endif
                </a>
                <a href="/student/registration/documents"
                   class="nav-link {{ request()->is('student/registration/documents') ? 'active' : '' }}">
                    <span class="nav-icon">📄</span> Belgelerim
                    @if(isset($docSummary))
                        <span class="nav-badge" style="background:{{ ($guestApplication?->docs_ready ?? false) ? 'rgba(22,163,74,.15)' : 'rgba(217,119,6,.15)' }};color:{{ ($guestApplication?->docs_ready ?? false) ? '#16a34a' : '#d97706' }};font-size:9px;padding:1px 6px;border-radius:6px;margin-left:auto;">{{ $docSummary['approved'] ?? 0 }}/{{ $docSummary['total'] ?? 0 }}</span>
                    @endif
                </a>
                <a href="/student/contract"
                   class="nav-link {{ request()->is('student/contract*') ? 'active' : '' }}">
                    <span class="nav-icon">📜</span> Sozlesme
                    @if(($guestApplication?->contract_status ?? '') === 'approved')
                        <span class="nav-badge" style="background:rgba(22,163,74,.15);color:#16a34a;font-size:9px;padding:1px 6px;border-radius:6px;margin-left:auto;">✓</span>
                    @endif
                </a>
                <a href="/student/process-tracking"
                   class="nav-link {{ request()->is('student/process-tracking*') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Surec Takibi
                </a>
                <a href="/student/university-applications"
                   class="nav-link {{ request()->is('student/university-applications*','student/institution-documents*') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Universiteler
                </a>
                <a href="/student/appointments"
                   class="nav-link {{ request()->is('student/appointments*') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span> Randevular
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Iletisim</div>
                <a href="/student/messages"
                   class="nav-link {{ request()->is('student/messages*') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> Mesajlar
                    @if((int)($dmUnread ?? 0) > 0)
                        <span class="nav-badge">{{ (int)$dmUnread }}</span>
                    @endif
                </a>
                <a href="/student/tickets"
                   class="nav-link {{ request()->is('student/tickets*') ? 'active' : '' }}">
                    <span class="nav-icon">📞</span> Destek
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Araclar</div>
                <a href="/student/services"
                   class="nav-link {{ request()->is('student/services*') ? 'active' : '' }}">
                    <span class="nav-icon">📦</span> Servisler
                </a>
                <a href="/student/ai-assistant"
                   class="nav-link {{ request()->is('student/ai-assistant*') ? 'active' : '' }}">
                    <span class="nav-icon">🤖</span> AI Asistan
                </a>
                <a href="/student/document-builder"
                   class="nav-link {{ request()->is('student/document-builder*') ? 'active' : '' }}">
                    <span class="nav-icon">📝</span> Dokuman Olustur
                </a>
                <a href="/student/cost-calculator"
                   class="nav-link {{ request()->is('student/cost-calculator*') ? 'active' : '' }}">
                    <span class="nav-icon">🧮</span> Maliyet Hesapla
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Kesfet</div>
                <a href="/student/visa"
                   class="nav-link {{ request()->is('student/visa*') ? 'active' : '' }}">
                    <span class="nav-icon">🛂</span> Vize Takibi
                </a>
                <a href="/student/housing"
                   class="nav-link {{ request()->is('student/housing*') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Konut & Barinma
                </a>
                <a href="/student/materials"
                   class="nav-link {{ request()->is('student/materials*') ? 'active' : '' }}">
                    <span class="nav-icon">📚</span> Materyaller
                </a>
                <a href="{{ route('student.discover') }}"
                   class="nav-link {{ request()->routeIs('student.discover') ? 'active' : '' }}">
                    <span class="nav-icon">🧭</span> Icerikler
                </a>
                <a href="{{ route('student.saved') }}"
                   class="nav-link {{ request()->routeIs('student.saved') ? 'active' : '' }}">
                    <span class="nav-icon">🔖</span> Favorilerim
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Hesap</div>
                <a href="/student/payments"
                   class="nav-link {{ request()->is('student/payments*') ? 'active' : '' }}">
                    <span class="nav-icon">💳</span> Odeme Durumum
                </a>
                <a href="/student/profile"
                   class="nav-link {{ request()->is('student/profile*') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Profil
                </a>
                <a href="/student/settings"
                   class="nav-link {{ request()->is('student/settings*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Ayarlar
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('student.handbook') }}" class="nav-link {{ request()->routeIs('student.handbook') ? 'active' : '' }}" style="margin-bottom:6px;">
                <span class="nav-icon">📖</span> Öğrenci Kılavuzu
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
            <div class="topbar-left" style="flex:0 1 auto;">
                <button class="icon-btn" id="premium-menu-btn"
                        style="display:none;">☰</button>
                <button class="icon-btn" id="premium-back-btn" title="Geri dön" style="font-size:18px;line-height:1;">&#8592;</button>
                <div>
                    <div class="topbar-title">@yield('page_title', 'Öğrenci Portalı')</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            {{-- Global search (topbar center) --}}
            <div style="position:relative;flex:1 1 300px;max-width:520px;margin:0 20px;" id="gs-wrap">
                <input type="text" id="gs-input" placeholder="🔍 Ara..." autocomplete="off" minlength="2"
                       style="width:100%;padding:9px 16px;border:1px solid var(--border,#d1d5db);border-radius:10px;font-size:14px;background:var(--surface,#f9fafb);color:var(--text,#111);outline:none;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <div id="gs-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;min-width:400px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9000;max-height:400px;overflow-y:auto;"></div>
            </div>

            <div class="topbar-right">
                <button class="icon-btn" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" id="design-btn" title="Tasarım Teması">🎨</button>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ $user?->name ?? 'Öğrenci' }}">
                    {{ $sideInitials }}
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
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:min(360px, calc(100vw - 40px));"></div>

{{-- Alpine.js --}}
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs  = $__manifest['resources/js/app.js']['file'] ?? null;
@endphp
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
    var STORE_KEY='student_sidebar_collapsed';
    function getCollapsed(){try{return JSON.parse(localStorage.getItem(STORE_KEY)||'[]');}catch(e){return[];}}
    function saveCollapsed(arr){localStorage.setItem(STORE_KEY,JSON.stringify(arr));}
    var _bb=document.getElementById('premium-back-btn');
    if(_bb){_bb.addEventListener('click',function(){history.back();});}
    // ── Mobile hamburger (CSP-safe) ──
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb)_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});
    if(_ov)_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});
    // Script is at bottom of body — DOM is already ready, run directly
    var labels=document.querySelectorAll('.nav-section-label');
    var collapsed=getCollapsed();
    labels.forEach(function(label){
        var section=label.closest('.nav-section');
        if(!section)return;
        var key=label.textContent.trim();
        if(collapsed.indexOf(key)>-1)section.classList.add('collapsed');
        label.addEventListener('click',function(){
            var isCollapsed=section.classList.toggle('collapsed');
            var arr=getCollapsed();
            if(isCollapsed){if(arr.indexOf(key)<0)arr.push(key);}
            else{arr=arr.filter(function(k){return k!==key;});}
            saveCollapsed(arr);
        });
    });
}());
</script>

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
            fetch('/student/search?q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}})
                .then(function(r){return r.json();})
                .then(function(d){
                    box.innerHTML=(d.results&&d.results.length)
                        ?d.results.map(function(x){return '<a href="'+x.url+'" style="display:flex;gap:10px;align-items:flex-start;padding:9px 12px;border-bottom:1px solid var(--border,#f3f4f6);text-decoration:none;color:var(--text,#111827);">'
                            +'<span style="font-size:16px;flex-shrink:0;">'+x.icon+'</span>'
                            +'<div><div style="font-size:13px;font-weight:600;">'+x.title+'</div><div style="font-size:11px;color:var(--muted,#9ca3af);">'+x.sub+' &mdash; '+(x.date||'')+'</div></div></a>';}).join('')
                        :'<div style="padding:12px;font-size:13px;color:var(--muted,#9ca3af);text-align:center;">Sonuç bulunamadı.</div>';
                    box.style.display='block';
                }).catch(function(){});
        },300);
    });
    document.addEventListener('click',function(e){
        var wrap=document.getElementById('gs-wrap');
        if(wrap&&!wrap.contains(e.target))box.style.display='none';
    });
}());
</script>

<script defer src="{{ Vite::asset('resources/js/icon-switcher.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
if('serviceWorker' in navigator){
    navigator.serviceWorker.register('/sw-student.js',{scope:'/student/'}).catch(function(){});
}
</script>
<script nonce="{{ $cspNonce ?? '' }}">window.__giphyKey={{ Js::from(config('services.giphy.key','')) }};</script>
@stack('scripts')

@include('partials.welcome-video-modal', ['wvPortal' => 'student'])
@include('partials.promo-popup')
</body>
</html>
