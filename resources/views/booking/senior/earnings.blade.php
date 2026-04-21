@extends('senior.layouts.app')
@section('title','Kazançlarım')
@section('page_title','💰 Kazançlarım')

@section('content')
<style>
.se-wrap { max-width:1000px; margin:20px auto; padding:0 16px; }
.se-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px; margin-bottom:16px; }
.se-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.se-stat { background:linear-gradient(135deg,#f8fafc,#f1f5f9); border:1px solid #e2e8f0; border-radius:10px; padding:16px; }
.se-stat .label { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
.se-stat .value { font-size:26px; color:#0f172a; font-weight:800; margin-top:4px; }
.se-stat .sub { font-size:11px; color:#94a3b8; margin-top:2px; }
.se-table { width:100%; border-collapse:collapse; font-size:13px; }
.se-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#64748b; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.se-table td { padding:10px; border-bottom:1px solid #f1f5f9; }
.se-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.se-badge.green { background:#dcfce7; color:#166534; }
.se-badge.yellow { background:#fef3c7; color:#92400e; }
.se-badge.red { background:#fee2e2; color:#991b1b; }
.se-badge.blue { background:#dbeafe; color:#1e40af; }
.se-info { background:#eff6ff; border-left:3px solid #3b82f6; border-radius:6px; padding:10px 14px; font-size:12px; color:#1e3a8a; line-height:1.6; margin-bottom:14px; }
</style>

<div class="se-wrap">

    <div class="se-info">
        ℹ️ <strong>Ödeme modülü kurulum aşamasında.</strong> Tüm booking'ler ücretsiz olarak akıyor; kazanç kaydı tutulsa da şu an tüm değerler €0 olarak görünüyor. Muhasebeci onayı ve Stripe entegrasyonu sonrası gerçek tutarlar burada oluşur.
    </div>

    {{-- KPI'lar --}}
    <div class="se-card">
        <div class="se-stats">
            <div class="se-stat">
                <div class="label">Bu Ay Görüşme</div>
                <div class="value">{{ $monthCount }}</div>
                <div class="sub">€{{ number_format($monthEarningsCents / 100, 2, ',', '.') }} kazanç</div>
            </div>
            <div class="se-stat">
                <div class="label">Bu Yıl Toplam</div>
                <div class="value">{{ $yearCount }}</div>
                <div class="sub">€{{ number_format($yearEarningsCents / 100, 2, ',', '.') }} kazanç</div>
            </div>
            <div class="se-stat">
                <div class="label">Ödenen (tüm zaman)</div>
                <div class="value">€{{ number_format($lifetimePaidCents / 100, 2, ',', '.') }}</div>
                <div class="sub">Payout geçmişi</div>
            </div>
            <div class="se-stat">
                <div class="label">Bekleyen Payout</div>
                <div class="value">€{{ number_format($lifetimePendingCents / 100, 2, ',', '.') }}</div>
                <div class="sub">Bir sonraki ödemeye katılacak</div>
            </div>
        </div>
    </div>

    {{-- Son kazanç kayıtları --}}
    <div class="se-card">
        <h2 style="margin:0 0 4px;font-size:16px;">🧾 Son Görüşme Kazançları</h2>
        <p style="margin:0 0 14px;font-size:12px;color:#64748b;">Her onaylı booking için kayıt tutulur. Ödeme yok iken tutarlar 0.</p>

        <table class="se-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Invitee</th>
                    <th>Net</th>
                    <th>Komisyon</th>
                    <th>Sana kalan</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentEarnings as $e)
                    <tr>
                        <td>{{ optional($e->recorded_at)->format('d.m.Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($e->publicBooking)
                                {{ $e->publicBooking->invitee_name }}<br>
                                <span style="font-size:11px;color:#64748b;">{{ $e->publicBooking->invitee_email }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>€{{ number_format($e->amount_net_cents / 100, 2, ',', '.') }}</td>
                        <td>€{{ number_format($e->commission_cents / 100, 2, ',', '.') }} <span style="color:#94a3b8;">(%{{ number_format((float)$e->commission_pct_applied, 0) }})</span></td>
                        <td><strong>€{{ number_format($e->senior_payout_cents / 100, 2, ',', '.') }}</strong></td>
                        <td>
                            @switch($e->status)
                                @case('recorded') <span class="se-badge blue">Kayıtlı</span> @break
                                @case('paid_out') <span class="se-badge green">Ödendi</span> @break
                                @case('refunded') <span class="se-badge red">İade</span> @break
                                @case('voided')   <span class="se-badge yellow">İptal</span> @break
                                @default <span class="se-badge">{{ $e->status }}</span>
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">Henüz kazanç kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Payout geçmişi --}}
    <div class="se-card">
        <h2 style="margin:0 0 4px;font-size:16px;">💸 Payout Geçmişi</h2>
        <p style="margin:0 0 14px;font-size:12px;color:#64748b;">Ay bazlı payout'ların. Her ayın 5'inde otomatik oluşur.</p>

        <table class="se-table">
            <thead>
                <tr>
                    <th>Dönem</th>
                    <th>Tutar</th>
                    <th>Yöntem</th>
                    <th>Durum</th>
                    <th>Ödendi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payouts as $p)
                    <tr>
                        <td>{{ $p->period_start->format('d.m.Y') }} – {{ $p->period_end->format('d.m.Y') }}</td>
                        <td><strong>€{{ number_format($p->amount_cents / 100, 2, ',', '.') }}</strong> {{ $p->currency }}</td>
                        <td>{{ $p->method }}</td>
                        <td>
                            @switch($p->status)
                                @case('pending')    <span class="se-badge yellow">Bekliyor</span> @break
                                @case('processing') <span class="se-badge blue">İşleniyor</span> @break
                                @case('paid')       <span class="se-badge green">Ödendi</span> @break
                                @case('failed')     <span class="se-badge red">Başarısız</span> @break
                                @default <span class="se-badge">{{ $p->status }}</span>
                            @endswitch
                        </td>
                        <td>{{ optional($p->paid_at)->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">Henüz payout yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
