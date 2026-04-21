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
    <title>@yield('title', config('brand.name', 'MentorDE') . ' — Eğitim Danışmanı Paneli')</title>

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

    {{-- Senior portal accent (cyan) + backwards-compat bridge --}}
    <style>
        /* ── Senior portal: tüm renk override'ları — mor tema ── */
        html, :root {
            --c-accent:    var(--theme-accent-senior, #7c3aed) !important;
            --c-accent2:   var(--theme-accent-senior, #6d28d9) !important;
            --accent-soft: rgba(124,58,237,.10) !important;
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-senior, #7c3aed), var(--theme-hero-to-senior, #6d28d9)) !important;

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
            --u-info:      #0891b2;
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

        /* ── Doğrudan element override'ları (CSS variable cascade sorunundan bağımsız) ── */
        .brand-logo       { background: linear-gradient(135deg,#7c3aed,#6d28d9) !important; }
        .avatar           { background: linear-gradient(135deg,#7c3aed,#6d28d9) !important; }
        .nav-link         { color:rgba(255,255,255,.75) !important; }
        .nav-link:hover   { color:#fff !important; background:rgba(255,255,255,.10) !important; }
        .nav-link.active  { color:#fff !important; background:rgba(255,255,255,.18) !important; font-weight:600; }
        .nav-link.active::before { background:#fff !important; }
        .btn-primary      { background:#7c3aed !important; border-color:#7c3aed !important; }
        .btn-primary:hover{ background:#6d28d9 !important; }
        .section-link     { color:#7c3aed !important; }
        .icon-btn:hover   { border-color:#7c3aed !important; color:#7c3aed !important; }
        .msg-unread       { background:#7c3aed !important; }
        .appt-day         { color:#7c3aed !important; }
        .progress-fill    { background:#7c3aed !important; }
        .qa-btn:hover     { border-color:#7c3aed !important; color:#7c3aed !important; background:rgba(124,58,237,.08) !important; }
        .doc-item:hover   { border-color:#7c3aed !important; background:rgba(124,58,237,.06) !important; }
        .tl-step.active .tl-dot { background:#7c3aed !important; border-color:#7c3aed !important; box-shadow:0 0 0 4px rgba(124,58,237,.18) !important; }

        /* Grid layout */
        .grid2  { display:grid; grid-template-columns:1fr 1fr;       gap:18px; margin-bottom:16px; }
        .grid3  { display:grid; grid-template-columns:1fr 1fr 1fr;   gap:18px; margin-bottom:16px; }
        .grid3-1{ display:grid; grid-template-columns:3fr 2fr;       gap:18px; margin-bottom:16px; }
        @media(max-width:900px){ .grid3 { grid-template-columns:1fr 1fr; } }
        @media(max-width:700px){ .grid2,.grid3,.grid3-1 { grid-template-columns:1fr; } }

        /* Panel card */
        .panel {
            background:var(--surface,#fff);
            border:1px solid var(--border,#e2e8f0);
            border-radius:12px;
            padding:18px 20px;
            transition:box-shadow .15s;
        }
        .panel:hover { box-shadow:var(--shadow-md,0 4px 12px rgba(0,0,0,.08)); }
        .panel h1,.panel h2,.panel h3 { font-size:var(--tx-base,15px); font-weight:700; color:var(--text,#0f172a); margin:0 0 12px; }

        /* Btn compat */
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
        .btn.alt     { background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text,#0f172a); }
        .btn.alt:hover { border-color:var(--c-accent);color:var(--c-accent); }
        .btn.ok      { background:var(--c-ok,#16a34a);color:#fff;border-color:transparent; }
        .btn.ok:hover{ opacity:.9; }
        .btn.warn    { background:var(--c-danger,#dc2626);color:#fff;border-color:transparent; }
        /* Form alanı kontrast düzeltmesi */
        input:not([type=checkbox]):not([type=radio]):not([type=range]),
        select, textarea {
            background: var(--surface, #ffffff) !important;
            border: 1.5px solid var(--border, #cbd5e1) !important;
            color: var(--text, #0f172a) !important;
        }
        input:not([type=checkbox]):not([type=radio]):not([type=range]):focus,
        select:focus, textarea:focus {
            outline: none;
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124,58,237,.15);
        }
        /* ── Nav accordion ── */
        .nav-section { margin-bottom: 2px; }

        /* override premium.css .nav-section-label — make it a clickable header */
        .nav-section-label {
            cursor: pointer !important;
            pointer-events: auto !important;
            display: flex !important; align-items: center; justify-content: space-between;
            user-select: none;
            padding: 7px 14px 7px 16px;
            margin: 0 6px;
            border-radius: 8px;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .07em;
            color: rgba(255,255,255,.75) !important;
            opacity: 1 !important;
            transition: background .15s;
        }
        .nav-section-label:hover { background: rgba(255,255,255,.1); }
        .nav-section-label .acc-arrow {
            font-size: 12px; opacity: .7;
            transition: transform .2s; display: inline-block;
        }
        /* accordion body — initial state set by PHP inline style, JS toggles via inline style */
        .nav-acc-body { display: block; }
        .nav-section.nav-collapsed .acc-arrow { transform: rotate(-90deg); }
    </style>

    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} --tx-xs:calc(var(--theme-font-size-senior,15px)*.733);--tx-sm:calc(var(--theme-font-size-senior,15px)*.867);--tx-base:var(--theme-font-size-senior,15px);--tx-lg:calc(var(--theme-font-size-senior,15px)*1.2);--tx-xl:calc(var(--theme-font-size-senior,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-senior,15px)*1.867); }html{font-size:var(--theme-font-size-senior,15px);font-family:var(--theme-font-family-senior,inherit);}</style>
    @endif

    <style>html:not(.jm-minimalist) .sidebar{background:linear-gradient(180deg,var(--theme-sidebar-from-senior,#162C4A),var(--theme-sidebar-to-senior,#1E3D6B));}</style>
    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Sidebar Header (merged brand + user) --}}
        @php
            $user      = auth()->user();
            $name      = (string) ($user?->name ?? 'Eğitim Danışmanı');
            $initials  = strtoupper(substr(preg_replace('/\s+/', '', $name), 0, 2));
            $sidebarStats      = $sidebarStats ?? [];
            $activeStudents    = (int) ($sidebarStats['active_students'] ?? 0);
            $pendingGuests     = (int) ($sidebarStats['pending_guests'] ?? 0);
            $todayTasks        = (int) ($sidebarStats['today_tasks'] ?? 0);
            $todayAppointments = (int) ($sidebarStats['today_appointments'] ?? 0);

            $isComm     = request()->is('senior/tickets*','senior/inbox*','im*','tasks*','senior/response-templates*','manager/requests*','bulletins*');
            $isOgrenci  = request()->is('senior/students*','senior/registration-documents*','senior/process-tracking*','senior/university-applications*','senior/contracts*','senior/appointments*','senior/batch-review*','senior/student-pipeline*','senior/guest-pipeline*');
            $isLojistik = request()->is('senior/notes*','senior/vault*');
            $isIcerik   = request()->is('senior/document-builder*','senior/materials*','senior/knowledge-base*','senior/services*','senior/ai-assistant*');
            $isKisisel  = request()->is('senior/performance*','senior/profile*','senior/settings*','my-contracts*');
        @endphp
        <div style="padding:14px 14px 0;">
            {{-- Brand logo + adı — sidebar'ın üstünde --}}
            @php
                $logoBg = ($brandLogoBg ?? 'light');
                $logoBgStyle = match($logoBg) {
                    'dark'        => 'background:#1a1a2e;',
                    'transparent' => 'background:transparent;',
                    default       => 'background:#fff;',
                };
            @endphp
            <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;background:rgba(255,255,255,.05);border-radius:10px;" title="{{ $brandName }}">
                @if(!empty($brandLogoUrl))
                    <div style="height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;{{ $logoBgStyle }}border-radius:8px;padding:4px 6px;">
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" style="height:100%;width:auto;max-width:80px;object-fit:contain;display:block;" onerror="this.parentElement.style.display='none';this.parentElement.nextElementSibling.style.display='flex';">
                    </div>
                    <span style="display:none;width:44px;height:44px;align-items:center;justify-content:center;background:#fff;color:var(--u-brand,#7c3aed);font-weight:800;border-radius:10px;font-size:18px;flex-shrink:0;">{{ $brandInitial }}</span>
                @else
                    <span style="display:flex;width:44px;height:44px;align-items:center;justify-content:center;background:#fff;color:var(--u-brand,#7c3aed);font-weight:800;border-radius:10px;font-size:18px;flex-shrink:0;">{{ $brandInitial }}</span>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $brandName }}</div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;">Eğitim Danışmanı Paneli</div>
                </div>
            </div>
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">

            {{-- Dashboard — no group --}}
            <a href="/senior/dashboard"
               class="nav-link {{ request()->is('senior/dashboard') ? 'active' : '' }}"
               style="margin:2px 6px;border-radius:8px;">
                <span class="nav-icon">🏠</span> Dashboard
            </a>

            {{-- Duyurular — standalone, dashboard altı --}}
            <a href="/bulletins"
               class="nav-link {{ request()->is('bulletins*') ? 'active' : '' }}"
               style="margin:2px 6px;border-radius:8px;justify-content:space-between;">
                <span><span class="nav-icon">📢</span> Duyurular</span>
                @if(($bulletinUnread ?? 0) > 0)<span class="nav-badge" style="background:#dc2626;">{{ $bulletinUnread }}</span>@endif
            </a>

            {{-- İletişim --}}
            <div class="nav-section" data-acc-key="iletisim">
                <div class="nav-section-label" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:7px 14px 7px 16px;margin:0 6px;border-radius:8px;user-select:none;"
                    >
                    <span>İletişim</span>
                    <span class="acc-arrow" style="font-size:12px;opacity:.7;display:inline-block;transition:transform .2s;{{ $isComm ? '' : 'transform:rotate(-90deg);' }}">▾</span>
                </div>
                <div class="nav-acc-body" style="{{ $isComm ? '' : 'display:none;' }}">
                    <a href="/senior/inbox"               class="nav-link {{ request()->is('senior/inbox*') ? 'active' : '' }}"><span class="nav-icon">📬</span> Gelen Kutusu</a>
                    <a href="/im"                         class="nav-link {{ request()->is('im*') ? 'active' : '' }}"><span class="nav-icon">💬</span> Danışan İletişim @if((int)($dmUnread??0)>0)<span class="nav-badge">{{(int)$dmUnread}}</span>@endif</a>
                    <a href="/senior/tickets"             class="nav-link {{ request()->is('senior/tickets*') ? 'active' : '' }}"><span class="nav-icon">🎫</span> Ticket</a>
                    <a href="/tasks"                      class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}"><span class="nav-icon">✅</span> Görevlerim</a>
                    <a href="/senior/response-templates"  class="nav-link {{ request()->is('senior/response-templates*') ? 'active' : '' }}"><span class="nav-icon">📋</span> Şablon Yanıtlar</a>
                    <a href="/manager/requests"           class="nav-link {{ request()->is('manager/requests*') ? 'active' : '' }}"><span class="nav-icon">📤</span> Manager'a Talep</a>
                </div>
            </div>

            {{-- Öğrenci Takibi --}}
            <div class="nav-section" data-acc-key="ogrenci">
                <div class="nav-section-label" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:7px 14px 7px 16px;margin:0 6px;border-radius:8px;user-select:none;"
                    >
                    <span>Öğrenci Takibi</span>
                    <span class="acc-arrow" style="font-size:12px;opacity:.7;display:inline-block;transition:transform .2s;{{ $isOgrenci ? '' : 'transform:rotate(-90deg);' }}">▾</span>
                </div>
                <div class="nav-acc-body" style="{{ $isOgrenci ? '' : 'display:none;' }}">
                    <a href="/senior/students"               class="nav-link {{ request()->is('senior/students*') ? 'active' : '' }}"><span class="nav-icon">🎓</span> Öğrencilerim</a>
                    <a href="/senior/registration-documents" class="nav-link {{ request()->is('senior/registration-documents*') ? 'active' : '' }}"><span class="nav-icon">📂</span> Belge Onayları</a>
                    <a href="/senior/process-tracking"       class="nav-link {{ request()->is('senior/process-tracking*','senior/university-applications*','senior/visa*','senior/housing*') ? 'active' : '' }}"><span class="nav-icon">🔄</span> Başvuru & Süreç @if(($deadlineIn7 ?? 0) > 0)<span style="background:#dc2626;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 6px;margin-left:4px;">{{ $deadlineIn7 }}</span>@endif</a>
                    <a href="/senior/guest-pipeline"         class="nav-link {{ request()->is('senior/guest-pipeline*') ? 'active' : '' }}" style="padding-left:32px;font-size:var(--tx-xs);"><span class="nav-icon" style="font-size:11px;">🌀</span> Aday Öğrenci Pipeline</a>
                    <a href="/senior/student-pipeline"       class="nav-link {{ request()->is('senior/student-pipeline*') ? 'active' : '' }}" style="padding-left:32px;font-size:var(--tx-xs);"><span class="nav-icon" style="font-size:11px;">🗂</span> Pipeline Kanban</a>
                    <a href="/senior/appointments"           class="nav-link {{ request()->is('senior/appointments*') ? 'active' : '' }}"><span class="nav-icon">📅</span> Randevularım</a>
                    @module('booking')
                    <a href="/senior/booking-settings"       class="nav-link {{ request()->is('senior/booking-settings*') ? 'active' : '' }}"><span class="nav-icon">🗓️</span> Randevu Ayarları</a>
                    @endmodule
                    <a href="/senior/contracts"              class="nav-link {{ request()->is('senior/contracts*') ? 'active' : '' }}"><span class="nav-icon">📜</span> Sözleşmeler</a>
                    <a href="/senior/batch-review"           class="nav-link {{ request()->is('senior/batch-review*') ? 'active' : '' }}"><span class="nav-icon">⚡</span> Toplu İnceleme</a>
                </div>
            </div>

            {{-- Lojistik & Destek --}}
            <div class="nav-section" data-acc-key="lojistik">
                <div class="nav-section-label" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:7px 14px 7px 16px;margin:0 6px;border-radius:8px;user-select:none;"
                    >
                    <span>Lojistik & Destek</span>
                    <span class="acc-arrow" style="font-size:12px;opacity:.7;display:inline-block;transition:transform .2s;{{ $isLojistik ? '' : 'transform:rotate(-90deg);' }}">▾</span>
                </div>
                <div class="nav-acc-body" style="{{ $isLojistik ? '' : 'display:none;' }}">
                    <a href="/senior/notes"   class="nav-link {{ request()->is('senior/notes*') ? 'active' : '' }}"><span class="nav-icon">🔒</span> Gizli Notlar</a>
                    <a href="/senior/vault"   class="nav-link {{ request()->is('senior/vault*') ? 'active' : '' }}"><span class="nav-icon">🔐</span> Hesap Kasası</a>
                </div>
            </div>

            {{-- İçerik & Araçlar --}}
            <div class="nav-section" data-acc-key="icerik">
                <div class="nav-section-label" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:7px 14px 7px 16px;margin:0 6px;border-radius:8px;user-select:none;"
                    >
                    <span>İçerik & Araçlar</span>
                    <span class="acc-arrow" style="font-size:12px;opacity:.7;display:inline-block;transition:transform .2s;{{ $isIcerik ? '' : 'transform:rotate(-90deg);' }}">▾</span>
                </div>
                <div class="nav-acc-body" style="{{ $isIcerik ? '' : 'display:none;' }}">
                    <a href="/senior/document-builder" class="nav-link {{ request()->is('senior/document-builder*') ? 'active' : '' }}"><span class="nav-icon">📝</span> Doküman Oluştur</a>
                    <a href="/senior/ai-assistant"     class="nav-link {{ request()->is('senior/ai-assistant*') ? 'active' : '' }}"><span class="nav-icon">🤖</span> AI Asistan</a>
                    <a href="/senior/knowledge-base"   class="nav-link {{ request()->is('senior/knowledge-base*','senior/materials*') ? 'active' : '' }}"><span class="nav-icon">📚</span> Materyaller & KB</a>
                    <a href="/senior/services"         class="nav-link {{ request()->is('senior/services*') ? 'active' : '' }}"><span class="nav-icon">🔧</span> Servisler</a>
                    @can('dam.view')
                    <a href="{{ route('senior.dam.index') }}" class="nav-link {{ request()->routeIs('senior.dam.*') ? 'active' : '' }}"><span class="nav-icon">📁</span> Dijital Varlıklar</a>
                    @endcan
                </div>
            </div>

            {{-- Kişisel --}}
            <div class="nav-section" data-acc-key="kisisel">
                <div class="nav-section-label" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:7px 14px 7px 16px;margin:0 6px;border-radius:8px;user-select:none;"
                    >
                    <span>Kişisel</span>
                    <span class="acc-arrow" style="font-size:12px;opacity:.7;display:inline-block;transition:transform .2s;{{ $isKisisel ? '' : 'transform:rotate(-90deg);' }}">▾</span>
                </div>
                <div class="nav-acc-body" style="{{ $isKisisel ? '' : 'display:none;' }}">
                    <a href="/senior/performance" class="nav-link {{ request()->is('senior/performance*') ? 'active' : '' }}"><span class="nav-icon">📊</span> Performansım</a>
                    <a href="/senior/profile"     class="nav-link {{ request()->is('senior/profile*','my-contracts*','hr/my/leaves*') ? 'active' : '' }}"><span class="nav-icon">👤</span> Profil & Sözleşmeler</a>
                    <a href="/senior/settings"    class="nav-link {{ request()->is('senior/settings*') ? 'active' : '' }}"><span class="nav-icon">⚙️</span> Ayarlar</a>
                </div>
            </div>

        </nav>

        {{-- KPI Chips kaldırıldı (E8) — sidebar sadece navigasyon, bu metrikler artık
             senior/dashboard.blade.php üst KPI tile'larında gösterilir. Veri kaynağı:
             $sidebarKpi view composer (AppServiceProvider::boot, senior.layouts.app). --}}

        <div class="sidebar-footer">
            {{-- Dijital Varlıklar → İçerik & Araçlar altına taşındı.
                 İzin Taleplerim → Kişisel altına taşındı.
                 Footer'da sadece Danışman Kılavuzu ve Çıkış Yap kalıyor. --}}
            <a href="{{ route('senior.handbook') }}" class="nav-link {{ request()->routeIs('senior.handbook') ? 'active' : '' }}" style="margin-bottom:4px;">
                <span class="nav-icon">📖</span> Danışman Kılavuzu
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
                <a href="/senior/dashboard" class="icon-btn" id="premium-back-btn" title="Geri dön" style="font-size:22px;line-height:1;width:44px;height:44px;flex-shrink:0;border:1px solid var(--u-line,#e5e7eb);background:var(--u-card,#fff);border-radius:10px;text-decoration:none;display:flex;align-items:center;justify-content:center;">&#8592;</a>
                <div>
                    <div class="topbar-title">@yield('page_title', 'Eğitim Danışmanı Paneli')</div>
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
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ $name }}">
                    {{ $initials }}
                </div>
            </div>
        </header>

        {{-- Acil Duyuru Banner --}}
        @if(!empty($urgentBulletins) && $urgentBulletins->isNotEmpty())
        <div style="background:#dc2626;color:#fff;padding:9px 22px;font-size:13px;font-weight:600;display:flex;gap:12px;align-items:center;flex-shrink:0;">
            <span>🚨</span><span>{{ $urgentBulletins->first()->title }}</span>
            <a href="/bulletins" style="color:#fff;text-decoration:underline;margin-left:auto;font-size:12px;">Tümünü Gör →</a>
        </div>
        @endif
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
    #premium-overlay.active { display:block !important; }
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

{{-- Quick Note Floating Widget --}}
<style>
#qn-fab{position:fixed;bottom:24px;right:24px;width:50px;height:50px;border-radius:50%;background:var(--c-accent,#7c3aed);color:#fff;font-size:22px;border:none;cursor:pointer;z-index:1000;box-shadow:0 4px 16px rgba(124,58,237,.45);display:flex;align-items:center;justify-content:center;transition:transform .15s,background .15s;}
#qn-fab:hover{opacity:.9;transform:scale(1.1);}
#qn-panel{position:fixed;bottom:84px;right:24px;width:320px;max-width:calc(100vw - 32px);background:var(--surface,#fff);border:1.5px solid var(--border,#e5e7eb);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.15);z-index:999;display:none;flex-direction:column;overflow:hidden;}
#qn-panel.open{display:flex;}
.qn-header{background:var(--hero-gradient);color:#fff;padding:12px 16px;display:flex;align-items:center;gap:8px;}
.qn-header-title{flex:1;font-size:14px;font-weight:700;}
.qn-header button{background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;}
.qn-body{padding:12px 14px;display:flex;flex-direction:column;gap:8px;}
.qn-textarea{width:100%;padding:8px;border:1px solid var(--border,#d1d5db);border-radius:8px;font-size:13px;resize:none;font-family:inherit;background:var(--surface,#fff);color:var(--text,#111);}
.qn-meta{display:flex;gap:6px;}
.qn-meta select{flex:1;padding:5px 7px;border:1px solid var(--border,#d1d5db);border-radius:6px;font-size:12px;background:var(--surface,#fff);color:var(--text,#111);}
.qn-footer{padding:8px 14px 12px;display:flex;justify-content:flex-end;gap:6px;}
.qn-recent{border-top:1px solid var(--border,#f3f4f6);max-height:180px;overflow-y:auto;}
.qn-note-item{padding:8px 14px;border-bottom:1px solid var(--border,#f3f4f6);font-size:12px;cursor:default;}
.qn-note-item:last-child{border-bottom:none;}
</style>

<button id="qn-fab" title="Hızlı Not (Alt+N)" onclick="qnToggle()">📝</button>

<div id="qn-panel">
    <div class="qn-header">
        <span class="qn-header-title">📝 Hızlı Not</span>
        <button onclick="qnLoadRecent()">Son Notlar</button>
        <button onclick="qnClose()" style="margin-left:4px;">✕</button>
    </div>
    <div class="qn-body">
        <textarea id="qn-content" class="qn-textarea" rows="4" placeholder="Not içeriği... (Ctrl+Enter gönderir)"></textarea>
        <div class="qn-meta">
            <select id="qn-category">
                <option value="general">Genel</option>
                <option value="document">Belge</option>
                <option value="visa">Vize</option>
                <option value="registration">Kayıt</option>
                <option value="housing">Konaklama</option>
                <option value="language">Dil</option>
            </select>
            <select id="qn-priority">
                <option value="medium">Normal</option>
                <option value="high">Yüksek</option>
                <option value="low">Düşük</option>
            </select>
        </div>
        <div id="qn-msg" style="font-size:12px;color:var(--c-ok,#16a34a);display:none;">✓ Kaydedildi</div>
    </div>
    <div class="qn-footer">
        <button class="btn alt" onclick="qnClose()">İptal</button>
        <button class="btn ok" onclick="qnSave()" id="qn-save-btn">Kaydet</button>
    </div>
    <div class="qn-recent" id="qn-recent-list" style="display:none;"></div>
</div>

{{-- Alpine.js — app.css zaten head'de yüklendi, burada sadece JS --}}
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
    localStorage.setItem('mentorde_design',next);if(window.__iconSwitcher)window.__iconSwitcher.apply(next);
    document.documentElement.classList.toggle('jm-minimalist',next==='minimalist');
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

{{-- Quick Note JS --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var csrf=document.querySelector('meta[name="csrf-token"]')?.content??'';
    window.qnToggle=function(){document.getElementById('qn-panel').classList.toggle('open');};
    window.qnClose=function(){document.getElementById('qn-panel').classList.remove('open');};
    window.qnSave=function(){
        var content=document.getElementById('qn-content').value.trim();
        if(!content)return;
        var btn=document.getElementById('qn-save-btn');
        btn.disabled=true;
        fetch('/senior/quick-note',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body:JSON.stringify({content:content,category:document.getElementById('qn-category').value,priority:document.getElementById('qn-priority').value})
        }).then(function(r){return r.json();}).then(function(d){
            if(d.ok){document.getElementById('qn-content').value='';var msg=document.getElementById('qn-msg');msg.style.display='block';setTimeout(function(){msg.style.display='none';},2000);}
        }).catch(function(){}).finally(function(){btn.disabled=false;});
    };
    window.qnLoadRecent=function(){
        var list=document.getElementById('qn-recent-list');
        list.style.display='block';
        list.innerHTML='<div style="padding:10px 14px;font-size:12px;color:var(--muted,#9ca3af);">Yükleniyor...</div>';
        fetch('/senior/quick-note/recent',{headers:{'Accept':'application/json'}})
        .then(function(r){return r.json();}).then(function(d){
            if(!d.notes||!d.notes.length){list.innerHTML='<div class="qn-note-item">Not bulunamadı.</div>';return;}
            list.innerHTML=d.notes.map(function(n){return '<div class="qn-note-item"><div style="display:flex;justify-content:space-between;"><span style="font-weight:600;font-size:11px;color:var(--muted,#6b7280);">'+(n.student_id||'')+' · '+n.category+'</span><span style="font-size:10px;color:var(--muted,#9ca3af);">'+n.created_at+'</span></div><div style="margin-top:2px;">'+n.content+'</div></div>';}).join('');
        }).catch(function(){list.innerHTML='<div class="qn-note-item">Yükleme hatası.</div>';});
    };
    document.getElementById('qn-content')?.addEventListener('keydown',function(e){if(e.key==='Enter'&&(e.ctrlKey||e.metaKey)){e.preventDefault();qnSave();}});
    document.addEventListener('keydown',function(e){if(e.altKey&&(e.key==='n'||e.key==='N')){e.preventDefault();qnToggle();setTimeout(function(){document.getElementById('qn-content')?.focus();},80);}});
    // ── Mobile hamburger (CSP-safe) ──
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb)_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});
    if(_ov)_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});
    // ── Sidebar accordion ──
    document.querySelectorAll('.sidebar-nav .nav-section-label').forEach(function(lbl){
        lbl.addEventListener('click', function(){
            var body = lbl.nextElementSibling;
            if(!body) return;
            var isHidden = (body.style.display === 'none' || body.style.display === '');
            body.style.display = isHidden ? 'block' : 'none';
            var arr = lbl.querySelector('.acc-arrow');
            if(arr) arr.style.transform = isHidden ? '' : 'rotate(-90deg)';
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
            fetch('/senior/search?q='+encodeURIComponent(q),{
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
@include('partials.promo-popup')
</body>
</html>
