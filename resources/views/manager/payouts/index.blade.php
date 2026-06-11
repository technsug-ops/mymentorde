@extends('manager.layouts.app')

@section('title', 'Senior Ödemeler')
@section('page_title', 'Senior Ödemeler')

@push('head')
<style>
.po-kpi-strip { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:14px; }
@media (max-width:900px) { .po-kpi-strip { grid-template-columns:repeat(2,1fr); } }
.po-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.po-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.po-kpi-val   { font-size:20px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.po-kpi.warn { border-top-color:#d97706; }
.po-kpi.info { border-top-color:#0891b2; }
.po-kpi.ok   { border-top-color:#16a34a; }
.po-kpi.err  { border-top-color:#dc2626; }

.po-filter { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:12px 14px; margin-bottom:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.po-filter-field { display:flex; flex-direction:column; gap:4px; }
.po-filter-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.po-filter-input { padding:7px 10px; font-size:13px; border:1.5px solid var(--border,#cbd5e1); border-radius:6px; background:var(--surface,#fff); min-width:180px; }

.po-table-wrap { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; overflow:hidden; }
.po-table { width:100%; border-collapse:collapse; font-size:13px; }
.po-table th { background:var(--bg,#f1f5f9); padding:9px 14px; text-align:left; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); border-bottom:1px solid var(--border,#e2e8f0); }
.po-table td { padding:10px 14px; border-bottom:1px solid var(--border,#e2e8f0); vertical-align:middle; }
.po-table tr:last-child td { border-bottom:none; }
.po-table tr:hover { background:rgba(30,64,175,.03); }
.po-amount { font-family:'SF Mono','Monaco',monospace; font-weight:700; color:var(--text,#0f172a); }
.po-status { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; }
.po-status.pending     { background:rgba(217,119,6,.10); color:#b45309; }
.po-status.processing  { background:rgba(8,145,178,.10); color:#0e7490; }
.po-status.paid        { background:rgba(22,163,74,.10); color:#15803d; }
.po-status.failed      { background:rgba(220,38,38,.10); color:#b91c1c; }
.po-status.cancelled   { background:rgba(100,116,139,.12); color:#475569; }
.po-status.voided      { background:rgba(100,116,139,.12); color:#475569; }
.po-empty { padding:40px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }
</style>
@endpush

@section('content')

<div class="po-kpi-strip">
    <div class="po-kpi warn">
        <div class="po-kpi-label">Bekleyen</div>
        <div class="po-kpi-val">{{ $kpis['pending'] }}</div>
    </div>
    <div class="po-kpi info">
        <div class="po-kpi-label">İşlemde</div>
        <div class="po-kpi-val">{{ $kpis['processing'] }}</div>
    </div>
    <div class="po-kpi ok">
        <div class="po-kpi-label">Ödendi</div>
        <div class="po-kpi-val">{{ $kpis['paid'] }}</div>
    </div>
    <div class="po-kpi err">
        <div class="po-kpi-label">Başarısız</div>
        <div class="po-kpi-val">{{ $kpis['failed'] }}</div>
    </div>
    <div class="po-kpi ok">
        <div class="po-kpi-label">Toplam Ödenen</div>
        <div class="po-kpi-val">€{{ number_format($kpis['total_paid_cents'] / 100, 2, ',', '.') }}</div>
    </div>
</div>

<form method="GET" class="po-filter">
    <div class="po-filter-field">
        <label class="po-filter-label">Durum</label>
        <select name="status" class="po-filter-input">
            <option value="">Tümü</option>
            @foreach(['pending'=>'Bekleyen', 'processing'=>'İşlemde', 'paid'=>'Ödendi', 'failed'=>'Başarısız', 'cancelled'=>'İptal', 'voided'=>'Geçersiz'] as $k => $l)
                <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="po-filter-field">
        <label class="po-filter-label">Senior</label>
        <select name="senior" class="po-filter-input">
            <option value="">Tümü</option>
            @foreach($seniors as $s)
                <option value="{{ $s->id }}" @selected($filters['seniorId'] === $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="po-filter-field">
        <label class="po-filter-label">Periyot (YYYY-MM)</label>
        <input type="month" name="period" value="{{ $filters['period'] }}" class="po-filter-input">
    </div>
    <button type="submit" class="btn-primary btn">
        <x-icon name="filter" size="13" /> Filtrele
    </button>
    <a href="{{ route('manager.payouts.index') }}" class="btn alt">Sıfırla</a>
</form>

@if(session('success'))
<div style="background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.25);border-radius:8px;padding:10px 14px;margin-bottom:12px;color:#15803d;font-size:13px;">
    <x-icon name="check-circle" size="14" /> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:rgba(220,38,38,.10);border:1px solid rgba(220,38,38,.25);border-radius:8px;padding:10px 14px;margin-bottom:12px;color:#b91c1c;font-size:13px;">
    <x-icon name="alert-triangle" size="14" /> {{ session('error') }}
</div>
@endif

<div class="po-table-wrap">
    <table class="po-table">
        <thead>
            <tr>
                <th style="width:60px;">#</th>
                <th>Senior</th>
                <th>Periyot</th>
                <th style="width:130px;text-align:right;">Tutar</th>
                <th style="width:120px;">Durum</th>
                <th style="width:140px;">Yöntem</th>
                <th style="width:120px;">Talep / Ödeme</th>
                <th style="width:110px;text-align:right;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payouts as $p)
                <tr>
                    <td><span style="font-family:'SF Mono','Monaco',monospace;font-size:11px;color:var(--muted,#64748b);">#{{ $p->id }}</span></td>
                    <td>
                        <strong>{{ $p->senior?->name ?? '—' }}</strong>
                        <div style="font-size:11px;color:var(--muted,#64748b);">{{ $p->senior?->email }}</div>
                    </td>
                    <td>
                        <span style="font-size:12px;">{{ \Illuminate\Support\Carbon::parse($p->period_start)->format('d.m.Y') }}</span>
                        <span style="color:var(--muted,#64748b);"> → </span>
                        <span style="font-size:12px;">{{ \Illuminate\Support\Carbon::parse($p->period_end)->format('d.m.Y') }}</span>
                    </td>
                    <td style="text-align:right;">
                        <span class="po-amount">{{ $p->currency === 'EUR' ? '€' : ($p->currency . ' ') }}{{ number_format($p->amount_cents / 100, 2, ',', '.') }}</span>
                    </td>
                    <td>
                        <span class="po-status {{ $p->status }}">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--muted,#64748b);">
                            @if($p->method === 'stripe_on_demand')
                                <x-icon name="zap" size="12" /> On-demand
                            @elseif($p->method === 'stripe')
                                <x-icon name="credit-card" size="12" /> Stripe
                            @elseif($p->method === 'bank_transfer')
                                <x-icon name="building-bank" size="12" /> Banka
                            @else
                                {{ $p->method }}
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($p->paid_at)
                            <div style="font-size:11px;color:#15803d;">Ödendi: {{ $p->paid_at->format('d.m.Y') }}</div>
                        @elseif($p->requested_at)
                            <div style="font-size:11px;color:var(--muted,#64748b);">Talep: {{ $p->requested_at->format('d.m.Y') }}</div>
                        @else
                            <span style="font-size:11px;color:var(--muted,#64748b);">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('manager.payouts.show', $p->id) }}" class="btn alt" style="padding:4px 10px;font-size:11px;">
                            <x-icon name="eye" size="12" /> Detay
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="po-empty">
                    <x-icon name="inbox" size="20" /><br>
                    Filtreyle eşleşen payout bulunamadı.
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:14px;">
    {{ $payouts->links() }}
</div>

@endsection
