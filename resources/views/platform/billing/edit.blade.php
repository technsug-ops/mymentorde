@extends('platform.layouts.app')

@section('title', $invoice->invoice_number . ' — Fatura Düzenle')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
    <h1 style="margin:0;font-size:24px;font-weight:800;color:#fff;">
        <x-icon name="pencil" size="20" /> {{ $invoice->invoice_number }} — Düzenle
    </h1>
    <span class="plat-badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
</div>
<a href="{{ route('platform.billing.show', $invoice) }}" style="color:var(--plat-muted);font-size:13px;">← Fatura Detayına Dön</a>

@if($errors->any())
    <div class="plat-alert plat-alert-danger" style="margin-top:16px;">
        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    </div>
@endif

<div class="plat-card" style="margin-top:20px;max-width:560px;">
    <h3 class="plat-card-title"><x-icon name="building-2" size="16" /> {{ $invoice->company?->name }} — {{ $invoice->tier }}</h3>
    <p style="color:var(--plat-muted);font-size:12px;margin:-4px 0 16px;">
        Dönem: {{ $invoice->period_start?->format('d.m.Y') }} — {{ $invoice->period_end?->format('d.m.Y') }}.
        Sadece taslak fatura düzenlenebilir; KDV tutarı ve toplam otomatik yeniden hesaplanır.
    </p>

    <form method="POST" action="{{ route('platform.billing.update', $invoice) }}">
        @csrf
        @method('PUT')

        <div class="plat-form-group">
            <label class="plat-form-label" for="amount_eur">Tutar (€, KDV hariç)</label>
            <input type="number" step="0.01" min="0" name="amount_eur" id="amount_eur" class="plat-input"
                   value="{{ old('amount_eur', $invoice->amount_eur) }}" required>
        </div>

        <div class="plat-form-group">
            <label class="plat-form-label" for="tax_rate_pct">KDV Oranı (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="tax_rate_pct" id="tax_rate_pct" class="plat-input"
                   value="{{ old('tax_rate_pct', $invoice->tax_rate_pct) }}" required>
        </div>

        <div class="plat-form-group">
            <label class="plat-form-label" for="notes">Notlar (opsiyonel)</label>
            <textarea name="notes" id="notes" class="plat-input" rows="3" maxlength="2000">{{ old('notes', $invoice->notes) }}</textarea>
        </div>

        <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="submit" class="plat-btn plat-btn-primary">
                <x-icon name="check" size="14" /> Kaydet
            </button>
            <a href="{{ route('platform.billing.show', $invoice) }}" class="plat-btn plat-btn-ghost">İptal</a>
        </div>
    </form>
</div>

@endsection
