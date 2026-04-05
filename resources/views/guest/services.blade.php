@extends('guest.layouts.app')

@section('title', 'Servisler')
@section('page_title', 'Servisler')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── gs-* Guest Services — compact redesign ── */

/* Package cards — horizontal 3-col */
.gs-pkg-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media(max-width:860px){ .gs-pkg-grid { grid-template-columns: 1fr 1fr; } }
@media(max-width:560px){ .gs-pkg-grid { grid-template-columns: 1fr; } }

.gs-pkg-card {
    border-radius: 12px;
    border: 2px solid var(--u-line);
    background: var(--u-card);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: border-color .18s, box-shadow .18s, transform .12s;
    position: relative;
}
.gs-pkg-card:hover { box-shadow: var(--u-shadow-md); transform: translateY(-2px); }
.gs-pkg-card.selected {
    border-color: var(--u-ok);
    box-shadow: 0 0 0 3px rgba(22,163,74,.10);
}
.gs-pkg-card.popular {
    border-color: var(--u-brand);
    box-shadow: 0 0 0 3px rgba(37,99,235,.10);
}

/* Top accent bar */
.gs-pkg-accent {
    height: 4px;
    background: linear-gradient(90deg, var(--pkg-a, #4b8cf7), var(--pkg-b, #2563eb));
}

/* Popular ribbon */
.gs-pkg-ribbon {
    position: absolute;
    top: 14px; right: -10px;
    background: var(--u-brand);
    color: #fff;
    font-size: 10px; font-weight: 700;
    padding: 2px 14px 2px 8px;
    border-radius: 4px 0 0 4px;
    letter-spacing: .3px;
}

.gs-pkg-body { padding: 14px 16px; display: flex; flex-direction: column; flex: 1; gap: 8px; }
.gs-pkg-name { font-size: 14px; font-weight: 700; color: var(--u-text); }
.gs-pkg-price { font-size: 24px; font-weight: 800; color: var(--u-brand); line-height: 1; }
.gs-pkg-period { font-size: 11px; color: var(--u-muted); margin-top: 1px; }
.gs-pkg-desc { color: var(--u-muted); font-size: 12px; line-height: 1.5; flex: 1; }
.gs-pkg-btn {
    margin-top: 6px;
    display: block; width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--u-line);
    background: var(--u-card);
    color: var(--u-text);
    font-size: 13px; font-weight: 600;
    cursor: pointer; text-align: center; transition: all .15s;
    font-family: inherit;
}
.gs-pkg-btn:hover:not(:disabled) { border-color: var(--u-brand); color: var(--u-brand); background: rgba(37,99,235,.05); }
.gs-pkg-btn.selected-btn { background: var(--u-ok); border-color: var(--u-ok); color: #fff; cursor: default; }
.gs-pkg-btn:disabled { opacity: .7; }

/* Extra services */
.gs-extra-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
@media(max-width:740px){ .gs-extra-grid { grid-template-columns: 1fr 1fr; } }
.gs-extra-card {
    border: 1.5px solid var(--u-line);
    border-radius: 10px;
    padding: 10px 12px;
    background: var(--u-card);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    font-family: inherit;
    text-align: left;
    transition: border-color .15s, background .15s;
}
.gs-extra-card:hover { border-color: var(--u-brand); background: rgba(37,99,235,.04); }
.gs-extra-name { font-weight: 600; color: var(--u-text); font-size: 12px; line-height: 1.3; flex: 1; }
.gs-extra-icon {
    width: 22px; height: 22px; flex-shrink: 0;
    border: 1.5px solid #93b8e8; color: #4b7ec8;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; background: #f0f6ff;
    transition: all .15s;
}
.gs-extra-card:hover .gs-extra-icon { border-color: var(--u-brand); color: var(--u-brand); background: #e0edff; }

.gs-extra-item {
    display: flex; justify-content: space-between; align-items: center;
    gap: 8px; padding: 7px 12px;
    border: 1px solid var(--u-line); border-radius: 8px;
    background: var(--u-card); margin-bottom: 6px; font-size: 13px;
}

/* Comparison cards */
.gs-cmp-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    padding: 16px;
}
@media(max-width:700px){ .gs-cmp-grid { grid-template-columns: 1fr; } }

.gs-cmp-card {
    border-radius: 16px;
    border: 2px solid var(--u-line);
    background: var(--u-card);
    display: flex; flex-direction: column;
    overflow: hidden;
    transition: transform .18s, box-shadow .18s;
}
.gs-cmp-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
.gs-cmp-card.popular {
    border-color: var(--u-brand);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--u-brand) 15%, transparent);
}

