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
    <title>@yield('title', $pageTitle ?? (config('brand.name', 'MentorDE') . ' — Marketing Admin'))</title>

    {{-- Premium Design System --}}
    <link id="mentorde-theme-css" rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Marketing portal accent (mor/pembe) + backwards-compat bridge --}}
    <style>
        :root {
            /* Marketing accent */
            --c-accent:  var(--theme-accent-marketing, #7c3aed);
            --c-accent2: var(--theme-accent-marketing, #6d28d9);
            --accent-soft: rgba(124,58,237,.09);
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-marketing, #7c3aed), var(--theme-hero-to-marketing, #ec4899));

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

        /* Card padding (premium.css has none) */
        .card { padding: 20px; }

        /* Grid utilities */
        .grid2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .grid3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .grid4 { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
        @media(max-width:900px){ .grid2,.grid3,.grid4 { grid-template-columns:1fr; } }
        @media(min-width:901px) and (max-width:1200px){ .grid3,.grid4 { grid-template-columns:repeat(2,minmax(0,1fr)); } }

        /* Btn compat */
        .btn { display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--r-sm,8px);font-size:var(--tx-sm,13px);font-weight:600;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:all .15s; }
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
            border-color: var(--c-accent, #1e40af) !important;
            box-shadow: 0 0 0 3px rgba(30,64,175,.15);
        }
    </style>

    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} --tx-xs:calc(var(--theme-font-size-marketing,15px)*.733);--tx-sm:calc(var(--theme-font-size-marketing,15px)*.867);--tx-base:var(--theme-font-size-marketing,15px);--tx-lg:calc(var(--theme-font-size-marketing,15px)*1.2);--tx-xl:calc(var(--theme-font-size-marketing,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-marketing,15px)*1.867); }html{font-size:var(--theme-font-size-marketing,15px);font-family:var(--theme-font-family-marketing,inherit);}</style>
    @endif

    <style>html:not(.jm-minimalist) .sidebar{background:linear-gradient(180deg,var(--theme-sidebar-from-marketing,#162C4A),var(--theme-sidebar-to-marketing,#1E3D6B));}</style>
    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Brand --}}
        @php
            $mktgUser     = auth()->user();
            $mktgRole     = (string) ($mktgUser?->role ?? '');
            $mktgInitials = strtoupper(substr(preg_replace('/\s+/', '', ($mktgUser?->name ?? 'MA')), 0, 2));

            // Tüm roller paneller arası serbestçe geçiş yapabilir
            $panelMode = session('mktg_panel_mode', 'marketing');
            $canToggle = true;

            $isMktgAdmin    = in_array($mktgRole, ['marketing_admin', 'manager', 'system_admin'], true);
            $isSalesAdmin   = $mktgRole === 'sales_admin';
            $isMktgStaff    = $mktgRole === 'marketing_staff';
            $isSalesStaff   = $mktgRole === 'sales_staff';
            $isAdmin        = $isMktgAdmin || $isSalesAdmin;
            $isStaff        = $isMktgStaff || $isSalesStaff;

            // Tüm admin roller her şeyi görür; tüm staff roller her şeyi görür
            $canSeeTracking     = $isAdmin;
            $canSeeAttribution  = $isAdmin;
            $canSeeScoring      = $isAdmin;
            $canSeeScoringCfg   = $isAdmin;
            $canSeeLeadSources  = $isAdmin;
            $canSeeDealers      = $isAdmin;
            $canSeeBudget       = $isAdmin;
            $canSeeKpi          = $isAdmin;
            $canSeeReports      = $isAdmin;
            $canSeeTeam         = $isAdmin;
            $canSeeSettings     = $isAdmin;
            $canSeeIntegrations = $isAdmin;
            $canSeeCampaigns    = $isAdmin || $isStaff;
            $canSeeContent      = $isAdmin || $isStaff;
            $canSeeEmail        = $isAdmin || $isStaff;
            $canSeeSocial       = $isAdmin || $isStaff;
            $canSeeEvents       = $isAdmin || $isStaff;
            $canSeeWorkflows    = $isAdmin;
            $canSeeABTests      = $isAdmin || $isStaff;
            $canSeePipeline     = $isAdmin || $isStaff;

            $maBrandName = config('brand.name', 'MentorDE');
            $brandLabel = $panelMode === 'sales' ? $maBrandName . ' Sales' : $maBrandName . ' Marketing';
            $roleLabel  = $isAdmin ? 'Admin' : 'Staff';
            $topMode    = $panelMode;
        @endphp
        {{-- Sidebar Header (merged brand + user) --}}
        <div style="padding:14px 14px 0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:46px;height:46px;font-size:17px;flex-shrink:0;">{{ $mktgInitials }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mktgUser?->name ?? 'Marketing' }}</div>
                    <div class="user-role" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $roleLabel }}</div>
                </div>
                <div class="brand-logo" style="width:28px;height:28px;font-size:11px;flex-shrink:0;overflow:hidden;" title="{{ $brandName }}">
                    @if(!empty($brandLogoUrl))<img src="{{ $brandLogoUrl }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">@endif
                    <span style="{{ !empty($brandLogoUrl)?'display:none;':'display:flex;' }}align-items:center;justify-content:center;width:100%;height:100%;">{{ $brandInitial }}</span>
                </div>
            </div>
            <div style="font-size:10px;color:var(--muted);font-weight:600;letter-spacing:.03em;margin-top:6px;padding-left:56px;">{{ $brandName }} · {{ $panelMode === 'sales' ? 'Sales' : 'Marketing' }}</div>
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Panel Mode Toggle — tüm roller görür --}}
        <div style="padding:8px 14px 12px;">
            <div style="font-size:10px;color:rgba(255,255,255,.45);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Panel Modu</div>
            <div style="display:flex;gap:4px;background:rgba(0,0,0,.25);border-radius:8px;padding:3px;">
                <a href="/mktg-admin/switch-mode/marketing"
                   title="Pazarlama paneline geç"
                   style="flex:1;text-align:center;padding:6px 4px;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;transition:all .15s;
                          {{ $panelMode === 'marketing' ? 'background:rgba(255,255,255,.22);color:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);' : 'color:rgba(255,255,255,.5);' }}">
                    📣 Pazarlama
                </a>
                <a href="/mktg-admin/switch-mode/sales"
                   title="Satış paneline geç"
                   style="flex:1;text-align:center;padding:6px 4px;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;transition:all .15s;
                          {{ $panelMode === 'sales' ? 'background:rgba(255,255,255,.22);color:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);' : 'color:rgba(255,255,255,.5);' }}">
                    💼 Satış
                </a>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="/mktg-admin/dashboard"
                   class="nav-link {{ request()->is('mktg-admin/dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> {{ $panelMode === 'sales' ? 'Sales Dashboard' : 'Marketing Dashboard' }}
                </a>
                <a href="/bulletins"
                   class="nav-link {{ request()->is('bulletins*') ? 'active' : '' }}"
                   style="justify-content:space-between;">
                    <span><span class="nav-icon">📢</span> Duyurular</span>
                    @if(($bulletinUnread ?? 0) > 0)<span class="nav-badge" style="background:#dc2626;">{{ $bulletinUnread }}</span>@endif
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">İletişim Merkezi</div>
                <a href="/im"
                   class="nav-link {{ request()->is('im*') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> İletişim
                </a>
                <a href="/mktg-admin/tasks"
                   class="nav-link {{ request()->is('mktg-admin/tasks*') ? 'active' : '' }}">
                    <span class="nav-icon">✅</span> Görevlerim
                </a>
                <a href="/manager/requests"
                   class="nav-link {{ request()->is('manager/requests*') ? 'active' : '' }}">
                    <span class="nav-icon">📤</span> Manager'a Talep
                </a>
            </div>

            @if($panelMode === 'marketing')
            {{-- ─── PAZARLAMA MENÜSÜ ─── --}}
            @php
                $isMktgIcerik  = request()->is('mktg-admin/campaigns*','mktg-admin/content*','mktg-admin/email*','mktg-admin/social*','mktg-admin/tracking-links*','mktg-admin/events*','mktg-admin/workflows*','mktg-admin/abtests*');
                $isMktgAnaliz  = request()->is('mktg-admin/attribution*','mktg-admin/kpi*','mktg-admin/reports*','mktg-admin/budget*');
                $isMktgYonetim = request()->is('mktg-admin/integrations*','mktg-admin/team*','mktg-admin/settings*');
                $isMktgHesap   = request()->is('mktg-admin/notifications*','mktg-admin/profile*','my-contracts*');
            @endphp

            @if($canSeePipeline)
            <div class="nav-section">
                <div class="nav-section-label">Satış Süreci</div>
                <a href="/mktg-admin/pipeline"
                   class="nav-link {{ request()->is('mktg-admin/pipeline') || (request()->is('mktg-admin/pipeline*') && !request()->is('mktg-admin/pipeline/kanban*')) ? 'active' : '' }}">
                    <span class="nav-icon">🗂</span> Sales Pipeline
                </a>
                <a href="/mktg-admin/pipeline/kanban"
                   class="nav-link {{ request()->is('mktg-admin/pipeline/kanban*') ? 'active' : '' }}"
                   style="padding-left:32px;font-size:var(--tx-xs);">
                    <span class="nav-icon" style="font-size:11px;">🌀</span> Pipeline Kanban
                </a>
            </div>
            @endif

            <div class="nav-section">
                <div class="nav-section-label">İçerik & Kampanya</div>
                @if($canSeeCampaigns)
                <a href="/mktg-admin/campaigns"
                   class="nav-link {{ request()->is('mktg-admin/campaigns*') ? 'active' : '' }}">
                    <span class="nav-icon">📢</span> Kampanyalar
                </a>
                @endif
                @if($canSeeContent)
                <a href="/mktg-admin/content"
                   class="nav-link {{ request()->is('mktg-admin/content*') ? 'active' : '' }}">
                    <span class="nav-icon">📝</span> CMS İçerik
                </a>
                @endif
                @if($canSeeEmail)
                <a href="/mktg-admin/email/templates"
                   class="nav-link {{ request()->is('mktg-admin/email*') ? 'active' : '' }}">
                    <span class="nav-icon">✉️</span> E-posta
                </a>
                @endif
                @if($canSeeSocial)
                <a href="/mktg-admin/social/metrics"
                   class="nav-link {{ request()->is('mktg-admin/social*') ? 'active' : '' }}">
                    <span class="nav-icon">📱</span> Sosyal Medya
                </a>
                @endif
                @if($canSeeTracking)
                <a href="/mktg-admin/tracking-links"
                   class="nav-link {{ request()->is('mktg-admin/tracking-links*') ? 'active' : '' }}">
                    <span class="nav-icon">🔗</span> Tracking Linkler
                </a>
                @endif
                @if($canSeeEvents)
                <a href="/mktg-admin/events"
                   class="nav-link {{ request()->is('mktg-admin/events*') ? 'active' : '' }}">
                    <span class="nav-icon">📅</span> Etkinlikler
                </a>
                @endif
                @if($canSeeWorkflows)
                <a href="/mktg-admin/workflows"
                   class="nav-link {{ request()->is('mktg-admin/workflows*') ? 'active' : '' }}">
                    <span class="nav-icon">⚡</span> Otomasyon
                </a>
                @endif
                @if($canSeeABTests)
                <a href="/mktg-admin/abtests"
                   class="nav-link {{ request()->is('mktg-admin/abtests*') ? 'active' : '' }}">
                    <span class="nav-icon">🧪</span> A/B Testler
                </a>
                @endif
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Analiz & Raporlar</div>
                @if($canSeeAttribution)
                <a href="/mktg-admin/attribution"
                   class="nav-link {{ request()->is('mktg-admin/attribution*') ? 'active' : '' }}">
                    <span class="nav-icon">🎯</span> Attribution
                </a>
                @endif
                @if($canSeeKpi)
                <a href="/mktg-admin/kpi"
                   class="nav-link {{ request()->is('mktg-admin/kpi*') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> KPI & Raporlar
                </a>
                @endif
                @if($canSeeReports)
                <a href="/mktg-admin/reports/scheduled"
                   class="nav-link {{ request()->is('mktg-admin/reports*') ? 'active' : '' }}">
                    <span class="nav-icon">📈</span> Zamanlanmış Raporlar
                </a>
                @endif
                @if($canSeeBudget)
                <a href="/mktg-admin/budget"
                   class="nav-link {{ request()->is('mktg-admin/budget*') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Bütçe
                </a>
                @endif
            </div>

            @if($canSeeIntegrations || $canSeeTeam || $canSeeSettings)
            <div class="nav-section">
                <div class="nav-section-label">Yönetim</div>
                @if($canSeeIntegrations)
                <a href="/mktg-admin/integrations"
                   class="nav-link {{ request()->is('mktg-admin/integrations*') ? 'active' : '' }}">
                    <span class="nav-icon">🔌</span> Entegrasyonlar
                </a>
                @endif
                @if($canSeeTeam)
                <a href="/mktg-admin/team"
                   class="nav-link {{ request()->is('mktg-admin/team*') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span> Ekip
                </a>
                @endif
                @if($canSeeSettings)
                <a href="/mktg-admin/settings"
                   class="nav-link {{ request()->is('mktg-admin/settings*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Ayarlar
                </a>
                @endif
            </div>
            @endif

            <div class="nav-section">
                <div class="nav-section-label">Hesap</div>
                <a href="/mktg-admin/notifications"
                   class="nav-link {{ request()->is('mktg-admin/notifications*') ? 'active' : '' }}">
                    <span class="nav-icon">🔔</span> Bildirimler
                </a>
                <a href="/mktg-admin/profile"
                   class="nav-link {{ request()->is('mktg-admin/profile*','my-contracts*') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Profil & Sözleşmeler
                </a>
            </div>

            @else
            {{-- ─── SATIŞ MENÜSÜ ─── --}}
            @php
                $isSalesSurec   = request()->is('mktg-admin/pipeline*','mktg-admin/scoring','mktg-admin/scoring/leaderboard*','mktg-admin/scoring/history*','mktg-admin/attribution*','mktg-admin/lead-sources*','mktg-admin/dealers*');
                $isSalesAnaliz  = request()->is('mktg-admin/kpi*','mktg-admin/reports*');
                $isSalesYonetim = request()->is('mktg-admin/scoring/config*','mktg-admin/team*','mktg-admin/settings*');
                $isSalesHesap   = request()->is('mktg-admin/notifications*','mktg-admin/profile*','my-contracts*');
            @endphp

            <div class="nav-section">
                <div class="nav-section-label">Satış Süreci</div>
                @if($canSeePipeline)
                <a href="/mktg-admin/pipeline"
                   class="nav-link {{ request()->is('mktg-admin/pipeline') || (request()->is('mktg-admin/pipeline*') && !request()->is('mktg-admin/pipeline/kanban*')) ? 'active' : '' }}">
                    <span class="nav-icon">🗂</span> Sales Pipeline
                </a>
                <a href="/mktg-admin/pipeline/kanban"
                   class="nav-link {{ request()->is('mktg-admin/pipeline/kanban*') ? 'active' : '' }}"
                   style="padding-left:32px;font-size:var(--tx-xs);">
                    <span class="nav-icon" style="font-size:11px;">🌀</span> Pipeline Kanban
                </a>
                @endif
                @if($canSeeScoring)
                <a href="/mktg-admin/scoring"
                   class="nav-link {{ request()->is('mktg-admin/scoring') || request()->is('mktg-admin/scoring/leaderboard*') || request()->is('mktg-admin/scoring/history*') ? 'active' : '' }}">
                    <span class="nav-icon">⭐</span> Lead Scoring
                </a>
                @endif
                @if($canSeeAttribution)
                <a href="/mktg-admin/attribution"
                   class="nav-link {{ request()->is('mktg-admin/attribution*') ? 'active' : '' }}">
                    <span class="nav-icon">🎯</span> Attribution
                </a>
                @endif
                @if($canSeeLeadSources)
                <a href="/mktg-admin/lead-sources"
                   class="nav-link {{ request()->is('mktg-admin/lead-sources*') ? 'active' : '' }}">
                    <span class="nav-icon">📡</span> Lead Kaynakları
                </a>
                @endif
                @if($canSeeDealers)
                <a href="/mktg-admin/dealers"
                   class="nav-link {{ request()->is('mktg-admin/dealers*') ? 'active' : '' }}">
                    <span class="nav-icon">🏪</span> Bayi İlişkileri
                </a>
                @endif
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Analiz & Raporlar</div>
                @if($canSeeKpi)
                <a href="/mktg-admin/kpi"
                   class="nav-link {{ request()->is('mktg-admin/kpi*') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> KPI & Raporlar
                </a>
                @endif
                @if($canSeeReports)
                <a href="/mktg-admin/reports/scheduled"
                   class="nav-link {{ request()->is('mktg-admin/reports*') ? 'active' : '' }}">
                    <span class="nav-icon">📈</span> Zamanlanmış Raporlar
                </a>
                @endif
            </div>

            @if($canSeeScoringCfg || $canSeeTeam || $canSeeSettings)
            <div class="nav-section">
                <div class="nav-section-label">Yönetim</div>
                @if($canSeeScoringCfg)
                <a href="/mktg-admin/scoring/config"
                   class="nav-link {{ request()->is('mktg-admin/scoring/config*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Scoring Yapılandırma
                </a>
                @endif
                @if($canSeeTeam)
                <a href="/mktg-admin/team"
                   class="nav-link {{ request()->is('mktg-admin/team*') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span> Ekip
                </a>
                @endif
                @if($canSeeSettings)
                <a href="/mktg-admin/settings"
                   class="nav-link {{ request()->is('mktg-admin/settings*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span> Ayarlar
                </a>
                @endif
            </div>
            @endif

            <div class="nav-section">
                <div class="nav-section-label">Hesap</div>
                <a href="/mktg-admin/notifications"
                   class="nav-link {{ request()->is('mktg-admin/notifications*') ? 'active' : '' }}">
                    <span class="nav-icon">🔔</span> Bildirimler
                </a>
                <a href="/mktg-admin/profile"
                   class="nav-link {{ request()->is('mktg-admin/profile*','my-contracts*') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Profil & Sözleşmeler
                </a>
            </div>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('marketing.handbook') }}" class="nav-link {{ request()->routeIs('marketing.handbook') ? 'active' : '' }}" style="margin-bottom:6px;">
                <span class="nav-icon">📖</span> Kılavuz
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
                <a href="/mktg-admin/dashboard" class="icon-btn" id="premium-back-btn" title="Geri dön" style="font-size:22px;line-height:1;width:44px;height:44px;flex-shrink:0;border:1px solid var(--u-line,#e5e7eb);background:var(--u-card,#fff);border-radius:10px;text-decoration:none;display:flex;align-items:center;justify-content:center;">&#8592;</a>
                <div>
                    <div class="topbar-title">{{ $pageTitle ?? ($topMode === 'sales' ? 'Sales Panel' : 'Marketing Admin') }}</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                {{-- Company switcher --}}
                @if(isset($currentCompany) && $currentCompany)
                    <span class="badge info" style="font-size:12px;">{{ $currentCompany->name }}</span>
                @endif
                <select id="mktgCompanySwitch" style="min-width:160px;font-size:13px;padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:var(--surface,#fff);color:var(--text,#111);">
                    <option value="">Firma seç...</option>
                </select>
                <button type="button" id="mktgCompanyReload" class="btn alt" style="padding:5px 10px;">↺</button>
                <button class="icon-btn" onclick="__dmToggle()" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" onclick="__designToggle()" id="design-btn" title="Tasarım Teması">🎨</button>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ $mktgUser?->name ?? 'Marketing' }}">
                    {{ $mktgInitials }}
                </div>
            </div>
        </header>

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
<div class="dark-toggle" onclick="__dmToggle()" title="Tema Değiştir">
    <span id="theme-icon">🌙</span>
