@extends('senior.layouts.app')

@section('title', 'Kazançlarım')
@section('page_title', 'Kazançlarım')

@push('head')
<style>
.se-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
@media (max-width:700px) { .se-kpi-strip { grid-template-columns:1fr; } }
.se-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:18px 20px; position:relative; overflow:hidden; }
.se-kpi.month { border-left:4px solid #1e40af; }
.se-kpi.pending { border-left:4px solid #d97706; }
.se-kpi.paid { border-left:4px solid #16a34a; }
.se-kpi-label { font-size:11px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
.se-kpi-val   { font-size:30px; font-weight:800; color:var(--text,#0f172a); line-height:1; font-family:'SF Mono','Monaco',monospace; }
.se-kpi-sub   { font-size:11px; color:var(--muted,#64748b); margin-top:6px; }
.se-icon-bg { position:absolute; right:14px; top:14px; opacity:.15; }

.se-on-demand-card { background:linear-gradient(135deg, rgba(124,58,237,.08), rgba(8,145,178,.08)); border:1px solid rgba(124,58,237,.20); border-radius:12px; padding:18px 22px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; }
.se-on-demand-info { flex:1; min-width:240px; }
.se-on-demand-info h3 { margin:0 0 6px; font-size:14px; font-weight:700; color:var(--text,#0f172a); }
.se-on-demand-info p { margin:0; font-size:12px; color:var(--muted,#64748b); line-height:1.5; }
.se-on-demand-btn { padding:10px 18px; background:#7c3aed; color:#fff; border:none; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.se-on-demand-btn:hover:not(:disabled) { background:#6d28d9; }
.se-on-demand-btn:disabled { background:#cbd5e1; cursor:not-allowed; }

.se-section { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; margin-bottom:16px; overflow:hidden; }
.se-section-head { padding:12px 18px; border-bottom:1px solid var(--border,#e2e8f0); background:var(--bg,#f8fafc); display:flex; align-items:center; justify-content:space-between; }
.se-section-head h3 { margin:0; font-size:13px; font-weight:700; color:var(--text,#0f172a); }

.se-table { width:100%; border-collapse:collapse; font-size:12px; }
.se-table th { background:var(--bg,#f1f5f9); padding:9px 14px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); }
.se-table td { padding:10px 14px; border-bottom:1px solid var(--border,#e2e8f0); }
.se-table tr:last-child td { border-bottom:none; }
.se-status { display:inline-block; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:600; }
.se-status.recorded { background:rgba(8,145,178,.10); color:#0e7490; }
.se-status.available { background:rgba(217,119,6,.10); color:#b45309; }
.se-status.paid_out { background:rgba(22,163,74,.10); color:#15803d; }
.se-status.refunded { background:rgba(220,38,38,.10); color:#b91c1c; }
.se-status.voided { background:rgba(100,116,139,.12); color:#475569; }
.se-payout-status { display:inline-block; padding:3px 11px; border-radius:999px; font-size:11px; font-weight:600; }
.se-payout-status.pending { background:rgba(217,119,6,.10); color:#b45309; }
.se-payout-status.processing { background:rgba(8,145,178,.10); color:#0e7490; }
.se-payout-status.paid { background:rgba(22,163,74,.10); color:#15803d; }
.se-payout-status.failed { background:rgba(220,38,38,.10); color:#b91c1c; }
.se-amount { font-family:'SF Mono','Monaco',monospace; font-weight:700; color:var(--text,#0f172a); }
.se-empty { padding:30px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.25);border-radius:8px;padding:11px 16px;margin-bottom:14px;color:#15803d;font-size:13px;">
    <x-icon name="check-circle" size="14" /> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:rgba(220,38,38,.10);border:1px solid rgba(220,38,38,.25);border-radius:8px;padding:11px 16px;margin-bottom:14px;color:#b91c1c;font-size:13px;">
    <x-icon name="alert-triangle" size="14" /> {{ session('error') }}
</div>
@endif

<div class="se-kpi-strip">
    <div class="se-kpi month">
        <div class="se-icon-bg"><x-icon name="trending-up" size="48" /></div>
        <div class="se-kpi-label">Bu Ay Kazanılan</div>
        <div class="se-kpi-val">€{{ number_format($thisMonthCents / 100, 2, ',', '.') }}</div>
        <div class="se-kpi-sub">{{ now()->format('F Y') }}</div>
    </div>
    <div class="se-kpi pending">
        <div class="se-icon-bg"><x-icon name="clock" size="48" /></div>
        <div class="se-kpi-label">Ödeme Bekliyor</div>
        <div class="se-kpi-val">€{{ number_format($pendingCents / 100, 2, ',', '.') }}</div>
        <div class="se-kpi-sub">Henüz hiçbir payout'a bağlanmamış kazanç</div>
    </div>
    <div class="se-kpi paid">
        <div class="se-icon-bg"><x-icon name="check-circle" size="48" /></div>
        <div class="se-kpi-label">Toplam Ödenen</div>
        <div class="se-kpi-val">€{{ number_format($totalPaidCents / 100, 2, ',', '.') }}</div>
        <div class="se-kpi-sub">Tüm zamanların kümülatif tutarı</div>
    </div>
</div>

@if($settings->allow_on_demand)
<div class="se-on-demand-card">
    <div class="se-on-demand-info">
        <h3><x-icon name="zap" size="14" /> On-Demand Ödeme</h3>
        <p>
            Aylık otomatik ödemeni beklemeden, biriken bakiyeni şimdi talep edebilirsin.
            Minimum eşik: <strong>€{{ number_format($minCents / 100, 2, ',', '.') }}</strong>.
            Bu ay kullanılan: <strong>{{ $onDemandThisMonth }}/{{ $onDemandLimit }}</strong>.
        </p>
    </div>
    <form method="POST" action="{{ route('senior.earnings.request-on-demand') }}">
        @csrf
        <button type="submit" class="se-on-demand-btn"
                @disabled(!$canRequestOnDemand || $onDemandThisMonth >= $onDemandLimit)>
            <x-icon name="zap" size="14" />
            @if($onDemandThisMonth >= $onDemandLimit)
                Aylık limit doldu
            @elseif(!$canRequestOnDemand)
                Yetersiz bakiye
            @else
                €{{ number_format($pendingCents / 100, 2, ',', '.') }} talep et
            @endif
        </button>
    </form>
</div>
@endif

<div class="se-section">
    <div class="se-section-head">
        <h3><x-icon name="list" size="13" /> Kazanç Detayı</h3>
        <span style="font-size:11px;color:var(--muted,#64748b);">{{ $earnings->total() }} kayıt</span>
    </div>
    <table class="se-table">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Kayıt</th>
                <th style="text-align:right;">Net</th>
                <th style="text-align:right;">Komisyon %</th>
                <th style="text-align:right;">Senior Payı</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            @forelse($earnings as $e)
                <tr>
                    <td><span style="color:var(--muted);font-size:11px;">{{ $e->recorded_at?->format('d.m.Y H:i') ?? '—' }}</span></td>
                    <td>#{{ $e->id }}</td>
                    <td style="text-align:right;"><span class="se-amount">€{{ number_format($e->amount_net_cents / 100, 2, ',', '.') }}</span></td>
                    <td style="text-align:right;">%{{ number_format((float) $e->commission_pct_applied, 2) }}</td>
                    <td style="text-align:right;"><span class="se-amount" style="color:#15803d;">€{{ number_format($e->senior_payout_cents / 100, 2, ',', '.') }}</span></td>
                    <td><span class="se-status {{ $e->status }}">{{ $e->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="se-empty">
                    <x-icon name="inbox" size="20" /><br>
                    Henüz kazanç kaydın yok. Booking'ler tamamlandıkça burada görünecek.
                </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($earnings->hasPages())
    <div style="padding:10px 14px;">{{ $earnings->links() }}</div>
    @endif
</div>

<div class="se-section">
    <div class="se-section-head">
        <h3><x-icon name="wallet" size="13" /> Ödeme Geçmişi</h3>
        <span style="font-size:11px;color:var(--muted,#64748b);">{{ $payouts->total() }} payout</span>
    </div>
    <table class="se-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Periyot</th>
                <th style="text-align:right;">Tutar</th>
                <th>Durum</th>
                <th>Yöntem</th>
                <th>Ödeme Tarihi</th>
                <th style="text-align:right;">Makbuz</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payouts as $p)
                <tr>
                    <td><span style="font-family:'SF Mono','Monaco',monospace;font-size:11px;color:var(--muted,#64748b);">#{{ $p->id }}</span></td>
                    <td>
                        <span style="font-size:11px;">{{ \Illuminate\Support\Carbon::parse($p->period_start)->format('d.m.Y') }}</span>
                        <span style="color:var(--muted,#64748b);"> → </span>
                        <span style="font-size:11px;">{{ \Illuminate\Support\Carbon::parse($p->period_end)->format('d.m.Y') }}</span>
                    </td>
                    <td style="text-align:right;"><span class="se-amount">€{{ number_format($p->amount_cents / 100, 2, ',', '.') }}</span></td>
                    <td><span class="se-payout-status {{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                    <td><span style="font-size:11px;color:var(--muted);">{{ $p->method === 'stripe_on_demand' ? 'On-demand' : $p->method }}</span></td>
                    <td><span style="font-size:11px;color:var(--muted);">{{ $p->paid_at?->format('d.m.Y') ?? '—' }}</span></td>
                    <td style="text-align:right;">
                        @if($p->status === 'paid')
                            <a href="{{ route('senior.earnings.invoice', $p->id) }}" class="btn alt" style="padding:4px 10px;font-size:11px;">
                                <x-icon name="download" size="11" /> PDF
                            </a>
                        @else
                            <span style="color:var(--muted,#64748b);font-size:11px;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="se-empty">
                    <x-icon name="wallet" size="20" /><br>
                    Henüz hiçbir ödeme yapılmadı.
                </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($payouts->hasPages())
    <div style="padding:10px 14px;">{{ $payouts->links() }}</div>
    @endif
</div>

@endsection
