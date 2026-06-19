@extends('platform.layouts.app')

@section('title', $invoice->invoice_number . ' — Fatura Detay')

@push('styles')
<style>
    .plat-badge-paid      { background: rgba(74,222,128,.16); color: var(--plat-ok); }
    .plat-badge-overdue   { background: rgba(248,113,113,.16); color: var(--plat-danger); }
    .plat-badge-sent      { background: rgba(96,165,250,.16); color: var(--plat-info); }
    .plat-badge-draft     { background: rgba(160,155,181,.16); color: var(--plat-muted); }
    .plat-badge-cancelled { background: rgba(160,155,181,.08); color: var(--plat-muted); text-decoration: line-through; }

    .inv-header { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: flex-start; }
    .inv-amount-summary { background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 10px; padding: 18px 20px; min-width: 280px; }
    .inv-summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
    .inv-summary-row.total { border-top: 1px solid var(--plat-border); padding-top: 12px; margin-top: 12px; font-size: 16px; font-weight: 800; color: #fff; }

    .inv-timeline { position: relative; padding-left: 18px; }
    .inv-timeline-item { position: relative; padding: 8px 0 8px 12px; border-left: 2px solid var(--plat-border); }
    .inv-timeline-item.active { border-left-color: var(--plat-accent); }
    .inv-timeline-item::before { content: ""; position: absolute; left: -7px; top: 12px; width: 12px; height: 12px; border-radius: 50%; background: var(--plat-border); }
    .inv-timeline-item.active::before { background: var(--plat-accent); }
    .inv-timeline-label { font-size: 13px; font-weight: 700; color: #fff; }
    .inv-timeline-sub { font-size: 11px; color: var(--plat-muted); margin-top: 2px; }

    .inv-pm-card { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 8px; margin-bottom: 8px; }
    .inv-pm-card.default { border-color: var(--plat-accent); }
    .inv-pm-label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--plat-text); }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">
            <x-icon name="file-text" size="20" /> {{ $invoice->invoice_number }}
            <span class="plat-badge {{ $invoice->statusBadgeClass() }}" style="margin-left:8px;">{{ $invoice->statusLabel() }}</span>
        </h1>
        <p class="plat-page-sub">
            <a href="{{ route('platform.billing') }}"><x-icon name="arrow-left" size="12" /> Tüm Faturalar</a>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('platform.billing.pdf', $invoice) }}" class="plat-btn plat-btn-ghost">
            <x-icon name="download" size="14" /> PDF İndir
        </a>
        @if(in_array($invoice->status, ['draft', 'overdue'], true))
            <form method="POST" action="{{ route('platform.billing.send', $invoice) }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="send" size="14" /> E-posta Gönder
                </button>
            </form>
        @endif
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
            <form method="POST" action="{{ route('platform.billing.mark-paid', $invoice) }}" style="margin:0;">
                @csrf
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="check" size="14" /> Ödendi İşaretle
                </button>
            </form>
        @endif

        {{-- İPTAL ET — gonderilmis/gecikmis fatura void edilir (audit korunur) --}}
        @if(in_array($invoice->status, ['sent', 'overdue'], true))
            <form method="POST" action="{{ route('platform.billing.cancel', $invoice) }}" style="margin:0;"
                  onsubmit="return confirm('{{ $invoice->invoice_number }} faturası iptal edilecek (cancelled). Devam edilsin mi?');">
                @csrf
                <button type="submit" class="plat-btn plat-btn-ghost">
                    <x-icon name="x" size="14" /> İptal Et
                </button>
            </form>
        @endif

        {{-- DÜZENLE — sadece taslak fatura duzenlenebilir --}}
        @if($invoice->status === 'draft')
            <a href="{{ route('platform.billing.edit', $invoice) }}" class="plat-btn plat-btn-ghost">
                <x-icon name="pencil" size="14" /> Düzenle
            </a>
        @endif

        {{-- SİL — sadece taslak/iptal faturalar tamamen silinebilir --}}
        @if(in_array($invoice->status, ['draft', 'cancelled'], true))
            <form method="POST" action="{{ route('platform.billing.destroy', $invoice) }}" style="margin:0;"
                  onsubmit="return confirm('{{ $invoice->invoice_number }} faturası KALICI olarak silinecek (promo varsa geri alınır). Bu işlem geri alınamaz. Devam?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="plat-btn plat-btn-ghost" style="color:#dc2626;border-color:#dc2626;">
                    <x-icon name="trash" size="14" /> Sil
                </button>
            </form>
        @endif
    </div>
</div>

