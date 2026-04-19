@extends('manager.layouts.app')
@section('title', 'Geri Bildirim Analitik')
@section('page_title', 'Geri Bildirim Analitik')

@php
    $typeOptions = [
        'all'       => 'Tüm Türler',
        'general'   => 'Genel',
        'process'   => 'Süreç',
        'senior'    => 'Danışman',
        'portal'    => 'Portal',
        'nps'       => 'NPS',
    ];
    // NPS segment renkleri
    $npsColor = $nps >= 30 ? '#16a34a' : ($nps >= 0 ? '#f59e0b' : '#dc2626');
@endphp

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;">📊 Geri Bildirim Analitik</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Aday Öğrenci + Öğrenci geri bildirim KPI'ları</div>
    </div>
    <a class="btn" href="{{ route('manager.feedback-analytics.export', request()->query()) }}"
       style="background:#16a34a;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;">
        ⬇ CSV Dışa Aktar
    </a>
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
            <option value="all"     @selected($filters['source']==='all')>Hepsi (Aday Öğrenci + Öğrenci)</option>
            <option value="guest"   @selected($filters['source']==='guest')>Aday Öğrenci</option>
            <option value="student" @selected($filters['source']==='student')>Öğrenci</option>
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">TÜR</label>
        <select name="type" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
            @foreach($typeOptions as $k => $v)
            <option value="{{ $k }}" @selected($filters['type']===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">SÜREÇ ADIMI</label>
        <select name="step" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
            <option value="all" @selected($filters['step']==='all')>Tümü</option>
            @foreach($stepLabels as $k => $v)
            <option value="{{ $k }}" @selected($filters['step']===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn" type="submit" style="padding:8px 16px;">🔍 Uygula</button>
    <a href="{{ route('manager.feedback-analytics') }}" class="u-muted" style="font-size:12px;text-decoration:none;margin-left:6px;">Sıfırla</a>
</form>

{{-- KPI Cards --}}
<div class="grid5" style="display:grid;grid-template-columns:repeat(5, 1fr);gap:12px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">TOPLAM SUBMISSION</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ number_format($total, 0, ',', '.') }}</div>
        <div class="u-muted" style="font-size:11px;">Seçilen aralıkta</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">ORT. MEMNUNİYET</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $avgRating >= 4 ? '#16a34a' : ($avgRating >= 3 ? '#f59e0b' : '#dc2626') }};">
            ⭐ {{ number_format((float)$avgRating, 2, ',', '') }}
        </div>
        <div class="u-muted" style="font-size:11px;">1-5 yıldız ortalaması</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">NPS SCORE</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $npsColor }};">{{ $nps }}</div>
        <div class="u-muted" style="font-size:11px;">{{ $npsDen }} NPS cevabı</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">PROMOTER / PASİF / ELEŞTİR.</div>
        <div style="font-size:16px;font-weight:700;margin-top:6px;">
            <span style="color:#16a34a;">{{ $promoters }}</span> /
            <span style="color:#f59e0b;">{{ $passives }}</span> /
            <span style="color:#dc2626;">{{ $detractors }}</span>
        </div>
        <div class="u-muted" style="font-size:11px;">NPS segmentleri</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">YORUM SAYISI</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ $commentsCount }}</div>
        <div class="u-muted" style="font-size:11px;">Metin içerikli feedback</div>
    </div>
</div>

{{-- Trend + Rating Distribution --}}
<div class="grid2" style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">📈 Günlük Trend (30 gün)</div>
        <div style="position:relative;height:220px;width:100%;max-height:220px;overflow:hidden;">
            <canvas id="trendChart" style="max-height:220px!important;"></canvas>
        </div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">⭐ Puan Dağılımı</div>
        @php $maxRd = max(array_values($ratingDist) ?: [1]); @endphp
        @foreach([5, 4, 3, 2, 1] as $r)
        @php
            $cnt = $ratingDist[$r] ?? 0;
            $pct = $maxRd > 0 ? round($cnt / $maxRd * 100) : 0;
            $pctTotal = $total > 0 ? round($cnt / $total * 100, 1) : 0;
            $rcolor = $r >= 4 ? '#16a34a' : ($r >= 3 ? '#f59e0b' : '#dc2626');
        @endphp
        <div style="margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                <span>{{ str_repeat('⭐', $r) }}</span>
                <span class="u-muted">{{ $cnt }} ({{ $pctTotal }}%)</span>
            </div>
            <div style="height:8px;background:rgba(0,0,0,.06);border-radius:4px;overflow:hidden;">
                <div style="width:{{ $pct }}%;height:100%;background:{{ $rcolor }};border-radius:4px;transition:width .3s;"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Type + Step breakdown --}}
