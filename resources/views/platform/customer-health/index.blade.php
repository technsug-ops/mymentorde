@extends('platform.layouts.app')

@section('title', 'Customer Sağlık Skorları — Platform')

@push('styles')
<style>
    .ch-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
    @media (max-width: 1100px) { .ch-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .ch-kpi-grid { grid-template-columns: 1fr; } }

    .ch-kpi { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 18px 20px; position: relative; overflow: hidden; }
    .ch-kpi.healthy::before  { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #4ade80; }
    .ch-kpi.warning::before  { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #fbbf24; }
    .ch-kpi.atrisk::before   { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #fb923c; }
    .ch-kpi.critical::before { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #f87171; }
    .ch-kpi-label { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--plat-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
    .ch-kpi-value { font-size: 28px; font-weight: 800; color: #fff; line-height: 1.2; }
    .ch-kpi-sub { font-size: 12px; color: var(--plat-muted); margin-top: 4px; }

    .ch-section-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 700; color: #fff; margin: 24px 0 14px; }
    .ch-section-title .lucide-icon { color: var(--plat-accent-2); }

    .ch-critical-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 24px; }
    @media (max-width: 900px) { .ch-critical-grid { grid-template-columns: 1fr; } }

    .ch-critical-card { background: linear-gradient(135deg, rgba(248,113,113,.10), rgba(248,113,113,.04)); border: 1px solid rgba(248,113,113,.35); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; }
    .ch-critical-card.warn { background: linear-gradient(135deg, rgba(251,191,36,.10), rgba(251,191,36,.04)); border-color: rgba(251,191,36,.35); }
    .ch-critical-card.ok { background: linear-gradient(135deg, rgba(74,222,128,.10), rgba(74,222,128,.04)); border-color: rgba(74,222,128,.35); }

    .ch-score-badge { display: flex; align-items: center; justify-content: center; min-width: 56px; height: 56px; border-radius: 50%; font-size: 18px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #f87171, #ef4444); flex-shrink: 0; }
    .ch-score-badge.green  { background: linear-gradient(135deg, #4ade80, #22c55e); }
    .ch-score-badge.yellow { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .ch-score-badge.orange { background: linear-gradient(135deg, #fb923c, #f97316); }
    .ch-score-badge.red    { background: linear-gradient(135deg, #f87171, #ef4444); }

    .ch-critical-info { flex: 1; min-width: 0; }
    .ch-critical-name { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
    .ch-critical-meta { font-size: 12px; color: var(--plat-muted); }
    .ch-critical-flags { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 6px; }
    .ch-flag-pill { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 700; background: rgba(248,113,113,.18); color: #fca5a5; text-transform: uppercase; letter-spacing: .3px; }

    .ch-filter-bar { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 14px 16px; margin-bottom: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: end; }
    .ch-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 130px; }
    .ch-filter-label { font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--plat-muted); }

    .ch-table-wrap { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; overflow: hidden; }
    .ch-table-wrap .plat-table { margin: 0; }
    .ch-row-score { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; }
    .ch-row-score-bar { width: 80px; height: 8px; background: var(--plat-panel-2); border-radius: 999px; overflow: hidden; }
    .ch-row-score-fill { height: 100%; border-radius: 999px; }

    .ch-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; }
    .ch-trend.rising    { color: #4ade80; }
    .ch-trend.declining { color: #f87171; }
    .ch-trend.stable    { color: var(--plat-muted); }

    .ch-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--plat-border); background: var(--plat-panel-2); }
    .ch-pagination .plat-btn { padding: 6px 12px; font-size: 12px; }
</style>
@endpush

@section('content')
    <div class="plat-page-header">
        <div>
            <h1 class="plat-page-title">
                <x-icon name="heart" size="22" style="vertical-align:-3px;color:var(--plat-accent-2);" /> Customer Sağlık Skorları
            </h1>
            <p class="plat-page-sub">
                {{ $totalCount }} şirket izleniyor —
                @if ($lastComputed)
                    son hesaplama: <strong style="color: var(--plat-text);">{{ \Illuminate\Support\Carbon::parse($lastComputed)->diffForHumans() }}</strong>
                @else
                    henüz hesaplama yapılmadı.
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <form method="POST" action="{{ route('platform.customer-health.recompute-all') }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="refresh-cw" size="14" /> Tüm Skorları Yenile
                </button>
            </form>
        </div>
    </div>

    {{-- ─── 4 KPI ─────────────────────────────────────────────────────── --}}
    <div class="ch-kpi-grid">
        <div class="ch-kpi healthy">
            <div class="ch-kpi-label"><x-icon name="shield-check" size="13" /> Sağlıklı (80+)</div>
            <div class="ch-kpi-value">{{ $healthyCount }}</div>
            <div class="ch-kpi-sub">Yüksek bağlılık, düşük risk</div>
        </div>
        <div class="ch-kpi warning">
            <div class="ch-kpi-label"><x-icon name="alert-triangle" size="13" /> Uyarı (50-79)</div>
            <div class="ch-kpi-value">{{ $warningCount }}</div>
            <div class="ch-kpi-sub">İzlemeye al, proaktif ilgi gerekir</div>
        </div>
        <div class="ch-kpi atrisk">
            <div class="ch-kpi-label"><x-icon name="circle-alert" size="13" /> Riskli (20-49)</div>
            <div class="ch-kpi-value">{{ $atRiskCount }}</div>
            <div class="ch-kpi-sub">Churn olasılığı yüksek</div>
        </div>
        <div class="ch-kpi critical">
            <div class="ch-kpi-label"><x-icon name="shield-alert" size="13" /> Kritik (&lt;20)</div>
            <div class="ch-kpi-value">{{ $criticalCount }}</div>
            <div class="ch-kpi-sub">Acil müdahale, kayıp riski</div>
        </div>
    </div>

    {{-- ─── Risk Priorities — en kritik 10 ─────────────────────────────── --}}
    @if ($criticalCompanies->isNotEmpty())
        <div class="ch-section-title">
            <x-icon name="flame" size="18" /> Acil İlgi Gereken İlk 10
        </div>
        <div class="ch-critical-grid">
            @foreach ($criticalCompanies as $cs)
                @php
                    $color = $cs->scoreColor();
                    $cardClass = $cs->overall_score < 20 ? '' : ($cs->overall_score < 50 ? '' : ($cs->overall_score < 80 ? 'warn' : 'ok'));
                @endphp
                <div class="ch-critical-card {{ $cardClass }}">
                    <div class="ch-score-badge {{ $color }}">{{ $cs->overall_score }}</div>
                    <div class="ch-critical-info">
                        <div class="ch-critical-name">
                            <x-icon name="building-2" size="14" />
                            <a href="{{ route('platform.customer-health.show', $cs->company_id) }}" style="color:#fff;">
                                {{ $cs->company?->name ?? 'Şirket #' . $cs->company_id }}
                            </a>
                            <span class="ch-trend {{ $cs->trend }}">
                                <x-icon name="{{ $cs->trendIcon() }}" size="12" /> {{ $cs->trendLabel() }}
                            </span>
                        </div>
                        <div class="ch-critical-meta">
                            {{ $cs->scoreLabel() }} ·
                            MRR: €{{ number_format((float) ($cs->company?->mrr_eur ?? 0), 2) }} ·
                            Tier: {{ strtoupper($cs->company?->subscription_tier ?? '—') }}
                        </div>
                        @if (!empty($cs->risk_flags))
                            <div class="ch-critical-flags">
                                @foreach ($cs->risk_flags as $f)
                                    @php $meta = \App\Models\CustomerHealthScore::flagMeta($f); @endphp
                                    <span class="ch-flag-pill" title="{{ $meta['fix'] }}">
                                        <x-icon name="{{ $meta['icon'] }}" size="10" /> {{ $meta['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <a href="{{ route('platform.customer-health.show', $cs->company_id) }}" class="plat-btn plat-btn-danger plat-btn-sm">
                        <x-icon name="eye" size="13" /> İlgilen
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ─── Filtreler ─────────────────────────────────────────────────── --}}
    <div class="ch-section-title">
        <x-icon name="filter" size="18" /> Tüm Şirketler
    </div>

    <form method="GET" class="ch-filter-bar">
        <div class="ch-filter-group">
            <label class="ch-filter-label">Min Skor</label>
            <input type="number" name="score_min" min="0" max="100" value="{{ $filters['score_min'] }}" class="plat-input" />
        </div>
        <div class="ch-filter-group">
            <label class="ch-filter-label">Max Skor</label>
            <input type="number" name="score_max" min="0" max="100" value="{{ $filters['score_max'] }}" class="plat-input" />
        </div>
        <div class="ch-filter-group">
            <label class="ch-filter-label">Trend</label>
            <select name="trend" class="plat-select">
                <option value="">(Hepsi)</option>
                <option value="rising"     {{ $filters['trend'] === 'rising' ? 'selected' : '' }}>Yükseliyor</option>
                <option value="stable"     {{ $filters['trend'] === 'stable' ? 'selected' : '' }}>Sabit</option>
                <option value="declining"  {{ $filters['trend'] === 'declining' ? 'selected' : '' }}>Düşüyor</option>
            </select>
        </div>
        <div class="ch-filter-group">
            <label class="ch-filter-label">Risk Flag</label>
            <select name="flag" class="plat-select">
                <option value="">(Yok)</option>
                @foreach ($availableFlags as $f)
                    @php $meta = \App\Models\CustomerHealthScore::flagMeta($f); @endphp
                    <option value="{{ $f }}" {{ $filters['flag'] === $f ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="ch-filter-group">
            <label class="ch-filter-label">Sırala</label>
            <select name="sort" class="plat-select">
                <option value="overall_score" {{ $filters['sort'] === 'overall_score' ? 'selected' : '' }}>Toplam Skor</option>
                <option value="login_score"   {{ $filters['sort'] === 'login_score' ? 'selected' : '' }}>Login</option>
                <option value="payment_score" {{ $filters['sort'] === 'payment_score' ? 'selected' : '' }}>Ödeme</option>
                <option value="usage_score"   {{ $filters['sort'] === 'usage_score' ? 'selected' : '' }}>Kullanım</option>
                <option value="support_score" {{ $filters['sort'] === 'support_score' ? 'selected' : '' }}>Destek</option>
                <option value="computed_at"   {{ $filters['sort'] === 'computed_at' ? 'selected' : '' }}>Hesaplama Zamanı</option>
            </select>
        </div>
        <div class="ch-filter-group" style="min-width:90px;">
            <label class="ch-filter-label">Yön</label>
            <select name="direction" class="plat-select">
                <option value="asc"  {{ $filters['direction'] === 'asc' ? 'selected' : '' }}>Artan</option>
                <option value="desc" {{ $filters['direction'] === 'desc' ? 'selected' : '' }}>Azalan</option>
            </select>
        </div>
        <div>
            <button type="submit" class="plat-btn plat-btn-primary">
                <x-icon name="search" size="14" /> Uygula
            </button>
            <a href="{{ route('platform.customer-health') }}" class="plat-btn plat-btn-ghost">Temizle</a>
        </div>
    </form>

    {{-- ─── Tablo ─────────────────────────────────────────────────────── --}}
    <div class="ch-table-wrap">
        <table class="plat-table">
            <thead>
                <tr>
                    <th>Şirket</th>
                    <th>Skor</th>
                    <th>Trend</th>
                    <th>Risk Flag'leri</th>
                    <th>MRR</th>
                    <th>Tier</th>
                    <th>Hesaplama</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($scores as $cs)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <x-icon name="building-2" size="14" style="color:var(--plat-muted);" />
                                <a href="{{ route('platform.customer-health.show', $cs->company_id) }}" style="color:#fff;font-weight:600;">
                                    {{ $cs->company?->name ?? 'Şirket #' . $cs->company_id }}
                                </a>
                            </div>
                        </td>
                        <td>
                            <span class="ch-row-score">
                                <span class="ch-row-score-bar">
                                    <span class="ch-row-score-fill" style="width: {{ $cs->overall_score }}%; background: {{ $cs->scoreHex() }};"></span>
                                </span>
                                <span style="color:{{ $cs->scoreHex() }};">{{ $cs->overall_score }}</span>
                            </span>
                        </td>
                        <td>
                            <span class="ch-trend {{ $cs->trend }}">
                                <x-icon name="{{ $cs->trendIcon() }}" size="12" /> {{ $cs->trendLabel() }}
                            </span>
                        </td>
                        <td>
                            @if (!empty($cs->risk_flags))
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    @foreach ($cs->risk_flags as $f)
                                        @php $meta = \App\Models\CustomerHealthScore::flagMeta($f); @endphp
                                        <span class="ch-flag-pill" title="{{ $meta['fix'] }}">
                                            <x-icon name="{{ $meta['icon'] }}" size="10" /> {{ $meta['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--plat-muted); font-size:12px;">—</span>
                            @endif
                        </td>
                        <td style="font-weight:700; color:#fff;">€{{ number_format((float) ($cs->company?->mrr_eur ?? 0), 2) }}</td>
                        <td>
                            <span class="plat-badge plat-badge-{{ $cs->company?->subscription_tier ?? 'trial' }}">
                                {{ strtoupper($cs->company?->subscription_tier ?? '—') }}
                            </span>
                        </td>
                        <td style="font-size:12px; color: var(--plat-muted);">
                            {{ $cs->computed_at ? $cs->computed_at->diffForHumans() : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('platform.customer-health.show', $cs->company_id) }}" class="plat-btn plat-btn-ghost plat-btn-sm">
                                <x-icon name="external-link" size="12" /> Detay
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:var(--plat-muted);">
                            <x-icon name="inbox" size="32" /> <br>
                            Filtrelere uyan kayıt yok. <a href="{{ route('platform.customer-health') }}" style="color:var(--plat-accent-2);">Tümünü göster</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($scores->hasPages())
            <div class="ch-pagination">
                <div style="font-size:12px; color: var(--plat-muted);">
                    Toplam {{ $scores->total() }} kayıt — sayfa {{ $scores->currentPage() }}/{{ $scores->lastPage() }}
                </div>
                <div>{{ $scores->links() }}</div>
            </div>
        @endif
    </div>
@endsection