<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">

    {{-- SOL: FATURA INFO + COMPANY --}}
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="building-2" size="16" /> Şirket Bilgisi</h3>
        @if($company)
            <div style="margin-bottom:12px;">
                <a href="{{ route('platform.companies.show', $company->id) }}" style="font-size:18px;font-weight:800;color:#fff;">
                    {{ $company->name }}
                </a>
                <div style="font-size:12px;color:var(--plat-muted);margin-top:2px;">{{ $company->code }}</div>
            </div>
            <table style="width:100%;font-size:13px;">
                <tr>
                    <td style="padding:6px 0;color:var(--plat-muted);">Tier:</td>
                    <td style="padding:6px 0;"><span class="plat-badge plat-badge-{{ $company->subscription_tier }}">{{ $tierLabel }}</span></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:var(--plat-muted);">Billing E-posta:</td>
                    <td style="padding:6px 0;">
                        @if($company->billing_email)
                            <a href="mailto:{{ $company->billing_email }}">{{ $company->billing_email }}</a>
                        @else
                            <span style="color:var(--plat-danger);">eksik</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:var(--plat-muted);">MRR:</td>
                    <td style="padding:6px 0;">€{{ number_format((float) $company->mrr_eur, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:var(--plat-muted);">Aktif:</td>
                    <td style="padding:6px 0;">
                        <span class="plat-badge plat-badge-{{ $company->is_active ? 'active' : 'inactive' }}">
                            {{ $company->is_active ? 'Aktif' : 'Pasif' }}
                        </span>
                    </td>
                </tr>
            </table>
        @else
            <p class="plat-card-sub">Şirket bilgisi bulunamadı.</p>
        @endif
    </div>

    {{-- SAG: TUTAR OZETI --}}
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="dollar-sign" size="16" /> Tutar Özeti</h3>
        <div class="inv-amount-summary">
            <div class="inv-summary-row">
                <span style="color:var(--plat-muted);">{{ $tierLabel }} ({{ optional($invoice->period_start)->format('m/Y') }})</span>
                <span>€{{ number_format((float) $invoice->amount_eur, 2, ',', '.') }}</span>
            </div>
            <div class="inv-summary-row">
                <span style="color:var(--plat-muted);">KDV (%{{ number_format((float) $invoice->tax_rate_pct, 0) }})</span>
                <span>€{{ number_format((float) $invoice->tax_amount_eur, 2, ',', '.') }}</span>
            </div>
            <div class="inv-summary-row total">
                <span>Toplam</span>
                <span>€{{ number_format((float) $invoice->total_eur, 2, ',', '.') }}</span>
            </div>
        </div>
        <table style="width:100%;font-size:12px;color:var(--plat-muted);margin-top:14px;">
            <tr>
                <td style="padding:4px 0;">Dönem Başlangıç:</td>
                <td style="text-align:right;color:var(--plat-text);">{{ optional($invoice->period_start)->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td style="padding:4px 0;">Dönem Bitiş:</td>
                <td style="text-align:right;color:var(--plat-text);">{{ optional($invoice->period_end)->format('d.m.Y') }}</td>
            </tr>
            @if($invoice->stripe_invoice_id)
                <tr>
                    <td style="padding:4px 0;">Stripe Invoice:</td>
                    <td style="text-align:right;color:var(--plat-text);font-family:monospace;font-size:11px;">{{ $invoice->stripe_invoice_id }}</td>
                </tr>
            @endif
        </table>
    </div>
</div>

<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">

    {{-- TIMELINE --}}
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="clock" size="16" /> Durum Geçmişi</h3>
        <div class="inv-timeline" style="margin-top:14px;">
            <div class="inv-timeline-item active">
                <div class="inv-timeline-label">Oluşturuldu (Taslak)</div>
                <div class="inv-timeline-sub">{{ $invoice->created_at->format('d.m.Y H:i') }}</div>
            </div>
            <div class="inv-timeline-item {{ $invoice->sent_at ? 'active' : '' }}">
                <div class="inv-timeline-label">Gönderildi</div>
                <div class="inv-timeline-sub">
                    {{ $invoice->sent_at ? $invoice->sent_at->format('d.m.Y H:i') : 'Henüz gönderilmedi' }}
                </div>
            </div>
            <div class="inv-timeline-item {{ $invoice->paid_at ? 'active' : '' }}">
                <div class="inv-timeline-label">Ödendi</div>
                <div class="inv-timeline-sub">
                    {{ $invoice->paid_at ? $invoice->paid_at->format('d.m.Y H:i') : 'Bekleniyor' }}
                </div>
            </div>
            @if($invoice->status === 'overdue')
                <div class="inv-timeline-item active" style="border-left-color:var(--plat-danger);">
                    <div class="inv-timeline-label" style="color:var(--plat-danger);">Gecikti</div>
                    <div class="inv-timeline-sub">30 günden uzun süredir ödenmedi</div>
                </div>
            @endif
        </div>
    </div>

    {{-- PAYMENT METHODS --}}
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="credit-card" size="16" /> Ödeme Araçları</h3>
        @if($paymentMethods->isEmpty())
            <p class="plat-card-sub" style="margin:14px 0;">Bu şirketin kayıtlı ödeme aracı yok.</p>
        @else
            <div style="margin-top:12px;">
                @foreach($paymentMethods as $pm)
                    <div class="inv-pm-card {{ $pm->is_default ? 'default' : '' }}">
                        <div class="inv-pm-label">
                            <x-icon name="credit-card" size="16" />
                            {{ $pm->label() }}
                            @if($pm->exp_month && $pm->exp_year)
                                <span style="color:var(--plat-muted);font-size:11px;">
                                    ({{ str_pad((string)$pm->exp_month, 2, '0', STR_PAD_LEFT) }}/{{ $pm->exp_year }})
                                </span>
                            @endif
                        </div>
                        @if($pm->is_default)
                            <span class="plat-badge plat-badge-active">Varsayılan</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($invoice->notes)
    <div class="plat-card">
        <h3 class="plat-card-title"><x-icon name="file-text" size="16" /> Notlar</h3>
        <div style="white-space:pre-wrap;color:var(--plat-text);font-size:13px;">{{ $invoice->notes }}</div>
    </div>
@endif

@endsection