.gs-cmp-head {
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--u-line);
    text-align: center;
    position: relative;
}
.gs-cmp-card.popular .gs-cmp-head {
    background: linear-gradient(135deg, var(--u-brand, #2563eb) 0%, #7c3aed 100%);
    color: #fff;
}
.gs-cmp-badge {
    position: absolute; top: -1px; right: 14px;
    background: #f59e0b; color: #fff;
    font-size: 10px; font-weight: 800;
    padding: 3px 10px; border-radius: 0 0 8px 8px;
    letter-spacing: .04em; text-transform: uppercase;
}
.gs-cmp-name  { font-size: 16px; font-weight: 900; margin-bottom: 8px; }
.gs-cmp-price { font-size: 28px; font-weight: 900; color: var(--u-brand); line-height: 1; }
.gs-cmp-card.popular .gs-cmp-price { color: #fff; }
.gs-cmp-try   { font-size: 11px; color: var(--u-muted); margin-top: 4px; }
.gs-cmp-card.popular .gs-cmp-try { color: rgba(255,255,255,.7); }
.gs-cmp-desc  { font-size: 11px; color: var(--u-muted); margin-top: 6px; line-height: 1.4; }
.gs-cmp-card.popular .gs-cmp-desc { color: rgba(255,255,255,.75); }

.gs-cmp-rows  { flex: 1; padding: 12px 0; }
.gs-cmp-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 20px; font-size: 12px; gap: 10px;
    border-bottom: 1px solid var(--u-line);
}
.gs-cmp-row:last-child { border-bottom: none; }
.gs-cmp-feat { color: var(--u-muted); font-weight: 600; flex-shrink: 0; }
.gs-cmp-val  { font-weight: 700; color: var(--u-text); text-align: right; }
.gs-cmp-yes  { color: #059669; font-size: 17px; font-weight: 900; }
.gs-cmp-no   { color: var(--u-muted); font-size: 17px; }
.gs-cmp-card.selected { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.15); }

.gs-cmp-foot { padding: 14px 16px; border-top: 1px solid var(--u-line); }

/* ── Akordion ── */
.gs-acc-card { overflow: hidden; }
.gs-acc-head {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    gap: 10px; padding: 11px 16px 11px 14px;
    background: var(--u-card); border: none; cursor: pointer;
    font-family: inherit; text-align: left;
    transition: background .15s;
}
.gs-acc-head:hover { background: rgba(0,0,0,.03); }
.gs-acc-head--active { background: rgba(22,163,74,.04); }
.gs-acc-title { font-size: 13px; font-weight: 700; color: var(--u-text); }
.gs-acc-arrow { font-size: 12px; color: var(--u-muted); transition: transform .2s; }
.gs-acc-arrow.open { transform: rotate(90deg); }
.gs-acc-body { border-top: 1px solid var(--u-line); }

/* ── Hizmet listesi satırları ── */
.gs-svc-list { display: flex; flex-direction: column; gap: 6px; }
.gs-svc-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; padding: 9px 12px;
    border: 1px solid var(--u-line); border-radius: 8px;
    background: var(--u-card); transition: border-color .15s, background .15s;
}
.gs-svc-row:hover { border-color: var(--u-brand); }
.gs-svc-row--added { border-color: var(--u-ok); background: rgba(22,163,74,.04); }
.gs-svc-info { flex: 1; min-width: 0; }
.gs-svc-title { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gs-svc-desc  { font-size: 11px; color: var(--u-muted); margin-top: 2px; line-height: 1.4; }
.gs-svc-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.gs-svc-price { font-size: 12px; font-weight: 700; color: var(--u-brand); white-space: nowrap; }

/* ── Minimalist overrides ── */
.jm-minimalist .gs-pkg-accent { background: var(--u-brand, #111) !important; }
.jm-minimalist .gs-cmp-card.popular { box-shadow: none !important; }
.jm-minimalist .gs-cmp-card.popular .gs-cmp-head {
    background: #e2e5ec !important;
    color: var(--u-text, #1a1a1a) !important;
    border-bottom: 1px solid rgba(0,0,0,.10) !important;
}
.jm-minimalist .gs-cmp-card.popular .gs-cmp-name  { color: var(--u-text, #1a1a1a) !important; }
.jm-minimalist .gs-cmp-card.popular .gs-cmp-price { color: var(--u-brand, #111) !important; }
.jm-minimalist .gs-cmp-card.popular .gs-cmp-try   { color: var(--u-muted, #666) !important; }
.jm-minimalist .gs-cmp-card.popular .gs-cmp-desc  { color: var(--u-muted, #666) !important; }
</style>
@endpush

@section('content')

{{-- ── Header bar ── --}}
<div class="card" style="margin-bottom:14px;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:12px 16px;">
        <div>
            <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">Paket ve Ek Servis Yönetimi</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Paketinizi seçin, ek servisleri ekleyin.</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if($selectedPackageTitle)
                <span class="badge ok" style="font-size:var(--tx-xs);">✓ {{ $selectedPackageTitle }}</span>
            @endif
            <a class="btn alt" style="font-size:var(--tx-xs);padding:6px 14px;" href="{{ route('guest.messages') }}">Danışmana Sor</a>
        </div>
    </div>
</div>

{{-- ── Paketler başlık bandı ── --}}
<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
    <div style="flex:1;height:1px;background:var(--u-line);"></div>
    <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">
        Hizmet Paketleri — Birini Seçin
    </div>
    <div style="flex:1;height:1px;background:var(--u-line);"></div>
</div>

{{-- ── Packages — compact 3-col ── --}}
@php
    $svcColors = [
        ['#4b8cf7','#2563eb'],
        ['#6366f1','#4338ca'],
        ['#0891b2','#0369a1'],
    ];
    $popularIdx = 1; // Plus = orta paket = en popüler
@endphp
<div class="gs-pkg-grid">
    @foreach($packages as $p)
        @php
            $ci = $loop->index % 3;
            $isSelected = $selectedPackageCode === $p['code'];
            $isPopular  = $loop->index === $popularIdx;
        @endphp
        <div class="gs-pkg-card {{ $isSelected ? 'selected' : ($isPopular ? 'popular' : '') }}"
             style="--pkg-a:{{ $svcColors[$ci][0] }};--pkg-b:{{ $svcColors[$ci][1] }};">
            <div class="gs-pkg-accent"></div>
            @if($isPopular && !$isSelected)
                <div class="gs-pkg-ribbon">⭐ Popüler</div>
            @endif
            <div class="gs-pkg-body">
                <div class="gs-pkg-name">{{ $p['title'] }}</div>
                <div>
                    <div class="gs-pkg-price">{{ $p['price'] }}</div>
                    @if(!empty($eurTryRate) && !empty($p['price_amount']))
                        <div class="gs-pkg-period">≈ ₺{{ number_format($p['price_amount'] * $eurTryRate, 0, ',', '.') }}</div>
                    @endif
                </div>
                <div class="gs-pkg-desc">{{ $p['includes'] }}</div>
                <form method="POST" action="{{ route('guest.services.select-package') }}">
                    @csrf
                    <input type="hidden" name="package_code"  value="{{ $p['code'] }}">
                    <input type="hidden" name="package_title" value="{{ $p['title'] }}">
                    <input type="hidden" name="package_price" value="{{ $p['price'] }}">
                    <button class="gs-pkg-btn {{ $isSelected ? 'selected-btn' : '' }}"
                            type="submit" @disabled($isSelected)>
                        {{ $isSelected ? '✓ Seçili Paket' : 'Paketi Seç' }}
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Bottom grid: Extra Services + Quick Support ── --}}
<div class="col3-1" style="margin-bottom:14px;">

    {{-- Ek Servisler + Karşılaştırma (sol 2fr) --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- ── Ek Hizmetler başlık bandı ── --}}
        <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
            <div style="flex:1;height:1px;background:var(--u-line);"></div>
            <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">
                Ek Hizmetler — İsteğe Bağlı
            </div>
            <div style="flex:1;height:1px;background:var(--u-line);"></div>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:-6px;margin-bottom:2px;">
            Paket seçiminizden bağımsız olarak istediğiniz hizmetleri ayrıca ekleyebilirsiniz.
        </div>

        {{-- ── Hizmet Kategorileri ── --}}
        @php $selectedExtraCodes = collect($selectedExtras ?? [])->pluck('code')->map(fn($c)=>(string)$c)->all(); @endphp

        @foreach($serviceCategories ?? [] as $catIdx => $cat)
        @php
            $catHasSelected = collect($cat['services'])->contains(fn($s) => in_array($s['code'], $selectedExtraCodes));
            $catId = 'gs-acc-' . $catIdx;
        @endphp
        <div class="card gs-acc-card" style="margin:0;">
            <button type="button" class="gs-acc-head {{ $catHasSelected ? 'gs-acc-head--active' : '' }}"
                    onclick="gsToggle('{{ $catId }}', this)"
                    style="border-left:3px solid {{ $cat['color'] }};">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span>{{ $cat['icon'] }}</span>
                    <span class="gs-acc-title">{{ $cat['title'] }}</span>
                    @if($catHasSelected)
                        @php $catCount = collect($cat['services'])->filter(fn($s)=>in_array($s['code'],$selectedExtraCodes))->count(); @endphp
                        <span class="badge ok" style="font-size:10px;padding:2px 7px;">{{ $catCount }} seçili</span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:11px;color:var(--u-muted);">{{ count($cat['services']) }} hizmet</span>
                    <span class="gs-acc-arrow">▸</span>
                </div>
            </button>
            <div class="gs-acc-body" id="{{ $catId }}" style="display:none;">
                <div class="gs-svc-list" style="padding:10px 14px;">
                    @foreach($cat['services'] as $svc)
                        @php $added = in_array($svc['code'], $selectedExtraCodes); @endphp
                        <div class="gs-svc-row {{ $added ? 'gs-svc-row--added' : '' }}">
                            <div class="gs-svc-info">
                                <div class="gs-svc-title">{{ $svc['title'] }}</div>
                                @if(!empty($svc['description']))
                                    <div class="gs-svc-desc">{{ $svc['description'] }}</div>
                                @endif
                            </div>
                            <div class="gs-svc-right">
                                <span class="gs-svc-price">{{ $svc['price'] }}</span>
                                @if($added)
                                    <form method="POST" action="{{ route('guest.services.remove-extra', ['extraCode' => $svc['code']]) }}" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn warn" style="font-size:11px;padding:4px 10px;white-space:nowrap;">Kaldır</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('guest.services.add-extra') }}" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="extra_code"  value="{{ $svc['code'] }}">
                                        <input type="hidden" name="extra_title" value="{{ $svc['title'] }}">
                                        <button type="submit" class="btn alt"
                                                onclick="this.disabled=true;this.form.submit();"
                                                style="font-size:11px;padding:4px 12px;white-space:nowrap;">+ Ekle</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        {{-- Seçili hizmetler özeti (tüm ek servisler toplamı) --}}
        @if(!empty($selectedExtras))
        <div class="card" style="margin:0;border-color:var(--u-ok);border-width:2px;">
            <div class="card-head" style="background:rgba(22,163,74,.05);padding:10px 16px;">
                <div class="card-title" style="color:var(--u-ok);font-size:var(--tx-sm);">Seçili Ek Hizmetler</div>
                @php
                    $extrasTotal = collect($selectedExtras)->sum(function($x) {
                        $found = collect(config('service_packages.extra_services', []))->firstWhere('code', $x['code'] ?? '');
                        return (float)($found['price_amount'] ?? 0);
                    });
                @endphp
                <span style="font-size:var(--tx-xs);color:var(--u-ok);font-weight:700;">Toplam: {{ number_format($extrasTotal, 0, ',', '.') }} EUR</span>
            </div>
            <div class="card-body" style="padding:8px 14px;">
                @foreach($selectedExtras as $x)
                <div class="gs-extra-item">
                    <strong style="font-size:var(--tx-sm);">{{ $x['title'] ?? '-' }}</strong>
                    <form method="POST" action="{{ route('guest.services.remove-extra', ['extraCode' => $x['code'] ?? '']) }}" style="margin:0;flex-shrink:0;">
                        @csrf
                        @method('DELETE')
                        <button class="btn warn" style="font-size:11px;padding:3px 10px;">Kaldır</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Comparison Table --}}
        @if(!empty($comparisonTable['packages']))
        <div class="card" style="margin:0;">
            <div class="card-head" style="cursor:pointer;" onclick="var b=document.getElementById('gs-compare-body');var i=document.getElementById('gs-compare-icon');var open=b.style.display!=='none';b.style.display=open?'none':'block';i.textContent=open?'▸':'▾;">
                <div class="card-title">Paket Karşılaştırması</div>
                <div style="display:flex;align-items:center;gap:10px;">
                    @if(!empty($eurTryRate) && $eurTryRate > 0)
                        <span style="font-size:var(--tx-xs);color:var(--u-muted);">1 EUR ≈ {{ number_format($eurTryRate,2) }} TRY</span>
                    @endif
                    <span id="gs-compare-icon" style="font-size:var(--tx-sm);color:var(--u-muted);font-weight:700;">▾</span>
                </div>
            </div>
            <div id="gs-compare-body" style="display:block;">
                <div class="gs-cmp-grid">
                    @foreach($comparisonTable['packages'] as $pkg)
                    @php
                        $isSel    = ($selectedPackageCode === $pkg['code']);
                        $isPopular = (($pkg['support_level'] ?? '') === 'plus');
                        $sl       = $pkg['support_level'] ?? '';
                        $slBadge  = match($sl) { 'premium'=>'ok','plus'=>'info', default=>'' };
                    @endphp
                    <div class="gs-cmp-card {{ $isPopular ? 'popular' : '' }} {{ $isSel ? 'selected' : '' }}">
                        @if($isPopular)
                            <div class="gs-cmp-badge">Popüler</div>
                        @endif
                        <div class="gs-cmp-head">
                            <div class="gs-cmp-name">{{ $pkg['title'] }}</div>
                            <div class="gs-cmp-price">{{ $pkg['price'] }}</div>
                            @if(!empty($eurTryRate) && !empty($pkg['price_amount']))
                                <div class="gs-cmp-try">≈ ₺{{ number_format($pkg['price_amount'] * $eurTryRate, 0, ',', '.') }}</div>
                            @endif
                        </div>
                        <div class="gs-cmp-rows">
                            <div class="gs-cmp-row">
                                <span class="gs-cmp-feat">⏱ Süre</span>
                                <span class="gs-cmp-val">{{ ($pkg['validity_months'] ?? 0) }} ay</span>
                            </div>
                            <div class="gs-cmp-row">
                                <span class="gs-cmp-feat">🛂 Vize Desteği</span>
                                <span class="gs-cmp-val {{ !empty($pkg['includes_visa']) ? 'gs-cmp-yes' : 'gs-cmp-no' }}">
                                    {{ !empty($pkg['includes_visa']) ? '✓' : '–' }}
                                </span>
                            </div>
                            <div class="gs-cmp-row">
                                <span class="gs-cmp-feat">🏠 Konut Desteği</span>
                                <span class="gs-cmp-val {{ !empty($pkg['includes_housing']) ? 'gs-cmp-yes' : 'gs-cmp-no' }}">
                                    {{ !empty($pkg['includes_housing']) ? '✓' : '–' }}
                                </span>
                            </div>
                            <div class="gs-cmp-row">
                                <span class="gs-cmp-feat">🎯 Destek</span>
                                <span class="badge {{ $slBadge }}">{{ ucfirst($sl ?: '-') }}</span>
                            </div>
                        </div>
                        <div class="gs-cmp-foot">
                            <form method="POST" action="{{ route('guest.services.select-package') }}">
                                @csrf
                                <input type="hidden" name="package_code"  value="{{ $pkg['code'] }}">
                                <input type="hidden" name="package_title" value="{{ $pkg['title'] }}">
                                <input type="hidden" name="package_price" value="{{ $pkg['price'] }}">
                                <button class="btn {{ $isSel ? 'ok' : ($isPopular ? '' : 'alt') }}" type="submit"
                                        style="width:100%;" @disabled($isSel)>
                                    {{ $isSel ? '✓ Seçili Paket' : 'Bu Paketi Seç' }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Sağ kolon — grid stretch ile sol kolona eşit yükseklik --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Seçili paket özeti --}}
        <div class="card" style="margin:0;">
            <div class="card-body" style="padding:14px 16px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Seçili Paket</div>
                @if($selectedPackageTitle)
                    <div style="font-size:var(--tx-base);font-weight:800;color:var(--u-text);">{{ $selectedPackageTitle }}</div>
                    <div style="font-size:var(--tx-xl);font-weight:800;color:var(--u-brand);margin-top:4px;">{{ $selectedPackagePrice }}</div>
                    @php $selPriceNum = (float) preg_replace('/[^0-9.]/', '', str_replace('.', '', $selectedPackagePrice ?? '')); @endphp
                    @if(!empty($eurTryRate) && $selPriceNum > 0)
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">≈ ₺{{ number_format($selPriceNum * $eurTryRate, 0, ',', '.') }}</div>
                    @endif
                @else
                    <div style="font-size:var(--tx-sm);color:var(--u-muted);">Henüz paket seçilmedi.</div>
                    <a href="#" onclick="window.scrollTo(0,0);return false;" style="font-size:var(--tx-xs);color:var(--u-brand);font-weight:600;margin-top:6px;display:block;">Yukarıdan seç →</a>
                @endif
            </div>
        </div>

        {{-- Ödeme Talebi --}}
        @if($selectedPackageTitle)
        <div class="card" style="margin:0;border-color:var(--u-brand);border-width:2px;">
            <div class="card-head" style="padding:12px 16px;background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                <div class="card-title" style="font-size:var(--tx-sm);color:var(--u-brand);">💳 Ödeme Talebi Oluştur</div>
            </div>
            <div class="card-body" style="padding:12px 16px;">
                <form method="POST" action="{{ route('guest.services.payment-request') }}">
                    @csrf
                    @error('payment')<div class="badge danger" style="margin-bottom:8px;display:block;">{{ $message }}</div>@enderror
                    <div style="margin-bottom:10px;">
                        <label style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-bottom:3px;">Ödeme Yöntemi</label>
                        <select name="payment_method" style="width:100%;border:1.5px solid var(--u-line);border-radius:8px;padding:7px 10px;font-size:var(--tx-sm);font-family:inherit;background:var(--u-card);color:var(--u-text);" required>
                            <option value="bank_transfer">🏦 Banka Havalesi</option>
                            <option value="credit_card">💳 Kredi Kartı</option>
                        </select>
                    </div>
                    <div style="margin-bottom:10px;">
                        <label style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-bottom:3px;">Not (opsiyonel)</label>
                        <textarea name="notes" rows="2"
                                  style="width:100%;border:1.5px solid var(--u-line);border-radius:8px;padding:7px 10px;font-size:var(--tx-xs);font-family:inherit;resize:none;background:var(--u-card);color:var(--u-text);"
                                  placeholder="Ödeme ile ilgili not..."></textarea>
                    </div>
                    <button class="btn ok" type="submit" style="width:100%;font-size:var(--tx-sm);">
                        Ödeme Talebi Gönder → {{ $selectedPackagePrice }}
                    </button>
                </form>
                <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;text-align:center;">
                    Talebiniz danışmanınıza iletilir. Ödeme bilgileri size ayrıca gönderilecektir.
                </div>
            </div>
        </div>
        @endif

        {{-- Hızlı Destek — flex:1 ile kalan alanı doldurur --}}
        <div class="card" style="margin:0;flex:1;display:flex;flex-direction:column;">
            <div class="card-head" style="padding:12px 16px;">
                <div class="card-title" style="font-size:var(--tx-sm);">Hızlı Destek</div>
            </div>
            <div class="card-body" style="padding:12px 16px;flex:1;display:flex;flex-direction:column;">
                <form method="POST" action="{{ route('guest.tickets.store') }}" style="flex:1;display:flex;flex-direction:column;">
                    @csrf
                    <input type="hidden" name="department" value="advisory">
                    <input type="hidden" name="priority"   value="normal">
                    <input type="hidden" name="return_to"  value="/guest/services">
                    <div style="margin-bottom:8px;">
                        <label style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-bottom:3px;">Konu</label>
                        <input name="subject" value="Servis/Paket desteği"
                               style="width:100%;border:1.5px solid var(--u-line);border-radius:8px;padding:7px 10px;font-size:var(--tx-sm);font-family:inherit;background:var(--u-card);color:var(--u-text);">
                    </div>
                    <div style="flex:1;display:flex;flex-direction:column;margin-bottom:10px;">
                        <label style="font-size:var(--tx-xs);color:var(--u-muted);display:block;margin-bottom:3px;">Mesaj</label>
                        <textarea name="message"
                                  style="flex:1;min-height:120px;width:100%;border:1.5px solid var(--u-line);border-radius:8px;padding:8px 10px;font-size:var(--tx-sm);font-family:inherit;resize:none;background:var(--u-card);color:var(--u-text);"
                                  placeholder="Sorunuzu yazın…"></textarea>
                    </div>
                    <button class="btn ok" type="submit" style="width:100%;font-size:var(--tx-sm);">Ticket Oluştur</button>
                </form>
                <div style="margin-top:8px;font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;">
                    Değişiklikler operations ekibine otomatik iletilir.
                </div>
            </div>
        </div>
    </div>
</div>

<script defer src="{{ Vite::asset('resources/js/guest-services.js') }}"></script>
<script>
function gsToggle(id, btn) {
    var body = document.getElementById(id);
    var arrow = btn.querySelector('.gs-acc-arrow');
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    arrow.classList.toggle('open', !open);
}
</script>
<script>
(function(){
    var _orig = window.__designToggle;
    window.__designToggle = function(d){
        if(_orig) _orig(d);
        setTimeout(function(){ document.documentElement.classList.toggle('jm-minimalist', d==='minimalist'); }, 50);
    };
})();
</script>
@endsection
