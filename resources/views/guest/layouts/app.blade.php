<!DOCTYPE html>
<html lang="tr" data-theme="{{ session('mentorde_theme_v2', 'light') }}">
<head>
    {{-- Dark restore (before render) --}}
    <script nonce="{{ $cspNonce ?? '' }}">
    !function(){
        var t=localStorage.getItem('mentorde_dark');
        if(t==='true'){document.documentElement.setAttribute('data-theme','dark');document.documentElement.classList.add('dark');}
        var d=localStorage.getItem('mentorde_design');
        if(d==='minimalist'){document.documentElement.classList.add('jm-minimalist');var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#1e3a8a;--c-accent2:#1e40af;--accent-soft:rgba(30,58,138,.06);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#1e3a8a;--sp-fg:var(--u-text,#1a1a1a);--sp-sep:var(--u-muted,#888);--bg:#efefef;--border:#d0d0d0;--u-line:#d0d0d0;--u-shadow:0 1px 4px rgba(0,0,0,.07);--u-shadow-md:0 3px 10px rgba(0,0,0,.09);--shadow:0 1px 4px rgba(0,0,0,.07);--shadow-md:0 3px 10px rgba(0,0,0,.09);}';document.head.appendChild(s);var ml=document.createElement('link');ml.rel='stylesheet';ml.id='minimalist-css-pre';ml.href='{{ Vite::asset('resources/css/minimalist.css') }}';document.head.appendChild(ml);document.addEventListener('DOMContentLoaded',function(){var l=document.getElementById('mentorde-theme-css');if(l)l.disabled=true;});}
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

    {{-- Preconnect: CDN (twemoji, chart.js) + Tenor GIF --}}
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

    {{-- Guest portal accent (mavi) + backwards-compat bridge --}}
    <style>
        /* Twemoji SVG boyut normalizasyonu */
        img.emoji {
            height: 1.1em;
            width: 1.1em;
            margin: 0 .05em 0 .1em;
            vertical-align: -0.1em;
            display: inline-block;
        }

        :root {
            /* Guest accent — tema panelinden ayarlanabilir */
            --c-accent:  var(--theme-accent-guest, #2563eb);
            --c-accent2: var(--theme-accent-guest, #1d4ed8);
            --accent-soft: rgba(37,99,235,.09);
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-guest, #1e3a8a), var(--theme-hero-to-guest, #3b82f6));
            --sp-fg:  #fff;
            --sp-sep: rgba(255,255,255,.6);

            /* Bridge: legacy --u-* → premium.css vars (all guest pages use these) */
            --u-card:      var(--surface, #ffffff);
            --u-line:      var(--border,  #e2e8f0);
            --u-text:      var(--text,    #0f172a);
            --u-muted:     var(--muted,   #64748b);
            --u-brand:     var(--c-accent,  #2563eb);
            --u-brand-2:   var(--c-accent2, #1d4ed8);
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

        /* Btn compat (.btn.alt, .btn.ok, .btn.warn) */
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
        .btn.alt     { background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text,#0f172a); }
        .btn.alt:hover { border-color:var(--c-accent);color:var(--c-accent); }
        .btn.ok      { background:var(--c-ok,#16a34a);color:#fff;border-color:transparent; }
        .btn.ok:hover{ opacity:.9; }
        .btn.warn    { background:var(--c-danger,#dc2626);color:#fff;border-color:transparent; }
    </style>

    <style>:root{ {!! $uiThemeCssVars ?? '' !!} --tx-xs:calc(var(--theme-font-size-guest,15px)*.733);--tx-sm:calc(var(--theme-font-size-guest,15px)*.867);--tx-base:var(--theme-font-size-guest,15px);--tx-lg:calc(var(--theme-font-size-guest,15px)*1.2);--tx-xl:calc(var(--theme-font-size-guest,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-guest,15px)*1.867); }html{font-size:var(--theme-font-size-guest,15px);font-family:var(--theme-font-family-guest,inherit);}</style>

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest-guest.json') }}">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('brand.name', 'MentorDE') }}">

    {{-- Guest portal sidebar: per-portal tema renklerini kullan --}}
    <style>
    html:not(.jm-minimalist) .sidebar {
        background: linear-gradient(180deg, var(--theme-sidebar-from-guest, #162C4A), var(--theme-sidebar-to-guest, #1E3D6B));
    }
    @media(max-width:600px){
        .sp-bar { padding: 8px 12px !important; gap: 6px 12px !important; }
        .sp-bar span { font-size: 11px !important; }
        .sp-sep { display: none !important; }
    }
    </style>

    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Sidebar Header (merged brand + user) --}}
        @php
            $sidePhoto    = trim((string) ($guest?->profile_photo_path ?? ''));
            $sideInitials = strtoupper(substr(trim(($guest?->first_name ?? 'G').' '.($guest?->last_name ?? 'U')), 0, 2));
            $isGuestComm  = request()->routeIs('guest.messages', 'guest.tickets');
            $isGuestKayit = request()->routeIs('guest.registration.form', 'guest.registration.documents', 'guest.contract', 'guest.services');
            $isGuestHesap = request()->routeIs('guest.profile', 'guest.settings', 'guest.help-center');
            $profilePct   = (int) ($profileCompletionPercent ?? $progressPercent ?? 0);
            $sideDisplayName = trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? '')) ?: ($user?->email ?? 'Kullanıcı');
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
                    <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sideDisplayName }}</div>
                    <div class="user-role">Aday Öğrenci</div>
                </div>
                @php $glBrandName = config('brand.name', 'MentorDE'); $glBrandLogo = config('brand.logo_url') ?: config('brand.logo_path'); @endphp
                @if($glBrandLogo)
                    <div class="brand-logo" style="width:28px;height:28px;flex-shrink:0;background:transparent;padding:0;" title="{{ $glBrandName }}"><img src="{{ $glBrandLogo }}" alt="{{ $glBrandName }}" style="max-height:28px;max-width:28px;object-fit:contain;"></div>
                @else
                    <div class="brand-logo" style="width:28px;height:28px;font-size:11px;flex-shrink:0;" title="{{ $glBrandName }}">{{ strtoupper(mb_substr($glBrandName, 0, 1)) }}</div>
                @endif
            </div>
            <div style="font-size:10px;color:var(--muted);font-weight:600;letter-spacing:.03em;margin-top:6px;padding-left:56px;">{{ $glBrandName }} · Öğrenci Portalı</div>
            @if($profilePct > 0)
            <div style="margin-top:8px;">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-bottom:3px;">
                    <span>Profil</span><span>%{{ $profilePct }}</span>
                </div>
                <div class="progress-bar"><div class="progress-fill ok" style="width:{{ $profilePct }}%;"></div></div>
            </div>
            @endif
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-label">Genel</div>
                <a href="{{ route('guest.dashboard') }}"
                   class="nav-link {{ request()->routeIs('guest.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Ana Sayfa
                </a>
                <a href="{{ route('guest.registration.form') }}"
                   class="nav-link {{ request()->routeIs('guest.registration.form') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> Başvuru Formu
                </a>
                <a href="{{ route('guest.registration.documents') }}"
                   class="nav-link {{ request()->routeIs('guest.registration.documents') ? 'active' : '' }}">
                    <span class="nav-icon">📂</span> Belgelerim
                    @if((int)($guestDocsUnread ?? 0) > 0)
                        <span class="nav-badge">{{ (int)$guestDocsUnread }}</span>
                    @endif
                </a>
                <a href="{{ route('guest.services') }}"
                   class="nav-link {{ request()->routeIs('guest.services') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Hizmetler
                </a>
                <a href="{{ route('guest.contract') }}"
                   class="nav-link {{ request()->routeIs('guest.contract') ? 'active' : '' }}">
                    <span class="nav-icon">📜</span> Sözleşmem
                </a>
                <a href="{{ route('guest.timeline') }}"
                   class="nav-link {{ request()->routeIs('guest.timeline') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span> Süreç Takvimi
                </a>
                <a href="{{ route('guest.cost-calculator') }}"
                   class="nav-link {{ request()->routeIs('guest.cost-calculator') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Maliyet Hesabı
                </a>
                <a href="{{ route('guest.ai-assistant') }}"
                   class="nav-link {{ request()->routeIs('guest.ai-assistant*') ? 'active' : '' }}">
                    <span class="nav-icon">🤖</span> AI Asistan
                    <span style="margin-left:auto;font-size:9px;font-weight:600;background:var(--accent-soft,rgba(0,0,0,.05));color:var(--text,#1a1a1a);padding:1px 6px;border-radius:4px;">Yeni</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">İletişim</div>
                <a href="{{ route('guest.messages') }}"
                   class="nav-link {{ request()->routeIs('guest.messages') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> Mesajlar
                    @if((int)($guestDmUnread ?? 0) > 0)
                        <span class="nav-badge" style="background:var(--c-info,#2678bd);">{{ (int)$guestDmUnread }}</span>
                    @endif
                </a>
                <a href="{{ route('guest.tickets') }}"
                   class="nav-link {{ request()->routeIs('guest.tickets') ? 'active' : '' }}">
                    <span class="nav-icon">🎫</span> Destek Talebi
                </a>
                <a href="{{ route('guest.feedback') }}"
                   class="nav-link {{ request()->routeIs('guest.feedback') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> Geri Bildirim
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Keşfet</div>
                <a href="{{ route('guest.university-guide') }}"
                   class="nav-link {{ request()->routeIs('guest.university-guide') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Üniversite Rehberi
                </a>
                <a href="{{ route('guest.document-guide') }}"
                   class="nav-link {{ request()->routeIs('guest.document-guide') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> Belge Hazırlama
                </a>
                <a href="{{ route('guest.success-stories') }}"
                   class="nav-link {{ request()->routeIs('guest.success-stories') ? 'active' : '' }}">
                    <span class="nav-icon">⭐</span> Başarı Hikayeleri
                </a>
                <a href="{{ route('guest.living-guide') }}"
                   class="nav-link {{ request()->routeIs('guest.living-guide') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Almanya'da Yaşam
                </a>
                <a href="{{ route('guest.vize-guide') }}"
                   class="nav-link {{ request()->routeIs('guest.vize-guide') ? 'active' : '' }}">
                    <span class="nav-icon">🛂</span> Vize & Sperrkonto
                </a>
                <a href="{{ route('guest.discover') }}"
                   class="nav-link {{ request()->routeIs('guest.discover') ? 'active' : '' }}">
                    <span class="nav-icon">📚</span> Tüm İçerikler
                </a>
                <a href="{{ route('guest.saved') }}"
                   class="nav-link {{ request()->routeIs('guest.saved') ? 'active' : '' }}">
                    <span class="nav-icon">🔖</span> Favorilerim
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Hesap</div>
                <a href="{{ route('guest.profile') }}"
                   class="nav-link {{ request()->routeIs('guest.profile') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Profilim
                </a>
                <a href="{{ route('guest.settings') }}"
                   class="nav-link {{ request()->routeIs('guest.settings') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Ayarlar
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('guest.handbook') }}" class="nav-link {{ request()->routeIs('guest.handbook') ? 'active' : '' }}" style="margin-bottom:6px;">
                <span class="nav-icon">📖</span> Kullanıcı Kılavuzu
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
                        style="display:none;">☰</button>
                <button class="icon-btn" id="premium-back-btn" title="Geri dön" style="font-size:18px;line-height:1;">&#8592;</button>
                <div>
                    <div class="topbar-title">@yield('page_title', config('brand.name', 'MentorDE'))</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                {{-- Global search --}}
                <div style="position:relative;max-width:240px;" id="gs-wrap">
                    <input type="text" id="gs-input" placeholder="Ara..." autocomplete="off" minlength="2"
                           style="width:100%;padding:6px 10px;border:1px solid var(--border,#e2e8f0);border-radius:8px;font-size:13px;background:var(--surface,#fff);color:var(--text,#111);outline:none;">
                    <div id="gs-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:9000;max-height:360px;overflow-y:auto;"></div>
                </div>
                <form method="POST" action="/language" style="display:inline-flex;align-items:center;gap:6px;">
                    @csrf
                    <select name="locale" onchange="this.form.submit()"
                            style="border:1px solid var(--u-line);border-radius:6px;padding:4px 8px;font-size:12px;background:var(--u-card);color:var(--u-text);cursor:pointer;">
                        <option value="tr" {{ app()->getLocale()==='tr' ? 'selected' : '' }}>TR</option>
                        <option value="de" {{ app()->getLocale()==='de' ? 'selected' : '' }}>DE</option>
                        <option value="en" {{ app()->getLocale()==='en' ? 'selected' : '' }}>EN</option>
                    </select>
                </form>
                <button class="icon-btn" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" id="design-btn" title="Tasarım Teması">🎨</button>
                <a href="{{ route('guest.messages') }}" class="icon-btn" title="Mesajlar">
                    💬
                    @if((int)($guestDmUnread ?? 0) > 0)<span class="notif-dot"></span>@endif
                </a>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ auth()->user()?->name ?? 'Kullanıcı' }}">
                    {{ $sideInitials }}
                </div>
            </div>
        </header>

        {{-- Content --}}
        <div class="content">
            {{-- Social proof bar --}}
            @if(!empty($socialProof))
            <div class="sp-bar" style="background:var(--hero-gradient);border-radius:10px;padding:10px 16px;display:flex;gap:12px 20px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
                <span style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.01em;">🎓 {{ number_format((int)($socialProof['total_students'] ?? 0)) }}+ öğrenci Almanya'da</span>
                <span class="sp-sep" style="font-size:14px;color:rgba(255,255,255,.4);font-weight:300;">|</span>
                <span style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.01em;">🏛️ {{ (int)($socialProof['total_unis'] ?? 50) }}+ üniversite kabulü</span>
                <span class="sp-sep" style="font-size:14px;color:rgba(255,255,255,.4);font-weight:300;">|</span>
                <span style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.01em;">⭐ %{{ (int)($socialProof['satisfaction_pct'] ?? 95) }} memnuniyet</span>
            </div>
            @endif

            {{-- Flash messages --}}
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

            {{-- ── Portal Notice Flash Banner ── --}}
            @if(session('portal_notice'))
            @php $pn = session('portal_notice'); @endphp
            <div id="portalFlashBanner" role="alert" aria-live="polite"
                 style="display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:10px;margin-bottom:14px;font-size:13px;font-weight:600;
                 background:{{ match($pn['type'] ?? 'info') { 'ok','success' => 'rgba(22,163,74,.1)', 'warn','warning' => 'rgba(217,119,6,.1)', 'danger','error' => 'rgba(220,38,38,.1)', default => 'rgba(37,99,235,.1)' } }};
                 border:1px solid {{ match($pn['type'] ?? 'info') { 'ok','success' => 'rgba(22,163,74,.3)', 'warn','warning' => 'rgba(217,119,6,.3)', 'danger','error' => 'rgba(220,38,38,.3)', default => 'rgba(37,99,235,.3)' } }};
                 color:{{ match($pn['type'] ?? 'info') { 'ok','success' => '#15803d', 'warn','warning' => '#b45309', 'danger','error' => '#b91c1c', default => '#1d4ed8' } }};">
                <span style="flex:1;">{{ $pn['msg'] ?? '' }}</span>
                <button type="button" id="portalFlashBannerClose"
                        aria-label="Kapat" style="background:none;border:none;cursor:pointer;font-size:16px;color:inherit;padding:0;line-height:1;opacity:.7;">✕</button>
            </div>
            <script nonce="{{ $cspNonce ?? '' }}">(function(){var b=document.getElementById('portalFlashBanner');if(!b)return;setTimeout(function(){b.remove();},5000);var cl=document.getElementById('portalFlashBannerClose');if(cl)cl.addEventListener('click',function(){b.remove();});}());</script>
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
<div class="dark-toggle" title="Tema Değiştir" id="theme-toggle">
    <span id="theme-icon">🌙</span>
</div>

{{-- Toast container --}}
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:min(360px, calc(100vw - 40px));"></div>

{{-- Floating Chat Widget --}}
@php $scn = $seniorCard ?? null; @endphp
<div>
    <button class="gchat-fab" id="gchatFab" title="Danışmana Mesaj">
        💬
        <span class="gchat-badge" id="gchatBadge" style="display:none;">0</span>
    </button>
    <div class="gchat-panel" id="gchatPanel">
        <div class="gchat-header">
            <div class="gchat-avatar">{{ strtoupper(substr($scn['name'] ?? 'D', 0, 1)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="gchat-header-name">{{ $scn['name'] ?? 'Destek Ekibi' }}</div>
                <div class="gchat-status">
                    @php
                        $h = (int) now()->format('H');
                        $dow = now()->dayOfWeek;
                        $isBizHours = ($dow >= 1 && $dow <= 5 && $h >= 9 && $h < 18);
                    @endphp
                    <span class="gchat-status-dot {{ $isBizHours ? 'online' : '' }}"></span>
                    {{ $isBizHours ? 'Çevrimiçi' : 'Çevrimdışı' }}
                </div>
            </div>
            <a href="{{ route('guest.messages') }}" style="font-size:11px;color:rgba(255,255,255,.8);text-decoration:none;white-space:nowrap;">Tümünü Gör →</a>
        </div>
        <div class="gchat-messages" id="gchatMessages">
            <div class="gchat-empty" id="gchatEmpty">Danışmanınıza mesaj yazın.</div>
        </div>
        <div class="gchat-input-row">
            <textarea class="gchat-input" id="gchatInput" rows="1" placeholder="Mesajınızı yazın..."></textarea>
            <button class="gchat-send" id="gchatSend">Gönder</button>
        </div>
    </div>
</div>

{{-- Alpine.js — app.css zaten head'de yüklendi --}}
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
        if(!document.getElementById('design-override')){var s=document.createElement('style');s.id='design-override';s.textContent=':root{--c-accent:#1e3a8a;--c-accent2:#1e40af;--accent-soft:rgba(30,58,138,.06);--hero-gradient:var(--subtle,#f7f7f7);--u-brand:#1e3a8a;--sp-fg:var(--u-text,#1a1a1a);--sp-sep:var(--u-muted,#888);--bg:#efefef;--border:#d0d0d0;--u-line:#d0d0d0;--u-shadow:0 1px 4px rgba(0,0,0,.07);--u-shadow-md:0 3px 10px rgba(0,0,0,.09);--shadow:0 1px 4px rgba(0,0,0,.07);--shadow-md:0 3px 10px rgba(0,0,0,.09);}';document.head.appendChild(s);}
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
.nav-section-label{cursor:pointer;user-select:none;display:flex;align-items:center;justify-content:space-between;}
.nav-section-label::after{content:'▾';font-size:.7rem;opacity:.6;transition:transform .2s;}
.nav-section.collapsed .nav-section-label::after{transform:rotate(-90deg);}
.nav-section.collapsed .nav-link{display:none!important;}
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var STORE_KEY='guest_sidebar_collapsed';
    function getCollapsed(){try{return JSON.parse(localStorage.getItem(STORE_KEY)||'[]');}catch(e){return[];}}
    function saveCollapsed(arr){localStorage.setItem(STORE_KEY,JSON.stringify(arr));}
    // ── Mobile hamburger (CSP-safe) ──
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb){_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});}
    if(_ov){_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});}
    var _bb=document.getElementById('premium-back-btn');
    if(_bb){_bb.addEventListener('click',function(){history.back();});}
    // ── Sidebar nav collapse ──
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

{{-- Chat Widget JS --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var fab=document.getElementById('gchatFab');
    var panel=document.getElementById('gchatPanel');
    var badge=document.getElementById('gchatBadge');
    var msgs=document.getElementById('gchatMessages');
    var empty=document.getElementById('gchatEmpty');
    var input=document.getElementById('gchatInput');
    var send=document.getElementById('gchatSend');
    var csrf=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
    var lastId=0,isOpen=false;
    if(!fab)return;
    fab.addEventListener('click',function(){isOpen=!isOpen;panel.classList.toggle('open',isOpen);if(isOpen){poll();input.focus();}});
    function appendMsg(m){
        var isOut=m.sender_role==='guest';
        var div=document.createElement('div');
        div.className='gchat-bubble '+(isOut?'out':'in');
        div.innerHTML='<div>'+(m.message||'').replace(/</g,'&lt;')+'</div><div class="gchat-bubble-time">'+(m.created_at||'').substring(11,16)+'</div>';
        if(empty){empty.remove();empty=null;}
        msgs.appendChild(div);msgs.scrollTop=msgs.scrollHeight;
        if(m.id>lastId)lastId=m.id;
    }
    function poll(){
        fetch('/guest/messages/poll?after='+lastId,{headers:{'Accept':'application/json'}})
            .then(function(r){return r.json();})
            .then(function(d){
                (d.messages||[]).forEach(appendMsg);
                var u=parseInt(d.unread||0,10);
                badge.textContent=u;badge.style.display=u>0?'flex':'none';
            }).catch(function(){});
    }
    send.addEventListener('click',function(){
        var txt=input.value.trim();if(!txt)return;input.value='';
        fetch('/guest/messages/send',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({message:txt})})
            .then(function(){poll();}).catch(function(){});
        appendMsg({sender_role:'guest',message:txt,created_at:new Date().toISOString()});
    });
    input.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send.click();}});
    setInterval(function(){
        if(isOpen)poll();
        else fetch('/guest/messages/poll?after='+lastId,{headers:{'Accept':'application/json'}})
            .then(function(r){return r.json();})
            .then(function(d){var u=parseInt(d.unread||0,10);badge.textContent=u;badge.style.display=u>0?'flex':'none';}).catch(function(){});
    },15000);
}());
</script>

<script defer src="{{ Vite::asset('resources/js/icon-switcher.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">window.__giphyKey={{ Js::from(config('services.giphy.key','')) }};</script>
@stack('scripts')

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
            var csrf = document.querySelector('meta[name="csrf-token"]');
            fetch('/guest/search?q='+encodeURIComponent(q),{
                credentials:'same-origin',
                headers:{
                    'Accept':'application/json',
                    'X-Requested-With':'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf ? csrf.content : ''
                }
            })
                .then(function(r){
                    if(!r.ok) throw new Error('HTTP '+r.status);
                    return r.json();
                })
                .then(function(d){
                    box.innerHTML=(d.results&&d.results.length)
                        ?d.results.map(function(x){return '<a href="'+x.url+'" style="display:flex;gap:10px;align-items:flex-start;padding:9px 12px;border-bottom:1px solid var(--border,#f3f4f6);text-decoration:none;color:var(--text,#111827);">'
                            +'<span style="font-size:16px;flex-shrink:0;">'+x.icon+'</span>'
                            +'<div><div style="font-size:13px;font-weight:600;">'+x.title+'</div><div style="font-size:11px;color:var(--muted,#9ca3af);">'+x.sub+' &mdash; '+(x.date||'')+'</div></div></a>';}).join('')
                        :'<div style="padding:12px;font-size:13px;color:var(--muted,#9ca3af);text-align:center;">Sonuç bulunamadı.</div>';
                    box.style.display='block';
                }).catch(function(e){
                    console.error('Arama hatası:',e);
                    box.innerHTML='<div style="padding:12px;font-size:12px;color:#dc2626;text-align:center;">Arama hatası: '+e.message+'</div>';
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

@stack('welcome-modal')

{{-- PWA Service Worker --}}
<script nonce="{{ $cspNonce ?? '' }}">
if('serviceWorker' in navigator){
    window.addEventListener('load',function(){
        navigator.serviceWorker.register('/sw-guest.js',{scope:'/guest/'}).catch(function(){});
    });
}
</script>
{{-- Twemoji: emoji'leri renkli SVG'ye dönüştür --}}
<script defer src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    twemoji.parse(document.body, {
        folder: 'svg',
        ext: '.svg',
        base: 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/'
    });
</script>
@include('partials.promo-popup')
</body>
</html>
