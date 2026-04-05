@extends('senior.layouts.app')

@section('title', 'Servisler')
@section('page_title', 'Servisler')

@push('head')
<style>
/* ── sr-svc-* Senior Services (read-only catalog mirror) ── */


/* Two-column main layout */
.sr-main-cols {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;
}
@media(max-width:960px){ .sr-main-cols { grid-template-columns: 1fr; } }

/* Student card grid inside right col */
.sr-stu-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
@media(max-width:1200px){ .sr-stu-grid { grid-template-columns: 1fr; } }

/* Accordion */
.sr-acc-wrap { display: flex; flex-direction: column; gap: 8px; }
.sr-acc-card {
    border: 2px solid #e2e8f0; border-radius: 12px;
    background: #fff; overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.sr-acc-btn {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 13px 16px;
    background: transparent; border: none; cursor: pointer;
    font-family: inherit; text-align: left; transition: background .15s;
}
.sr-acc-btn:hover { background: rgba(0,0,0,.025); }
.sr-acc-left  { display: flex; align-items: center; gap: 10px; }
.sr-acc-icon  {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.sr-acc-info  { display: flex; flex-direction: column; gap: 2px; }
.sr-acc-title { font-size: 13px; font-weight: 700; color: var(--u-text); }
.sr-acc-sub   { font-size: 11px; color: var(--u-muted); }
.sr-acc-right { display: flex; align-items: center; gap: 8px; }
.sr-acc-arrow { font-size: 11px; color: var(--u-muted); transition: transform .2s; display: inline-block; }
.sr-acc-arrow.open { transform: rotate(90deg); }
.sr-acc-body  { border-top: 1.5px solid var(--u-line); background: var(--u-bg); }

/* Service items grid */
.sr-svc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; }
@media(max-width:680px){ .sr-svc-grid { grid-template-columns: 1fr; } }

