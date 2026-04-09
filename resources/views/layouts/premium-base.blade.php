{{--
    MentorDE Premium Layout
    resources/views/layouts/premium-base.blade.php

    Kullanım:
    @extends('layouts.premium-base', [
        'portalKey'   => 'guest',
        'portalTitle' => 'Öğrenci Portalı',
        'accentColor' => '#2563eb',
    ])
--}}
<!DOCTYPE html>
<html lang="tr" data-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MentorDE — ' . ($portalTitle ?? 'Portal'))</title>

    {{-- Premium Design System CSS --}}
    <link rel="stylesheet" href="{{ Vite::asset('resources/css/premium.css') }}">

    {{-- Portal accent override --}}
    @php
        $accents = [
            'guest'    => ['accent' => '#2563eb', 'accent2' => '#1d4ed8', 'bg' => '#f1f5f9', 'border' => '#e2e8f0', 'text' => '#0f172a', 'gradient' => 'linear-gradient(135deg, #2563eb, #7c3aed)'],
            'student'  => ['accent' => '#7c3aed', 'accent2' => '#6d28d9', 'bg' => '#faf5ff', 'border' => '#ede9fe', 'text' => '#1e1b4b', 'gradient' => 'linear-gradient(135deg, #7c3aed, #2563eb)'],
            'senior'   => ['accent' => '#0891b2', 'accent2' => '#0e7490', 'bg' => '#f0f9ff', 'border' => '#e0f2fe', 'text' => '#0c4a6e', 'gradient' => 'linear-gradient(135deg, #0891b2, #2563eb)'],
            'manager'  => ['accent' => '#0f172a', 'accent2' => '#1e293b', 'bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#0f172a', 'gradient' => 'linear-gradient(135deg, #0f172a, #1e40af)'],
            'dealer'   => ['accent' => '#16a34a', 'accent2' => '#15803d', 'bg' => '#f0fdf4', 'border' => '#dcfce7', 'text' => '#14532d', 'gradient' => 'linear-gradient(135deg, #16a34a, #0891b2)'],
            'marketing'=> ['accent' => '#7c3aed', 'accent2' => '#6d28d9', 'bg' => '#faf5ff', 'border' => '#ede9fe', 'text' => '#1e1b4b', 'gradient' => 'linear-gradient(135deg, #7c3aed, #ec4899)'],
        ];
        $a = $accents[$portalKey ?? 'guest'] ?? $accents['guest'];
    @endphp
    <style>
        :root {
            --c-accent: {{ $a['accent'] }};
            --c-accent2: {{ $a['accent2'] }};
            --accent-soft: {{ $a['accent'] }}18;
            --hero-gradient: {{ $a['gradient'] }};
        }
    </style>

    {{-- Tema override (Manager'dan) --}}
    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} }</style>
    @endif

    @stack('head')
</head>
<body>
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="premium-sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">M</div>
            <div>
                <div class="brand-name">MentorDE</div>
                <div class="brand-sub">{{ $portalTitle ?? 'Portal' }}</div>
            </div>
        </div>

        @yield('sidebar-user')

        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>

        <div class="sidebar-footer">
            @yield('sidebar-footer')
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
                {{-- Mobile hamburger --}}
                <button class="icon-btn" style="display:none;" id="premium-menu-btn">
                    ☰
                </button>
                <div>
                    <div class="topbar-title">@yield('page_title', $portalTitle ?? '')</div>
                    @hasSection('page_subtitle')
                        <div class="topbar-sub">@yield('page_subtitle')</div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                <a href="#" class="icon-btn" title="Bildirimler">
                    🔔
                    @if(($unreadNotifications ?? 0) > 0)<span class="notif-dot"></span>@endif
                </a>
                <a href="#" class="icon-btn" title="Mesajlar">💬</a>
                <div class="avatar" title="{{ auth()->user()?->name ?? 'Kullanıcı' }}">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                </div>
            </div>
        </header>

        {{-- Content --}}
        <div class="content">
            {{-- Flash --}}
            @if (session('status'))
                <div class="card" style="border-left:4px solid var(--c-ok);margin-bottom:16px;">
                    <div class="card-body" style="padding:12px 16px;color:var(--c-ok);font-weight:500;">
                        {{ session('status') }}
                    </div>
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

{{-- Mobile overlay --}}
<div id="premium-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"></div>
<style>
    #premium-overlay.active { display: block; }
    @media (max-width: 900px) { #premium-menu-btn { display: flex !important; } }
</style>

{{-- Dark mode toggle --}}
<div class="dark-toggle" title="Tema Değiştir" id="theme-toggle">
    <span id="theme-icon">🌙</span>
</div>

{{-- Toast Container --}}
<div id="toast-container" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:min(360px, calc(100vw - 40px));"></div>

{{-- Alpine.js (manifest okuma) --}}
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs = $__manifest['resources/js/app.js']['file'] ?? null;
@endphp
@if($__appJs)
    <script type="module" src="/build/{{ $__appJs }}"></script>
@endif

{{-- Theme + Toast + Sidebar scripts --}}
<script nonce="{{ $cspNonce ?? '' }}">
// Dark mode
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.getElementById('theme-icon').textContent = next === 'dark' ? '☀️' : '🌙';
    fetch('/api/theme-toggle', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Content-Type': 'application/json' }, body: JSON.stringify({ theme: next }) }).catch(() => {});
    localStorage.setItem('mentorde_theme', next);
}
// Restore theme
(function() {
    const saved = localStorage.getItem('mentorde_theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        document.addEventListener('DOMContentLoaded', () => {
            const icon = document.getElementById('theme-icon');
            if (icon) icon.textContent = saved === 'dark' ? '☀️' : '🌙';
        });
    }
})();

// ── Mobile hamburger + overlay (CSP-safe addEventListener) ──
(function(){
    var _mb=document.getElementById('premium-menu-btn');
    var _ov=document.getElementById('premium-overlay');
    var _sb=document.getElementById('premium-sidebar');
    if(_mb){_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});}
    if(_ov){_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});}
    var _tt=document.getElementById('theme-toggle');
    if(_tt){_tt.addEventListener('click',toggleTheme);}
})();

// Toast (Alpine effect)
document.addEventListener('alpine:init', () => {
    Alpine.effect(() => {
        const items = Alpine.store('toast').items;
        const c = document.getElementById('toast-container');
        if (!c) return;
        c.innerHTML = items.map(i => {
            const bg = i.type==='ok'?'var(--c-ok)':i.type==='danger'?'var(--c-danger)':i.type==='warn'?'var(--c-warn)':'var(--c-accent)';
            return '<div style="background:'+bg+';color:#fff;padding:12px 18px;border-radius:var(--r-sm);box-shadow:var(--shadow-md);font-size:var(--tx-sm);font-weight:500;margin-top:8px;animation:slideIn .3s ease">'+i.message+'</div>';
        }).join('');
    });
});
</script>
<style>@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }</style>

@yield('portal-widgets')
@stack('scripts')
</body>
</html>
