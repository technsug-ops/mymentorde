@extends('manager.layouts.app')

@section('title', 'Ödeme Ayarları')
@section('page_title', 'Senior Ödeme Ayarları')

@push('head')
<style>
.ps-intro { background:rgba(30,64,175,.06); border:1px solid rgba(30,64,175,.18); border-radius:9px; padding:10px 16px; margin-bottom:14px; font-size:12px; color:var(--u-muted); line-height:1.6; }
.ps-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px; padding:22px 24px; max-width:760px; }
.ps-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px 22px; }
@media (max-width:640px) { .ps-grid { grid-template-columns:1fr; } }
.ps-field-label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:6px; }
.ps-field-input { width:100%; padding:9px 12px; font-size:14px; border:1.5px solid var(--border,#cbd5e1); border-radius:7px; background:var(--surface,#fff); }
.ps-help { font-size:11px; color:var(--muted,#64748b); margin-top:5px; line-height:1.5; }
.ps-toggle-row { display:flex; align-items:center; gap:12px; padding:14px 16px; background:var(--bg,#f8fafc); border-radius:8px; }
.ps-toggle { position:relative; display:inline-block; width:46px; height:26px; }
.ps-toggle input { opacity:0; width:0; height:0; }
.ps-toggle-slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:13px; transition:.2s; }
.ps-toggle-slider::before { position:absolute; content:""; height:20px; width:20px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
.ps-toggle input:checked + .ps-toggle-slider { background:#16a34a; }
.ps-toggle input:checked + .ps-toggle-slider::before { transform:translateX(20px); }
.ps-actions { margin-top:22px; display:flex; gap:10px; }
.ps-info-card { margin-top:14px; padding:12px 14px; background:rgba(8,145,178,.07); border-left:3px solid #0891b2; border-radius:6px; font-size:12px; color:#0e7490; }
</style>
@endpush

@section('content')

<div class="ps-intro">
    <strong style="color:var(--u-text);">Senior Ödeme Ayarları:</strong>
    Senior'lara aylık otomatik veya on-demand (talep üzerine) ödeme akışını burada yönetin.
    Sistem, ayın belirlediğin gününde tüm uygun senior'lara Stripe Connect üzerinden transfer yapar.
</div>

@if(session('success'))
<div style="background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.25);border-radius:8px;padding:10px 14px;margin-bottom:12px;color:#15803d;font-size:13px;max-width:760px;">
    <x-icon name="check-circle" size="14" /> {{ session('success') }}
</div>
@endif

<div class="ps-card">
    <form method="POST" action="{{ route('manager.payout-settings.update') }}">
        @csrf
        @method('PUT')

        <div class="ps-grid">
            <div>
                <label class="ps-field-label">Aylık Ödeme Günü</label>
                <input type="number" min="1" max="28" name="payout_day" class="ps-field-input"
                       value="{{ old('payout_day', $settings?->payout_day ?? 5) }}" required>
                <div class="ps-help">Her ayın bu gününde otomatik ödeme komutu çalışır. (1-28 arası)</div>
            </div>

            <div>
                <label class="ps-field-label">Minimum Bakiye Eşiği (EUR)</label>
                <input type="number" step="0.01" min="0" name="payout_minimum_eur" class="ps-field-input"
                       value="{{ old('payout_minimum_eur', $settings?->payout_minimum_eur ?? 100.00) }}" required>
                <div class="ps-help">Bu tutarın altında bakiyesi olan senior'lara ödeme yapılmaz, gelecek aya devreder.</div>
            </div>

            <div>
                <label class="ps-field-label">Para Birimi</label>
                <select name="currency" class="ps-field-input" required>
                    @foreach(['EUR'=>'EUR — Euro', 'USD'=>'USD — Dolar', 'GBP'=>'GBP — Sterlin', 'TRY'=>'TRY — Türk Lirası'] as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency', $settings?->currency ?? 'EUR') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="ps-help">Stripe transfer para birimi.</div>
            </div>

            <div>
                <label class="ps-field-label">Bildirim E-postası</label>
                <input type="email" name="notification_email" class="ps-field-input"
                       value="{{ old('notification_email', $settings?->notification_email ?? '') }}"
                       placeholder="finance@sirket.com">
                <div class="ps-help">Aylık payout raporları bu adrese gönderilir.</div>
            </div>
        </div>

        <div style="margin-top:18px;">
            <label class="ps-field-label" style="margin-bottom:8px;">On-Demand Ödeme</label>
            <label class="ps-toggle-row" style="cursor:pointer;">
                <span class="ps-toggle">
                    <input type="checkbox" name="allow_on_demand" value="1"
                           @checked(old('allow_on_demand', $settings?->allow_on_demand ?? true))>
                    <span class="ps-toggle-slider"></span>
                </span>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--text,#0f172a);">Senior'lar on-demand ödeme talep edebilsin</div>
                    <div style="font-size:11px;color:var(--muted,#64748b);">Ayda en fazla 2 kez, minimum eşik şartıyla.</div>
                </div>
            </label>
        </div>

        <div class="ps-info-card">
            <x-icon name="info" size="13" />
            <strong>Otomatik schedule:</strong> Sistem ayın belirlediğin gününde
            <code style="background:#fff;padding:1px 5px;border-radius:3px;">payouts:run-monthly</code>
            komutunu çalıştırır. Stripe transferi başarısız olursa payout <strong>failed</strong> statüsüne geçer ve
            Ödemeler sayfasından retry edilebilir.
        </div>

        <div class="ps-actions">
            <button type="submit" class="btn-primary btn">
                <x-icon name="save" size="14" /> Kaydet
            </button>
            <a href="{{ route('manager.payouts.index') }}" class="btn alt">
                <x-icon name="list" size="14" /> Ödemeler Listesi
            </a>
        </div>
    </form>
</div>

@endsection