.sr-svc-row {
    background: #fff; border: 2px solid #e2e8f0;
    border-radius: 10px; padding: 12px 14px;
    display: flex; flex-direction: column; gap: 6px;
}
.sr-svc-row.has-sel { border-color: #059669; background: rgba(5,150,105,.04); }
.sr-svc-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.sr-svc-title  { font-size: 13px; font-weight: 700; color: var(--u-text); line-height: 1.3; flex: 1; }
.sr-svc-price  {
    font-size: 12px; font-weight: 800; color: var(--u-brand);
    white-space: nowrap; background: rgba(124,58,237,.08);
    padding: 2px 7px; border-radius: 6px;
}
.sr-svc-desc  { font-size: 11px; color: var(--u-muted); line-height: 1.5; }
.sr-svc-cnt   {
    font-size: 11px; font-weight: 700; color: var(--u-muted);
    display: flex; align-items: center; gap: 4px;
}
.sr-svc-cnt.has { color: #059669; }

/* Student cards */
.sr-stu-card {
    background: #fff; border: 2px solid #e2e8f0;
    border-radius: 12px; padding: 14px 16px;
    display: flex; flex-direction: column; gap: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
    transition: border-color .15s, box-shadow .15s;
}
.sr-stu-card:hover { border-color: var(--u-brand); box-shadow: 0 4px 14px rgba(0,0,0,.12); }
.sr-stu-card.has-pkg { border-left: 4px solid var(--u-brand); }
.sr-stu-card-top  { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.sr-stu-name  { font-size: 14px; font-weight: 700; color: var(--u-text); line-height: 1.3; }
.sr-stu-id    { font-size: 11px; color: var(--u-muted); margin-top: 2px; }
.sr-stu-extras-wrap { display: flex; flex-wrap: wrap; gap: 5px; }
.sr-stu-extra-chip {
    font-size: 11px; font-weight: 600; padding: 3px 9px;
    border-radius: 999px; background: rgba(37,99,235,.07);
    color: var(--u-brand); border: 1px solid rgba(37,99,235,.15);
    white-space: nowrap;
}
.sr-stu-noextra { font-size: 11px; color: var(--u-muted); font-style: italic; }

/* Filter bar */
.sr-stu-filter {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px;
}
.sr-stu-filter input, .sr-stu-filter select {
    padding: 7px 10px; border: 2px solid #cbd5e1;
    border-radius: 8px; background: #fff; color: #1e293b;
    font-size: 12px; font-family: inherit;
}
.sr-stu-filter input:focus, .sr-stu-filter select:focus {
    outline: none; border-color: #7c3aed;
}

/* KPI row */
.sr-kpi-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.sr-kpi-chip {
    background: var(--u-card); border: 1.5px solid var(--u-line);
    border-radius: 10px; padding: 10px 16px;
    display: flex; flex-direction: column; gap: 3px; min-width: 100px;
}
.sr-kpi-val  { font-size: 22px; font-weight: 900; color: var(--u-brand); line-height: 1; }
.sr-kpi-lbl  { font-size: 11px; color: var(--u-muted); font-weight: 600; }

.sr-sec-label {
    font-size: 11px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: #64748b; margin-bottom: 10px;
}
</style>
@endpush

@section('content')
@php
    $svcColors   = [['#7c3aed','#6d28d9'],['#6366f1','#4338ca'],['#0891b2','#0e7490']];
    $pkgCounts   = $pkgCounts   ?? collect();
    $extraCounts = $extraCounts ?? [];
    $studentsWithPkg   = ($services ?? collect())->filter(fn($r)=>!empty($r->selected_package_code))->count();
    $studentsWithExtra = ($services ?? collect())->filter(fn($r)=>!empty($r->selected_extra_services))->count();
@endphp

{{-- KPI Bar — tam genislik --}}
<div class="sr-kpi-bar">
    <div class="sr-kpi-chip">
        <span class="sr-kpi-val">{{ $totalStudents ?? 0 }}</span>
        <span class="sr-kpi-lbl">Toplam Ogrenci</span>
    </div>
    <div class="sr-kpi-chip">
        <span class="sr-kpi-val">{{ $studentsWithPkg }}</span>
        <span class="sr-kpi-lbl">Paket Secti</span>
    </div>
    <div class="sr-kpi-chip">
        <span class="sr-kpi-val">{{ $studentsWithExtra }}</span>
        <span class="sr-kpi-lbl">Ek Servis Secti</span>
    </div>
    @foreach($packages ?? [] as $p)
        @php $cnt = (int)($pkgCounts->get($p['code']) ?? 0); @endphp
        @if($cnt > 0)
        <div class="sr-kpi-chip" style="border-color:rgba(124,58,237,.3);">
            <span class="sr-kpi-val" style="font-size:18px;">{{ $cnt }}</span>
            <span class="sr-kpi-lbl">{{ $p['title'] }}</span>
        </div>
        @endif
    @endforeach
</div>

{{-- 2-KOLON ANA LAYOUT --}}
<div class="sr-main-cols">

    {{-- SOL: Paket Ozeti + Ek Hizmetler Akordion --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Paket Ozeti --}}
        <div>
            <div class="sr-sec-label">Hizmet Paketleri</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($packages ?? [] as $p)
                @php
                    $ci  = $loop->index % 3;
                    $cnt = (int)($pkgCounts->get($p['code']) ?? 0);
                @endphp
                <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;
                            border-radius:10px;border:2px solid {{ $cnt>0 ? '#6d28d9' : '#e2e8f0' }};
                            background:{{ $cnt>0 ? 'rgba(109,40,217,.05)' : '#fff' }};
                            box-shadow:0 1px 3px rgba(0,0,0,.06);">
                    <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;
                                background:linear-gradient(135deg,{{ $svcColors[$ci][0] }},{{ $svcColors[$ci][1] }});"></div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;">{{ $p['title'] }}</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $p['includes'] }}</div>
                    </div>
                    <span style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:8px;white-space:nowrap;
                                 background:{{ $cnt>0 ? 'rgba(5,150,105,.1)' : '#f1f5f9' }};
                                 color:{{ $cnt>0 ? '#059669' : '#94a3b8' }};">
                        {{ $cnt>0 ? "✓ {$cnt} ogrenci" : "Secilmedi" }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Ek Hizmetler Akordion --}}
        <div>
            <div class="sr-sec-label">Ek Hizmetler Katalogu</div>
            <div class="sr-acc-wrap">
                @foreach($serviceCategories ?? [] as $catIdx => $cat)
                @php
                    $catSelCount = collect($cat['services'])->sum(fn($s)=>(int)($extraCounts[$s['code']] ?? 0));
                    $catId = 'sr-acc-' . $catIdx;
                @endphp
                <div class="sr-acc-card">
                    <button type="button" class="sr-acc-btn" onclick="srAccToggle('{{ $catId }}', this)">
                        <div class="sr-acc-left">
                            <div class="sr-acc-icon" style="background:{{ $cat['color'] }}18;">{{ $cat['icon'] }}</div>
                            <div class="sr-acc-info">
                                <span class="sr-acc-title">{{ $cat['title'] }}</span>
                                <span class="sr-acc-sub">{{ count($cat['services']) }} hizmet
                                    @if($catSelCount > 0)
                                        &middot; <span style="color:#059669;font-weight:700;">{{ $catSelCount }} secim</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="sr-acc-right">
                            @if($catSelCount > 0)
                                <span class="badge ok" style="font-size:10px;padding:2px 8px;">{{ $catSelCount }}</span>
                            @endif
                            <span class="sr-acc-arrow">&#9658;</span>
                        </div>
                    </button>
                    <div class="sr-acc-body" id="{{ $catId }}" style="display:none;">
                        <div class="sr-svc-grid">
                            @foreach($cat['services'] as $svc)
                            @php $cnt = (int)($extraCounts[$svc['code']] ?? 0); @endphp
                            <div class="sr-svc-row {{ $cnt > 0 ? 'has-sel' : '' }}">
                                <span class="sr-svc-title">{{ $svc['title'] }}</span>
                                @if(!empty($svc['description']))
                                    <div class="sr-svc-desc">{{ $svc['description'] }}</div>
                                @endif
                                <div class="sr-svc-cnt {{ $cnt > 0 ? 'has' : '' }}">
                                    {{ $cnt > 0 ? "✓ {$cnt} ogrenci secti" : "Secilmedi" }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- SAG: Ogrenci Secim Listesi --}}
    <div>
        <div class="sr-sec-label">
            Ogrenci Secim Durumu
            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;">— {{ ($services ?? collect())->count() }} kayit</span>
        </div>

        <form method="GET" action="{{ url('/senior/services') }}" class="sr-stu-filter">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Isim veya ogrenci ID..." style="flex:1;min-width:0;">
            <select name="package" onchange="this.form.submit()">
                <option value="all" @selected(($filters['package'] ?? 'all') === 'all')>Tum Paketler</option>
                @foreach($packages ?? [] as $p)
                    <option value="{{ $p['code'] }}" @selected(($filters['package'] ?? '') === $p['code'])>{{ $p['title'] }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Ara</button>
            @if(($filters['q'] ?? '') !== '' || ($filters['package'] ?? 'all') !== 'all')
                <a class="btn alt" href="{{ url('/senior/services') }}">Temizle</a>
            @endif
        </form>

        @if(($services ?? collect())->isEmpty())
            <div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:13px;
                        background:#fff;border:2px solid #e2e8f0;border-radius:12px;">
                Kayit bulunamadi.
            </div>
        @else
        <div class="sr-stu-grid">
            @foreach($services ?? [] as $row)
            @php
                $name     = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $extras   = is_array($row->selected_extra_services) ? $row->selected_extra_services : [];
                $pkgCode  = $row->selected_package_code ?? '';
                $pkgLabel = $pkgCode ? ($row->selected_package_title ?: $pkgCode) : null;
                $badgeCls = match($pkgCode) { 'pkg_premium'=>'badge warn', 'pkg_plus'=>'badge info', 'pkg_basic'=>'badge ok', default=>'' };
            @endphp
            <div class="sr-stu-card {{ $pkgCode ? 'has-pkg' : '' }}">
                <div class="sr-stu-card-top">
                    <div>
                        <div class="sr-stu-name">{{ $name ?: '-' }}</div>
                        <div class="sr-stu-id">{{ $row->converted_student_id ?? '-' }}</div>
                    </div>
                    @if($pkgLabel)
                        <span class="{{ $badgeCls }}" style="font-size:10px;padding:2px 8px;white-space:nowrap;flex-shrink:0;">{{ $pkgLabel }}</span>
                    @else
                        <span style="font-size:10px;padding:2px 8px;white-space:nowrap;flex-shrink:0;
                                     background:#f1f5f9;color:#94a3b8;border-radius:6px;font-weight:600;">Paket yok</span>
                    @endif
                </div>
                @if(!empty($extras))
                    <div class="sr-stu-extras-wrap">
                        @foreach($extras as $e)
                            <span class="sr-stu-extra-chip">{{ $e['title'] ?? '-' }}</span>
                        @endforeach
                    </div>
                @else
                    <span class="sr-stu-noextra">Ek servis secilmemis</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

<script>
function srAccToggle(id, btn) {
    var body  = document.getElementById(id);
    var arrow = btn.querySelector('.sr-acc-arrow');
    var open  = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    arrow.classList.toggle('open', !open);
}
</script>
@endsection
