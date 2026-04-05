@extends('marketing-admin.layouts.app')

@section('title', 'A/B Test — ' . $abtest->name)

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <div>
        <a href="/mktg-admin/abtests" class="btn alt">← Listele</a>
        <strong style="font-size:var(--tx-base);margin-left:10px;">{{ $abtest->name }}</strong>
    </div>
    <div style="display:flex;gap:8px;">
        @if($abtest->status === 'draft' || $abtest->status === 'paused')
        <form method="POST" action="/mktg-admin/abtests/{{ $abtest->id }}/activate">
            @csrf @method('PUT')
            <button class="btn ok">▶ Başlat</button>
        </form>
        @endif
        @if($significance['significant'] && ($significance['winner'] ?? false))
        <form method="POST" action="/mktg-admin/abtests/{{ $abtest->id }}/apply-winner">
            @csrf @method('POST')
            <button class="btn">🏆 Kazananı Uygula ({{ $significance['winner'] }})</button>
        </form>
        @endif
    </div>
</div>

{{-- KPI Kartları --}}
<div class="kpis">
    <div class="card kpi">
        <div class="label">Durum</div>
        <div class="val" style="font-size:var(--tx-base);">{{ $abtest->status }}</div>
    </div>
    <div class="card kpi">
        <div class="label">Test Türü</div>
        <div class="val" style="font-size:var(--tx-sm);">{{ $abtest->test_type }}</div>
    </div>
    <div class="card kpi">
        <div class="label">Güven Aralığı</div>
        <div class="val">{{ round($abtest->confidence_level * 100) }}%</div>
    </div>
    <div class="card kpi">
        <div class="label">Min. Örnek</div>
        <div class="val">{{ $abtest->min_sample_size }}</div>
    </div>
</div>

{{-- İstatistiksel Anlamlılık --}}
<div class="card" style="margin-top:16px;">
    <div class="label" style="font-weight:600;margin-bottom:10px;">İstatistiksel Analiz</div>
    @if($significance['significant'] ?? false)
    <div style="padding:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;margin-bottom:12px;">
        <strong style="color:#15803d;">✅ İstatistiksel olarak anlamlı!</strong>
        <span style="margin-left:8px;">Kazanan: <strong>{{ $significance['winner'] }}</strong>
        — p-değeri: {{ $significance['p_value'] ?? '—' }}</span>
    </div>
    @elseif(isset($significance['reason']))
    <div style="padding:12px;background:#fef9ec;border:1px solid #fcd34d;border-radius:8px;margin-bottom:12px;">
        <strong style="color:#92400e;">⏳ Henüz yeterli veri yok.</strong>
        @if($significance['reason'] === 'insufficient_sample')
        <span style="margin-left:8px;">A: {{ $significance['a_sample'] ?? 0 }} / B: {{ $significance['b_sample'] ?? 0 }} (gerekli: {{ $significance['required'] ?? 0 }})</span>
        @endif
    </div>
    @endif
</div>

{{-- Varyant Performansı --}}
<div class="card" style="margin-top:12px;">
    <div class="label" style="font-weight:600;margin-bottom:12px;">Varyant Performansı</div>
    <div class="list">
        <div class="item" style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);border-bottom:1px solid var(--u-line);">
            <span style="width:60px;">Varyant</span>
            <span style="flex:3;">Config</span>
            <span style="width:80px;text-align:right;">Gösterim</span>
            <span style="width:80px;text-align:right;">Dönüşüm</span>
            <span style="width:80px;text-align:right;">Oran</span>
        </div>
        @forelse($abtest->variants as $variant)
        <div class="item">
            <span style="width:60px;">
                <span class="badge {{ $abtest->winner_variant === $variant->variant_code ? 'ok' : 'info' }}" style="font-weight:700;font-size:var(--tx-sm);">
                    {{ $variant->variant_code }}
                    @if($abtest->winner_variant === $variant->variant_code) 🏆 @endif
                </span>
            </span>
            <span style="flex:3;font-size:var(--tx-xs);color:var(--u-muted);">
                {{ json_encode($variant->variant_config) }}
            </span>
            <span style="width:80px;text-align:right;">{{ $variant->impressions }}</span>
            <span style="width:80px;text-align:right;color:var(--u-ok);">{{ $variant->conversions }}</span>
            <span style="width:80px;text-align:right;font-weight:700;">
                {{ $variant->conversion_rate }}%
            </span>
        </div>
        @empty
        <div class="item muted">Varyant yok.</div>
        @endforelse
    </div>

<details class="card" style="margin-top:0;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu — A/B Test Detayı</h3>
        <span class="det-chev">▼</span>
    </summary>
    <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;padding-top:12px;">
        <li>Her varyant için başlık ve içerik farklılaştır — tek değişken ilkesine uyu</li>
        <li>Test yeterli trafik aldıktan sonra (min. 100 gösterim/varyant) kazananı belirle</li>
        <li>Conversion oranı en yüksek varyant → aktif kampanyanın ana versiyonu yap</li>
        <li>İstatistiksel anlamlılık için en az 2 hafta test süresi önerilir</li>
    </ul>
</details>

</div>

@endsection
