@extends('dealer.layouts.app')

@section('title', 'Kazançlarım')
@section('page_title', 'Kazançlarım')
@section('page_subtitle', 'Komisyon hareketleri ve milestone ilerlemesi')

@push('head')
<style>
/* KPI */
.earn-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
@media(max-width:900px){ .earn-kpi-strip { grid-template-columns:1fr 1fr; } }

.earn-kpi {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-top: 3px solid var(--border,#e2e8f0);
    border-radius: 12px;
    padding: 16px 18px;
}
.earn-kpi.total  { border-top-color: #16a34a; }
.earn-kpi.month  { border-top-color: #0891b2; }
.earn-kpi.pending{ border-top-color: #d97706; }
.earn-kpi.paid   { border-top-color: #7c3aed; }
.earn-kpi-label  { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:6px; }
.earn-kpi-val    { font-size:24px; font-weight:900; color:var(--text,#0f172a); line-height:1; }
.earn-kpi-sub    { font-size:11px; color:var(--muted,#64748b); margin-top:4px; }

/* Stat mini strip */
.earn-stat-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
.earn-stat { background:var(--bg,#f1f5f9); border-radius:10px; padding:12px 14px; }
.earn-stat-val   { font-size:20px; font-weight:800; color:var(--text,#0f172a); }
.earn-stat-label { font-size:11px; color:var(--muted,#64748b); margin-top:2px; }

/* Milestone tracker */
.earn-ms-card {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
}
.earn-ms-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.earn-ms-head h3 { margin:0; font-size:14px; font-weight:700; }
.earn-ms-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:0; }
@media(max-width:900px){ .earn-ms-grid { grid-template-columns:1fr 1fr; } }

.earn-ms-item {
    padding: 16px 18px;
    border-right: 1px solid var(--border,#e2e8f0);
    border-bottom: 1px solid var(--border,#e2e8f0);
    text-align: center;
}
.earn-ms-item:last-child { border-right: none; }
.earn-ms-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:8px; }
.earn-ms-num   { font-size:28px; font-weight:900; color:#16a34a; line-height:1; }
.earn-ms-total { font-size:12px; color:var(--muted,#64748b); margin-top:2px; }
.earn-ms-bar   { height:8px; background:var(--border,#e2e8f0); border-radius:4px; margin-top:10px; overflow:hidden; }
.earn-ms-fill  { height:100%; background:linear-gradient(90deg,#16a34a,#0891b2); border-radius:4px; }

.earn-ms-commission {
    padding: 12px 20px;
    background: rgba(22,163,74,.06);
    border-top: 1px solid rgba(22,163,74,.15);
    font-size:13px;
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.earn-ms-commission strong { color: #15803d; }

/* Filter + list card */
.earn-main-card {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-radius: 12px;
    overflow: hidden;
}
.earn-main-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.earn-main-head h3 { margin:0; font-size:14px; font-weight:700; }

.earn-filter {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: flex-end;
    background: var(--bg,#f8fafc);
}
.earn-filter-group { display:flex; flex-direction:column; gap:5px; }
.earn-filter-group.grow { flex:2; min-width:140px; }
.earn-filter-group.md   { flex:1; min-width:120px; }
.earn-filter-label { font-size:11px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.earn-filter input,
.earn-filter select {
    border: 1.5px solid var(--border,#e2e8f0);
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 13px;
    background: var(--surface,#fff);
    color: var(--text,#0f172a);
    width: 100%;
    box-sizing: border-box;
}
.earn-filter input:focus,
.earn-filter select:focus {
    outline: none;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
}

/* List items */
.earn-item {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    transition: background .12s;
    flex-wrap: wrap;
}
.earn-item:last-child { border-bottom: none; }
.earn-item:hover { background: var(--bg,#f8fafc); }
.earn-item-sid   { font-size:14px; font-weight:700; color:var(--text,#0f172a); }
.earn-item-type  { font-size:12px; color:var(--muted,#64748b); }
.earn-item-amounts { display:flex; gap:14px; margin-top:6px; flex-wrap:wrap; }
.earn-item-amt   { font-size:13px; }
.earn-item-amt.ok   { color: #16a34a; font-weight:700; }
.earn-item-amt.warn { color: #d97706; font-weight:700; }
.earn-item-milestones { display:flex; gap:6px; margin-top:6px; flex-wrap:wrap; }
.earn-ml-dot {
    display:inline-flex; align-items:center; gap:3px;
    font-size:11px; padding:2px 7px; border-radius:999px;
    font-weight:600;
}
.earn-ml-dot.done   { background:rgba(22,163,74,.1);  color:#15803d; }
.earn-ml-dot.undone { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); opacity:.6; }
.earn-item-date { font-size:11px; color:var(--muted,#64748b); margin-top:4px; }

.earn-empty { padding:40px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }

/* badge compat */
.earn-badge { display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; }
.earn-badge.ok      { background:rgba(22,163,74,.12); color:#15803d; }
.earn-badge.warn    { background:rgba(217,119,6,.12);  color:#b45309; }
.earn-badge.neutral { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }
</style>
@endpush

@section('content')

@include('partials.manager-hero', [
    'label' => 'Komisyon Takibi',
    'title' => 'Kazançlarım',
    'sub'   => 'Referansından dönen öğrencilerden bu ay ve tüm zamanlar toplam kazancın, bekleyen ve ödenmiş tutarlar bir arada.',
    'icon'  => '💶',
    'bg'    => 'https://images.unsplash.com/photo-1579621970590-9d624316904b?w=1400&q=80',
    'tone'  => 'green',
    'stats' => [
        ['icon' => '💰', 'text' => '€' . number_format((float) ($summary['earned'] ?? 0), 0, ',', '.') . ' toplam'],
        ['icon' => '📅', 'text' => '€' . number_format((float) ($summary['month'] ?? 0), 0, ',', '.') . ' bu ay'],
        ['icon' => '⏳', 'text' => '€' . number_format((float) ($summary['pending'] ?? 0), 0, ',', '.') . ' bekleyen'],
        ['icon' => '✅', 'text' => '€' . number_format((float) ($summary['paid'] ?? 0), 0, ',', '.') . ' ödenen'],
    ],
])

{{-- KPI strip --}}
<div class="earn-kpi-strip">
    <div class="earn-kpi total">
        <div class="earn-kpi-label">Toplam Kazanılan</div>
        <div class="earn-kpi-val">{{ number_format((float)$summary['earned'], 2, ',', '.') }}</div>
        <div class="earn-kpi-sub">EUR</div>
    </div>
    <div class="earn-kpi month">
        <div class="earn-kpi-label">Bu Ay</div>
        <div class="earn-kpi-val">{{ number_format((float)$summary['month'], 2, ',', '.') }}</div>
        <div class="earn-kpi-sub">EUR</div>
    </div>
    <div class="earn-kpi pending">
        <div class="earn-kpi-label">Bekleyen</div>
        <div class="earn-kpi-val">{{ number_format((float)$summary['pending'], 2, ',', '.') }}</div>
        <div class="earn-kpi-sub">EUR</div>
    </div>
    <div class="earn-kpi paid">
        <div class="earn-kpi-label">Ödenen</div>
        <div class="earn-kpi-val">{{ number_format((float)$summary['paid'], 2, ',', '.') }}</div>
        <div class="earn-kpi-sub">EUR</div>
    </div>
</div>

{{-- Stat strip --}}
<div class="earn-ms-card" style="padding:16px 20px;margin-bottom:16px;">
<div class="earn-stat-strip" style="margin-bottom:0;">
    <div class="earn-stat">
        <div class="earn-stat-val">{{ $completedCount ?? 0 }}</div>
        <div class="earn-stat-label">Tamamlanmış Kayıt</div>
    </div>
    <div class="earn-stat">
        <div class="earn-stat-val">{{ $pendingCount ?? 0 }}</div>
        <div class="earn-stat-label">Kısmi / Bekleyen</div>
    </div>
    <div class="earn-stat">
        <div class="earn-stat-val">{{ $summary['students'] }}</div>
        <div class="earn-stat-label">Kazanç Olan Öğrenci</div>
    </div>
</div>
</div>

{{-- Milestone Tracker --}}
@if(!empty($milestoneTracker) && $milestoneTracker->isNotEmpty())
<div class="earn-ms-card">
    <div class="earn-ms-head">
        <h3>Milestone İlerlemesi</h3>
        @if($currentRate > 0)
            <span class="earn-badge ok">Komisyon Oranı: %{{ $currentRate }}</span>
        @endif
    </div>
    <div class="earn-ms-grid">
        @foreach($milestoneTracker as $ms)
        <div class="earn-ms-item">
            <div class="earn-ms-title">{{ $ms['label'] }}</div>
            <div class="earn-ms-num">{{ $ms['reached'] }}</div>
            <div class="earn-ms-total">/ {{ $ms['total'] }} öğrenci</div>
            <div class="earn-ms-bar">
                <div class="earn-ms-fill" style="width:{{ $ms['pct'] }}%;"></div>
            </div>
        </div>
        @endforeach
    </div>
    @if($commissionBreakdown->isNotEmpty() && $currentRate > 0)
    <div class="earn-ms-commission">
        💰 Tahmini toplam komisyon:
        <strong>{{ number_format($commissionBreakdown->sum('commission_amount'), 2, ',', '.') }} EUR</strong>
        <span style="color:var(--muted,#64748b);">(%{{ $currentRate }} oran üzerinden)</span>
    </div>
    @endif
</div>
@endif

{{-- Hareketler --}}
<div class="earn-main-card">
    <div class="earn-main-head">
        <h3>Kazanç Hareketleri</h3>
        <a class="btn" href="{{ route('dealer.earnings.export') }}" style="font-size:var(--tx-xs);padding:6px 14px;">⬇ CSV İndir</a>
    </div>

    <div class="earn-filter">
        <form method="GET" style="display:contents;">
            <div class="earn-filter-group grow">
                <span class="earn-filter-label">Öğrenci ID</span>
                <input name="student" value="{{ $filterStudent ?? '' }}" placeholder="Ara...">
            </div>
            <div class="earn-filter-group md">
                <span class="earn-filter-label">Durum</span>
                <select name="status">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" @selected(($filterStatus??'')==='pending')>Bekleyen</option>
                    <option value="earned"  @selected(($filterStatus??'')==='earned')>Kazanıldı</option>
                    <option value="empty"   @selected(($filterStatus??'')==='empty')>Sıfır Gelir</option>
                </select>
            </div>
            <div class="earn-filter-group md">
                <span class="earn-filter-label">Tarihten</span>
                <input type="date" name="from" value="{{ $filterFrom ?? '' }}">
            </div>
            <div class="earn-filter-group md">
                <span class="earn-filter-label">Tarihe</span>
                <input type="date" name="to" value="{{ $filterTo ?? '' }}">
            </div>
            <button class="btn btn-primary" style="align-self:flex-end;">Filtrele</button>
        </form>
    </div>

    @php
        $mlLabels = ['DM-001'=>'Kayıt','DM-002'=>'Üniv. Kabul','DM-003'=>'Vize','DM-004'=>'Tamamlandı'];
    @endphp

    @forelse($rows as $r)
        @php
            $earned  = (float) ($r->total_earned ?? 0);
            $pending = (float) ($r->total_pending ?? 0);
            $state   = $pending > 0 ? 'pending' : ($earned > 0 ? 'earned' : 'empty');
            $stateMap = [
                'pending' => ['label' => 'Bekleyen',    'badge' => 'warn'],
                'earned'  => ['label' => 'Kazanıldı',   'badge' => 'ok'],
                'empty'   => ['label' => 'Sıfır Gelir', 'badge' => 'neutral'],
            ];
            $st = $stateMap[$state];
            $milestones = is_array($r->milestone_progress) ? $r->milestone_progress : [];
        @endphp
        <div class="earn-item">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span class="earn-item-sid">{{ $r->student_id }}</span>
                    @if($r->dealer_type)<span class="earn-item-type">{{ $r->dealer_type }}</span>@endif
                    <span class="earn-badge {{ $st['badge'] }}">{{ $st['label'] }}</span>
                </div>
                <div class="earn-item-amounts">
                    <span class="earn-item-amt ok">↑ {{ number_format($earned, 2, ',', '.') }} EUR kazanıldı</span>
                    @if($pending > 0)
                    <span class="earn-item-amt warn">⏳ {{ number_format($pending, 2, ',', '.') }} EUR bekliyor</span>
                    @endif
                </div>
                @if(!empty($milestones))
                <div class="earn-item-milestones">
                    @foreach($mlLabels as $key => $label)
                        <span class="earn-ml-dot {{ !empty($milestones[$key]) ? 'done' : 'undone' }}">
                            {{ !empty($milestones[$key]) ? '✓' : '◻' }} {{ $label }}
                        </span>
                    @endforeach
                </div>
                @endif
                <div class="earn-item-date">{{ optional($r->updated_at)->format('d.m.Y H:i') }}</div>
            </div>
        </div>
    @empty
        <div class="earn-empty">Kazanç kaydı bulunamadı.</div>
    @endforelse
</div>

@if($rows->hasPages())
<div style="margin-top:12px;">{{ $rows->withQueryString()->links() }}</div>
@endif

@include('dealer._partials.usage-guide', [
    'items' => [
        'Kazanç ekranı dealer revenue milestone verilerini konsolide eder.',
        'CSV İndir butonu tüm kazanç kayıtlarını dışa aktarır.',
        '"Bu Ay" sadece bu ay güncellenen kayıtları kapsar; "Ödenen" toplam kazanılan eksi bekleyen hesaplamasıdır.',
        'Ödeme talebi için Ödemeler ekranına git.',
    ]
])

@endsection