<div class="grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">🏷️ Feedback Türüne Göre</div>
        @forelse($byType as $type => $data)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.06);">
            <div>
                <div style="font-weight:600;text-transform:capitalize;">{{ $typeOptions[$type] ?? $type }}</div>
                <div class="u-muted" style="font-size:11px;">{{ $data['count'] }} submission</div>
            </div>
            <div style="text-align:right;min-width:120px;">
                @if($data['avgRating'] > 0)
                <div style="font-weight:700;color:{{ $data['avgRating'] >= 4 ? '#16a34a' : ($data['avgRating'] >= 3 ? '#f59e0b' : '#dc2626') }};">
                    ⭐ {{ number_format($data['avgRating'], 2, ',', '') }}
                </div>
                @endif
                @if($data['avgNps'] > 0)
                <div class="u-muted" style="font-size:11px;">NPS ort: {{ number_format($data['avgNps'], 1, ',', '') }}</div>
                @endif
                @if($data['avgRating'] <= 0 && $data['avgNps'] <= 0)
                <div class="u-muted" style="font-size:12px;">— puan yok</div>
                @endif
            </div>
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;">Veri yok.</div>
        @endforelse
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">🧭 Süreç Adımına Göre</div>
        @forelse($byStep as $stepKey => $data)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.06);">
            <div>
                <div style="font-weight:600;">{{ $data['label'] }}</div>
                <div class="u-muted" style="font-size:11px;">{{ $data['count'] }} submission</div>
            </div>
            <div style="text-align:right;min-width:120px;">
                @if($data['avgRating'] > 0)
                <div style="font-weight:700;color:{{ $data['avgRating'] >= 4 ? '#16a34a' : ($data['avgRating'] >= 3 ? '#f59e0b' : '#dc2626') }};">
                    ⭐ {{ number_format($data['avgRating'], 2, ',', '') }}
                </div>
                @endif
                @if(!empty($data['avgNps']) && $data['avgNps'] > 0)
                <div class="u-muted" style="font-size:11px;">NPS ort: {{ number_format($data['avgNps'], 1, ',', '') }}</div>
                @endif
                @if($data['avgRating'] <= 0 && (empty($data['avgNps']) || $data['avgNps'] <= 0))
                <div class="u-muted" style="font-size:12px;">— puan yok</div>
                @endif
            </div>
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;">Süreç adımı verisi yok.</div>
        @endforelse
    </div>
</div>

{{-- Improvement Priorities --}}
@if($improvementAreas->isNotEmpty())
<div class="card" style="margin-bottom:20px;padding:16px;border-left:4px solid #f59e0b;">
    <div class="card-title" style="font-weight:700;margin-bottom:8px;">⚠️ Öncelikli İyileştirme Alanları</div>
    <div class="u-muted" style="font-size:12px;margin-bottom:10px;">En düşük ortalama puanlı süreç adımları (min 2 submission):</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @foreach($improvementAreas as $stepKey => $data)
        <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:10px 14px;">
            <div style="font-weight:700;">{{ $data['label'] }}</div>
            <div style="color:#dc2626;font-weight:700;">⭐ {{ number_format($data['avgRating'], 2, ',', '') }}</div>
            <div class="u-muted" style="font-size:11px;">{{ $data['count'] }} yanıt</div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Recent Comments --}}
<div class="card" style="padding:16px;">
    <div class="card-title" style="font-weight:700;margin-bottom:12px;">💬 Son Yorumlar ({{ $recentComments->count() }})</div>
    @forelse($recentComments as $f)
    <div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="display:flex;justify-content:space-between;gap:10px;font-size:11px;color:var(--u-muted);margin-bottom:4px;">
            <div>
                <span style="background:{{ ($f->_source ?? '') === 'guest' ? '#dbeafe' : '#dcfce7' }};color:{{ ($f->_source ?? '') === 'guest' ? '#1e40af' : '#166534' }};padding:2px 6px;border-radius:4px;font-weight:600;">
                    {{ ($f->_source ?? '') === 'guest' ? 'Aday' : 'Öğrenci' }}
                </span>
                <strong style="margin-left:6px;">{{ $f->_owner ?? '' }}</strong>
                @if($f->feedback_type)
                <span class="u-muted">· {{ $typeOptions[$f->feedback_type] ?? $f->feedback_type }}</span>
                @endif
                @if($f->process_step)
                <span class="u-muted">· {{ $stepLabels[$f->process_step] ?? $f->process_step }}</span>
                @endif
            </div>
            <div>
                @if($f->rating)<span>⭐ {{ $f->rating }}</span>@endif
                @if($f->nps_score !== null && $f->nps_score >= 0)<span class="u-muted"> · NPS {{ $f->nps_score }}</span>@endif
                · {{ optional($f->created_at)->format('d.m.Y H:i') }}
            </div>
        </div>
        <div style="font-size:13px;line-height:1.5;color:var(--u-text);">{{ $f->comment }}</div>
    </div>
    @empty
    <div class="u-muted" style="padding:12px;text-align:center;">Bu filtrelerle yorum yok.</div>
    @endforelse
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    const trendData = @json($trendDays->values());
    const ctx = document.getElementById('trendChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.label),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Submission',
                        data: trendData.map(d => d.count),
                        backgroundColor: 'rgba(59, 130, 246, .5)',
                        borderRadius: 4,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Ort. Puan',
                        data: trendData.map(d => d.avgRating || null),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,.1)',
                        tension: .3,
                        yAxisID: 'y1',
                        spanGaps: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, title: { display: true, text: 'Submission' } },
                    y1: { position: 'right', min: 0, max: 5, title: { display: true, text: 'Ort. Puan (1-5)' }, grid: { drawOnChartArea: false } },
                },
                plugins: { legend: { position: 'top' } },
            },
        });
    }
</script>

<style>
@media (max-width: 900px) {
    .grid5 { grid-template-columns: repeat(2, 1fr) !important; }
    .grid2 { grid-template-columns: 1fr !important; }
}
</style>
@endsection
