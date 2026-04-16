@extends('manager.layouts.app')
@section('title', 'Dönüşüm Hunisi')
@section('page_title', 'Dönüşüm Hunisi')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;">🎯 Dönüşüm Hunisi (Lead → Öğrenci → Ödeme)</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Bu aralıkta oluşturulan lead'lerin şu ana kadar ilerlediği aşamalar</div>
    </div>
</div>

<form method="GET" class="card" style="margin-bottom:20px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;padding:14px;">
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BAŞLANGIÇ</label>
        <input type="date" name="start_date" value="{{ $filters['start_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BİTİŞ</label>
        <input type="date" name="end_date" value="{{ $filters['end_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">KAYNAK</label>
        <select name="source" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
            <option value="all" @selected($filters['source']==='all')>Tüm Kaynaklar</option>
            @foreach($sourceOptions as $src)
            <option value="{{ $src }}" @selected($filters['source']===$src)>{{ ucfirst($src) }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn" type="submit" style="padding:8px 16px;">🔍 Uygula</button>
    <a href="{{ route('manager.conversion-funnel') }}" class="u-muted" style="font-size:12px;text-decoration:none;margin-left:6px;">Sıfırla</a>
</form>

{{-- KPI Cards --}}
<div class="grid4" style="display:grid;grid-template-columns:repeat(4, 1fr);gap:12px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">TOPLAM LEAD</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ number_format($totalLeads, 0, ',', '.') }}</div>
        <div class="u-muted" style="font-size:11px;">Seçilen aralık</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">GENEL DÖNÜŞÜM</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $overallConv >= 20 ? '#16a34a' : ($overallConv >= 10 ? '#f59e0b' : '#dc2626') }};">%{{ $overallConv }}</div>
        <div class="u-muted" style="font-size:11px;">Lead → Öğrenci</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">ORT. DÖNÜŞÜM SÜRESİ</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ $avgDaysToConvert }}</div>
        <div class="u-muted" style="font-size:11px;">gün (lead → öğrenci)</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">KAZANILAN GELİR</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:#0891b2;">€ {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        <div class="u-muted" style="font-size:11px;">Bu cohort'tan</div>
    </div>
</div>

{{-- Main Funnel Visualization --}}
<div class="card" style="padding:20px;margin-bottom:20px;">
    <div class="card-title" style="font-weight:700;margin-bottom:16px;">🔽 Dönüşüm Hunisi</div>
    @php $maxW = 100; @endphp
    @foreach($funnel as $level => $st)
    <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <div style="font-weight:600;font-size:14px;">
                {{ $st['icon'] }} {{ $st['label'] }}
            </div>
            <div style="font-size:13px;">
                <strong>{{ $st['count'] }}</strong>
                <span class="u-muted" style="margin-left:8px;">%{{ $st['pctTotal'] }} toplamdan</span>
                @if($level > 1)
                <span class="u-muted" style="margin-left:8px;">
                    (önceki adımın %{{ $st['pctStep'] }}'i
                    @if($st['pctStep'] < 100 && $funnel[$level-1]['count'] > 0)
                    — {{ $funnel[$level-1]['count'] - $st['count'] }} lead kayıp
                    @endif
                    )
                </span>
                @endif
            </div>
        </div>
        <div style="position:relative;height:32px;background:rgba(0,0,0,.04);border-radius:6px;overflow:hidden;">
            <div style="position:absolute;inset:0;width:{{ $st['pctTotal'] }}%;background:linear-gradient(90deg, {{ $st['color'] }}, {{ $st['color'] }}cc);border-radius:6px;transition:width .4s ease;"></div>
            @if($st['count'] > 0)
            <div style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#fff;font-weight:700;font-size:13px;text-shadow:0 1px 2px rgba(0,0,0,.3);">
                {{ $st['count'] }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Source + Senior Breakdown --}}
<div class="grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">🌐 Kaynak Bazında Dönüşüm</div>
        @forelse($bySource as $src => $data)
        <div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <div style="font-weight:600;text-transform:capitalize;">{{ $src }}</div>
                <div style="font-size:13px;">
                    <strong style="color:{{ $data['convPct'] >= 20 ? '#16a34a' : ($data['convPct'] >= 10 ? '#f59e0b' : '#dc2626') }};">%{{ $data['convPct'] }}</strong>
                    <span class="u-muted" style="margin-left:6px;">{{ $data['converted'] }} / {{ $data['total'] }}</span>
                </div>
            </div>
            <div style="height:6px;background:rgba(0,0,0,.06);border-radius:3px;overflow:hidden;">
                <div style="width:{{ $data['convPct'] }}%;height:100%;background:{{ $data['convPct'] >= 20 ? '#16a34a' : ($data['convPct'] >= 10 ? '#f59e0b' : '#dc2626') }};"></div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;">Veri yok.</div>
        @endforelse
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">👤 Danışman Performansı</div>
        @forelse($bySenior->take(10) as $email => $data)
        <div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-weight:600;font-size:13px;">{{ $email }}</div>
                <div class="u-muted" style="font-size:11px;">{{ $data['total'] }} lead</div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;color:{{ $data['convPct'] >= 20 ? '#16a34a' : ($data['convPct'] >= 10 ? '#f59e0b' : '#dc2626') }};">
                    %{{ $data['convPct'] }}
                </div>
                <div class="u-muted" style="font-size:11px;">{{ $data['converted'] }} dönüşüm</div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;">Atanmış danışman verisi yok.</div>
        @endforelse
    </div>
</div>

{{-- Lost Reasons + Lead Trend --}}
<div class="grid2" style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">❌ Kayıp Sebepleri</div>
        @php $maxLost = $lostReasons->max() ?: 1; @endphp
        @forelse($lostReasons as $reason => $count)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                <span>{{ $reason }}</span>
                <span class="u-muted">{{ $count }}</span>
            </div>
            <div style="height:6px;background:rgba(0,0,0,.06);border-radius:3px;overflow:hidden;">
                <div style="width:{{ round($count/$maxLost*100) }}%;height:100%;background:#dc2626;"></div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;font-size:13px;">Kayıp sebebi kaydedilmemiş lead yok 👏</div>
        @endforelse
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">📅 Yeni Lead Trendi (son 30 gün, filtreden bağımsız)</div>
        <canvas id="leadTrendChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    const leadTrendData = @json($leadTrend->values());
    const ctx = document.getElementById('leadTrendChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: leadTrendData.map(d => d.label),
                datasets: [{
                    label: 'Yeni Lead',
                    data: leadTrendData.map(d => d.count),
                    backgroundColor: 'rgba(59, 130, 246, .6)',
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } },
            },
        });
    }
</script>

<style>
@media (max-width: 900px) {
    .grid4 { grid-template-columns: repeat(2, 1fr) !important; }
    .grid2 { grid-template-columns: 1fr !important; }
}
</style>
@endsection
