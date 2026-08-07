<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DGmarkt Platform — Owner Console')</title>
    {{-- DGmarkt favicon (Platform Owner panelinde, Mentorde'nin favicon'ını ezer) --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/dgmarkt/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('brand/dgmarkt/mark.svg') }}">

    <link rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Platform Owner accent: koyu siyah + mor (admin/dev tarzi, customer panellerden ayrismasi icin) --}}
    <style>
        :root {
            --plat-bg:        #0f0c1f;
            --plat-panel:     #1a1730;
            --plat-panel-2:   #221d3b;
            --plat-border:    #2e2848;
            --plat-text:      #f3f1fc;
            --plat-muted:     #a09bb5;
            --plat-accent:    #7e58bf;
            --plat-accent-2:  #b395e6;
            --plat-accent-bg: rgba(126,88,191,.14);
            --plat-ok:        #4ade80;
            --plat-warn:      #fbbf24;
            --plat-danger:    #f87171;
            --plat-info:      #60a5fa;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: var(--plat-bg); color: var(--plat-text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Inter", system-ui, sans-serif; font-size: 14px; line-height: 1.5; }
        a { color: var(--plat-accent-2); text-decoration: none; }
        a:hover { color: #fff; }

        .plat-app { display: grid; grid-template-columns: 248px 1fr; min-height: 100vh; }
        .plat-sidebar { background: linear-gradient(180deg, #0d0a1e 0%, #1a1535 100%); border-right: 1px solid var(--plat-border); padding: 18px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .plat-brand { display: flex; align-items: center; gap: 10px; padding: 0 18px 18px; border-bottom: 1px solid var(--plat-border); margin-bottom: 14px; }
        .plat-brand-mark { width: 40px; height: 40px; border-radius: 10px; overflow: hidden; flex-shrink: 0; }
        .plat-brand-mark img { width: 100%; height: 100%; display: block; }
        .plat-brand-text { display: flex; flex-direction: column; }
        .plat-brand-title { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -.3px; }
        .plat-brand-sub { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--plat-accent-2); }

        .plat-nav { padding: 0 12px; }
        .plat-nav-label { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--plat-muted); padding: 14px 10px 6px; }
        .plat-nav a { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; color: var(--plat-text); font-size: 13px; font-weight: 500; margin-bottom: 2px; transition: background .12s, color .12s; }
        .plat-nav a:hover { background: var(--plat-panel-2); color: #fff; }
        .plat-nav a.active { background: var(--plat-accent-bg); color: var(--plat-accent-2); font-weight: 700; }
        .plat-nav a .lucide-icon { flex-shrink: 0; opacity: .9; }

        .plat-main { padding: 0; }
        .plat-topbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 28px; background: linear-gradient(90deg, #0f0c1f 0%, #1a1430 100%); border-bottom: 1px solid var(--plat-border); position: sticky; top: 0; z-index: 30; }
        .plat-topbar-title { font-size: 13px; color: var(--plat-muted); font-weight: 600; }
        .plat-topbar-title strong { color: #fff; font-weight: 800; margin-right: 8px; }
        .plat-user { display: flex; align-items: center; gap: 12px; }
        .plat-user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--plat-accent), var(--plat-accent-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 13px; }
        .plat-user-info { display: flex; flex-direction: column; }
        .plat-user-name { font-size: 13px; font-weight: 700; color: #fff; }
        .plat-user-role { font-size: 10px; color: var(--plat-accent-2); font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .plat-logout { background: transparent; border: 1px solid var(--plat-border); color: var(--plat-muted); padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; }
        .plat-logout:hover { color: #fff; border-color: var(--plat-accent); }

        .plat-content { padding: 28px; max-width: 1500px; }
        .plat-page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .plat-page-title { font-size: 22px; font-weight: 800; color: #fff; margin: 0; }
        .plat-page-sub { font-size: 13px; color: var(--plat-muted); margin: 4px 0 0; }

        /* Cards */
        .plat-card { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 18px 20px; }
        .plat-card-title { font-size: 14px; font-weight: 700; color: #fff; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
        .plat-card-sub { font-size: 12px; color: var(--plat-muted); font-weight: 500; }

        .plat-grid { display: grid; gap: 16px; }
        .plat-grid-4 { grid-template-columns: repeat(4, 1fr); }
        .plat-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .plat-grid-2 { grid-template-columns: repeat(2, 1fr); }
        @media (max-width: 1100px) { .plat-grid-4 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .plat-grid-4, .plat-grid-3, .plat-grid-2 { grid-template-columns: 1fr; } .plat-app { grid-template-columns: 1fr; } .plat-sidebar { position: relative; height: auto; } }

        /* KPI tiles */
        .plat-kpi { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 18px 20px; position: relative; overflow: hidden; }
        .plat-kpi::before { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg, var(--plat-accent), var(--plat-accent-2)); }
        .plat-kpi-label { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--plat-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .plat-kpi-value { font-size: 28px; font-weight: 800; color: #fff; line-height: 1.2; }
        .plat-kpi-sub { font-size: 12px; color: var(--plat-muted); margin-top: 4px; }

        /* Buttons */
        .plat-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: all .15s; line-height: 1; }
        .plat-btn-primary { background: linear-gradient(135deg, var(--plat-accent), var(--plat-accent-2)); color: #fff; border: none; }
        .plat-btn-primary:hover { opacity: .92; color: #fff; transform: translateY(-1px); }
        .plat-btn-ghost { background: var(--plat-panel-2); border-color: var(--plat-border); color: var(--plat-text); }
        .plat-btn-ghost:hover { border-color: var(--plat-accent); color: #fff; }
        .plat-btn-danger { background: rgba(248,113,113,.14); border-color: rgba(248,113,113,.4); color: var(--plat-danger); }
        .plat-btn-danger:hover { background: rgba(248,113,113,.24); color: #fff; }
        .plat-btn-sm { padding: 6px 10px; font-size: 12px; }

        /* Badges */
        .plat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .plat-badge-trial    { background: rgba(96,165,250,.14); color: var(--plat-info); }
        .plat-badge-basic    { background: rgba(74,222,128,.14); color: var(--plat-ok); }
        .plat-badge-gold     { background: rgba(251,191,36,.16); color: var(--plat-warn); }
        .plat-badge-premium  { background: rgba(126,88,191,.20); color: var(--plat-accent-2); }
        .plat-badge-active   { background: rgba(74,222,128,.14); color: var(--plat-ok); }
        .plat-badge-inactive { background: rgba(248,113,113,.14); color: var(--plat-danger); }

        /* Forms */
        .plat-form-group { margin-bottom: 16px; }
        .plat-form-label { display: block; font-size: 12px; font-weight: 700; color: var(--plat-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .3px; }
        .plat-input, .plat-select, .plat-textarea {
            width: 100%; padding: 9px 12px; background: var(--plat-bg); border: 1px solid var(--plat-border);
            color: var(--plat-text); border-radius: 8px; font-size: 13px; font-family: inherit;
        }
        .plat-input:focus, .plat-select:focus, .plat-textarea:focus { outline: none; border-color: var(--plat-accent); box-shadow: 0 0 0 3px var(--plat-accent-bg); }

        /* Table */
        .plat-table { width: 100%; border-collapse: collapse; }
        .plat-table th { text-align: left; padding: 12px 14px; font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--plat-muted); border-bottom: 1px solid var(--plat-border); background: var(--plat-panel-2); }
        .plat-table td { padding: 12px 14px; border-bottom: 1px solid var(--plat-border); font-size: 13px; }
        .plat-table tbody tr:hover { background: rgba(255,255,255,.02); }

        /* Alerts */
        .plat-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; border: 1px solid; display: flex; align-items: center; gap: 10px; }
        .plat-alert-success { background: rgba(74,222,128,.10); border-color: rgba(74,222,128,.30); color: var(--plat-ok); }
        .plat-alert-danger  { background: rgba(248,113,113,.10); border-color: rgba(248,113,113,.30); color: var(--plat-danger); }
        .plat-alert-warn    { background: rgba(251,191,36,.10); border-color: rgba(251,191,36,.30); color: var(--plat-warn); }

        /* Tier bars (dashboard) */
        .plat-tier-bar { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
        .plat-tier-bar-label { width: 140px; font-size: 13px; font-weight: 600; color: var(--plat-text); }
        .plat-tier-bar-track { flex: 1; height: 12px; background: var(--plat-panel-2); border-radius: 999px; overflow: hidden; }
        .plat-tier-bar-fill { height: 100%; background: linear-gradient(90deg, var(--plat-accent), var(--plat-accent-2)); border-radius: 999px; transition: width .3s; }
        .plat-tier-bar-count { width: 40px; text-align: right; font-size: 13px; font-weight: 700; color: #fff; }

        /* Module toggle matrix */
        .plat-module-toggle { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 8px; margin-bottom: 8px; }
        .plat-module-toggle.locked { opacity: .6; }
        .plat-module-name { display: flex; flex-direction: column; }
        .plat-module-name-title { font-size: 13px; font-weight: 700; color: #fff; }
        .plat-module-name-desc { font-size: 11px; color: var(--plat-muted); margin-top: 2px; }
        .plat-switch { position: relative; display: inline-block; width: 42px; height: 22px; }
        .plat-switch input { opacity: 0; width: 0; height: 0; }
        .plat-switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: var(--plat-border); border-radius: 22px; transition: .2s; }
        .plat-switch-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .2s; }
        .plat-switch input:checked + .plat-switch-slider { background: linear-gradient(135deg, var(--plat-accent), var(--plat-accent-2)); }
        .plat-switch input:checked + .plat-switch-slider:before { transform: translateX(20px); }
        .plat-switch input:disabled + .plat-switch-slider { cursor: not-allowed; }
    </style>
    @stack('head')
    @stack('styles')
</head>
<body>
<div class="plat-app">

    {{-- SIDEBAR --}}
    <aside class="plat-sidebar">
        <div class="plat-brand">
            <div class="plat-brand-mark">
                <img src="{{ asset('brand/dgmarkt/mark.svg') }}" alt="DGmarkt" loading="eager">
            </div>
            <div class="plat-brand-text">
                <span class="plat-brand-title">dgmarkt</span>
                <span class="plat-brand-sub">Owner Console</span>
            </div>
        </div>

        <nav class="plat-nav">
            <div class="plat-nav-label">Genel Bakış</div>
            <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                <x-icon name="home" size="16" /> Dashboard
            </a>
            <a href="{{ route('platform.companies') }}" class="{{ request()->routeIs('platform.companies') || request()->routeIs('platform.companies.show') || request()->routeIs('platform.companies.create') ? 'active' : '' }}">
                <x-icon name="building-2" size="16" /> Şirketler
            </a>
            {{-- Konsolide portföy: tüm şirketlerin adayları/öğrencileri tek listede --}}
            <a href="{{ route('platform.leads') }}" class="{{ request()->routeIs('platform.leads') ? 'active' : '' }}">
                <x-icon name="users" size="16" /> Tüm Adaylar
            </a>
            <a href="{{ route('platform.students') }}" class="{{ request()->routeIs('platform.students') ? 'active' : '' }}">
                <x-icon name="graduation-cap" size="16" /> Tüm Öğrenciler
            </a>
            <a href="{{ route('platform.customer-health') }}" class="{{ request()->routeIs('platform.customer-health') || request()->routeIs('platform.customer-health.*') ? 'active' : '' }}">
                <x-icon name="heart" size="16" /> Customer Sağlık
            </a>
            <a href="{{ route('platform.broadcasts') }}" class="{{ request()->routeIs('platform.broadcasts') || request()->routeIs('platform.broadcasts.*') ? 'active' : '' }}">
                <x-icon name="megaphone" size="16" /> Duyurular
            </a>

            <div class="plat-nav-label">Analiz & Faturalama</div>
            <a href="{{ route('platform.analytics') }}" class="{{ request()->routeIs('platform.analytics') || request()->routeIs('platform.analytics.export') ? 'active' : '' }}">
                <x-icon name="bar-chart-3" size="16" /> Analytics
            </a>
            <a href="{{ route('platform.billing') }}" class="{{ request()->routeIs('platform.billing') || request()->routeIs('platform.billing.show') || request()->routeIs('platform.billing.pdf') ? 'active' : '' }}">
                <x-icon name="dollar-sign" size="16" /> Faturalama
            </a>
            <a href="{{ route('platform.mrr-trend') }}" class="{{ request()->routeIs('platform.mrr-trend') || request()->routeIs('platform.mrr-trend.export') ? 'active' : '' }}">
                <x-icon name="trending-up" size="16" /> MRR Trendi
            </a>
            <a href="{{ route('platform.trial') }}" class="{{ request()->routeIs('platform.trial') || request()->routeIs('platform.trial.*') ? 'active' : '' }}">
                <x-icon name="hourglass" size="16" /> Trial Yonetimi
            </a>
            <a href="{{ route('platform.promo-codes') }}" class="{{ request()->routeIs('platform.promo-codes') || request()->routeIs('platform.promo-codes.*') ? 'active' : '' }}">
                <x-icon name="tag" size="16" /> İndirim Kodları
            </a>

            <div class="plat-nav-label">Sistem</div>
            <a href="{{ route('platform.settings') }}" class="{{ request()->routeIs('platform.settings') || request()->routeIs('platform.settings.*') ? 'active' : '' }}">
                <x-icon name="settings" size="16" /> Platform Ayarları
            </a>
            <a href="{{ route('platform.infrastructure') }}" class="{{ request()->routeIs('platform.infrastructure') || request()->routeIs('platform.infrastructure.*') ? 'active' : '' }}">
                <x-icon name="server" size="16" /> Altyapı
            </a>
            {{-- Form tek merkezden yönetiliyor; sapan firma buradan görünür. --}}
            <a href="{{ route('platform.form-template') }}" class="{{ request()->routeIs('platform.form-template*') ? 'active' : '' }}">
                <x-icon name="file-text" size="16" /> Form Şablonu
            </a>
            <a href="{{ route('platform.backups.index') }}" class="{{ request()->routeIs('platform.backups.*') ? 'active' : '' }}">
                <x-icon name="database" size="16" /> Yedekler
            </a>
            <a href="{{ route('platform.security') }}" class="{{ request()->routeIs('platform.security') || request()->routeIs('platform.security.*') ? 'active' : '' }}">
                <x-icon name="shield" size="16" /> Güvenlik
            </a>
            <a href="{{ route('platform.audit-log') }}" class="{{ request()->routeIs('platform.audit-log') || request()->routeIs('platform.audit-log.*') ? 'active' : '' }}">
                <x-icon name="shield" size="16" /> Denetim Kayıtları
            </a>

            <div class="plat-nav-label">Diğer</div>
            <a href="/manager/dashboard">
                <x-icon name="building-2" size="16" /> Manager Paneli
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="plat-main">
        {{-- Topbar --}}
        <div class="plat-topbar">
            <div class="plat-topbar-title">
                <strong>DGmarkt Platform</strong>
                <span>Owner Console</span>
            </div>
            <div class="plat-user">
                @php $u = auth()->user(); $initials = strtoupper(substr(preg_replace('/\s+/', '', $u?->name ?? 'PO'), 0, 2)); @endphp
                <div class="plat-user-info">
                    <span class="plat-user-name">{{ $u?->name ?? 'Platform Owner' }}</span>
                    <span class="plat-user-role">Platform Owner</span>
                </div>
                <div class="plat-user-avatar">{{ $initials }}</div>
                <form method="POST" action="/logout" style="margin:0;">
                    @csrf
                    <button type="submit" class="plat-logout"><x-icon name="log-out" size="14" /> Çıkış</button>
                </form>
            </div>
        </div>

        {{-- Content --}}
        <div class="plat-content">
            @if (session('success'))
                <div class="plat-alert plat-alert-success"><x-icon name="check" size="16" /> {{ session('success') }}</div>
            @endif
            {{-- Bazı controller'lar Laravel'in yaygın 'status' anahtarını kullanıyor
                 (marka kaydetme, aday devri). Karşılığı olmadığı için mesajlar sessizce
                 kayboluyordu — kullanıcı işlemin geçip geçmediğini göremiyordu. --}}
            @if (session('status'))
                <div class="plat-alert plat-alert-success"><x-icon name="check" size="16" /> {{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="plat-alert plat-alert-danger"><x-icon name="circle-alert" size="16" /> {{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="plat-alert plat-alert-danger">
                    <x-icon name="alert-triangle" size="16" />
                    <div>
                        @foreach ($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
{{-- Real-time bildirim (Pusher) — addon: down olursa sayfa etkilenmez --}}
@include('partials.notification-toast')
</body>
</html>
