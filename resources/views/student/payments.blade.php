@extends('student.layouts.app')
@section('title', 'Ödeme Durumum')
@section('page_title', 'Ödeme Durumum')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.pay-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#064e3b url('https://images.unsplash.com/photo-1579621970590-9d624316904b?w=1400&q=80') center/cover; }
.pay-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(6,78,59,.93) 0%, rgba(5,150,105,.82) 100%); }
.pay-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.pay-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.pay-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.pay-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.pay-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.pay-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.pay-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.pay-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.pay-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .pay-hero-body { gap:14px; padding:18px; align-items:flex-start; } .pay-hero-title { font-size:20px; } .pay-hero-sub { font-size:12px; } .pay-hero-icon { font-size:36px; } }

/* ── pay-* Payments ── */

.pay-rate-chip {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 999px; padding: 5px 14px;
    font-size: 12px; font-weight: 700; color: var(--u-text);
    margin-bottom: 16px;
}
.pay-rate-chip .rate-val { color: #7c3aed; }

/* KPI row */
.pay-kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
@media(max-width:820px){ .pay-kpi-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px){ .pay-kpi-row { grid-template-columns: 1fr; } }

.pay-kpi-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 14px 16px;
}
.pay-kpi-label  { font-size: 11px; font-weight: 700; color: var(--u-muted); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
.pay-kpi-value  { font-size: 22px; font-weight: 800; color: var(--u-text); line-height: 1; }
.pay-kpi-sub    { font-size: 11px; color: var(--u-muted); margin-top: 4px; }
.pay-kpi-card.ok     { border-color: rgba(22,163,74,.25);  background: linear-gradient(135deg,#f0fdf4,var(--u-card)); }
.pay-kpi-card.warn   { border-color: rgba(217,119,6,.25);  background: linear-gradient(135deg,#fffbeb,var(--u-card)); }
.pay-kpi-card.danger { border-color: rgba(220,38,38,.2);   background: linear-gradient(135deg,#fef2f2,var(--u-card)); }
.pay-kpi-card.purple { border-color: rgba(124,58,237,.25); background: linear-gradient(135deg,#f5f3ff,var(--u-card)); }

/* Progress bar */
.pay-bar-wrap { margin-bottom: 8px; }
.pay-bar-labels { display: flex; justify-content: space-between; font-size: 12px; color: var(--u-muted); margin-bottom: 5px; }
.pay-bar-labels strong { color: var(--u-text); }
.pay-bar-track { height: 8px; background: var(--u-line); border-radius: 4px; overflow: hidden; position: relative; }
.pay-bar-fill  { height: 100%; border-radius: 4px; transition: width .5s ease; }
.pay-bar-fill.ok   { background: linear-gradient(90deg,#16a34a,#22c55e); }
.pay-bar-fill.warn { background: linear-gradient(90deg,#d97706,#f59e0b); }

/* Milestones */
.pay-milestones { display: flex; flex-direction: column; gap: 0; }
.pay-ms-item {
    display: flex; align-items: flex-start; gap: 0;
    position: relative;
}
.pay-ms-item:not(:last-child) .pay-ms-line { height: 100%; }

.pay-ms-indicator { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 32px; }
.pay-ms-dot {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; z-index: 1;
    border: 2px solid transparent;
}
.pay-ms-dot.paid    { background: #16a34a; color: #fff; border-color: #16a34a; }
.pay-ms-dot.pending { background: #fff;    color: #d97706; border-color: #f59e0b; }
.pay-ms-dot.future  { background: var(--u-bg); color: var(--u-muted); border-color: var(--u-line); }
.pay-ms-line-seg {
    width: 2px; background: var(--u-line); flex: 1; min-height: 24px; margin: 2px 0;
}
.pay-ms-line-seg.done { background: #16a34a; }

.pay-ms-body {
    flex: 1; padding: 0 0 20px 14px;
}
.pay-ms-label    { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 3px; }
.pay-ms-label.muted-label { color: var(--u-muted); font-weight: 600; }
.pay-ms-date     { font-size: 11px; color: var(--u-muted); margin-bottom: 4px; }
.pay-ms-amount   {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;
}
.pay-ms-amount.paid    { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
.pay-ms-amount.pending { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.pay-ms-amount.future  { background: var(--u-bg); color: var(--u-muted); border: 1px solid var(--u-line); }

/* Support card */
.pay-support {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 16px 18px;
}
</style>
@endpush

@section('content')
@php
    $r       = $revenue ?? null;
    $total   = (float) ($r?->package_total_price ?? 0);
    $earned  = (float) ($r?->total_earned        ?? 0);
    $pending = (float) ($r?->total_pending       ?? 0);
    $remain  = (float) ($r?->total_remaining     ?? 0);
    $paidPct    = $total > 0 ? min(100, round($earned  / $total * 100)) : 0;
    $pendingPct = $total > 0 ? min(100, round($pending / $total * 100)) : 0;
    $tryRate    = $eurTryRate ?? null;
    $tryDate    = $eurTryRateDate ?? null;

    $milestones = [];
    if ($r && $r->milestone_progress) {
        $raw = is_array($r->milestone_progress) ? $r->milestone_progress : json_decode($r->milestone_progress, true);
        $milestones = is_array($raw) ? $raw : [];
    }

    $fmtEur = fn($v) => '€ ' . number_format((float)$v, 2, ',', '.');
@endphp

{{-- ══════ Hero ══════ --}}
<div class="pay-hero">
    <div class="pay-hero-body">
        <div class="pay-hero-main">
            <div class="pay-hero-label"><span class="pay-hero-marker"></span>Finansal Özet</div>
            <h1 class="pay-hero-title">Ödeme Durumum</h1>
            <div class="pay-hero-sub">Paket ücreti, ödeme takvimi ve taksit durumunu tek bakışta gör. Taksitleri Stripe üzerinden güvenle öde.</div>
            <div class="pay-hero-stats">
                <span class="pay-hero-stat">💰 {{ $fmtEur($total) }} toplam</span>
                <span class="pay-hero-stat">✅ {{ $fmtEur($earned) }} ödenmiş</span>
                @if($remain > 0)<span class="pay-hero-stat">⏳ {{ $fmtEur($remain) }} kalan</span>@endif
                <span class="pay-hero-stat">📊 %{{ $paidPct }}</span>
            </div>
        </div>
        <div class="pay-hero-icon">💶</div>
    </div>
</div>

@if(!$r)
{{-- Empty state --}}
<div style="text-align:center;padding:48px 20px;background:var(--u-card);border:1px solid var(--u-line);border-radius:14px;color:var(--u-muted);">
    <div style="font-size:40px;margin-bottom:10px;">💶</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:6px;color:var(--u-text);">Henüz ödeme kaydı yok</div>
    <div style="font-size:var(--tx-sm);margin-bottom:16px;">Paket seçimini tamamladıktan sonra ödeme takviminiz burada görünecek.</div>
    <a class="btn" href="/student/services" style="background:#7c3aed;color:#fff;padding:10px 22px;font-size:var(--tx-sm);">Paket Seç</a>
</div>
@else

{{-- EUR/TRY chip --}}
@if($tryRate)
<div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <span class="pay-rate-chip">
        💱 1 EUR = <span class="rate-val">₺ {{ number_format($tryRate, 2, ',', '.') }}</span>
        @if($tryDate)<span style="color:var(--u-muted);font-weight:400;">· {{ $tryDate }}</span>@endif
    </span>
    @if($total > 0)
    <span class="pay-rate-chip">
        Paket toplamı ≈ <span class="rate-val">₺ {{ number_format($total * $tryRate, 0, ',', '.') }}</span>
    </span>
    @endif
</div>
@endif

{{-- KPI Cards --}}
<div class="pay-kpi-row">
    <div class="pay-kpi-card purple">
        <div class="pay-kpi-label">Paket Tutarı</div>
        <div class="pay-kpi-value">{{ $fmtEur($total) }}</div>
        @if($tryRate)<div class="pay-kpi-sub">≈ ₺ {{ number_format($total * $tryRate, 0, ',', '.') }}</div>@endif
    </div>
    <div class="pay-kpi-card ok">
        <div class="pay-kpi-label">Ödenen</div>
        <div class="pay-kpi-value" style="color:#16a34a;">{{ $fmtEur($earned) }}</div>
        <div class="pay-kpi-sub">%{{ $paidPct }} tamamlandı</div>
    </div>
    <div class="pay-kpi-card {{ $pending > 0 ? 'warn' : '' }}">
        <div class="pay-kpi-label">Bekleyen</div>
        <div class="pay-kpi-value" style="{{ $pending > 0 ? 'color:#d97706;' : '' }}">{{ $fmtEur($pending) }}</div>
        <div class="pay-kpi-sub">{{ $pending > 0 ? 'yaklaşan ödeme' : 'bekleyen yok' }}</div>
    </div>
    <div class="pay-kpi-card {{ $remain > 0 ? 'danger' : 'ok' }}">
        <div class="pay-kpi-label">Kalan Bakiye</div>
        <div class="pay-kpi-value" style="{{ $remain > 0 ? 'color:#dc2626;' : 'color:#16a34a;' }}">{{ $fmtEur($remain) }}</div>
        <div class="pay-kpi-sub">{{ $remain <= 0 ? '✓ tamamlandı' : 'ödenmemiş' }}</div>
    </div>
</div>

{{-- Progress --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 18px;margin-bottom:20px;">
    <div style="font-size:var(--tx-sm);font-weight:800;color:var(--u-text);margin-bottom:14px;">Ödeme İlerlemesi</div>

    <div class="pay-bar-wrap">
        <div class="pay-bar-labels">
            <span>Ödenen</span>
            <strong>{{ $fmtEur($earned) }} — %{{ $paidPct }}</strong>
        </div>
        <div class="pay-bar-track">
            <div class="pay-bar-fill ok" style="width:{{ $paidPct }}%"></div>
        </div>
    </div>

    @if($pending > 0)
    <div class="pay-bar-wrap" style="margin-top:10px;">
        <div class="pay-bar-labels">
            <span>Bekleyen</span>
            <strong>{{ $fmtEur($pending) }} — %{{ $pendingPct }}</strong>
        </div>
        <div class="pay-bar-track">
            <div class="pay-bar-fill warn" style="width:{{ $pendingPct }}%"></div>
        </div>
    </div>
    @endif

    <div style="margin-top:12px;padding:10px 12px;border-radius:8px;background:var(--u-bg);border:1px solid var(--u-line);display:flex;gap:20px;flex-wrap:wrap;font-size:var(--tx-xs);">
        <span><span style="color:var(--u-muted);">Toplam:</span> <strong>{{ $fmtEur($total) }}</strong></span>
        <span><span style="color:var(--u-muted);">Ödenen:</span> <strong style="color:#16a34a;">{{ $fmtEur($earned) }}</strong></span>
        @if($pending > 0)<span><span style="color:var(--u-muted);">Bekleyen:</span> <strong style="color:#d97706;">{{ $fmtEur($pending) }}</strong></span>@endif
        @if($remain > 0)<span><span style="color:var(--u-muted);">Kalan:</span> <strong style="color:#dc2626;">{{ $fmtEur($remain) }}</strong></span>@endif
        <span style="margin-left:auto;color:var(--u-muted);">Son güncelleme: {{ $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('d.m.Y H:i') : '–' }}</span>
    </div>
</div>

{{-- Milestones timeline --}}
@if(!empty($milestones))
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:16px 18px;margin-bottom:20px;">
    <div style="font-size:var(--tx-sm);font-weight:800;color:var(--u-text);margin-bottom:18px;">Ödeme Takvimi</div>
    <div class="pay-milestones">
        @foreach($milestones as $idx => $ms)
        @php
            $msStatus  = (string)($ms['status'] ?? '');
            $msDone    = $msStatus === 'paid' || !empty($ms['done']) || !empty($ms['completed']);
            $msPending = $msStatus === 'pending' && !$msDone;
            $dotCls    = $msDone ? 'paid' : ($msPending ? 'pending' : 'future');
            $label     = (string)($ms['label'] ?? $ms['title'] ?? 'Aşama '.($idx+1));
            $amount    = isset($ms['amount']) ? (float)$ms['amount'] : null;
            $paidAt    = $ms['paid_at'] ?? null;
            $dueAt     = $ms['due_at']  ?? $ms['date'] ?? null;
            $dateStr   = null;
            if ($msDone && $paidAt)       $dateStr = '✓ ' . \Carbon\Carbon::parse($paidAt)->format('d.m.Y') . ' tarihinde ödendi';
            elseif ($msPending && $dueAt) $dateStr = '⏰ Son ödeme: ' . \Carbon\Carbon::parse($dueAt)->format('d.m.Y');
            $isLast = $loop->last;
        @endphp
        <div class="pay-ms-item">
            <div class="pay-ms-indicator">
                <div class="pay-ms-dot {{ $dotCls }}">
                    @if($msDone) ✓ @elseif($msPending) {{ $loop->iteration }} @else {{ $loop->iteration }} @endif
                </div>
                @if(!$isLast)
                <div class="pay-ms-line-seg {{ $msDone ? 'done' : '' }}"></div>
                @endif
            </div>
            <div class="pay-ms-body">
                <div class="pay-ms-label {{ !$msDone && !$msPending ? 'muted-label' : '' }}">{{ $label }}</div>
                @if($dateStr)<div class="pay-ms-date">{{ $dateStr }}</div>@endif
                @if($amount !== null)
                <span class="pay-ms-amount {{ $dotCls }}">
                    {{ $fmtEur($amount) }}
                    @if($tryRate) <span style="font-weight:400;opacity:.7;">≈ ₺{{ number_format($amount * $tryRate, 0, ',', '.') }}</span>@endif
                </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- All paid --}}
@if($remain <= 0 && $earned > 0)
<div style="padding:16px 18px;border-radius:12px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;text-align:center;margin-bottom:20px;">
    <div style="font-size:var(--tx-xl);margin-bottom:6px;">🎉</div>
    <strong style="color:#16a34a;">Tüm ödemeler tamamlandı!</strong>
    <div style="font-size:var(--tx-sm);color:#16a34a;opacity:.8;margin-top:4px;">Teşekkür ederiz, süreciniz devam etmektedir.</div>
</div>
@endif

{{-- Support --}}
@if($pending > 0 || $remain > 0)
<div class="pay-support">
    <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);margin-bottom:4px;">Ödeme ile ilgili sorunuz mu var?</div>
    <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:12px;">Danışmanınızla iletişime geçin veya destek talebi oluşturun.</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('student.messages') }}" class="btn" style="background:#7c3aed;color:#fff;padding:8px 18px;font-size:var(--tx-sm);">💬 Danışmana Mesaj</a>
        <a href="/student/tickets" class="btn" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);padding:8px 18px;font-size:var(--tx-sm);">🎫 Destek Talebi</a>
    </div>
</div>
@endif

@endif

{{-- ── Fatura Listesi (StudentPayment kayıtları) ──────────────────────── --}}
@if(isset($invoices) && $invoices->isNotEmpty())
<div class="card" style="margin-top:24px;padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:10px;">
        <span style="font-size:18px;">🧾</span>
        <div>
            <div style="font-size:14px;font-weight:800;color:var(--u-text);">Faturalarım</div>
            <div style="font-size:11px;color:var(--u-muted);">Danışmanlık hizmet faturaları</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:var(--u-bg);">
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Fatura No</th>
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Açıklama</th>
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Tutar</th>
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Vade</th>
                <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.4px;">Durum</th>
            </tr>
        </thead>
        <tbody>
        @foreach($invoices as $inv)
            @php
                $bc = match($inv->status) { 'paid'=>'ok','overdue'=>'danger','cancelled'=>'info',default=>'warn' };
                $bl = match($inv->status) { 'paid'=>'Ödendi','overdue'=>'Vadesi Geçti','cancelled'=>'İptal',default=>'Bekliyor' };
            @endphp
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:12px 14px;">
                    <code style="font-size:11px;background:var(--u-bg);padding:2px 6px;border-radius:4px;font-weight:700;">{{ $inv->invoice_number }}</code>
                </td>
                <td style="padding:12px 14px;color:var(--u-text);">{{ $inv->description }}</td>
                <td style="padding:12px 14px;font-weight:700;color:var(--u-text);">
                    {{ number_format($inv->amount_eur, 2, ',', '.') }} {{ $inv->currency }}
                </td>
                <td style="padding:12px 14px;color:var(--u-muted);font-size:12px;">
                    {{ $inv->due_date->format('d.m.Y') }}
                    @if($inv->paid_at)
                        <br><span style="color:#16a34a;font-size:11px;">Ödendi: {{ $inv->paid_at->format('d.m.Y') }}</span>
                    @endif
                </td>
                <td style="padding:12px 14px;">
                    <span class="badge {{ $bc }}">{{ $bl }}</span>
                </td>
                <td style="padding:12px 14px;">
                    @if(in_array($inv->status, ['pending', 'overdue']) && config('services.stripe.secret'))
                        <a href="{{ route('student.payment.checkout', $inv->id) }}"
                           class="btn"
                           style="background:#635bff;color:#fff;padding:7px 16px;font-size:12px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:600;">
                            💳 Öde
                        </a>
                    @elseif($inv->status === 'paid')
                        <span style="font-size:11px;color:#16a34a;">✓ Tamamlandı</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@endsection
