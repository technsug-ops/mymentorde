@extends('platform.layouts.app')

@section('title', 'Platform Analytics — DGmarkt')

@push('styles')
<style>
    .ana-filterbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; background:var(--plat-panel); border:1px solid var(--plat-border); border-radius:12px; padding:14px 18px; margin-bottom:20px; }
    .ana-filterbar form { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:0; }
    .ana-filterbar label { font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--plat-muted); margin-right:4px; }
    .ana-filterbar select { background:var(--plat-bg); border:1px solid var(--plat-border); color:var(--plat-text); padding:7px 10px; border-radius:7px; font-size:13px; font-family:inherit; min-width:120px; }

    .ana-kpi-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px; }
    @media (max-width: 1100px) { .ana-kpi-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width: 720px)  { .ana-kpi-grid { grid-template-columns:1fr; } }

    .ana-tier-row { display:grid; grid-template-columns:120px 1fr 60px 80px; gap:12px; align-items:center; padding:10px 0; border-bottom:1px dashed var(--plat-border); }
    .ana-tier-row:last-child { border-bottom:0; }
    .ana-tier-row .ana-bar-track { height:10px; background:var(--plat-panel-2); border-radius:999px; overflow:hidden; }
    .ana-tier-row .ana-bar-fill  { height:100%; background:linear-gradient(90deg, var(--plat-accent), var(--plat-accent-2)); border-radius:999px; }
    .ana-tier-row .ana-tier-count { font-weight:700; color:#fff; text-align:right; }
    .ana-tier-row .ana-tier-meta  { font-size:11px; color:var(--plat-muted); text-align:right; }

    .ana-funnel-row { display:grid; grid-template-columns:140px 1fr 90px; gap:14px; align-items:center; padding:9px 0; }
    .ana-funnel-row .ana-funnel-label { font-size:13px; font-weight:600; color:var(--plat-text); display:flex; align-items:center; gap:8px; }
    .ana-funnel-row .ana-funnel-track { height:18px; background:var(--plat-panel-2); border-radius:6px; overflow:hidden; position:relative; }
    .ana-funnel-row .ana-funnel-fill  { height:100%; border-radius:6px; transition:width .25s; }
    .ana-funnel-row .ana-funnel-count { font-weight:800; color:#fff; text-align:right; font-size:14px; }
    .ana-funnel-fill.created   { background:linear-gradient(90deg, #60a5fa, #a78bfa); }
    .ana-funnel-fill.pending   { background:linear-gradient(90deg, #fbbf24, #f59e0b); }
    .ana-funnel-fill.paid      { background:linear-gradient(90deg, #4ade80, #22d3ee); }
    .ana-funnel-fill.completed { background:linear-gradient(90deg, #7e58bf, #b395e6); }
    .ana-funnel-fill.canceled  { background:linear-gradient(90deg, #f87171, #fb7185); opacity:.85; }

    .ana-role-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; background:var(--plat-panel-2); border:1px solid var(--plat-border); border-radius:999px; font-size:11px; font-weight:600; color:var(--plat-text); margin:2px 4px 2px 0; }
    .ana-role-pill strong { color:#fff; margin-left:4px; font-weight:800; }

    .ana-export-btn { background:rgba(74,222,128,.10); border:1px solid rgba(74,222,128,.30); color:var(--plat-ok); padding:7px 12px; border-radius:7px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
    .ana-export-btn:hover { background:rgba(74,222,128,.20); color:#fff; border-color:var(--plat-ok); }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Platform Analytics</h1>
        <p class="plat-page-sub">Cross-company kullanım, booking ve aktiflik metrikleri</p>
    </div>
    <a href="{{ route('platform.analytics.export', request()->only('range', 'segment')) }}" class="ana-export-btn">
        <x-icon name="download" size="14" /> CSV İndir
    </a>
</div>

{{-- FILTER BAR --}}
<div class="ana-filterbar">
    <x-icon name="filter" size="14" />
    <form method="GET" action="{{ route('platform.analytics') }}">
        <label for="range">Dönem</label>
        <select name="range" id="range" onchange="this.form.submit()">
            <option value="7d"   {{ $range === '7d'   ? 'selected' : '' }}>Son 7 gün</option>
            <option value="30d"  {{ $range === '30d'  ? 'selected' : '' }}>Son 30 gün</option>
            <option value="90d"  {{ $range === '90d'  ? 'selected' : '' }}>Son 90 gün</option>
            <option value="365d" {{ $range === '365d' ? 'selected' : '' }}>Son 365 gün</option>
        </select>

        <label for="segment" style="margin-left:14px;">Segment</label>
        <select name="segment" id="segment" onchange="this.form.submit()">
            <option value="all"   {{ $segment === 'all'   ? 'selected' : '' }}>Tümü</option>
            <option value="trial" {{ $segment === 'trial' ? 'selected' : '' }}>Trial</option>
            <option value="paid"  {{ $segment === 'paid'  ? 'selected' : '' }}>Ücretli</option>
        </select>

        <span style="margin-left:14px;font-size:12px;color:var(--plat-muted);">
            {{ $periodFrom->format('d.m.Y') }} – {{ $periodTo->format('d.m.Y') }} · {{ $totalCompanies }} şirket
        </span>
    </form>
</div>

{{-- KPI GRID (6) --}}
<div class="ana-kpi-grid">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Aktif Kullanıcı</div>
        <div class="plat-kpi-value">{{ number_format($kpis['total_active_users']) }}</div>
        <div class="plat-kpi-sub">
            @foreach($userByRole as $role => $count)
                <span class="ana-role-pill">{{ $role }} <strong>{{ $count }}</strong></span>
            @endforeach
        </div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="calendar" size="12" /> Booking ({{ $rangeDays }} gün)</div>
        <div class="plat-kpi-value">{{ number_format($kpis['bookings_period']) }}</div>
        <div class="plat-kpi-sub">toplam randevu sayısı</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="dollar-sign" size="12" /> Booking Gelir</div>
        <div class="plat-kpi-value">€{{ number_format($kpis['booking_revenue_eur'], 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">Stripe gross — {{ $rangeDays }} gün</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="gift" size="12" /> Aktif Trial</div>
        <div class="plat-kpi-value">{{ number_format($kpis['active_trials']) }}</div>
        <div class="plat-kpi-sub">trial tier'da olan şirketler</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="sparkles" size="12" /> Yeni Şirket</div>
        <div class="plat-kpi-value">{{ number_format($kpis['new_signups_period']) }}</div>
        <div class="plat-kpi-sub">{{ $rangeDays }} gün içinde signup</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="alert-triangle" size="12" /> Churn (proxy)</div>
        <div class="plat-kpi-value">{{ number_format($kpis['churn_count']) }}</div>
        <div class="plat-kpi-sub">trial süresi dolmuş, hala trial</div>
    </div>
</div>

{{-- TIER DAGILIM + MODULE HEATMAP --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="bar-chart-3" size="16" /> Tier Bazlı Kullanıcı Dağılımı</h3>
        @php
            $maxUsers = 1;
            foreach ($tierDistribution as $row) { $maxUsers = max($maxUsers, $row['total_users']); }
        @endphp
        @foreach($tierDistribution as $tier => $row)
            <div class="ana-tier-row">
                <div><span class="plat-badge plat-badge-{{ $tier }}">{{ $tierLabels[$tier] ?? $tier }}</span></div>
                <div class="ana-bar-track">
                    <div class="ana-bar-fill" style="width: {{ round($row['total_users'] / $maxUsers * 100) }}%;"></div>
                </div>
                <div class="ana-tier-count">{{ $row['companies'] }}<div class="ana-tier-meta">şirket</div></div>
                <div class="ana-tier-count">{{ $row['total_users'] }}<div class="ana-tier-meta">⌀ {{ $row['avg_users'] }}</div></div>
            </div>
        @endforeach
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="layers" size="16" /> Modül Kullanım Heatmap</h3>
        <p class="plat-card-sub" style="margin-bottom:10px;">Hangi modül kaç şirkette aktif</p>
        <table class="plat-table" style="font-size:12px;">
            <thead>
                <tr><th>Modül</th><th style="text-align:right;">Şirket</th><th style="width:140px;">Oran</th></tr>
            </thead>
            <tbody>
                @foreach($moduleUsage as $moduleKey => $row)
                    <tr>
                        <td>
                            <strong style="color:#fff;">{{ $moduleMeta[$moduleKey]['label'] ?? $moduleKey }}</strong>
                            <div style="font-size:10px;color:var(--plat-muted);">{{ $moduleKey }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700;color:#fff;">{{ $row['count'] }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:6px;background:var(--plat-panel-2);border-radius:99px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $row['percent'] }}%;background:linear-gradient(90deg,var(--plat-accent),var(--plat-accent-2));border-radius:99px;"></div>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:var(--plat-accent-2);min-width:34px;text-align:right;">{{ $row['percent'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

{{-- TOP COMPANIES + BOOKING FUNNEL --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="trending-up" size="16" /> En Aktif 10 Şirket</h3>
        <p class="plat-card-sub" style="margin-bottom:10px;">User count + 3× booking (son 30 gün)</p>
        @if(empty($topCompanies))
            <p style="color:var(--plat-muted);font-size:13px;">Şirket bulunamadı.</p>
        @else
            <table class="plat-table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th>#</th><th>Şirket</th><th>Tier</th>
                        <th style="text-align:right;">Kull.</th>
                        <th style="text-align:right;">Book/30</th>
                        <th style="text-align:right;">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topCompanies as $i => $c)
                        <tr>
                            <td style="color:var(--plat-muted);font-weight:700;">{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ route('platform.companies.show', $c['id']) }}" style="color:#fff;font-weight:700;">{{ $c['name'] }}</a>
                            </td>
                            <td><span class="plat-badge plat-badge-{{ $c['tier'] ?: 'trial' }}">{{ $tierLabels[$c['tier']] ?? $c['tier'] }}</span></td>
                            <td style="text-align:right;color:#fff;">{{ $c['users'] }}</td>
                            <td style="text-align:right;color:#fff;">{{ $c['bookings_30d'] }}</td>
                            <td style="text-align:right;font-weight:800;color:var(--plat-accent-2);">{{ $c['score'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="activity" size="16" /> Booking Funnel</h3>
        <p class="plat-card-sub" style="margin-bottom:14px;">Son {{ $rangeDays }} gün içinde public booking akışı</p>
        @php
            $funnelStages = [
                'created'   => ['Created',   'created'],
                'pending'   => ['Pending Pay','pending'],
                'paid'      => ['Paid',      'paid'],
                'completed' => ['Completed', 'completed'],
                'canceled'  => ['Canceled',  'canceled'],
            ];
        @endphp
        @foreach($funnelStages as $key => [$label, $colorClass])
            @php
                $count = $bookingFunnel[$key] ?? 0;
                $width = $funnelMax > 0 ? round($count / $funnelMax * 100) : 0;
            @endphp
            <div class="ana-funnel-row">
                <div class="ana-funnel-label">{{ $label }}</div>
                <div class="ana-funnel-track">
                    <div class="ana-funnel-fill {{ $colorClass }}" style="width: {{ $width }}%;"></div>
                </div>
                <div class="ana-funnel-count">{{ number_format($count) }}</div>
            </div>
        @endforeach
        @php
            $createdCount   = $bookingFunnel['created'] ?? 0;
            $completedCount = $bookingFunnel['completed'] ?? 0;
            $conversion     = $createdCount > 0 ? round($completedCount / $createdCount * 100, 1) : 0;
        @endphp
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--plat-border);font-size:12px;color:var(--plat-muted);">
            <strong style="color:#fff;">Conversion (created → completed):</strong>
            <span style="color:var(--plat-accent-2);font-weight:800;margin-left:6px;">{{ $conversion }}%</span>
        </div>
    </div>

</div>

@endsection
