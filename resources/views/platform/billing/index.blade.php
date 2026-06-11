@extends('platform.layouts.app')

@section('title', 'Faturalama — Platform Owner')

@push('styles')
<style>
    /* Status badge'leri — billing'e ozel */
    .plat-badge-paid      { background: rgba(74,222,128,.16); color: var(--plat-ok); }
    .plat-badge-overdue   { background: rgba(248,113,113,.16); color: var(--plat-danger); }
    .plat-badge-sent      { background: rgba(96,165,250,.16); color: var(--plat-info); }
    .plat-badge-draft     { background: rgba(160,155,181,.16); color: var(--plat-muted); }
    .plat-badge-cancelled { background: rgba(160,155,181,.08); color: var(--plat-muted); text-decoration: line-through; }

    .bill-filter-bar { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 18px; padding: 16px; background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; }
    @media (max-width: 1100px) { .bill-filter-bar { grid-template-columns: 1fr 1fr; } }

    .bill-actions-cell { display: flex; gap: 6px; flex-wrap: nowrap; }
    .bill-actions-cell button, .bill-actions-cell a { padding: 5px 8px; font-size: 11px; }

    .bill-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 100; display: none; align-items: center; justify-content: center; }
    .bill-modal-backdrop.show { display: flex; }
    .bill-modal { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 24px; width: 480px; max-width: calc(100% - 32px); }
    .bill-modal h3 { margin: 0 0 16px; color: #fff; font-size: 16px; font-weight: 800; }
    .bill-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }

    .bill-inline-form { display: inline; margin: 0; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Faturalama</h1>
        <p class="plat-page-sub">Cross-company fatura yönetimi — KDV %19 (DE)</p>
    </div>
    <button type="button" class="plat-btn plat-btn-primary" id="open-generate-modal">
        <x-icon name="plus" size="16" /> Manuel Fatura Oluştur
    </button>
</div>

{{-- KPI ROW --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="file-text" size="12" /> Bu Ay Toplam</div>
        <div class="plat-kpi-value">{{ number_format($monthTotalCount) }}</div>
        <div class="plat-kpi-sub">€{{ number_format($monthTotalAmount, 2, ',', '.') }}</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-ok);">
        <div class="plat-kpi-label" style="color:var(--plat-ok);"><x-icon name="check" size="12" /> Ödenmiş</div>
        <div class="plat-kpi-value">{{ number_format($monthPaidCount) }}</div>
        <div class="plat-kpi-sub">€{{ number_format($monthPaidAmount, 2, ',', '.') }}</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-info);">
        <div class="plat-kpi-label" style="color:var(--plat-info);"><x-icon name="clock" size="12" /> Bekleyen</div>
        <div class="plat-kpi-value">{{ number_format($monthPendingCount) }}</div>
        <div class="plat-kpi-sub">€{{ number_format($monthPendingAmount, 2, ',', '.') }} ({{ $draftCount }} taslak)</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-danger);">
        <div class="plat-kpi-label" style="color:var(--plat-danger);"><x-icon name="circle-alert" size="12" /> Gecikmiş</div>
        <div class="plat-kpi-value">{{ number_format($overdueCount) }}</div>
        <div class="plat-kpi-sub">€{{ number_format($overdueAmount, 2, ',', '.') }} tüm dönemler</div>
    </div>
</div>

