@extends('student.layouts.app')

@section('title', 'Servisler')
@section('page_title', 'Servisler')

@push('head')
<style>
/* ── Student Services ── */

/* Paket kartları */
.svc-pkg-grid {
    display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px;
}
@media(max-width:860px){ .svc-pkg-grid { grid-template-columns: 1fr 1fr; } }
@media(max-width:560px){ .svc-pkg-grid { grid-template-columns: 1fr; } }

.svc-pkg-card {
    border-radius: 14px; border: 2px solid var(--u-line);
    background: var(--u-card); overflow: hidden;
    display: flex; flex-direction: column;
    transition: border-color .18s, box-shadow .18s, transform .12s;
    position: relative;
}
.svc-pkg-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); transform: translateY(-3px); }
.svc-pkg-card.svc-selected { border-color: var(--u-ok); }
.svc-pkg-card.svc-popular  { border-color: var(--u-brand); }

.svc-pkg-head {
    padding: 18px 18px 14px;
    background: linear-gradient(135deg, var(--svc-a,#7c3aed), var(--svc-b,#6d28d9));
}
.svc-pkg-name  { font-size: 13px; font-weight: 700; color: rgba(255,255,255,.85); margin-bottom: 6px; }
.svc-pkg-price { font-size: 26px; font-weight: 900; color: #fff; line-height: 1; }

.svc-popular-badge {
    position: absolute; top: 12px; right: 12px;
    background: rgba(255,255,255,.25); backdrop-filter: blur(4px);
    color: #fff; font-size: 10px; font-weight: 700;
    padding: 3px 8px; border-radius: 999px;
}

.svc-pkg-body  { padding: 14px 16px; display: flex; flex-direction: column; flex: 1; gap: 10px; }
.svc-pkg-desc  { font-size: 12px; color: var(--u-muted); line-height: 1.6; flex: 1; }
.svc-pkg-btn {
    display: block; width: 100%; padding: 9px;
    border-radius: 9px; border: 2px solid var(--u-brand);
    background: var(--u-brand); color: #fff;
    font-size: 13px; font-weight: 700; cursor: pointer;
    text-align: center; transition: all .15s; font-family: inherit;
}
.svc-pkg-btn:hover:not(:disabled) { opacity: .88; }
.svc-pkg-btn.svc-active-btn { background: var(--u-ok); border-color: var(--u-ok); cursor: default; }
.svc-pkg-btn:disabled { opacity: 1; }

/* Alt layout */
.svc-bottom { display: grid; grid-template-columns: 1fr 420px; gap: 16px; align-items: start; }
@media(max-width:1100px){ .svc-bottom { grid-template-columns: 1fr 360px; } }
@media(max-width:920px){ .svc-bottom { grid-template-columns: 1fr; } }

/* Akordion */
.svc-acc-wrap { display: flex; flex-direction: column; gap: 8px; }
.svc-acc-card {
    border: 1.5px solid var(--u-line); border-radius: 12px;
    background: var(--u-card); overflow: hidden;
}
.svc-acc-btn {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 13px 16px;
    background: transparent; border: none; cursor: pointer;
    font-family: inherit; text-align: left; transition: background .15s;
}
.svc-acc-btn:hover { background: rgba(0,0,0,.025); }
.svc-acc-btn--active { background: rgba(22,163,74,.05); }
.svc-acc-left  { display: flex; align-items: center; gap: 10px; }
.svc-acc-icon  {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; background: var(--svc-cat-bg, #f1f5f9);
}
.svc-acc-info  { display: flex; flex-direction: column; gap: 2px; }
.svc-acc-title { font-size: 13px; font-weight: 700; color: var(--u-text); }
.svc-acc-sub   { font-size: 11px; color: var(--u-muted); }
.svc-acc-right { display: flex; align-items: center; gap: 8px; }
.svc-acc-arrow { font-size: 11px; color: var(--u-muted); transition: transform .2s; display: inline-block; }
.svc-acc-arrow.open { transform: rotate(90deg); }
.svc-acc-body  { border-top: 1.5px solid var(--u-line); background: var(--u-bg); }

/* Servis grid */
.svc-svc-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; }
@media(max-width:680px){ .svc-svc-grid { grid-template-columns: 1fr; } }

.svc-svc-row {
    background: var(--u-card); border: 1.5px solid var(--u-line);
    border-radius: 10px; padding: 12px 14px;
    display: flex; flex-direction: column; gap: 8px;
    transition: border-color .15s, box-shadow .15s;
}
.svc-svc-row:hover { border-color: var(--u-brand); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
.svc-svc-row--added { border-color: #059669; background: rgba(5,150,105,.03); }
.svc-svc-row--added:hover { border-color: #059669; }

.svc-svc-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.svc-svc-title  { font-size: 13px; font-weight: 700; color: var(--u-text); line-height: 1.3; flex: 1; }
.svc-svc-price  {
    font-size: 13px; font-weight: 800; color: var(--u-brand);
    white-space: nowrap; background: rgba(124,58,237,.08);
    padding: 2px 7px; border-radius: 6px;
}
.svc-svc-row--added .svc-svc-price {
    color: #059669; background: rgba(5,150,105,.10);
}
.svc-svc-desc   { font-size: 11px; color: var(--u-muted); line-height: 1.5; flex: 1; }
.svc-svc-footer { display: flex; justify-content: flex-end; }

/* Sağ panel */
.svc-sidebar { display: flex; flex-direction: column; gap: 12px; position: sticky; top: 16px; }
.svc-panel {
    background: var(--u-card); border: 1.5px solid var(--u-line);
    border-radius: 12px; overflow: hidden;
}
.svc-panel-head {
    padding: 12px 16px; border-bottom: 1.5px solid var(--u-line);
    font-size: 13px; font-weight: 700; color: var(--u-text);
    display: flex; align-items: center; gap: 8px;
}
.svc-panel-body { padding: 14px 16px; }

.svc-summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid var(--u-line); gap: 8px;
}
.svc-summary-row:last-child { border-bottom: none; }
.svc-summary-label { font-size: 12px; color: var(--u-muted); }
.svc-summary-val   { font-size: 13px; font-weight: 700; color: var(--u-brand); }

.svc-remove-btn {
    padding: 3px 8px; border: 1px solid #fca5a5; border-radius: 6px;
    background: #fff1f2; color: #dc2626; font-size: 11px; font-weight: 600;
    cursor: pointer; white-space: nowrap; flex-shrink: 0;
}
.svc-remove-btn:hover { background: #fee2e2; }

.svc-input, .svc-textarea {
    width: 100%; box-sizing: border-box;
    padding: 9px 11px; border: 1.5px solid var(--u-line); border-radius: 8px;
    background: var(--u-bg); color: var(--u-text); font-size: 12px;
    font-family: inherit; transition: border-color .15s; display: block;
    margin-bottom: 8px;
}
.svc-input:focus, .svc-textarea:focus { outline: none; border-color: var(--u-brand); }
.svc-textarea { min-height: 80px; resize: vertical; }
</style>
@endpush

@section('content')
@php
    $svcColors = [['#7c3aed','#6d28d9'],['#6366f1','#4338ca'],['#0891b2','#0e7490']];
    $hasPackage = !empty($selectedPackageTitle);
    $hasExtras  = !empty($selectedExtras);
    $selectedExtraCodes = collect($selectedExtras ?? [])->pluck('code')->map(fn($c)=>(string)$c)->all();
    $extrasTotal = collect($selectedExtras ?? [])->sum(function($x) {
        $f = collect(config('service_packages.extra_services', []))->firstWhere('code', $x['code'] ?? '');
        return (float)($f['price_amount'] ?? 0);
    });
    $popularIdx = 1;
@endphp

{{-- ── Hizmet Paketleri ── --}}
<div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--u-muted);margin-bottom:10px;">
    Hizmet Paketleri <span style="font-weight:400;text-transform:none;letter-spacing:0;">— birini seçin veya ek hizmetlerle devam edin</span>
</div>

<div class="svc-pkg-grid">
    @foreach(($packages ?? collect()) as $p)
    @php
        $ci = $loop->index % 3;
        $isSelected = ($selectedPackageCode ?? '') === $p['code'];
        $isPopular  = $loop->index === $popularIdx;
    @endphp
    <div class="svc-pkg-card {{ $isSelected ? 'svc-selected' : ($isPopular ? 'svc-popular' : '') }}"
         style="--svc-a:{{ $svcColors[$ci][0] }};--svc-b:{{ $svcColors[$ci][1] }};">
        @if($isPopular && !$isSelected)
            <div class="svc-popular-badge">⭐ Popüler</div>
        @endif
        <div class="svc-pkg-head">
            <div class="svc-pkg-name">{{ $p['title'] }}</div>
            <div class="svc-pkg-price">{{ $p['price'] }}</div>
        </div>
        <div class="svc-pkg-body">
            <div class="svc-pkg-desc">{{ $p['includes'] }}</div>
            <form method="post" action="{{ route('student.services.select-package') }}">
                @csrf
                <input type="hidden" name="package_code"  value="{{ $p['code'] }}">
                <input type="hidden" name="package_title" value="{{ $p['title'] }}">
                <input type="hidden" name="package_price" value="{{ $p['price'] }}">
                <button class="svc-pkg-btn {{ $isSelected ? 'svc-active-btn' : '' }}" type="submit" @disabled($isSelected)>
                    {{ $isSelected ? '✓ Aktif Paket' : 'Paketi Seç' }}
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Ek Hizmetler ── --}}
<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
    <div style="flex:1;height:1px;background:var(--u-line);"></div>
    <span style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;">Ek Hizmetler — İsteğe Bağlı</span>
    <div style="flex:1;height:1px;background:var(--u-line);"></div>
</div>

<div class="svc-bottom">

    {{-- SOL: Akordion --}}
    <div>
        <div style="font-size:12px;color:var(--u-muted);margin-bottom:12px;line-height:1.6;">
            Kategoriyi açıp bireysel hizmetleri sepete ekleyin. Paket seçmek zorunda değilsiniz.
        </div>
        <div class="svc-acc-wrap">
            @foreach($serviceCategories ?? [] as $catIdx => $cat)
            @php
                $catHasSelected = collect($cat['services'])->contains(fn($s) => in_array($s['code'], $selectedExtraCodes));
                $catId = 'svc-acc-' . $catIdx;
                $catCount = collect($cat['services'])->filter(fn($s)=>in_array($s['code'],$selectedExtraCodes))->count();
            @endphp
            <div class="svc-acc-card">
                <button type="button" class="svc-acc-btn {{ $catHasSelected ? 'svc-acc-btn--active' : '' }}"
                        onclick="svcToggle('{{ $catId }}', this)">
                    <div class="svc-acc-left">
                        <div class="svc-acc-icon" style="background:{{ $cat['color'] }}18;">
                            {{ $cat['icon'] }}
                        </div>
                        <div class="svc-acc-info">
                            <span class="svc-acc-title">{{ $cat['title'] }}</span>
                            <span class="svc-acc-sub">{{ count($cat['services']) }} hizmet
                                @if($catHasSelected)· <span style="color:#059669;font-weight:700;">{{ $catCount }} seçili</span>@endif
                            </span>
                        </div>
                    </div>
                    <div class="svc-acc-right">
                        @if($catHasSelected)
                            <span class="badge ok" style="font-size:10px;padding:2px 8px;">{{ $catCount }} ✓</span>
                        @endif
                        <span class="svc-acc-arrow">▸</span>
                    </div>
                </button>
                <div class="svc-acc-body" id="{{ $catId }}" style="display:none;">
                    <div class="svc-svc-grid">
                        @foreach($cat['services'] as $svc)
                        @php $added = in_array($svc['code'], $selectedExtraCodes); @endphp
                        <div class="svc-svc-row {{ $added ? 'svc-svc-row--added' : '' }}">
                            <div class="svc-svc-header">
                                <span class="svc-svc-title">{{ $svc['title'] }}</span>
                                <span class="svc-svc-price">{{ $svc['price'] }}</span>
                            </div>
                            @if(!empty($svc['description']))
                                <div class="svc-svc-desc">{{ $svc['description'] }}</div>
                            @endif
                            <div class="svc-svc-footer">
                                @if($added)
                                    <form method="post" action="{{ route('student.services.remove-extra', $svc['code']) }}" style="margin:0;">
                                        @csrf @method('DELETE')
                                        <button class="svc-remove-btn" type="submit">✕ Kaldır</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('student.services.add-extra') }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="extra_code"  value="{{ $svc['code'] }}">
                                        <input type="hidden" name="extra_title" value="{{ $svc['title'] }}">
                                        <button type="submit" class="btn alt"
                                                onclick="this.disabled=true;this.form.submit();"
                                                style="font-size:12px;padding:5px 14px;">+ Ekle</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- SAĞ: Sidebar --}}
    <div class="svc-sidebar">

        {{-- Seçim özeti --}}
        <div class="svc-panel" style="{{ $hasExtras ? 'border-color:#059669;border-width:2px;' : '' }}">
            <div class="svc-panel-head" style="{{ $hasExtras ? 'background:rgba(5,150,105,.06);' : '' }}">
                <span>📋 Seçim Özeti</span>
                @if($hasExtras)
                    <span style="margin-left:auto;font-size:11px;color:#059669;font-weight:700;">+{{ number_format($extrasTotal,0,',','.') }} EUR ek</span>
                @endif
            </div>
            <div class="svc-panel-body">
                @if(!$hasPackage && !$hasExtras)
                    <div style="font-size:12px;color:var(--u-muted);text-align:center;padding:8px 0;">Henüz seçim yapılmadı.</div>
                @else
                    @if($hasPackage)
                    <div class="svc-summary-row">
                        <span class="svc-summary-label">📦 Paket</span>
                        <span class="svc-summary-val">{{ $selectedPackagePrice }}</span>
                    </div>
                    @endif
                    @foreach($selectedExtras as $e)
                    <div class="svc-summary-row">
                        <span style="font-size:12px;color:var(--u-text);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:6px;">{{ $e['title'] ?? '-' }}</span>
                        <form method="post" action="{{ route('student.services.remove-extra', $e['code'] ?? '') }}" style="margin:0;flex-shrink:0;">
                            @csrf @method('DELETE')
                            <button class="svc-remove-btn" type="submit" style="padding:2px 7px;">✕</button>
                        </form>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Hızlı Destek --}}
        <div class="svc-panel">
            <div class="svc-panel-head">🎫 Hızlı Destek</div>
            <div class="svc-panel-body">
                <form method="post" action="{{ route('student.tickets.store') }}">
                    @csrf
                    <input type="hidden" name="department" value="advisory">
                    <input type="hidden" name="priority"   value="normal">
                    <input type="hidden" name="return_to"  value="/student/services">
                    <input class="svc-input" name="subject" value="Servis/Paket desteği" placeholder="Konu">
                    <textarea class="svc-textarea" name="message" placeholder="Sorunuzu yazın..."></textarea>
                    <button class="btn ok" type="submit" style="width:100%;font-size:13px;">Ticket Oluştur →</button>
                </form>
                <div style="font-size:11px;color:var(--u-muted);margin-top:8px;line-height:1.5;">
                    Değişiklikler operations ekibine otomatik iletilir.
                </div>
            </div>
        </div>

        {{-- Nasıl çalışır --}}
        <div class="svc-panel">
            <div class="svc-panel-head">📖 Nasıl Çalışır?</div>
            <div class="svc-panel-body" style="padding:12px 16px;">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach([
                        ['1', 'Üstten bir paket seç (isteğe bağlı).'],
                        ['2', 'Kategorileri açıp bireysel hizmetleri ekle.'],
                        ['3', 'Seçimler sağ panelde listelenir.'],
                        ['4', 'Soru için destek talebi oluştur.'],
                    ] as [$n, $txt])
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <span style="width:22px;height:22px;border-radius:50%;background:var(--u-brand);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $n }}</span>
                        <span style="font-size:12px;color:var(--u-muted);line-height:1.5;padding-top:2px;">{{ $txt }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function svcToggle(id, btn) {
    var body = document.getElementById(id);
    var arrow = btn.querySelector('.svc-acc-arrow');
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    arrow.classList.toggle('open', !open);
}
</script>
<script defer src="{{ Vite::asset('resources/js/student-services.js') }}"></script>
@endsection
