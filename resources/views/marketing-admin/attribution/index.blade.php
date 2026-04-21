@extends('marketing-admin.layouts.app')

@section('title', 'Multi-Touch Attribution')

@section('page_subtitle', 'Multi-Touch Attribution — kanal kredisi ve dönüşüm temas analizi')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/attribution') && !request()->is('mktg-admin/attribution/*') ? '' : 'alt' }}" href="/mktg-admin/attribution" style="font-size:var(--tx-xs);padding:6px 12px;">Attribution Raporu</a>
<a class="btn {{ request()->is('mktg-admin/attribution/compare') ? '' : 'alt' }}" href="/mktg-admin/attribution/compare" style="font-size:var(--tx-xs);padding:6px 12px;">Model Karşılaştırma</a>
@endsection

@section('content')

@include('partials.manager-hero', [
    'label' => 'Marketing Attribution',
    'title' => 'Dönüşüm Atıf Analizi',
    'sub'   => 'Multi-touch attribution: bir öğrencinin dönüşümünde hangi reklamlar, linkler ve temas noktaları rol oynadı?',
    'icon'  => '🧭',
    'bg'    => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1400&q=80',
    'tone'  => 'purple',
    'stats' => [],
])

<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.at-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.at-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.at-stat:last-child { border-right:none; }
.at-val   { font-size:22px; font-weight:700; color:var(--u-brand,#1e40af); line-height:1.1; }
.at-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
</style>

<div style="display:grid;gap:12px;">

    {{-- Model + Dönem Filtresi --}}
    <div class="card">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Model:</span>
            @foreach($models as $m => $label)
            <a href="/mktg-admin/attribution?model={{ $m }}&days={{ $days }}"
               class="btn {{ $model === $m ? '' : 'alt' }}" style="padding:4px 12px;font-size:var(--tx-xs);">{{ $label }}</a>
            @endforeach
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-left:8px;">Dönem:</span>
            <a href="?model={{ $model }}&days=30" class="btn {{ $days == 30 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">30 Gün</a>
            <a href="?model={{ $model }}&days=60" class="btn {{ $days == 60 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">60 Gün</a>
            <a href="?model={{ $model }}&days=90" class="btn {{ $days == 90 ? '' : 'alt' }}" style="padding:4px 10px;font-size:var(--tx-xs);">90 Gün</a>
        </div>
    </div>

    {{-- KPI Bar --}}
    @if($avgTouches)
    <div class="at-stats">
        <div class="at-stat">
            <div class="at-val">{{ number_format((float)$avgTouches, 1) }}</div>
            <div class="at-lbl">Ort. Temas Sayısı</div>
        </div>
        <div class="at-stat">
            <div class="at-val">{{ $touchpointsByChannel->sum('total') }}</div>
            <div class="at-lbl">Toplam Touchpoint</div>
        </div>
    </div>
    @endif

    <div class="grid2">

        {{-- Kanal Attribution --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Kanal Kredisi — <span style="color:var(--u-brand,#1e40af);text-transform:none;">{{ $models[$model] }}</span>
            </div>
            @if(empty($channelSummary))
            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);">Henüz dönüşüm verisi yok.</div>
            @else
            <div class="list">
                @foreach($channelSummary as $row)
                @php $pct = $row['share_pct']; @endphp
                <div class="item" style="flex-direction:column;align-items:stretch;gap:4px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-size:var(--tx-sm);font-weight:500;">{{ $row['channel'] }}</span>
                        <span style="font-weight:700;">{{ $row['share_pct'] }}%</span>
                    </div>
                    <div style="background:var(--u-line,#e2e8f0);border-radius:4px;height:6px;overflow:hidden;">
                        <div style="width:{{ $pct }}%;height:100%;background:var(--u-brand,#1e40af);border-radius:4px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Touchpoint Sayıları --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Kanal Temas Sayısı</div>
            <div class="list">
                <div class="item" style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);letter-spacing:.04em;text-transform:uppercase;">
                    <span style="flex:2;">Kanal</span>
                    <span style="width:80px;text-align:right;">Temas</span>
                </div>
                @forelse($touchpointsByChannel as $row)
                <div class="item">
                    <span style="flex:2;">{{ $row->channel }}</span>
                    <span style="width:80px;text-align:right;font-weight:600;">{{ $row->total }}</span>
                </div>
                @empty
                <div class="item" style="color:var(--u-muted,#64748b);">Veri yok.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Attribution Analizi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li><strong>First-Touch:</strong> Dönüşüm kredisini tamamen ilk temasa verir — yeni kullanıcı kaynağını anlamak için kullanılır.</li>
            <li><strong>Last-Touch:</strong> Krediyi son temasa verir — kapatma kanalının performansını gösterir.</li>
            <li><strong>Linear:</strong> Tüm temaslara eşit kredi — dengeli bir genel bakış sunar.</li>
            <li><strong>U-Shape:</strong> İlk ve son temasa %40'ar, ortakilere %20 — hem kazanım hem kapatma önemsendiğinde kullanılır.</li>
            <li>Dönem filtresini değiştirerek mevsimsel farklılıkları karşılaştırın. Model Karşılaştırma sekmesinden tüm modelleri yan yana görün.</li>
        </ol>
    </details>

</div>
@endsection
