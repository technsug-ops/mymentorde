@extends('platform.layouts.app')

@section('title', ($company->name ?? 'Şirket') . ' — Sağlık Detayı')

@push('styles')
<style>
    .ch-hero { display:flex; align-items:center; gap: 18px; background: linear-gradient(135deg, rgba(126,88,191,.15), rgba(126,88,191,.04)); border:1px solid var(--plat-border); border-radius: 14px; padding: 22px; margin-bottom: 22px; }
    .ch-hero-score { width:96px; height:96px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:34px; font-weight:800; color:#fff; flex-shrink:0; }
    .ch-hero-score.green  { background: linear-gradient(135deg, #4ade80, #22c55e); }
    .ch-hero-score.yellow { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .ch-hero-score.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
    .ch-hero-score.red    { background: linear-gradient(135deg, #f87171, #ef4444); }
    .ch-hero-info { flex:1; min-width:0; }
    .ch-hero-name { font-size: 22px; font-weight: 800; color: #fff; display:flex; align-items:center; gap:10px; margin: 0 0 6px; }
    .ch-hero-meta { font-size: 13px; color: var(--plat-muted); display:flex; gap: 14px; flex-wrap: wrap; }
    .ch-hero-meta strong { color: var(--plat-text); }

    .ch-grid-2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; margin-bottom: 18px; }
    @media (max-width: 1024px) { .ch-grid-2 { grid-template-columns: 1fr; } }

    .ch-breakdown-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--plat-border); }
    .ch-breakdown-row:last-child { border-bottom: 0; }
    .ch-breakdown-icon { width:34px; height:34px; border-radius:8px; background: var(--plat-panel-2); display:flex; align-items:center; justify-content:center; color: var(--plat-accent-2); flex-shrink:0; }
    .ch-breakdown-info { flex: 1; min-width: 0; }
    .ch-breakdown-name { font-size: 13px; font-weight: 700; color: #fff; }
    .ch-breakdown-weight { font-size: 11px; color: var(--plat-muted); }
    .ch-breakdown-bar { width: 100%; height: 10px; background: var(--plat-panel-2); border-radius: 999px; overflow: hidden; margin-top: 6px; }
    .ch-breakdown-fill { height: 100%; border-radius: 999px; transition: width .3s; }
    .ch-breakdown-score { font-size: 18px; font-weight: 800; color: #fff; min-width: 48px; text-align: right; }

    .ch-flag-card { background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-left: 3px solid #f87171; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; }
    .ch-flag-title { font-size: 13px; font-weight: 700; color: #fff; display:flex; align-items:center; gap:8px; margin-bottom: 4px; }
    .ch-flag-fix { font-size: 12px; color: var(--plat-muted); }

    .ch-timeline { list-style: none; padding: 0; margin: 0; }
    .ch-timeline li { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--plat-border); }
    .ch-timeline li:last-child { border-bottom: 0; }
    .ch-timeline-icon { width: 30px; height: 30px; background: var(--plat-panel-2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--plat-accent-2); flex-shrink: 0; }
    .ch-timeline-body { flex: 1; min-width: 0; }
    .ch-timeline-label { font-size: 13px; color: var(--plat-text); }
    .ch-timeline-time { font-size: 11px; color: var(--plat-muted); }
</style>
@endpush

@section('content')
    <div class="plat-page-header">
        <div>
            <a href="{{ route('platform.customer-health') }}" style="font-size:12px; color: var(--plat-muted);">
                <x-icon name="arrow-left" size="13" /> Tüm Sağlık Skorları
            </a>
            <h1 class="plat-page-title" style="margin-top:6px;">
                <x-icon name="heart" size="22" style="vertical-align:-3px;color:var(--plat-accent-2);" />
                {{ $company->name }} — Sağlık Detayı
            </h1>
        </div>
        <div style="display:flex; gap:8px;">
            <form method="POST" action="{{ route('platform.customer-health.recompute', $company->id) }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="refresh-cw" size="14" /> Yeniden Hesapla
                </button>
            </form>
            <a href="{{ route('platform.companies.show', $company->id) }}" class="plat-btn plat-btn-ghost">
                <x-icon name="building-2" size="14" /> Şirket Profili
            </a>
        </div>
    </div>

    {{-- ─── HERO ─────────────────────────────────────────────────────── --}}
    @if ($score)
        <div class="ch-hero">
            <div class="ch-hero-score {{ $score->scoreColor() }}">{{ $score->overall_score }}</div>
            <div class="ch-hero-info">
                <h2 class="ch-hero-name">
                    {{ $score->scoreLabel() }}
                    <span class="ch-trend {{ $score->trend }}" style="font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:4px;
                        color: {{ $score->trend === 'rising' ? '#4ade80' : ($score->trend === 'declining' ? '#f87171' : 'var(--plat-muted)') }};">
                        <x-icon name="{{ $score->trendIcon() }}" size="14" /> {{ $score->trendLabel() }}
                    </span>
                </h2>
                <div class="ch-hero-meta">
                    <span>Tier: <strong>{{ strtoupper($company->subscription_tier ?? '—') }}</strong></span>
                    <span>MRR: <strong>€{{ number_format((float) ($company->mrr_eur ?? 0), 2) }}</strong></span>
                    <span>Aktif: <strong>{{ $company->is_active ? 'Evet' : 'Hayır' }}</strong></span>
                    @if ($company->trial_ends_at)
                        <span>Trial Bitiş: <strong>{{ $company->trial_ends_at->format('d.m.Y') }}</strong></span>
                    @endif
                    @if ($company->subscription_renews_at)
                        <span>Yenileme: <strong>{{ $company->subscription_renews_at->format('d.m.Y') }}</strong></span>
                    @endif
                    <span>Son hesaplama:
                        <strong>{{ $score->computed_at ? $score->computed_at->diffForHumans() : '—' }}</strong>
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="plat-alert plat-alert-warn">
            <x-icon name="alert-triangle" size="16" />
            Bu şirket için henüz sağlık skoru yok. "Yeniden Hesapla" butonu ile tetikleyin.
        </div>
    @endif

    {{-- ─── BREAKDOWN + RISK FLAGS ─────────────────────────────────── --}}
    <div class="ch-grid-2">
        {{-- Sub-score breakdown --}}
        <div class="plat-card">
            <h3 class="plat-card-title">
                <x-icon name="bar-chart-3" size="16" /> Skor Detayı
            </h3>
            @if ($score && count($breakdown) > 0)
                @foreach ($breakdown as $row)
                    @php
                        $color = \App\Models\CustomerHealthScore::scoreColorFor((int) $row['score']);
                        $hex   = match ($color) {
                            'green'  => '#4ade80',
                            'yellow' => '#fbbf24',
                            'orange' => '#fb923c',
                            'red'    => '#f87171',
                            default  => '#a09bb5',
                        };
                    @endphp
                    <div class="ch-breakdown-row">
                        <div class="ch-breakdown-icon"><x-icon name="{{ $row['icon'] }}" size="16" /></div>
                        <div class="ch-breakdown-info">
                            <div style="display:flex; justify-content:space-between; align-items:end;">
                                <div>
                                    <div class="ch-breakdown-name">{{ $row['label'] }}</div>
                                    <div class="ch-breakdown-weight">Ağırlık: %{{ (int) $row['weight'] }}</div>
                                </div>
                            </div>
                            <div class="ch-breakdown-bar">
                                <div class="ch-breakdown-fill" style="width: {{ $row['score'] }}%; background: {{ $hex }};"></div>
                            </div>
                        </div>
                        <div class="ch-breakdown-score" style="color: {{ $hex }};">{{ $row['score'] }}</div>
                    </div>
                @endforeach
            @else
                <p style="color: var(--plat-muted); font-size:13px; margin:0;">Skor henüz hesaplanmamış.</p>
            @endif
        </div>

        {{-- Risk flags + remediation --}}
        <div class="plat-card">
            <h3 class="plat-card-title">
                <x-icon name="alert-triangle" size="16" /> Risk Bayrakları
            </h3>
            @if (count($flagDetails) > 0)
                @foreach ($flagDetails as $f)
                    <div class="ch-flag-card">
                        <div class="ch-flag-title">
                            <x-icon name="{{ $f['icon'] }}" size="14" />
                            {{ $f['label'] }}
                        </div>
                        <div class="ch-flag-fix">
                            <strong style="color: var(--plat-accent-2);">Öneri:</strong> {{ $f['fix'] }}
                        </div>
                    </div>
                @endforeach
            @else
                <p style="color: var(--plat-ok); font-size:13px; margin:0;">
                    <x-icon name="circle-check" size="14" /> Aktif risk bayrağı yok — bu müşteri stabil.
                </p>
            @endif
        </div>
    </div>

    {{-- ─── ACTIVITY TIMELINE ─────────────────────────────────────────── --}}
    <div class="plat-card">
        <h3 class="plat-card-title">
            <x-icon name="activity" size="16" /> Son Aktivite (30 gün)
        </h3>
        @if (count($timeline) > 0)
            <ul class="ch-timeline">
                @foreach ($timeline as $event)
                    <li>
                        <div class="ch-timeline-icon"><x-icon name="{{ $event['icon'] }}" size="14" /></div>
                        <div class="ch-timeline-body">
                            <div class="ch-timeline-label">{{ $event['label'] }}</div>
                            <div class="ch-timeline-time">
                                {{ $event['at'] ? \Illuminate\Support\Carbon::parse($event['at'])->diffForHumans() : '—' }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p style="color: var(--plat-muted); font-size:13px; margin:0;">Son 30 gün içinde belirgin aktivite kaydı yok.</p>
        @endif
    </div>
@endsection
