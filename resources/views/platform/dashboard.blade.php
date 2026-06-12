@extends('platform.layouts.app')

@section('title', 'Platform Dashboard — DGmarkt')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Platform Dashboard</h1>
        <p class="plat-page-sub">DGmarkt SaaS — cross-company genel bakış</p>
    </div>
    <a href="{{ route('platform.companies.create') }}" class="plat-btn plat-btn-primary">
        <x-icon name="plus" size="16" /> Yeni Şirket
    </a>
</div>

{{-- KPI ROW --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="building-2" size="12" /> Toplam Şirket</div>
        <div class="plat-kpi-value">{{ number_format($totalCompanies) }}</div>
        <div class="plat-kpi-sub">{{ $activeCompanies }} aktif</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="check" size="12" /> Aktif Şirket</div>
        <div class="plat-kpi-value">{{ number_format($activeCompanies) }}</div>
        <div class="plat-kpi-sub">{{ $totalCompanies > 0 ? round($activeCompanies / $totalCompanies * 100) : 0 }}% oran</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="dollar-sign" size="12" /> Toplam MRR</div>
        <div class="plat-kpi-value">€{{ number_format($totalMrr, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">aylık tekrarlayan gelir</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="gift" size="12" /> Aktif Trial</div>
        <div class="plat-kpi-value">{{ number_format($activeTrials) }}</div>
        <div class="plat-kpi-sub">
            @if($expiredTrials > 0)
                <span style="color:var(--plat-danger);">{{ $expiredTrials }} süresi geçmiş</span>
            @else
                tümü geçerli
            @endif
        </div>
    </div>
</div>

{{-- TIER DAĞILIMI + UYARILAR --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="bar-chart-3" size="16" /> Tier Dağılımı</h3>
        @foreach($tierCounts as $tierKey => $count)
            <div class="plat-tier-bar">
                <div class="plat-tier-bar-label">
                    <span class="plat-badge plat-badge-{{ $tierKey }}">{{ $tierLabels[$tierKey] ?? $tierKey }}</span>
                </div>
                <div class="plat-tier-bar-track">
                    <div class="plat-tier-bar-fill" style="width: {{ round($count / $tierMaxCount * 100) }}%;"></div>
                </div>
                <div class="plat-tier-bar-count">{{ $count }}</div>
            </div>
        @endforeach
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="alert-triangle" size="16" /> Trial Uyarıları</h3>
        @if($expiringTrials->isEmpty() && $expiredTrials === 0)
            <p class="plat-card-sub" style="margin:14px 0;"><x-icon name="check" size="14" /> Önümüzdeki 7 günde biten trial yok.</p>
        @else
            @if($expiredTrials > 0)
                <div class="plat-alert plat-alert-danger" style="margin-bottom:12px;">
                    <x-icon name="circle-alert" size="14" />
                    <strong>{{ $expiredTrials }}</strong> şirketin trial süresi geçmiş — hala 'trial' tier'da.
                </div>
            @endif
            @foreach($expiringTrials as $c)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--plat-border);">
                    <div>
                        <a href="{{ route('platform.companies.show', $c->id) }}" style="font-weight:700;color:#fff;">{{ $c->name }}</a>
                        <div style="font-size:11px;color:var(--plat-muted);">Trial bitiş: {{ optional($c->trial_ends_at)->format('d.m.Y') }}</div>
                    </div>
                    <span class="plat-badge plat-badge-trial">
                        @php $days = (int) now()->startOfDay()->diffInDays($c->trial_ends_at, false); @endphp
                        {{ $days >= 0 ? "{$days} gün" : 'geçmiş' }}
                    </span>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- MODUL KULLANIM --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="layers" size="16" /> Modül Kullanımı</h3>
        <p class="plat-card-sub" style="margin-bottom:12px;">Hangi modül kaç şirkette aktif?</p>
        <table class="plat-table" style="font-size:12px;">
            <thead>
                <tr><th>Modül</th><th style="text-align:right;">Aktif</th><th style="text-align:right;">Oran</th></tr>
            </thead>
            <tbody>
                @foreach($moduleUsage as $moduleKey => $count)
                    <tr>
                        <td>
                            <strong style="color:#fff;">{{ $moduleMeta[$moduleKey]['label'] ?? $moduleKey }}</strong>
                            <div style="font-size:10px;color:var(--plat-muted);">{{ $moduleKey }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700;color:#fff;">{{ $count }}</td>
                        <td style="text-align:right;">
                            <span class="plat-badge plat-badge-premium">{{ $totalCompanies > 0 ? round($count / $totalCompanies * 100) : 0 }}%</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="sparkles" size="16" /> Son Eklenen Şirketler</h3>
        <p class="plat-card-sub" style="margin-bottom:12px;">Son 30 gün</p>
        @if($recentCompanies->isEmpty())
            <p style="color:var(--plat-muted);font-size:13px;">Son 30 günde yeni şirket eklenmedi.</p>
        @else
            <table class="plat-table" style="font-size:12px;">
                <thead>
                    <tr><th>Şirket</th><th>Tier</th><th style="text-align:right;">Eklenme</th></tr>
                </thead>
                <tbody>
                    @foreach($recentCompanies as $c)
                        <tr>
                            <td>
                                <a href="{{ route('platform.companies.show', $c->id) }}" style="color:#fff;font-weight:700;">{{ $c->name }}</a>
                                <div style="font-size:10px;color:var(--plat-muted);">{{ $c->code }}</div>
                            </td>
                            <td><span class="plat-badge plat-badge-{{ $c->subscription_tier ?? 'trial' }}">{{ $tierLabels[$c->subscription_tier] ?? $c->subscription_tier }}</span></td>
                            <td style="text-align:right;color:var(--plat-muted);">{{ optional($c->created_at)->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
