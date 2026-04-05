@extends('manager.layouts.app')
@section('title', 'Tema Yönetimi')
@section('page_title', 'Tema Yönetimi')

@push('head')
<style>
/* ── Ana wrapper: form tam genişlik ── */
.th-wrap { display:block; }

/* ── Form içi 2 sütunlu kart griди ── */
.th-grid   { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.th-span2  { grid-column:1 / -1; }
@media(max-width:900px){ .th-grid { grid-template-columns:1fr; } .th-span2 { grid-column:1; } }

/* ── Kart ── */
.th-card {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:12px; overflow:hidden;
}
.th-card-head {
    display:flex; align-items:center; gap:7px;
    padding:10px 14px;
    background:var(--subtle,#f8fafc);
    border-bottom:1px solid var(--border,#e2e8f0);
    font-size:11px; font-weight:800; color:var(--text,#0f172a);
    letter-spacing:-.01em;
}
.th-card-body { padding:12px 14px; }

/* ── Preset chipler ── */
.th-presets { display:flex; gap:6px; flex-wrap:wrap; }
.th-chip {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border:1.5px solid var(--border,#e2e8f0);
    border-radius:999px; font-size:11px; font-weight:600;
    cursor:pointer; background:var(--surface,#fff); color:var(--text,#374151);
    transition:all .15s;
}
.th-chip:hover { border-color:#1e40af; color:#1e40af; }
.th-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }

/* ── Renk satırı ── */
.cr { display:grid; grid-template-columns:20px 1fr auto; gap:8px; align-items:center; padding:7px 0; border-bottom:1px solid var(--subtle,#f8fafc); }
.cr:last-child { border-bottom:none; }
.cr-swatch { width:20px; height:20px; border-radius:4px; border:1px solid rgba(0,0,0,.09); flex-shrink:0; }
.cr-label  { font-size:11px; color:var(--text,#374151); line-height:1.3; }
.cr-label small { display:block; font-size:10px; color:var(--muted,#64748b); font-weight:400; }
.cr-pick   { display:flex; align-items:center; gap:4px; }
.cr-pick input[type="color"] { width:32px; height:26px; padding:2px; border:1px solid var(--border,#e2e8f0); border-radius:5px; cursor:pointer; }
.cr-hex    { font-size:10px; font-family:monospace; color:var(--muted,#64748b); min-width:50px; }

/* ── Range ── */
.rr { margin-bottom:12px; }
.rr:last-child { margin-bottom:0; }
.rr-label { font-size:11px; font-weight:700; color:var(--text,#374151); margin-bottom:5px; display:flex; justify-content:space-between; align-items:center; }
.rr-label strong { font-size:12px; color:#1e40af; }
.rr-hint  { display:flex; justify-content:space-between; font-size:10px; color:var(--muted,#94a3b8); margin-top:3px; }
input[type="range"] { width:100%; accent-color:#1e40af; }

/* ── Sidebar mini önizleme ── */
.th-sbp {
    border-radius:8px; overflow:hidden; margin-bottom:10px;
    background:linear-gradient(180deg, var(--theme-sidebar-from-manager,#162C4A), var(--theme-sidebar-to-manager,#1E3D6B));
    padding:12px 10px;
}
.th-sbp-logo  { color:var(--theme-sidebar-text,#e2e8f0); font-weight:800; font-size:12px; margin-bottom:10px; display:flex; align-items:center; gap:7px; }
.th-sbp-link  { padding:6px 8px; border-radius:5px; font-size:10px; color:var(--theme-sidebar-text,#e2e8f0); margin-bottom:2px; opacity:.6; display:flex; align-items:center; gap:6px; }
.th-sbp-link.on { background:rgba(255,255,255,.15); opacity:1; font-weight:700; }

/* ── Önizleme kartı — form içinde tam genişlik ── */
.th-preview { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; overflow:hidden; margin-top:12px; }
.th-prev-bar { display:flex; align-items:center; justify-content:space-between; padding:9px 14px; border-bottom:1px solid var(--border,#e2e8f0); background:var(--subtle,#f8fafc); }
.th-prev-lbl { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.06em; }

.prev-shell  { display:flex; min-height:180px; }
.prev-sidebar {
    background:linear-gradient(180deg,var(--theme-sidebar-from-manager,#162C4A),var(--theme-sidebar-to-manager,#1E3D6B));
    padding:12px 10px; min-width:130px;
}
.prev-sb-logo   { color:var(--theme-sidebar-text,#e2e8f0); font-weight:800; font-size:11px; margin-bottom:12px; }
.prev-sb-active { background:rgba(255,255,255,.16); border-radius:5px; padding:5px 7px; color:var(--theme-sidebar-text,#fff); font-size:9px; margin-bottom:2px; font-weight:700; }
.prev-sb-item   { padding:5px 7px; color:var(--theme-sidebar-text,#e2e8f0); font-size:9px; margin-bottom:2px; opacity:.55; }
.prev-main  { flex:1; background:var(--theme-bg,#F0F4FA); padding:10px; display:flex; flex-direction:column; gap:7px; min-width:0; }
.prev-card  { background:var(--theme-surface,#fff); border:1px solid var(--theme-line,#E2E8F0); border-radius:var(--theme-radius,12px); padding:9px; }
.prev-kpi   { font-size:18px; font-weight:900; color:var(--theme-brand-primary,#2563EB); }
.prev-lbl   { font-size:8px; color:var(--theme-muted,#64748B); text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
.prev-bar-bg   { height:3px; border-radius:999px; background:var(--theme-line,#E2E8F0); overflow:hidden; margin-top:5px; }
.prev-bar-fill { width:65%; height:100%; background:var(--theme-brand-primary,#2563EB); border-radius:999px; }
.prev-btn   { display:inline-flex; padding:3px 9px; border-radius:var(--theme-radius,8px); font-size:9px; font-weight:600; background:var(--theme-brand-primary,#2563EB); color:#fff; }
.pbadge     { display:inline-block; font-size:8px; font-weight:600; padding:2px 5px; border-radius:var(--theme-radius,8px); }
.pbadge-ok     { background:color-mix(in srgb,var(--theme-ok,#10B981) 14%,white); color:color-mix(in srgb,var(--theme-ok,#10B981) 72%,black); }
.pbadge-warn   { background:color-mix(in srgb,var(--theme-warn,#F59E0B) 14%,white); color:color-mix(in srgb,var(--theme-warn,#F59E0B) 72%,black); }
.pbadge-danger { background:color-mix(in srgb,var(--theme-danger,#EF4444) 14%,white); color:color-mix(in srgb,var(--theme-danger,#EF4444) 72%,black); }
.pbadge-info   { background:color-mix(in srgb,var(--theme-info,#2563EB) 14%,white); color:color-mix(in srgb,var(--theme-info,#2563EB) 72%,black); }

.th-typo-prev { padding:10px 14px; border-top:1px solid var(--border,#e2e8f0); background:var(--subtle,#f8fafc); }

/* ── 3 sütunlu renk grubu ── */
.th-3col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-top:12px; }
@media(max-width:1120px){ .th-3col { grid-template-columns:1fr 1fr; } }
@media(max-width:700px) { .th-3col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

@if(!$tableReady)
<div style="padding:10px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fca5a5;margin-bottom:12px;">
    <div style="font-size:var(--tx-sm);font-weight:700;color:#991b1b;">⚠ Ayar tablosu bulunamadı</div>
    <div style="font-size:var(--tx-xs);color:#7f1d1d;margin-top:2px;"><code>php artisan migrate</code> komutunu çalıştırın.</div>
</div>
@endif
@if(session('status'))
<div style="padding:9px 14px;border-radius:8px;background:#16a34a;color:#fff;margin-bottom:12px;font-size:var(--tx-sm);font-weight:600;">✓ {{ session('status') }}</div>
@endif

<div class="page-header">
    <div>
        <h1 style="margin:0">🎨 Tema Yönetimi</h1>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">Tüm portallarda geçerli — anlık canlı önizleme ile</div>
    </div>
</div>

<div class="th-wrap">

    {{-- ══ SOL: FORM ══ --}}
    <form method="POST" action="{{ route('manager.theme.update') }}" id="theme-form">
        @csrf

        {{-- ── Satır 1: Hazır Tema + Tipografi + Marka ── --}}
        <div class="th-grid">

            <div class="th-card th-span2">
                <div class="th-card-head">🗂 Hazır Tema</div>
                <div class="th-card-body">
                    <div class="th-presets">
                        <button type="button" class="th-chip" data-preset="mentorde"><span class="th-dot" style="background:#1e40af"></span>MentorDE</button>
                        <button type="button" class="th-chip" data-preset="violet"><span class="th-dot" style="background:#7c3aed"></span>Mor</button>
                        <button type="button" class="th-chip" data-preset="emerald"><span class="th-dot" style="background:#059669"></span>Yeşil</button>
                        <button type="button" class="th-chip" data-preset="rose"><span class="th-dot" style="background:#e11d48"></span>Kırmızı</button>
                        <button type="button" class="th-chip" data-preset="slate"><span class="th-dot" style="background:#475569"></span>Gri</button>
                        <button type="button" class="th-chip" data-preset="midnight"><span class="th-dot" style="background:#0a0a0f"></span>Midnight</button>
                    </div>
                </div>
            </div>

            <div class="th-card">
                <div class="th-card-head">🔤 Tipografi</div>
                <div class="th-card-body">
                    <div class="rr">
                        <div class="rr-label">Font Ailesi</div>
                        <select id="font_family" name="font_family"
                                style="width:100%;border:1px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 10px;font-size:var(--tx-xs);background:var(--surface,#fff);color:var(--text,#374151);">
                            @foreach(\App\Support\PortalTheme::fontFamilyOptions() as $key => $label)
                            <option value="{{ $key }}" {{ $theme['font_family'] === $key ? 'selected' : '' }}
                                    data-stack="{{ match($key) {
                                        'inter'    => 'Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
                                        'roboto'   => 'Roboto,-apple-system,"Helvetica Neue",Arial,sans-serif',
                                        'opensans' => '"Open Sans",-apple-system,"Helvetica Neue",sans-serif',
                                        default    => '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif',
                                    } }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rr" style="margin-top:12px;">
                        <div class="rr-label">Yazı Boyutu <strong id="font-size-label">{{ $theme['font_size'] }}px</strong></div>
                        <input id="font_size" name="font_size" type="range" min="12" max="16" step="1" value="{{ (int)$theme['font_size'] }}">
                        <div class="rr-hint"><span>12 Kompakt</span><span>14 Standart</span><span>16 Büyük</span></div>
                    </div>
                </div>
            </div>

            <div class="th-card">
                <div class="th-card-head">🎯 Marka &amp; Butonlar</div>
                <div class="th-card-body">
                    <div class="cr">
                        <span class="cr-swatch" id="swatch-brand_primary" style="background:{{ $theme['brand_primary'] }}"></span>
                        <label class="cr-label" for="brand_primary">Birincil Renk<small>Buton, link, aktif öğe</small></label>
                        <div class="cr-pick">
                            <input id="brand_primary" name="brand_primary" type="color" value="{{ $theme['brand_primary'] }}"
                                   data-var="--theme-brand-primary" data-swatch="swatch-brand_primary" class="theme-color-input">
                            <span class="cr-hex" id="hex-brand_primary">{{ $theme['brand_primary'] }}</span>
                        </div>
                    </div>
                    <div class="rr" style="margin-top:12px;">
                        <div class="rr-label">Köşe Yarıçapı <strong id="radius-label">{{ $theme['radius'] }}px</strong></div>
                        <input id="radius" name="radius" type="range" min="4" max="20" step="1" value="{{ (int)$theme['radius'] }}">
                        <div class="rr-hint"><span>4 Kare</span><span>12 Normal</span><span>20 Oval</span></div>
                    </div>
                </div>
            </div>

        </div>{{-- /th-grid --}}

        {{-- ── Satır 2: Renk Paleti (3 eşit sütun) ── --}}
        <div class="th-3col">

            <div class="th-card">
                <div class="th-card-head">◧ Sidebar Metin Rengi</div>
                <div class="th-card-body">
                    <div style="font-size:10px;color:var(--muted,#64748b);background:var(--subtle,#f8fafc);border-radius:6px;padding:6px 8px;margin-bottom:10px;line-height:1.5;">
                        Sidebar arkaplan rengi portal aksanından otomatik türetilir. Sadece metin/ikon rengi ayarlanabilir.
                    </div>
                    <div class="cr">
                        <span class="cr-swatch" id="swatch-sidebar_text" style="background:{{ $theme['sidebar_text'] }}"></span>
                        <label class="cr-label" for="sidebar_text">Sidebar Metin<small>Sidebar içi yazılar ve ikonlar</small></label>
                        <div class="cr-pick">
                            <input id="sidebar_text" name="sidebar_text" type="color"
                                   value="{{ $theme['sidebar_text'] }}" data-var="--theme-sidebar-text"
                                   data-swatch="swatch-sidebar_text" class="theme-color-input">
                            <span class="cr-hex" id="hex-sidebar_text">{{ $theme['sidebar_text'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="th-card">
                <div class="th-card-head">🖥 Sayfa &amp; İçerik Alanı</div>
                <div class="th-card-body">
                    @php $pageColors = [
                        'bg'      => ['Sayfa Arka Planı',  '--theme-bg',     'İçerik alanı zemini'],
                        'surface' => ['Kart / Panel',      '--theme-surface', 'Kart ve form alanları'],
                        'text'    => ['Ana Metin',          '--theme-text',   'Başlık ve birincil metin'],
                        'muted'   => ['Yardımcı Metin',     '--theme-muted',  'İkincil açıklamalar'],
                        'line'    => ['Çizgi / Border',     '--theme-line',   'Kart kenarı, ayırıcı'],
                    ]; @endphp
                    @foreach($pageColors as $field => [$lbl, $var, $hint])
                    <div class="cr">
                        <span class="cr-swatch" id="swatch-{{ $field }}" style="background:{{ $theme[$field] }}"></span>
                        <label class="cr-label" for="{{ $field }}">{{ $lbl }}<small>{{ $hint }}</small></label>
                        <div class="cr-pick">
                            <input id="{{ $field }}" name="{{ $field }}" type="color"
                                   value="{{ $theme[$field] }}" data-var="{{ $var }}"
                                   data-swatch="swatch-{{ $field }}" class="theme-color-input">
                            <span class="cr-hex" id="hex-{{ $field }}">{{ $theme[$field] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="th-card">
                <div class="th-card-head">🚦 Durum Renkleri &amp; Badge</div>
                <div class="th-card-body">
                    <div style="display:flex;gap:5px;flex-wrap:wrap;padding:5px 8px;background:var(--subtle,#f8fafc);border-radius:6px;margin-bottom:10px;">
                        <span class="pbadge pbadge-ok">Onaylandı</span>
                        <span class="pbadge pbadge-warn">Bekliyor</span>
                        <span class="pbadge pbadge-info">Bilgi</span>
                        <span class="pbadge pbadge-danger">Reddedildi</span>
                    </div>
                    @php $statusColors = [
                        'ok'     => ['Başarı / Onay',    '--theme-ok',     'Onaylandı, tamamlandı'],
                        'warn'   => ['Uyarı / Bekliyor', '--theme-warn',   'İncelemede, bekliyor'],
                        'info'   => ['Bilgi',            '--theme-info',   'Genel bilgi mesajları'],
                        'danger' => ['Hata / Kritik',    '--theme-danger', 'Reddedildi, kritik'],
                    ]; @endphp
                    @foreach($statusColors as $field => [$lbl, $var, $hint])
                    <div class="cr">
                        <span class="cr-swatch" id="swatch-{{ $field }}" style="background:{{ $theme[$field] }}"></span>
                        <label class="cr-label" for="{{ $field }}">{{ $lbl }}<small>{{ $hint }}</small></label>
                        <div class="cr-pick">
                            <input id="{{ $field }}" name="{{ $field }}" type="color"
                                   value="{{ $theme[$field] }}" data-var="{{ $var }}"
                                   data-swatch="swatch-{{ $field }}" class="theme-color-input">
                            <span class="cr-hex" id="hex-{{ $field }}">{{ $theme[$field] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>{{-- /th-3col --}}

        {{-- ── Satır 3: Portal Bazlı Tipografi (mini kart grid) ── --}}
        <div class="th-card" style="margin-top:12px;">
            <div class="th-card-head">🖊 Portal Bazlı Yazı Boyutu <span style="font-weight:400;font-size:var(--tx-xs);color:var(--muted,#64748b);margin-left:4px;">— Font ailesi globalden gelir; boyut portala özel</span></div>
            <div class="th-card-body">
                @php
                $portalDefs = [
                    'student'   => ['🎓', 'Öğrenci'],
                    'guest'     => ['👤', 'Guest (Aday)'],
                    'senior'    => ['🧑‍🏫', 'Senior / Danışman'],
                    'dealer'    => ['🤝', 'Bayi'],
                    'manager'   => ['🏢', 'Manager'],
                    'marketing' => ['📣', 'Marketing Admin'],
                ];
                @endphp
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                @foreach($portalDefs as $p => [$icon, $label])
                @php $fsKey = "font_size_{$p}"; $base = (int)($theme[$fsKey] ?? 14); @endphp
                <div style="border:1px solid var(--border,#e2e8f0);border-radius:8px;padding:10px 12px;background:var(--subtle,#f8fafc);">
                    <div style="font-size:11px;font-weight:800;color:var(--text,#0f172a);margin-bottom:9px;">{{ $icon }} {{ $label }}</div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                        <span style="font-size:10px;color:var(--muted,#64748b);">Boyut</span>
                        <strong id="pf_lbl_{{ $p }}" style="font-size:10px;color:#1e40af;">{{ $base }}px</strong>
                    </div>
                    <input type="range" name="{{ $fsKey }}" id="pf_fs_{{ $p }}"
                           min="12" max="36" step="1" value="{{ $base }}"
                           data-portal="{{ $p }}"
                           style="width:100%;accent-color:#1e40af;margin-bottom:7px;">
                    <div id="pf_prev_{{ $p }}"
                         style="padding:4px 8px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:5px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;line-height:1.5;font-size:{{ min($base,14) }}px;color:var(--text,#0f172a);">
                        Aa Bb Cc — 0123
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- ── Satır 4: Portal Bazlı Renk Kombinasyonları ── --}}
        <div class="th-card" style="margin-top:12px;">
            <div class="th-card-head">🎨 Portal Bazlı Renk Kombinasyonları <span style="font-weight:400;font-size:var(--tx-xs);color:var(--muted,#64748b);margin-left:4px;">— Accent ve sidebar renkleri; hero banner accent'ten otomatik</span></div>
            <div class="th-card-body">
                @php
                $portalColorDefs = [
                    'student'   => ['🎓','Öğrenci',  'accent_student'],
                    'guest'     => ['👤','Guest',    'accent_guest'],
                    'senior'    => ['🧑‍🏫','Senior',  'accent_senior'],
                    'dealer'    => ['🤝','Bayi',     'accent_dealer'],
                    'manager'   => ['🏢','Manager',  'accent_manager'],
                    'marketing' => ['📣','Marketing','accent_marketing'],
                ];
                @endphp
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                @foreach($portalColorDefs as $p => [$icon,$label,$ak])
                @php
                    $accentVal       = $theme[$ak] ?? '#2563eb';
                    $darknessPct     = (int) ($theme["hero_darkness_{$p}"] ?? 22);
                    $darknessRatio   = $darknessPct / 100;
                    $heroFromPreview = $darknessRatio > 0 ? \App\Support\PortalTheme::hexMix($accentVal, '#000000', $darknessRatio) : $accentVal;
                    $sideFromPreview = \App\Support\PortalTheme::hexMix($accentVal, '#000000', 0.30);
                    $sideToPreview   = \App\Support\PortalTheme::hexMix($accentVal, '#000000', 0.45);
                    $heroPreview     = "linear-gradient(135deg,{$heroFromPreview},{$accentVal})";
                    $sidePreview     = "linear-gradient(180deg,{$sideFromPreview},{$sideToPreview})";
                @endphp
                <div style="border:1px solid var(--border,#e2e8f0);border-radius:10px;overflow:hidden;background:var(--surface,#fff);">
                    {{-- Header: hero + sidebar önizleme --}}
                    <div style="height:36px;background:{{ $heroPreview }};display:flex;align-items:center;padding:0 10px;gap:7px;" id="hero-prev-{{ $p }}">
                        <span style="font-size:14px;">{{ $icon }}</span>
                        <span style="font-size:10px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.4);">{{ $label }}</span>
                        <div style="margin-left:auto;width:28px;height:16px;border-radius:4px;background:{{ $sidePreview }};border:1.5px solid rgba(255,255,255,.4);" id="side-prev-{{ $p }}"></div>
                    </div>
                    <div style="padding:10px 12px;">
                        <div class="cr" style="padding:5px 0;">
                            <span class="cr-swatch" id="swatch-{{ $ak }}" style="background:{{ $accentVal }}"></span>
                            <label class="cr-label" for="{{ $ak }}">Portal Rengi<small>Hero · Sidebar · Buton hepsi türetilir</small></label>
                            <div class="cr-pick">
                                <input id="{{ $ak }}" name="{{ $ak }}" type="color" value="{{ $accentVal }}"
                                       data-var="--theme-accent-{{ $p }}" data-swatch="swatch-{{ $ak }}"
                                       class="theme-color-input portal-color-input" data-portal="{{ $p }}" data-role="accent">
                                <span class="cr-hex" id="hex-{{ $ak }}">{{ $accentVal }}</span>
                            </div>
                        </div>
                        {{-- Hero Gradient Koyuluk --}}
                        <div style="margin-top:8px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <label style="font-size:11px;color:var(--muted,#64748b);" for="hero_darkness_{{ $p }}">
                                    🌑 Gradyan Koyuluk
                                </label>
                                <span style="font-size:11px;font-weight:600;color:var(--text,#1e293b);" id="hd-val-{{ $p }}">{{ $darknessPct }}%</span>
                            </div>
                            <input type="range" id="hero_darkness_{{ $p }}" name="hero_darkness_{{ $p }}"
                                   min="0" max="80" step="1" value="{{ $darknessPct }}"
                                   style="width:100%;accent-color:{{ $accentVal }};"
                                   class="portal-darkness-input" data-portal="{{ $p }}">
                            <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--muted,#64748b);margin-top:2px;">
                                <span>Düz renk</span><span>Koyu gradient</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- ── Satır 5: Canlı Önizleme (tam genişlik kart) ── --}}
        <div class="th-preview">
            <div class="th-prev-bar">
                <div class="th-prev-lbl">👁 Canlı Önizleme</div>
                <span class="badge ok" style="font-size:var(--tx-xs);">Anlık</span>
            </div>
            <div class="prev-shell">
                <div class="prev-sidebar">
                    <div class="prev-sb-logo">MentorDE</div>
                    <div class="prev-sb-active">📊 Dashboard</div>
                    <div class="prev-sb-item">👥 Öğrenciler</div>
                    <div class="prev-sb-item">📄 Belgeler</div>
                    <div class="prev-sb-item">💬 Mesajlar</div>
                    <div class="prev-sb-item">⚙ Ayarlar</div>
                </div>
                <div class="prev-main">
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Öğrenci</div>
                            <div class="prev-kpi">142</div>
                            <div class="prev-bar-bg"><div class="prev-bar-fill"></div></div>
                        </div>
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Onaylı</div>
                            <div class="prev-kpi" style="color:var(--theme-ok,#10B981);">38</div>
                            <div class="prev-bar-bg"><div style="width:27%;height:100%;background:var(--theme-ok,#10B981);border-radius:999px;"></div></div>
                        </div>
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Bekleyen</div>
                            <div class="prev-kpi" style="color:var(--theme-warn,#F59E0B);">12</div>
                            <div class="prev-bar-bg"><div style="width:9%;height:100%;background:var(--theme-warn,#F59E0B);border-radius:999px;"></div></div>
                        </div>
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Dealer</div>
                            <div class="prev-kpi" style="color:var(--theme-info,#2563EB);">24</div>
                            <div class="prev-bar-bg"><div style="width:17%;height:100%;background:var(--theme-info,#2563EB);border-radius:999px;"></div></div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="prev-card">
                            <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-bottom:6px;">
                                <span class="prev-btn">Kaydet</span>
                                <span style="font-size:var(--tx-xs);padding:3px 7px;border-radius:var(--theme-radius,8px);border:1px solid var(--theme-line,#e2e8f0);color:var(--theme-text,#0f172a);background:var(--theme-surface,#fff);">İptal</span>
                            </div>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <span class="pbadge pbadge-ok">Onaylandı</span>
                                <span class="pbadge pbadge-warn">Bekliyor</span>
                                <span class="pbadge pbadge-danger">Reddedildi</span>
                                <span class="pbadge pbadge-info">Bilgi</span>
                            </div>
                        </div>
                        <div class="prev-card">
                            <div style="font-size:var(--tx-xs);color:var(--theme-text,#1E293B);font-weight:600;margin-bottom:2px;">Ahmet Yılmaz</div>
                            <div style="font-size:var(--tx-xs);color:var(--theme-muted,#64748B);margin-bottom:6px;">Almanya · Münih · Devam ediyor</div>
                            <div class="prev-bar-bg"><div class="prev-bar-fill" style="width:72%;"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="th-typo-prev">
                <div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:2px;">Başlık — AaBbCc 0123</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:12px;flex-wrap:wrap;">
                    <span>Font: <em id="preview-font-label">{{ \App\Support\PortalTheme::fontFamilyOptions()[$theme['font_family']] ?? $theme['font_family'] }}</em></span>
                    <span>Boyut: <em id="preview-size-label">{{ $theme['font_size'] }}px</em></span>
                    <span>Radius: <em id="preview-radius-label">{{ $theme['radius'] }}px</em></span>
                </div>
            </div>
            <div style="padding:8px 14px;border-top:1px solid var(--border,#e2e8f0);background:var(--subtle,#f8fafc);font-size:var(--tx-xs);color:var(--muted,#64748b);">
                🌐 <strong>Student · Guest · Senior · Dealer · Manager · Marketing</strong>
            </div>
        </div>

        {{-- KAYDET --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:12px;">
            <button class="btn primary" type="submit">💾 Tema Kaydet</button>
            <button class="btn" type="button" id="reset-defaults">↩ Varsayılana Dön</button>
        </div>
    </form>

    {{-- ── Marka & Logo ── --}}
    <form method="POST" action="{{ route('manager.theme.brand') }}" style="margin-top:16px;">
        @csrf
        <div class="th-card">
            <div class="th-card-head">🏷 Marka Adı &amp; Logo</div>
            <div class="th-card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--muted,#64748b);display:block;margin-bottom:4px;">Marka Adı</label>
                        <input type="text" name="brand_name" value="{{ $brand['name'] }}"
                               style="width:100%;box-sizing:border-box;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--surface,#fff);color:var(--text,#0f172a);"
                               placeholder="MentorDE">
                        <div style="font-size:10px;color:var(--muted,#94a3b8);margin-top:4px;">Başvuru formu sol panelinde görünür</div>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--muted,#64748b);display:block;margin-bottom:4px;">Vurgulanan Kısım <span style="font-weight:400;">(sarı renk)</span></label>
                        <input type="text" name="brand_accent" value="{{ $brand['accent'] }}"
                               style="width:100%;box-sizing:border-box;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--surface,#fff);color:var(--text,#0f172a);"
                               placeholder="DE">
                        <div style="font-size:10px;color:var(--muted,#94a3b8);margin-top:4px;">Boş bırakılırsa vurgu rengi uygulanmaz</div>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:11px;font-weight:700;color:var(--muted,#64748b);display:block;margin-bottom:4px;">Logo URL <span style="font-weight:400;">(isteğe bağlı)</span></label>
                        <input type="url" name="brand_logo_url" value="{{ $brand['logo_url'] }}"
                               style="width:100%;box-sizing:border-box;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--surface,#fff);color:var(--text,#0f172a);"
                               placeholder="https://...logo.png">
                        <div style="font-size:10px;color:var(--muted,#94a3b8);margin-top:4px;">Dolu ise metin adı yerine logo gösterilir. Beyaz/transparan PNG önerilir.</div>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:var(--muted,#64748b);display:block;margin-bottom:4px;">Logo Yüksekliği (px)</label>
                        <input type="number" name="brand_logo_height" value="{{ $brand['logo_height'] }}" min="20" max="120"
                               style="width:100%;box-sizing:border-box;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--surface,#fff);color:var(--text,#0f172a);">
                    </div>
                    @if($brand['logo_url'])
                    <div style="display:flex;align-items:center;justify-content:center;background:#1e293b;border-radius:8px;padding:12px;">
                        <img src="{{ $brand['logo_url'] }}" alt="Logo önizleme"
                             style="height:{{ $brand['logo_height'] }}px;width:auto;filter:brightness(0) invert(1);max-width:160px;">
                    </div>
                    @endif
                </div>
                <div style="margin-top:12px;">
                    <button class="btn primary" type="submit">💾 Marka Kaydet</button>
                </div>
            </div>
        </div>
    </form>

    {{-- eski panel kaldırıldı, önizleme artık form içinde --}}
    <div style="display:none">
            <div class="th-prev-bar">
                <div class="th-prev-lbl">Canlı Önizleme</div>
                <span class="badge ok" style="font-size:var(--tx-xs);">Anlık</span>
            </div>

            <div class="prev-shell">
                <div class="prev-sidebar">
                    <div class="prev-sb-logo">MentorDE</div>
                    <div class="prev-sb-active">📊 Dashboard</div>
                    <div class="prev-sb-item">👥 Öğrenciler</div>
                    <div class="prev-sb-item">📄 Belgeler</div>
                    <div class="prev-sb-item">💬 Mesajlar</div>
                    <div class="prev-sb-item">⚙ Ayarlar</div>
                </div>
                <div class="prev-main">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Öğrenci</div>
                            <div class="prev-kpi">142</div>
                            <div class="prev-bar-bg"><div class="prev-bar-fill"></div></div>
                        </div>
                        <div class="prev-card" style="text-align:center;">
                            <div class="prev-lbl">Onaylı</div>
                            <div class="prev-kpi" style="color:var(--theme-ok,#10B981);">38</div>
                            <div class="prev-bar-bg"><div style="width:27%;height:100%;background:var(--theme-ok,#10B981);border-radius:999px;"></div></div>
                        </div>
                    </div>
                    <div class="prev-card">
                        <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-bottom:6px;">
                            <span class="prev-btn">Kaydet</span>
                            <span style="font-size:var(--tx-xs);padding:3px 7px;border-radius:var(--theme-radius,8px);border:1px solid var(--theme-line,#e2e8f0);color:var(--theme-text,#0f172a);background:var(--theme-surface,#fff);">İptal</span>
                        </div>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <span class="pbadge pbadge-ok">Onaylandı</span>
                            <span class="pbadge pbadge-warn">Bekliyor</span>
                            <span class="pbadge pbadge-danger">Reddedildi</span>
                            <span class="pbadge pbadge-info">Bilgi</span>
                        </div>
                    </div>
                    <div class="prev-card">
                        <div style="font-size:var(--tx-xs);color:var(--theme-text,#1E293B);font-weight:600;margin-bottom:2px;">Ahmet Yılmaz</div>
                        <div style="font-size:var(--tx-xs);color:var(--theme-muted,#64748B);">Almanya · Münih · Devam ediyor</div>
                    </div>
                </div>
            </div>

            <div class="th-typo-prev">
                <div style="font-size:var(--tx-sm);font-weight:700;margin-bottom:2px;">Başlık — AaBbCc 0123</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.6;margin-bottom:5px;">Bu satır seçilen font ve boyutu gösterir.</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);display:flex;gap:10px;flex-wrap:wrap;">
                    <span>Font: <em id="preview-font-label">{{ \App\Support\PortalTheme::fontFamilyOptions()[$theme['font_family']] ?? $theme['font_family'] }}</em></span>
                    <span>Boyut: <em id="preview-size-label">{{ $theme['font_size'] }}px</em></span>
                    <span>Radius: <em id="preview-radius-label">{{ $theme['radius'] }}px</em></span>
                </div>
            </div>

            <div style="padding:8px 14px;border-top:1px solid var(--border,#e2e8f0);background:var(--subtle,#f8fafc);font-size:var(--tx-xs);color:var(--muted,#64748b);">
                🌐 <strong>Student · Guest · Senior · Dealer · Manager · Marketing</strong>
            </div>
    </div>

</div>{{-- /th-wrap --}}

<script nonce="{{ $cspNonce ?? '' }}">
window.__themeData = { defaults: @json(\App\Support\PortalTheme::defaults()) };

var __presets = {
    mentorde: { brand_primary:'#1e40af', brand_secondary:'#0f172a', brand_secondary_end:'#1e3a8a', sidebar_text:'#e2e8f0', bg:'#f1f5f9', surface:'#ffffff', text:'#0f172a', muted:'#64748b', line:'#e2e8f0', ok:'#16a34a', warn:'#d97706', info:'#2563eb', danger:'#dc2626' },
    violet:   { brand_primary:'#7c3aed', brand_secondary:'#2e1065', brand_secondary_end:'#4c1d95', sidebar_text:'#ede9fe', bg:'#f5f3ff', surface:'#ffffff', text:'#1e1b4b', muted:'#6b7280', line:'#ede9fe', ok:'#059669', warn:'#d97706', info:'#2563eb', danger:'#dc2626' },
    emerald:  { brand_primary:'#059669', brand_secondary:'#022c22', brand_secondary_end:'#064e3b', sidebar_text:'#d1fae5', bg:'#f0fdf4', surface:'#ffffff', text:'#022c22', muted:'#6b7280', line:'#d1fae5', ok:'#16a34a', warn:'#d97706', info:'#2563eb', danger:'#dc2626' },
    rose:     { brand_primary:'#e11d48', brand_secondary:'#4c0519', brand_secondary_end:'#881337', sidebar_text:'#fecdd3', bg:'#fff1f2', surface:'#ffffff', text:'#1f0507', muted:'#6b7280', line:'#fecdd3', ok:'#059669', warn:'#d97706', info:'#2563eb', danger:'#dc2626' },
    slate:    { brand_primary:'#475569', brand_secondary:'#0f172a', brand_secondary_end:'#1e293b', sidebar_text:'#cbd5e1', bg:'#f8fafc', surface:'#ffffff', text:'#0f172a', muted:'#64748b', line:'#e2e8f0', ok:'#16a34a', warn:'#d97706', info:'#2563eb', danger:'#dc2626' },
    midnight: { brand_primary:'#6366f1', brand_secondary:'#0a0a0f', brand_secondary_end:'#13131f', sidebar_text:'#c7d2fe', bg:'#0f0f1a', surface:'#1a1a2e', text:'#e2e8f0', muted:'#94a3b8', line:'#2d2d4e', ok:'#34d399', warn:'#fbbf24', info:'#60a5fa', danger:'#f87171' },
};

function applyPreset(name) {
    var p = __presets[name]; if (!p) return;
    Object.keys(p).forEach(function(k) {
        var el = document.getElementById(k);
        if (el) { el.value = p[k]; el.dispatchEvent(new Event('input')); }
    });
}
</script>
<script defer src="{{ Vite::asset('resources/js/manager-theme.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
/* Per-portal font preview sync
 * html { font-size } üzerinden ölçekleme:
 * --tx-xs(11p) --tx-sm(13p) --tx-base(15p) --tx-lg(18p) --tx-xl(22p)
 * hepsi orantılı değişir — başlık/içerik/etiket ayrı ayrı kontrol gerekmez.
 */
/* Hex renk karıştırma — PHP hexMix'in JS eşleniği */
function hexMixJS(hex, base, ratio) {
    function toRgb(h) { h = h.replace('#',''); return [parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)]; }
    var hc = toRgb(hex), bc = toRgb(base);
    return '#' + [0,1,2].map(function(i){ return Math.round(hc[i]*ratio + bc[i]*(1-ratio)).toString(16).padStart(2,'0'); }).join('');
}
function pfSync(p) {
    var fsEl  = document.getElementById('pf_fs_' + p);
    var lblEl = document.getElementById('pf_lbl_' + p);
    var prvEl = document.getElementById('pf_prev_' + p);
    if (!fsEl) return;
    var sz = fsEl.value + 'px';
    if (lblEl) lblEl.textContent = sz;
    if (prvEl) prvEl.style.fontSize = Math.min(parseInt(fsEl.value), 14) + 'px';
    document.documentElement.style.setProperty('--theme-font-size-' + p, sz);
}
/* Koyuluk slider önizlemesi */
function updateHeroPrev(p) {
    var acEl  = document.getElementById('accent_' + p);
    var hdEl  = document.getElementById('hero_darkness_' + p);
    var heroEl = document.getElementById('hero-prev-' + p);
    if (!acEl || !hdEl || !heroEl) return;
    var accent   = acEl.value;
    var ratio    = parseInt(hdEl.value) / 100;
    var heroFrom = ratio > 0 ? hexMixJS(accent, '#000000', ratio) : accent;
    heroEl.style.background = 'linear-gradient(135deg,' + heroFrom + ',' + accent + ')';
    document.documentElement.style.setProperty('--theme-hero-from-' + p, heroFrom);
}
/* Portal renk mini-önizleme güncelle; hero + sidebar accent'ten türetilir */
function portalColorSync(p) {
    var acEl   = document.getElementById('accent_' + p);
    var hdEl   = document.getElementById('hero_darkness_' + p);
    var heroEl = document.getElementById('hero-prev-' + p);
    var sideEl = document.getElementById('side-prev-' + p);
    if (!acEl) return;
    var accent      = acEl.value;
    var ratio       = hdEl ? parseInt(hdEl.value) / 100 : 0.22;
    var heroFrom    = ratio > 0 ? hexMixJS(accent, '#000000', ratio) : accent;
    var sidebarFrom = hexMixJS(accent, '#000000', 0.30);
    var sidebarTo   = hexMixJS(accent, '#000000', 0.45);
    if (heroEl) heroEl.style.background = 'linear-gradient(135deg,' + heroFrom + ',' + accent + ')';
    if (sideEl) sideEl.style.background = 'linear-gradient(180deg,' + sidebarFrom + ',' + sidebarTo + ')';
    var root = document.documentElement;
    root.style.setProperty('--theme-accent-'       + p, accent);
    root.style.setProperty('--theme-hero-from-'    + p, heroFrom);
    root.style.setProperty('--theme-hero-to-'      + p, accent);
    root.style.setProperty('--theme-sidebar-from-' + p, sidebarFrom);
    root.style.setProperty('--theme-sidebar-to-'   + p, sidebarTo);
}
document.querySelectorAll('.portal-color-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
        portalColorSync(this.dataset.portal);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Preset butonları
    document.querySelectorAll('.th-chip[data-preset]').forEach(function(btn) {
        btn.addEventListener('click', function() { applyPreset(this.dataset.preset); });
    });

    // Font boyutu slider'ları (data-portal ile tanımlı)
    document.querySelectorAll('input[type="range"][data-portal]:not(.portal-darkness-input)').forEach(function(el) {
        el.addEventListener('input',  function() { pfSync(this.dataset.portal); });
        el.addEventListener('change', function() { pfSync(this.dataset.portal); });
    });

    // Hero koyuluk slider'ları
    document.querySelectorAll('.portal-darkness-input').forEach(function(el) {
        el.addEventListener('input', function() {
            var lbl = document.getElementById('hd-val-' + this.dataset.portal);
            if (lbl) lbl.textContent = this.value + '%';
            updateHeroPrev(this.dataset.portal);
        });
    });

    @foreach(array_keys(['student'=>1,'guest'=>1,'senior'=>1,'dealer'=>1,'manager'=>1,'marketing'=>1]) as $p)
    pfSync('{{ $p }}');
    portalColorSync('{{ $p }}');
    @endforeach
});

/* Hex label update & sidebar preview sync */
document.querySelectorAll('.theme-color-input').forEach(function(inp) {
    function sync() {
        var hexEl = document.getElementById('hex-' + inp.name);
        if (hexEl) hexEl.textContent = inp.value;
        var sp = document.getElementById('sp-preview');
        if (sp) {
            if (inp.name === 'brand_secondary')     sp.style.setProperty('--theme-brand-secondary', inp.value);
            if (inp.name === 'brand_secondary_end') sp.style.setProperty('--theme-brand-secondary-end', inp.value);
            if (inp.name === 'sidebar_text')        sp.style.setProperty('--theme-sidebar-text', inp.value);
        }
        var sw = inp.dataset.swatch ? document.getElementById(inp.dataset.swatch) : null;
        if (sw) sw.style.background = inp.value;
    }
    inp.addEventListener('input', sync);
    sync();
});

var __portals = ['student','guest','senior','dealer','manager','marketing'];
</script>
@endsection