</div>

{{-- Toast container --}}
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:min(360px, calc(100vw - 40px));"></div>

{{-- Alpine.js --}}
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs  = $__manifest['resources/js/app.js']['file'] ?? null;
    $__appCss = $__manifest['resources/css/app.css']['file'] ?? null;
@endphp
@if($__appCss)<link rel="stylesheet" href="/build/{{ $__appCss }}">@endif
@if($__appJs)<script type="module" src="/build/{{ $__appJs }}"></script>@endif

<script defer src="{{ Vite::asset('resources/js/mktg-company-switch.js') }}?v={{ @filemtime(public_path('js/mktg-company-switch.js')) ?: time() }}"></script>

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

// ── Mobile hamburger + back btn (CSP-safe) ──
(function(){
    var _bb=document.getElementById('premium-back-btn');
    // Back button artık inline onclick ile yönetiliyor (çakışma önlendi)
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb)_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});
    if(_ov)_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});
})();
// ── Sidebar accordion ──────────────────────────────────────────────────────
(function(){
    function initSidebarAccordion(){
        var sb = document.getElementById('premium-sidebar');
        if(!sb) return;
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem('mktg_sb_acc') || '{}'); } catch(e){}
        var labels = sb.querySelectorAll('.sidebar-nav .nav-section-label');
        labels.forEach(function(label, i){
            var section = label.closest('.nav-section');
            if(!section) return;
            var links = Array.from(section.children).filter(function(c){ return c !== label; });
            if(!links.length) return;

            // Caret indicator
            var caret = document.createElement('span');
            caret.style.cssText = 'font-size:10px;opacity:.7;transition:transform .2s;display:inline-block;';
            caret.textContent = '▾';
            label.style.cursor = 'pointer';
            label.style.display = 'flex';
            label.style.justifyContent = 'space-between';
            label.style.alignItems = 'center';
            label.appendChild(caret);

            var key = 'sec' + i;
            // Default: open if page has an active link inside or not yet saved
            var hasActive = section.querySelector('.nav-link.active') !== null;
            var isOpen = saved[key] !== undefined ? saved[key] : true;
            if(hasActive) isOpen = true; // always keep current section open

            function apply(open){
                links.forEach(function(l){ l.style.display = open ? '' : 'none'; });
                caret.style.transform = open ? 'rotate(0deg)' : 'rotate(-90deg)';
            }
            apply(isOpen);

            label.addEventListener('click', function(){
                var nowOpen = links[0] && links[0].style.display !== 'none';
                var next = !nowOpen;
                apply(next);
                saved[key] = next;
                try { localStorage.setItem('mktg_sb_acc', JSON.stringify(saved)); } catch(e){}
            });
        });
    }
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', initSidebarAccordion);
    } else {
        initSidebarAccordion();
    }
})();

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
</script>
<style>@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}</style>

<script defer src="{{ Vite::asset('resources/js/icon-switcher.js') }}"></script>
{{-- Giphy API key — GIF picker tarafından okunur (messaging-hub.js + emoji-gif-picker.js) --}}
<script nonce="{{ $cspNonce ?? '' }}">window.__giphyKey={{ Js::from(config('services.giphy.key','')) }};</script>
@stack('scripts')
</body>
</html>