{{-- FILTRE --}}
<form method="GET" action="{{ route('platform.billing') }}" class="bill-filter-bar">
    <div>
        <label class="plat-form-label">Durum</label>
        <select name="status" class="plat-select">
            <option value="">Hepsi</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" {{ $filters['status'] === $s ? 'selected' : '' }}>
                    {{ ucfirst($s) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="plat-form-label">Şirket</label>
        <select name="company_id" class="plat-select">
            <option value="0">Hepsi</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ (int) $filters['company_id'] === (int) $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="plat-form-label">Başlangıç</label>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="plat-input">
    </div>
    <div>
        <label class="plat-form-label">Bitiş</label>
        <input type="date" name="to" value="{{ $filters['to'] }}" class="plat-input">
    </div>
    <div style="display:flex;gap:6px;">
        <button type="submit" class="plat-btn plat-btn-primary">
            <x-icon name="filter" size="14" /> Filtrele
        </button>
        <a href="{{ route('platform.billing') }}" class="plat-btn plat-btn-ghost" title="Temizle">
            <x-icon name="refresh-cw" size="14" />
        </a>
    </div>
</form>

{{-- TABLO --}}
<div class="plat-card" style="padding: 0; overflow: hidden;">
    <table class="plat-table">
        <thead>
            <tr>
                <th>Fatura No</th>
                <th>Şirket</th>
                <th>Period</th>
                <th>Tier</th>
                <th style="text-align:right;">Toplam</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th style="text-align:right;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
                <tr>
                    <td>
                        <a href="{{ route('platform.billing.show', $inv) }}" style="font-weight:700;color:#fff;">
                            {{ $inv->invoice_number }}
                        </a>
                    </td>
                    <td>
                        @if($inv->company)
                            <a href="{{ route('platform.companies.show', $inv->company_id) }}">{{ $inv->company->name }}</a>
                        @else
                            <span style="color:var(--plat-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size:12px;">
                            {{ optional($inv->period_start)->format('d.m.Y') }}
                            <span style="color:var(--plat-muted);">→</span>
                            {{ optional($inv->period_end)->format('d.m.Y') }}
                        </span>
                    </td>
                    <td><span class="plat-badge plat-badge-{{ $inv->tier }}">{{ ucfirst($inv->tier) }}</span></td>
                    <td style="text-align:right;font-weight:700;color:#fff;">€{{ number_format((float)$inv->total_eur, 2, ',', '.') }}</td>
                    <td><span class="plat-badge {{ $inv->statusBadgeClass() }}">{{ $inv->statusLabel() }}</span></td>
                    <td style="font-size:11px;color:var(--plat-muted);">
                        @if($inv->paid_at)
                            Ödendi: {{ $inv->paid_at->format('d.m.Y') }}
                        @elseif($inv->sent_at)
                            Gönderildi: {{ $inv->sent_at->format('d.m.Y') }}
                        @else
                            Oluşturuldu: {{ $inv->created_at->format('d.m.Y') }}
                        @endif
                    </td>
                    <td class="bill-actions-cell" style="justify-content:flex-end;">
                        <a href="{{ route('platform.billing.show', $inv) }}" class="plat-btn plat-btn-ghost plat-btn-sm" title="Detay">
                            <x-icon name="eye" size="12" />
                        </a>
                        <a href="{{ route('platform.billing.pdf', $inv) }}" class="plat-btn plat-btn-ghost plat-btn-sm" title="PDF indir">
                            <x-icon name="download" size="12" />
                        </a>
                        @if(in_array($inv->status, ['draft', 'overdue'], true))
                            <form method="POST" action="{{ route('platform.billing.send', $inv) }}" class="bill-inline-form">
                                @csrf
                                <button type="submit" class="plat-btn plat-btn-ghost plat-btn-sm" title="Gönder">
                                    <x-icon name="send" size="12" />
                                </button>
                            </form>
                        @endif
                        @if($inv->status !== 'paid' && $inv->status !== 'cancelled')
                            <form method="POST" action="{{ route('platform.billing.mark-paid', $inv) }}" class="bill-inline-form">
                                @csrf
                                <button type="submit" class="plat-btn plat-btn-ghost plat-btn-sm" title="Ödendi olarak işaretle">
                                    <x-icon name="check" size="12" />
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--plat-muted);">
                        Filtreye uyan fatura yok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $invoices->onEachSide(1)->links() }}
</div>

{{-- GENERATE MODAL --}}
<div class="bill-modal-backdrop" id="generate-modal-backdrop">
    <div class="bill-modal">
        <h3><x-icon name="plus" size="16" /> Manuel Fatura Oluştur</h3>
        <form method="POST" action="{{ route('platform.billing.generate') }}">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label" for="gen-company">Şirket</label>
                <select name="company_id" id="gen-company" class="plat-select" required>
                    <option value="">— seçin —</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="plat-form-group">
                <label class="plat-form-label" for="gen-period">Dönem (Y-m)</label>
                <input type="month" name="period" id="gen-period" class="plat-input" value="{{ now()->format('Y-m') }}">
                <small style="color:var(--plat-muted);font-size:11px;">Boşsa: bu ay</small>
            </div>
            <div class="bill-modal-actions">
                <button type="button" class="plat-btn plat-btn-ghost" id="close-generate-modal">İptal</button>
                <button type="submit" class="plat-btn plat-btn-primary">
                    <x-icon name="check" size="14" /> Oluştur
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var backdrop = document.getElementById('generate-modal-backdrop');
    var openBtn  = document.getElementById('open-generate-modal');
    var closeBtn = document.getElementById('close-generate-modal');
    if (openBtn)  openBtn.addEventListener('click', function(){ backdrop.classList.add('show'); });
    if (closeBtn) closeBtn.addEventListener('click', function(){ backdrop.classList.remove('show'); });
    if (backdrop) backdrop.addEventListener('click', function(e){
        if (e.target === backdrop) backdrop.classList.remove('show');
    });
})();
</script>
@endpush
