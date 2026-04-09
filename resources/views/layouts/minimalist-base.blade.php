{{--
    Minimalist Layout (white-label, brand.php config)
    resources/views/layouts/minimalist-base.blade.php

    Kullanım:
    @extends('layouts.minimalist-base', [
        'portalKey'   => 'guest',
        'portalTitle' => 'Öğrenci Portalı',
    ])
--}}
<!DOCTYPE html>
<html lang="tr" data-theme="{{ session('mentorde_theme_v2', 'light') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('brand.name', 'MentorDE') . ' — ' . ($portalTitle ?? 'Portal'))</title>

    {{-- Minimalist CSS --}}
    <link rel="stylesheet" href="{{ Vite::asset('resources/css/minimalist.css') }}">

    {{-- Tema override (Manager'dan) --}}
    @if (!empty($uiThemeCssVars))
        <style>:root{ {!! $uiThemeCssVars !!} }</style>
    @endif

    @stack('head')
</head>
<body>
<div class="app">

    {{-- Sidebar --}}
    @php
        $mbBrandName = config('brand.name', 'MentorDE');
        $mbBrandLogo = config('brand.logo_url') ?: config('brand.logo_path');
        $mbBrandInit = strtoupper(mb_substr($mbBrandName, 0, 1));
    @endphp
    <aside class="sidebar" id="min-sidebar">
        <div class="sidebar-brand">
            @if($mbBrandLogo)
                <div class="brand-logo" style="background:transparent;padding:0;"><img src="{{ $mbBrandLogo }}" alt="{{ $mbBrandName }}" style="max-height:{{ (int) config('brand.logo_height', 40) }}px;width:auto;"></div>
            @else
                <div class="brand-logo">{{ $mbBrandInit }}</div>
            @endif
            <div>
                <div class="brand-name">{{ $mbBrandName }}</div>
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
                <span class="nav-icon">→</span> Çıkış Yap
            </a>
        </div>
    </aside>

    {{-- Main --}}
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn" id="min-menu-btn" style="display:none;">
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
                <div class="avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}</div>
            </div>
        </header>

        <div class="content">
            {{-- Flash --}}
            @if (session('status'))
                <div style="background:var(--subtle);border:1px solid var(--border);border-left:3px solid var(--c-ok);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:16px;font-size:13px;color:var(--text);">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div style="background:var(--subtle);border:1px solid var(--border);border-left:3px solid var(--c-danger);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:16px;font-size:13px;color:var(--c-danger);">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

{{-- Mobile overlay --}}
<div id="min-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:99;"></div>
<style>
    #min-overlay.active { display: block; }
    @media (max-width: 900px) { #min-menu-btn { display: flex !important; } }
</style>

{{-- Dark mode toggle --}}
<div class="dark-toggle" id="min-theme-toggle">🌙</div>

{{-- Toast Container --}}
<div id="toast-container" style="position:fixed;bottom:16px;right:56px;z-index:9999;display:flex;flex-direction:column;gap:6px;max-width:320px;"></div>

{{-- Alpine.js --}}
@php
    $__manifest = json_decode(@file_get_contents(public_path('build/manifest.json')) ?: '{}', true);
    $__appJs = $__manifest['resources/js/app.js']['file'] ?? null;
@endphp
@if($__appJs)
    <script type="module" src="/build/{{ $__appJs }}"></script>
@endif

<script nonce="{{ $cspNonce ?? '' }}">
// Dark mode
function toggleMinTheme() {
    const html = document.documentElement;
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.getElementById('min-theme-toggle').textContent = next === 'dark' ? '☀️' : '🌙';
    localStorage.setItem('mentorde_theme', next);
}
(function() {
    const saved = localStorage.getItem('mentorde_theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('min-theme-toggle');
            if (el) el.textContent = saved === 'dark' ? '☀️' : '🌙';
        });
    }
})();

// ── Mobile hamburger + overlay + theme toggle (CSP-safe addEventListener) ──
(function(){
    var _mb=document.getElementById('min-menu-btn');
    var _ov=document.getElementById('min-overlay');
    var _sb=document.getElementById('min-sidebar');
    if(_mb){_mb.addEventListener('click',function(){_sb.classList.toggle('mobile-open');if(_ov)_ov.classList.toggle('active');});}
    if(_ov){_ov.addEventListener('click',function(){_sb.classList.remove('mobile-open');_ov.classList.remove('active');});}
    var _tt=document.getElementById('min-theme-toggle');
    if(_tt){_tt.addEventListener('click',toggleMinTheme);}
})();

// Sidebar toggle
document.querySelectorAll('[data-toggle-group]').forEach(btn => {
    btn.addEventListener('click', () => {
        const g = document.getElementById(btn.dataset.toggleGroup);
        if (g) g.classList.toggle('open');
    });
});

// Toast
document.addEventListener('alpine:init', () => {
    Alpine.effect(() => {
        const items = Alpine.store('toast').items;
        const c = document.getElementById('toast-container');
        if (!c) return;
        c.innerHTML = items.map(i => {
            const bg = i.type==='ok'?'var(--c-ok)':i.type==='danger'?'var(--c-danger)':i.type==='warn'?'var(--c-warn)':'var(--text)';
            return '<div style="background:'+bg+';color:var(--surface);padding:8px 14px;border-radius:var(--r-sm);font-size:12px;font-weight:500;">'+i.message+'</div>';
        }).join('');
    });
});
</script>

@yield('portal-widgets')
@stack('scripts')
</body>
</html>
