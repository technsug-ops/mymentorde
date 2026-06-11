@extends('manager.layouts.app')

@section('title', 'Payout #' . $payout->id)
@section('page_title', 'Payout Detayı #' . $payout->id)

@push('head')
<style>
.psh-grid { display:grid; grid-template-columns:2fr 1fr; gap:18px; }
@media (max-width:900px) { .psh-grid { grid-template-columns:1fr; } }
.psh-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:18px 20px; }
.psh-card h3 { margin:0 0 14px; font-size:13px; font-weight:700; color:var(--text,#0f172a); text-transform:uppercase; letter-spacing:.04em; }
.psh-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed var(--border,#e2e8f0); font-size:13px; }
.psh-row:last-child { border-bottom:none; }
.psh-row .lbl { color:var(--muted,#64748b); font-weight:500; }
.psh-row .val { color:var(--text,#0f172a); font-weight:600; }
.psh-amount-big { font-size:32px; font-weight:800; color:var(--text,#0f172a); font-family:'SF Mono','Monaco',monospace; }
.psh-status { display:inline-block; padding:4px 14px; border-radius:999px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.psh-status.pending { background:rgba(217,119,6,.12); color:#b45309; }
.psh-status.processing { background:rgba(8,145,178,.12); color:#0e7490; }
.psh-status.paid { background:rgba(22,163,74,.12); color:#15803d; }
.psh-status.failed { background:rgba(220,38,38,.12); color:#b91c1c; }
.psh-status.cancelled, .psh-status.voided { background:rgba(100,116,139,.15); color:#475569; }
.psh-error { background:rgba(220,38,38,.07); border:1px solid rgba(220,38,38,.20); border-left:3px solid #dc2626; border-radius:6px; padding:10px 14px; color:#b91c1c; font-size:12px; line-height:1.5; margin-bottom:14px; }
.psh-table { width:100%; border-collapse:collapse; font-size:12px; }
.psh-table th, .psh-table td { padding:6px 10px; border-bottom:1px solid var(--border,#e2e8f0); text-align:left; }
.psh-table th { background:var(--bg,#f1f5f9); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); }
</style>
@endpush

@section('content')

<div style="margin-bottom:14px;">
    <a href="{{ route('manager.payouts.index') }}" style="font-size:12px;color:var(--muted,#64748b);text-decoration:none;">
        <x-icon name="arrow-left" size="12" /> Tüm Ödemeler
    </a>
</div>

@if($payout->status === 'failed' && $payout->failure_reason)
<div class="psh-error">
    <strong><x-icon name="alert-triangle" size="13" /> Hata:</strong> {{ $payout->failure_reason }}
</div>
@endif

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

<div class="psh-grid">
    <div>
        <div class="psh-card" style="margin-bottom:18px;">
            <h3>Payout Bilgileri</h3>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div class="psh-amount-big">{{ $payout->currency === 'EUR' ? '€' : ($payout->currency . ' ') }}{{ number_format($payout->amount_cents / 100, 2, ',', '.') }}</div>
                <span class="psh-status {{ $payout->status }}">{{ ucfirst($payout->status) }}</span>
            </div>
            <div class="psh-row"><span class="lbl">Senior</span><span class="val">{{ $payout->senior?->name }} <span style="color:var(--muted);font-weight:400;">({{ $payout->senior?->email }})</span></span></div>
            <div class="psh-row"><span class="lbl">Periyot</span><span class="val">{{ \Illuminate\Support\Carbon::parse($payout->period_start)->format('d.m.Y') }} — {{ \Illuminate\Support\Carbon::parse($payout->period_end)->format('d.m.Y') }}</span></div>
            <div class="psh-row"><span class="lbl">Yöntem</span><span class="val">{{ $payout->method }}</span></div>
            <div class="psh-row"><span class="lbl">Talep zamanı</span><span class="val">{{ $payout->requested_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
            <div class="psh-row"><span class="lbl">Ödeme zamanı</span><span class="val">{{ $payout->paid_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
            @if($payout->stripe_transfer_id)
                <div class="psh-row"><span class="lbl">Stripe Transfer ID</span><span class="val" style="font-family:'SF Mono','Monaco',monospace;font-size:11px;">{{ $payout->stripe_transfer_id }}</span></div>
            @endif
            @if($payout->external_reference)
                <div class="psh-row"><span class="lbl">Dış Referans</span><span class="val">{{ $payout->external_reference }}</span></div>
            @endif
            @if($payout->notes)
                <div class="psh-row"><span class="lbl">Notlar</span><span class="val">{{ $payout->notes }}</span></div>
            @endif
        </div>

        <div class="psh-card">
            <h3>Bağlı Kazançlar ({{ $payout->earnings->count() }})</h3>
            @if($payout->earnings->isEmpty())
                <div style="color:var(--muted);font-size:12px;">Bu payout'a bağlı kazanç bulunamadı.</div>
            @else
                <table class="psh-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kayıt Zamanı</th>
                            <th>Net</th>
                            <th>Komisyon %</th>
                            <th>Senior Payı</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payout->earnings as $e)
                            <tr>
                                <td>#{{ $e->id }}</td>
                                <td>{{ $e->recorded_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                <td>€{{ number_format($e->amount_net_cents / 100, 2, ',', '.') }}</td>
                                <td>%{{ number_format((float) $e->commission_pct_applied, 2) }}</td>
                                <td><strong>€{{ number_format($e->senior_payout_cents / 100, 2, ',', '.') }}</strong></td>
                                <td><span style="font-size:11px;color:var(--muted);">{{ $e->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div>
        <div class="psh-card">
            <h3>İşlemler</h3>
            @if(in_array($payout->status, ['failed', 'cancelled'], true))
                <form method="POST" action="{{ route('manager.payouts.retry', $payout->id) }}">
                    @csrf
                    <button type="submit" class="btn-primary btn" style="width:100%;justify-content:center;">
                        <x-icon name="refresh-cw" size="14" /> Tekrar Dene
                    </button>
                </form>
                <p style="font-size:11px;color:var(--muted,#64748b);margin-top:8px;line-height:1.5;">
                    Stripe transfer'i yeniden başlatır. Önce başarısızlık nedenini kontrol et.
                </p>
            @elseif($payout->status === 'pending')
                <p style="font-size:12px;color:var(--muted);line-height:1.5;">
                    Bu payout henüz işlenmedi. Sıradaki <code style="background:var(--bg,#f1f5f9);padding:1px 4px;border-radius:3px;">payouts:run-monthly</code>
                    veya manuel komut çalıştırılınca Stripe transfer'i denenecek.
                </p>
            @elseif($payout->status === 'paid')
                <p style="font-size:12px;color:#15803d;line-height:1.5;">
                    <x-icon name="check-circle" size="12" /> Ödeme başarıyla tamamlandı.
                </p>
            @endif
        </div>

        <div class="psh-card" style="margin-top:14px;">
            <h3>Kazanç Özeti</h3>
            @php
                $netTotal = $payout->earnings->sum('amount_net_cents');
                $commTotal = $payout->earnings->sum('commission_cents');
                $taxTotal = $payout->earnings->sum('tax_amount_cents');
            @endphp
            <div class="psh-row"><span class="lbl">Net toplam</span><span class="val">€{{ number_format($netTotal / 100, 2, ',', '.') }}</span></div>
            <div class="psh-row"><span class="lbl">Vergi toplam</span><span class="val">€{{ number_format($taxTotal / 100, 2, ',', '.') }}</span></div>
            <div class="psh-row"><span class="lbl">Platform komisyonu</span><span class="val">€{{ number_format($commTotal / 100, 2, ',', '.') }}</span></div>
            <div class="psh-row" style="border-top:2px solid var(--border,#e2e8f0);padding-top:10px;margin-top:4px;">
                <span class="lbl" style="font-weight:700;color:var(--text);">Senior Net</span>
                <span class="val" style="color:#15803d;">€{{ number_format($payout->amount_cents / 100, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
