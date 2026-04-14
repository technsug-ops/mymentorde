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
    <title>@yield('title', config('brand.name', 'MentorDE') . ' — Manager Portalı')</title>

    {{-- Premium Design System --}}
    <link id="mentorde-theme-css" rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Manager portal accent (koyu lacivert) + backwards-compat bridge --}}
    <style>
        :root {
            /* Manager accent */
            --c-accent:  var(--theme-accent-manager, #0f172a);
            --c-accent2: var(--theme-accent-manager, #1e293b);
            --accent-soft: rgba(15,23,42,.09);
            --hero-gradient: linear-gradient(to right, var(--theme-hero-from-manager, #0f172a), var(--theme-hero-to-manager, #1e40af));

            /* Bridge: legacy --u-* → premium.css vars */
            --u-card:      var(--surface, #ffffff);
            --u-line:      var(--border,  #e2e8f0);
            --u-text:      var(--text,    #0f172a);
            --u-muted:     var(--muted,   #64748b);
            --u-brand:     var(--c-accent,  #1e40af);
            --u-brand-2:   var(--c-accent2, #1e293b);
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
        .btn.alt:hover { border-color:#1e40af;color:#1e40af; }
        .btn.ok      { background:var(--c-ok,#16a34a);color:#fff;border-color:transparent; }
        .btn.ok:hover{ opacity:.9; }
        .btn.warn    { background:var(--c-danger,#dc2626);color:#fff;border-color:transparent; }
        .btn-primary { background:#1e40af;color:#fff;border-color:transparent; }
        .btn-primary:hover{ opacity:.9;color:#fff; }
        .btn-secondary{ background:var(--surface,#fff);border-color:var(--border,#e2e8f0);color:var(--text); }
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
            border-color: #1e40af !important;
            box-shadow: 0 0 0 3px rgba(30,64,175,.15);
        }
        /* Grid layout */
        .grid2  { display:grid; grid-template-columns:1fr 1fr;             gap:14px; margin-bottom:12px; }
        .grid3  { display:grid; grid-template-columns:1fr 1fr 1fr;         gap:14px; margin-bottom:12px; }
        .grid4  { display:grid; grid-template-columns:1fr 1fr 1fr 1fr;     gap:12px; margin-bottom:12px; }
        .grid3-1{ display:grid; grid-template-columns:3fr 2fr;             gap:14px; margin-bottom:12px; }
        @media(max-width:1100px){ .grid4 { grid-template-columns:1fr 1fr 1fr; } }
        @media(max-width:900px) { .grid3,.grid4 { grid-template-columns:1fr 1fr; } }
        @media(max-width:700px) { .grid2,.grid3,.grid3-1,.grid4 { grid-template-columns:1fr; } }
        /* Panel card */
        .panel { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:18px 20px; transition:box-shadow .15s; }
        .panel:hover { box-shadow:var(--shadow-md,0 4px 12px rgba(0,0,0,.08)); }
        .panel h1,.panel h2,.panel h3 { font-size:var(--tx-base,15px); font-weight:700; color:var(--text,#0f172a); margin:0 0 12px; }

        /* Manager sidebar — premium.css default (beyaz), sadece aktif link rengi */
        .sidebar .nav-link.active,
        .sidebar-nav a.active {
            color: #1e40af !important;
            background: rgba(30,64,175,.08) !important;
        }
        .brand-logo { background: linear-gradient(135deg,#0f172a,#1e40af) !important; }
        .avatar     { background: linear-gradient(135deg,#0f172a,#1e40af) !important; }
    </style>

    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} --tx-xs:calc(var(--theme-font-size-manager,15px)*.733);--tx-sm:calc(var(--theme-font-size-manager,15px)*.867);--tx-base:var(--theme-font-size-manager,15px);--tx-lg:calc(var(--theme-font-size-manager,15px)*1.2);--tx-xl:calc(var(--theme-font-size-manager,15px)*1.467);--tx-2xl:calc(var(--theme-font-size-manager,15px)*1.867); }html{font-size:var(--theme-font-size-manager,15px);font-family:var(--theme-font-family-manager,inherit);}</style>
    @endif

    <style>html:not(.jm-minimalist) .sidebar{background:linear-gradient(180deg,var(--theme-sidebar-from-manager,#162C4A),var(--theme-sidebar-to-manager,#1E3D6B));}</style>
    @stack('head')
    @stack('styles')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">

        {{-- Sidebar Header (merged brand + user) --}}
        @php
            $mgrUser     = auth()->user();
            $mgrInitials = strtoupper(substr(preg_replace('/\s+/', '', ($mgrUser?->name ?? 'MG')), 0, 2));
            $isCommHub   = request()->is('tasks*') || request()->is('tickets-center*') || request()->is('im*');
        @endphp
        <div style="padding:14px 14px 0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:46px;height:46px;font-size:17px;flex-shrink:0;">{{ $mgrInitials }}</div>
                <div style="flex:1;min-width:0;">
                    <div class="user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mgrUser?->name ?? 'Manager' }}</div>
                    <div class="user-role" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mgrUser?->email }}</div>
                </div>
                <div class="brand-logo" style="width:28px;height:28px;font-size:11px;flex-shrink:0;overflow:hidden;" title="{{ $brandName }}">
                    @if(!empty($brandLogoUrl))<img src="{{ $brandLogoUrl }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">@endif
                    <span style="{{ !empty($brandLogoUrl)?'display:none;':'display:flex;' }}align-items:center;justify-content:center;width:100%;height:100%;">{{ $brandInitial }}</span>
                </div>
            </div>
            <div style="font-size:10px;color:var(--muted);font-weight:600;letter-spacing:.03em;margin-top:6px;padding-left:56px;">{{ $brandName }} · Manager Portalı</div>
            <div style="height:1px;background:rgba(255,255,255,.12);margin-top:12px;"></div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <div class="nav-section">
                <a href="/manager/dashboard"
                   class="nav-link {{ request()->is('manager/dashboard*') ? 'active' : '' }}">
                    <span class="nav-icon">🏠</span> Dashboard
                </a>
                <a href="/manager/bulletins"
                   class="nav-link {{ request()->is('manager/bulletins*','bulletins*') ? 'active' : '' }}"
                   style="justify-content:space-between;">
                    <span><span class="nav-icon">📢</span> Duyurular</span>
                    @if(($bulletinUnread ?? 0) > 0)<span class="nav-badge" style="background:#dc2626;">{{ $bulletinUnread }}</span>@endif
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">İletişim & Görevler</div>
                <a href="/tasks"
                   class="nav-link {{ request()->is('tasks*') ? 'active' : '' }}">
                    <span class="nav-icon">✅</span> Görevler
                </a>
                <a href="/tickets-center"
                   class="nav-link {{ request()->is('tickets-center*') ? 'active' : '' }}">
                    <span class="nav-icon">🎫</span> Ticket Merkezi
                </a>
                <a href="/im"
                   class="nav-link {{ request()->is('im*') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span> İletişim Merkezi
                </a>
                <a href="/availability"
                   class="nav-link {{ request()->is('availability*') ? 'active' : '' }}">
                    <span class="nav-icon">📡</span> Müsaitlik Ayarları
                </a>
                <a href="/manager/requests"
                   class="nav-link {{ request()->is('manager/requests*') ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> Başvurular
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Kullanıcı Yönetimi</div>
                <a href="/manager/guests"
                   class="nav-link {{ request()->is('manager/guests*') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> Aday Öğrenci Yönetimi
                </a>
                <a href="/manager/students"
                   class="nav-link {{ request()->is('manager/students*') ? 'active' : '' }}">
                    <span class="nav-icon">👨‍🎓</span> Öğrenciler
                </a>
                <a href="/manager/seniors"
                   class="nav-link {{ request()->is('manager/seniors*') ? 'active' : '' }}">
                    <span class="nav-icon">👨‍💼</span> Eğitim Danışmanı Yönetimi
                </a>
                <a href="/manager/dealers"
                   class="nav-link {{ request()->is('manager/dealers*') ? 'active' : '' }}">
                    <span class="nav-icon">🏪</span> Bayi Yönetimi
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">İnsan Kaynakları</div>
                <a href="/manager/hr"
                   class="nav-link {{ (request()->is('manager/hr') && !request()->is('manager/hr/*')) ? 'active' : '' }}">
                    <span class="nav-icon">📋</span> HR Özet
                </a>
                <a href="/manager/staff"
                   class="nav-link {{ (request()->is('manager/staff*') || request()->is('manager/hr/persons*')) && !request()->is('manager/staff/performance*') && !request()->is('manager/staff/leaderboard*') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span> Personel & Çalışanlar
                </a>
                <a href="/manager/staff/performance"
                   class="nav-link {{ request()->is('manager/staff/performance*') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Performans & KPI
                </a>
                <a href="/manager/hr/leaves"
                   class="nav-link {{ request()->is('manager/hr/leaves*') ? 'active' : '' }}"
                   style="justify-content:space-between;">
                    <span><span class="nav-icon">🌴</span> İzin Yönetimi</span>
                    @if(($pendingLeaveCount ?? 0) > 0)
                    <span style="background:#dc2626;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;min-width:18px;text-align:center;line-height:16px;">{{ $pendingLeaveCount }}</span>
                    @endif
                </a>
                <a href="/manager/hr/certifications"
                   class="nav-link {{ request()->is('manager/hr/certifications*') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Sertifikalar
                </a>
                <a href="/manager/hr/attendance"
                   class="nav-link {{ request()->is('manager/hr/attendance*') ? 'active' : '' }}">
                    <span class="nav-icon">⏰</span> Devam Raporu
                </a>
                <a href="/manager/hr/recruitment"
                   class="nav-link {{ request()->is('manager/hr/recruitment*') ? 'active' : '' }}">
                    <span class="nav-icon">🎯</span> İşe Alım
                </a>
                <a href="/manager/hr/salary"
                   class="nav-link {{ request()->is('manager/hr/salary*') ? 'active' : '' }}">
                    <span class="nav-icon">💳</span> Bordro Profilleri
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Finans</div>
                <a href="/manager/finance"
                   class="nav-link {{ request()->is('manager/finance') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Finans Özeti
                </a>
                <a href="/manager/finance/reports"
                   class="nav-link {{ request()->is('manager/finance/reports*') ? 'active' : '' }}">
                    <span class="nav-icon">📈</span> Raporlar & Projeksiyon
                </a>
                <a href="/manager/finance/entries"
                   class="nav-link {{ request()->is('manager/finance/entries*') ? 'active' : '' }}">
                    <span class="nav-icon">📒</span> Gelir & Gider Kayıtları
                </a>
                <a href="/manager/commissions"
                   class="nav-link {{ request()->is('manager/commissions*') ? 'active' : '' }}">
                    <span class="nav-icon">💸</span> Komisyonlar
                </a>
                <a href="/manager/payments"
                   class="nav-link {{ request()->is('manager/payments*') ? 'active' : '' }}">
                    <span class="nav-icon">🧾</span> Öğrenci Faturaları
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Belgeler & Sözleşmeler</div>
                @can('dam.view')
                <a href="{{ route('manager.dam.index') }}"
                   class="nav-link {{ request()->is('manager/digital-assets*') ? 'active' : '' }}">
                    <span class="nav-icon">📁</span> Dijital Varlıklar
                </a>
                @endcan
                <a href="/manager/university-requirements"
                   class="nav-link {{ request()->is('manager/university-requirements*') ? 'active' : '' }}">
                    <span class="nav-icon">🗺️</span> Üniversite Belge Haritası
                </a>
                <a href="/manager/doc-templates"
                   class="nav-link {{ request()->is('manager/doc-templates*') ? 'active' : '' }}">
                    <span class="nav-icon">📝</span> Belge Şablonları
                </a>
                @php
                    $isSozlesmeOpen = request()->is('manager/contract-template*') || request()->is('manager/business-contracts*') || request()->is('my-contracts*');
                @endphp
                <div>
                    <button type="button"
                            id="sozlesme-btn"
                            data-toggle="sozlesme"
                            class="nav-link {{ $isSozlesmeOpen ? 'active' : '' }}"
                            style="display:flex;align-items:center;justify-content:space-between;width:100%;background:none;border:none;cursor:pointer;text-align:left;">
                        <span><span class="nav-icon">📋</span> Sözleşme Yönetimi</span>
                        <span id="sozlesme-caret" style="font-size:10px;transition:transform .2s;{{ $isSozlesmeOpen ? 'transform:rotate(180deg)' : '' }}">▾</span>
                    </button>
                    <div id="sozlesme-sub" style="{{ $isSozlesmeOpen ? '' : 'display:none;' }}padding-left:12px;">
                        <a href="/manager/contract-template"
                           class="nav-link {{ request()->is('manager/contract-template*') ? 'active' : '' }}"
                           style="font-size:12px;padding:6px 12px;">
                            <span class="nav-icon" style="font-size:14px;">👤</span> Öğrenci
                        </a>
                        <a href="/manager/business-contracts?type=staff"
                           class="nav-link {{ (request()->is('manager/business-contracts*') && request()->get('type')==='staff') ? 'active' : '' }}"
                           style="font-size:12px;padding:6px 12px;">
                            <span class="nav-icon" style="font-size:14px;">👥</span> Staff
                        </a>
                        <a href="/manager/business-contracts?type=dealer"
                           class="nav-link {{ (request()->is('manager/business-contracts*') && request()->get('type')==='dealer') ? 'active' : '' }}"
                           style="font-size:12px;padding:6px 12px;">
                            <span class="nav-icon" style="font-size:14px;">🤝</span> Dealer
                        </a>
                    </div>
                </div>
                <a href="/manager/contract-analytics"
                   class="nav-link {{ request()->is('manager/contract-analytics*') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Sözleşme Analitik
                </a>
                <a href="/my-contracts"
                   class="nav-link {{ request()->is('my-contracts*') ? 'active' : '' }}">
                    <span class="nav-icon">📄</span> İş Sözleşmem
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Sistem</div>
                <a href="/manager/system"
                   class="nav-link {{ request()->is('manager/system') ? 'active' : '' }}">
                    <span class="nav-icon">🖥</span> Sistem Paneli
                </a>
                <a href="/manager/system/security"
                   class="nav-link {{ request()->is('manager/system/security*') ? 'active' : '' }}">
                    <span class="nav-icon">🛡</span> Güvenlik Paneli
                </a>
                <a href="/manager/system/roles"
                   class="nav-link {{ request()->is('manager/system/roles*') ? 'active' : '' }}">
                    <span class="nav-icon">🔑</span> Rol Yönetimi
                </a>
                <a href="/manager/system/ip-rules"
                   class="nav-link {{ request()->is('manager/system/ip-rules*') ? 'active' : '' }}">
                    <span class="nav-icon">🌐</span> IP Erişim Kuralları
                </a>
                <a href="/manager/audit-log"
                   class="nav-link {{ request()->is('manager/audit-log*') ? 'active' : '' }}">
                    <span class="nav-icon">🔍</span> Denetim Kayıtları
                </a>
                <a href="/manager/gdpr-dashboard"
                   class="nav-link {{ request()->is('manager/gdpr-dashboard*') ? 'active' : '' }}">
                    <span class="nav-icon">🔒</span> GDPR Paneli
                </a>
                <a href="/manager/notification-stats"
                   class="nav-link {{ request()->is('manager/notification-stats*') ? 'active' : '' }}">
                    <span class="nav-icon">🔔</span> Bildirim İstatistik
                </a>
                <a href="/manager/webhooks"
                   class="nav-link {{ request()->is('manager/webhooks*') ? 'active' : '' }}">
                    <span class="nav-icon">🔗</span> Webhook Logları
                </a>
                <a href="/manager/theme"
                   class="nav-link {{ request()->is('manager/theme*') ? 'active' : '' }}">
                    <span class="nav-icon">🎨</span> Tema Yönetimi
                </a>
                <a href="/manager/brand"
                   class="nav-link {{ request()->is('manager/brand*') ? 'active' : '' }}">
                    <span class="nav-icon">🏷</span> Marka Ayarları
                </a>
                <a href="/config"
                   class="nav-link {{ request()->is('config*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙</span> Sistem Ayarları
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('manager.handbook') }}" class="nav-link {{ request()->routeIs('manager.handbook') ? 'active' : '' }}" style="margin-bottom:6px;">
                <span class="nav-icon">📖</span> El Kitabı
            </a>
            <form method="POST" action="{{ route('system.cache-clear') }}" style="margin:0 0 6px;">
                @csrf
                <button type="submit" class="nav-link" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;font:inherit;color:inherit;padding:8px 14px;">
                    <span class="nav-icon">🗑️</span> Cache Temizle
                </button>
            </form>
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
                    <div class="topbar-title">@yield('page_title', 'Manager Portalı')</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                <button class="icon-btn" onclick="__dmToggle()" id="dm-btn" title="Tema">🌙</button>
                <button class="icon-btn" onclick="__designToggle()" id="design-btn" title="Tasarım Teması">🎨</button>
                <div class="avatar" style="width:36px;height:36px;font-size:13px;" title="{{ $mgrUser?->name ?? 'Manager' }}">
                    {{ $mgrInitials }}
                </div>
            </div>
        </header>

        @if(!empty($urgentBulletins) && $urgentBulletins->isNotEmpty())
        <div style="background:#dc2626;color:#fff;padding:9px 22px;font-size:13px;font-weight:600;display:flex;gap:12px;align-items:center;flex-shrink:0;">
            <span>🚨</span><span>{{ $urgentBulletins->first()->title }}</span>
            <a href="/manager/bulletins" style="color:#fff;text-decoration:underline;margin-left:auto;font-size:12px;">Yönet →</a>
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
    #premium-overlay.active { display:block; }
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
<style>
.nav-section-label{cursor:pointer!important;pointer-events:auto!important;user-select:none;display:flex!important;align-items:center;justify-content:space-between;opacity:1!important;}
.nav-section-label::after{content:'▾';font-size:.7rem;opacity:.6;transition:transform .2s;}
.nav-section.collapsed .nav-section-label::after{transform:rotate(-90deg);}
.nav-section.collapsed .nav-link{display:none!important;}
</style>
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // ── Mobile hamburger + back btn (CSP-safe) ──
    var _bb=document.getElementById('premium-back-btn');
    if(_bb){_bb.addEventListener('click',function(){history.back();});}
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb)_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});
    if(_ov)_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});
    var STORE_KEY='manager_sidebar_collapsed';
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
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('sozlesme-btn')?.addEventListener('click', function() {
    var sub   = document.getElementById('sozlesme-sub');
    var caret = document.getElementById('sozlesme-caret');
    var open  = sub.style.display !== 'none';
    sub.style.display      = open ? 'none' : 'block';
    caret.style.transform  = open ? '' : 'rotate(180deg)';
});
</script>
@stack('scripts')
@include('partials.push-init')
</body>
</html>
