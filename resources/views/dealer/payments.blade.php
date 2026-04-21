@extends('dealer.layouts.app')

@section('title', 'Ödemeler')
@section('page_title', 'Ödemeler')
@section('page_subtitle', 'Banka hesapları, ödeme talepleri ve geçmiş')

@push('head')
<style>
/* KPI */
.pay-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .pay-kpi-strip { grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px){ .pay-kpi-strip { grid-template-columns:1fr; } }

.pay-kpi {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-top:3px solid var(--border,#e2e8f0);
    border-radius:12px;
    padding:16px 18px;
}
.pay-kpi.balance { border-top-color:#16a34a; }
.pay-kpi.accounts{ border-top-color:#0891b2; }
.pay-kpi.history { border-top-color:#7c3aed; }
.pay-kpi-label   { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:6px; }
.pay-kpi-val     { font-size:26px; font-weight:900; color:var(--text,#0f172a); line-height:1; }
.pay-kpi-sub     { font-size:11px; color:var(--muted,#64748b); margin-top:4px; }

/* Shared card */
.pay-card {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:12px;
    overflow:hidden;
    margin-bottom:16px;
}
.pay-card-head {
    padding:14px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    flex-wrap:wrap;
}
.pay-card-head h3 { margin:0; font-size:14px; font-weight:700; }
.pay-card-body { padding:20px; }

/* Bank account item */
.pay-acc-item {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
    padding:14px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    transition:background .12s;
}
.pay-acc-item:last-of-type { border-bottom:none; }
.pay-acc-item:hover { background:var(--bg,#f8fafc); }
.pay-acc-bank  { font-size:14px; font-weight:700; color:var(--text,#0f172a); margin-bottom:4px; display:flex;align-items:center;gap:8px; }
.pay-acc-iban  { font-size:13px; color:var(--muted,#64748b); font-family:monospace; }
.pay-acc-holder{ font-size:12px; color:var(--muted,#64748b); margin-top:2px; }

/* Add account details */
.pay-add-wrap { padding:0 20px 20px; }
.pay-add-details summary {
    display:inline-flex; align-items:center; gap:6px;
    cursor:pointer; list-style:none;
    padding:9px 18px; border-radius:8px;
    font-size:13px; font-weight:600;
    border:1.5px dashed var(--c-accent,#16a34a);
    color:var(--c-accent,#16a34a);
    transition:all .15s;
    margin-bottom:0;
}
.pay-add-details summary:hover { background:rgba(22,163,74,.06); }
.pay-add-details[open] summary { background:rgba(22,163,74,.08); }
.pay-add-form {
    margin-top:16px;
    padding:20px;
    background:var(--bg,#f8fafc);
    border-radius:10px;
    border:1px solid var(--border,#e2e8f0);
}
.pay-field { margin-bottom:14px; }
.pay-field label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:6px; }
.pay-field input, .pay-field select {
    width:100%; box-sizing:border-box;
    border:1.5px solid var(--border,#e2e8f0);
    border-radius:8px; padding:10px 12px;
    font-size:13px; color:var(--text,#0f172a);
    background:var(--surface,#fff);
    transition:border-color .15s, box-shadow .15s;
}
.pay-field input:focus, .pay-field select:focus {
    outline:none; border-color:#16a34a;
    box-shadow:0 0 0 3px rgba(22,163,74,.12);
}
.pay-field .pay-err { font-size:12px; color:var(--c-danger,#dc2626); margin-top:4px; }

.pay-check-row { display:flex; align-items:center; gap:8px; cursor:pointer; padding:12px 0 0; }
.pay-check-row input[type=checkbox] { width:16px;height:16px;accent-color:#16a34a;flex-shrink:0; }
.pay-check-row span { font-size:13px; }

/* Request card */
.pay-req-banner {
    margin:0 20px 16px;
    padding:14px 16px;
    background:rgba(22,163,74,.06);
    border:1px solid rgba(22,163,74,.18);
    border-radius:10px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:8px;
    font-size:13px;
}
.pay-req-banner strong { color:#15803d; font-size:16px; }
.pay-req-banner .pay-min { font-size:11px; color:var(--muted,#64748b); }

.pay-req-insufficient {
    margin:0 20px 20px;
    padding:14px 16px;
    background:rgba(220,38,38,.05);
    border:1px solid rgba(220,38,38,.15);
    border-radius:10px;
    font-size:13px;
    color:var(--c-danger,#dc2626);
}

/* History list */
.pay-hist-item {
    padding:14px 20px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
    transition:background .12s;
}
.pay-hist-item:last-child { border-bottom:none; }
.pay-hist-item:hover { background:var(--bg,#f8fafc); }
.pay-hist-amount { font-size:18px; font-weight:800; color:var(--text,#0f172a); margin-bottom:4px; }
.pay-hist-bank   { font-size:12px; color:var(--muted,#64748b); font-family:monospace; }
.pay-hist-meta   { font-size:11px; color:var(--muted,#64748b); margin-top:4px; display:flex; gap:10px; flex-wrap:wrap; }
.pay-hist-right  { display:flex; flex-direction:column; align-items:flex-end; gap:6px; }

.pay-empty { padding:36px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }

/* Badge */
.pay-badge { display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; }
.pay-badge.ok      { background:rgba(22,163,74,.12);  color:#15803d; }
.pay-badge.info    { background:rgba(8,145,178,.12);   color:#0e7490; }
.pay-badge.warn    { background:rgba(217,119,6,.12);   color:#b45309; }
.pay-badge.danger  { background:rgba(220,38,38,.1);    color:#b91c1c; }
.pay-badge.default { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }

/* Guide */
.pay-guide { background:var(--bg,#f1f5f9); border:1px solid var(--border,#e2e8f0); border-radius:12px; padding:16px 20px; }
.pay-guide-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px; }
.pay-guide ul { margin:0;padding-left:18px; }
.pay-guide li { font-size:13px;color:var(--muted,#64748b);margin-bottom:6px; }
</style>
@endpush

@section('content')

@include('partials.manager-hero', [
    'label' => 'Ödeme Yönetimi',
    'title' => 'Ödemelerim',
    'sub'   => 'Birikmiş komisyon, bekleyen talepler ve net kullanılabilir tutar. Ödeme talebini buradan başlat, hesaplarını yönet.',
    'icon'  => '💳',
    'bg'    => 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?w=1400&q=80',
    'tone'  => 'teal',
    'stats' => [
        ['icon' => '💰', 'text' => '€' . number_format($totalPending ?? 0, 0, ',', '.') . ' birikmiş'],
        ['icon' => '⏳', 'text' => '€' . number_format($pendingRequestTotal ?? 0, 0, ',', '.') . ' bekleyen'],
        ['icon' => '✅', 'text' => '€' . number_format($netAvailable ?? 0, 0, ',', '.') . ' kullanılabilir'],
        ['icon' => '🏦', 'text' => ($accounts->count() ?? 0) . ' hesap'],
    ],
])

{{-- KPI --}}
<div class="pay-kpi-strip">
    <div class="pay-kpi balance">
        <div class="pay-kpi-label">Brüt Kazanım</div>
        <div class="pay-kpi-val">{{ number_format($totalPending, 2, ',', '.') }}</div>
        <div class="pay-kpi-sub">EUR toplam birikmiş</div>
    </div>
    <div class="pay-kpi" style="border-top-color:#d97706;">
        <div class="pay-kpi-label">Bekleyen Talep</div>
        <div class="pay-kpi-val" style="{{ ($pendingRequestTotal ?? 0) > 0 ? 'color:#b45309;' : '' }}">
            {{ number_format($pendingRequestTotal ?? 0, 2, ',', '.') }}
        </div>
        <div class="pay-kpi-sub">EUR · işlemde</div>
    </div>
    <div class="pay-kpi" style="border-top-color:#16a34a;">
        <div class="pay-kpi-label">Net Kullanılabilir</div>
        <div class="pay-kpi-val" style="color:#15803d;">{{ number_format($netAvailable ?? 0, 2, ',', '.') }}</div>
        <div class="pay-kpi-sub">
            EUR · min. 100 EUR talep
            @if(($bonusAdd ?? 0) > 0)
                <br><span style="color:#16a34a;font-weight:600;">+{{ number_format($bonusAdd, 2, ',', '.') }} € bonus dahil</span>
            @endif
        </div>
    </div>
    <div class="pay-kpi accounts">
        <div class="pay-kpi-label">Kayıtlı Hesap</div>
        <div class="pay-kpi-val">{{ $accounts->count() }}</div>
        <div class="pay-kpi-sub">banka hesabı</div>
    </div>
</div>

{{-- Bonus durumu kartı --}}
@if(!empty($bonus) && ($bonus['status'] ?? 'locked') !== 'unlocked')
    @php $bs = $bonus['status'] ?? 'locked'; @endphp
    <div style="background:{{ $bs === 'pending' ? '#dbeafe' : '#fef3c7' }};border:1px solid {{ $bs === 'pending' ? '#93c5fd' : '#fbbf24' }};border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:13px;">
        <span style="font-size:22px;">{{ $bs === 'pending' ? '⏳' : '🔒' }}</span>
        <div>
            <strong>Hoş Geldin Bonusu: {{ number_format((float) ($bonus['amount'] ?? 100), 2, ',', '.') }} €</strong>
            — Durum: <strong>{{ $bonus['label'] ?? '-' }}</strong>
            @if($bs === 'locked')
                · İlk öğrenci yönlendirmenizi yapın.
            @else
                · Öğrenciniz sözleşme imzalayıp ödeme yapınca çekilebilir olur.
            @endif
        </div>
    </div>
@endif

<div class="grid2" style="align-items:start;">

{{-- Sol: Hesap Bilgilerim --}}
<div>
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>🏦 Hesap Bilgilerim</h3>
            @if($accounts->isNotEmpty())
                <span class="pay-badge default">{{ $accounts->count() }} hesap</span>
            @endif
        </div>

        @forelse($accounts as $acc)
        <div class="pay-acc-item">
            <div>
                <div class="pay-acc-bank">
                    {{ $acc->bank_name }}
                    @if($acc->is_default)<span class="pay-badge ok">Varsayılan</span>@endif
                </div>
                <div class="pay-acc-iban">{{ $acc->iban }}</div>
                <div class="pay-acc-holder">{{ $acc->account_holder }}</div>
            </div>
            <form method="POST" action="{{ route('dealer.payments.accounts.delete', $acc->id) }}"
                  onsubmit="return confirm('Bu hesabı silmek istiyor musun?')">
                @csrf @method('DELETE')
                <button class="btn" type="submit"
                        style="font-size:var(--tx-xs);padding:6px 12px;color:var(--c-danger,#dc2626);border-color:rgba(220,38,38,.3);">
                    Sil
                </button>
            </form>
        </div>
        @empty
        <div class="pay-empty">Henüz kayıtlı banka hesabı yok.</div>
        @endforelse

        <div class="pay-add-wrap" style="padding-top:16px;">
            <details class="pay-add-details">
                <summary>+ Yeni Hesap Ekle</summary>
                <form method="POST" action="{{ route('dealer.payments.accounts.store') }}" class="pay-add-form">
                    @csrf
                    <div class="grid2" style="margin-bottom:0;">
                        <div class="pay-field">
                            <label>Banka Adı *</label>
                            <input name="bank_name" value="{{ old('bank_name') }}" placeholder="Örn: Ziraat Bankası" required>
                            @error('bank_name')<div class="pay-err">{{ $message }}</div>@enderror
                        </div>
                        <div class="pay-field">
                            <label>IBAN *</label>
                            <input name="iban" value="{{ old('iban') }}" placeholder="TR00 0000 0000 0000 0000 0000 00" required>
                            @error('iban')<div class="pay-err">{{ $message }}</div>@enderror
                        </div>
                        <div class="pay-field" style="margin-bottom:0;">
                            <label>Hesap Sahibi *</label>
                            <input name="account_holder" value="{{ old('account_holder') }}" required>
                            @error('account_holder')<div class="pay-err">{{ $message }}</div>@enderror
                        </div>
                        <div style="display:flex;align-items:center;">
                            <label class="pay-check-row">
                                <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
                                <span>Varsayılan hesap olarak ayarla</span>
                            </label>
                        </div>
                    </div>
                    <button class="btn btn-primary" style="margin-top:14px;">Hesabı Kaydet</button>
                </form>
            </details>
        </div>
    </div>
</div>

{{-- Sag: Ödeme Talebi + Geçmiş --}}
<div>
    @if($accounts->isNotEmpty())
    <div class="pay-card" style="margin-bottom:16px;">
        <div class="pay-card-head">
            <h3>💸 Ödeme Talebi</h3>
        </div>
        <div class="pay-req-banner">
            <div>
                <strong>{{ number_format($netAvailable ?? $totalPending, 2, ',', '.') }} EUR</strong>
                <span> net kullanılabilir bakiye</span>
                @if(($pendingRequestTotal ?? 0) > 0)
                    <br><small style="color:#b45309;">{{ number_format($pendingRequestTotal, 2, ',', '.') }} EUR işlemde (bekleyen talep)</small>
                @endif
            </div>
            <span class="pay-min">Min. 100 EUR talep edilebilir</span>
        </div>
        @if(($netAvailable ?? $totalPending) >= 100)
        <div class="pay-card-body" style="padding-top:0;">
            <form method="POST" action="{{ route('dealer.payments.request') }}">
                @csrf
                @php $availableForRequest = $netAvailable ?? $totalPending; @endphp
                <div class="grid2" style="margin-bottom:0;">
                    <div class="pay-field">
                        <label>Talep Tutarı (EUR) *</label>
                        <input type="number" name="amount"
                               min="100" max="{{ $availableForRequest }}" step="0.01"
                               value="{{ old('amount', (int) $availableForRequest) }}" required>
                        @error('amount')<div class="pay-err">{{ $message }}</div>@enderror
                    </div>
                    <div class="pay-field">
                        <label>Ödeme Hesabı *</label>
                        <select name="payout_account_id" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected($acc->is_default)>
                                    {{ $acc->bank_name }} — {{ $acc->iban }}
                                </option>
                            @endforeach
                        </select>
                        @error('payout_account_id')<div class="pay-err">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button class="btn btn-primary" style="width:100%;justify-content:center;">Ödeme Talep Et →</button>
            </form>
        </div>
        @else
        <div class="pay-req-insufficient">
            Ödeme talebi için minimum <strong>100 EUR</strong> net bakiye gerekmektedir.
            Net kullanılabilir: {{ number_format($netAvailable ?? $totalPending, 2, ',', '.') }} EUR
            @if(($pendingRequestTotal ?? 0) > 0)
                <br>({{ number_format($pendingRequestTotal, 2, ',', '.') }} EUR bekleyen talepte)
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Ödeme Geçmişi --}}
    <div class="pay-card">
        <div class="pay-card-head">
            <h3>📋 Ödeme Geçmişi</h3>
            @if($payoutRequests->isNotEmpty())
                <span class="pay-badge default">{{ $payoutRequests->count() }} talep</span>
            @endif
        </div>
        @forelse($payoutRequests as $req)
            @php
                $statusMap = [
                    'requested' => ['label' => 'Talep Edildi', 'cls' => 'warn'],
                    'approved'  => ['label' => 'Onaylandı',   'cls' => 'info'],
                    'paid'      => ['label' => 'Ödendi',      'cls' => 'ok'],
                    'rejected'  => ['label' => 'Reddedildi',  'cls' => 'danger'],
                ];
                $st = $statusMap[$req->status] ?? ['label' => $req->status, 'cls' => 'default'];
            @endphp
            <div class="pay-hist-item">
                <div>
                    <div class="pay-hist-amount">{{ number_format((float)$req->amount, 2, ',', '.') }} {{ $req->currency }}</div>
                    @if($req->account)
                        <div class="pay-hist-bank">{{ $req->account->bank_name }} — {{ $req->account->iban }}</div>
                    @endif
                    <div class="pay-hist-meta">
                        <span>Talep: {{ optional($req->created_at)->format('d.m.Y') }}</span>
                        @if($req->paid_at)<span>Ödendi: {{ $req->paid_at->format('d.m.Y') }}</span>@endif
                        @if($req->rejection_reason)<span style="color:var(--c-danger,#dc2626);">{{ $req->rejection_reason }}</span>@endif
                    </div>
                </div>
                <div class="pay-hist-right">
                    <span class="pay-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
                    @if($req->receipt_url)
                        <a class="btn" href="{{ $req->receipt_url }}" target="_blank" rel="noopener"
                           style="font-size:var(--tx-xs);padding:5px 12px;">Dekont</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="pay-empty">Henüz ödeme talebi geçmişi yok.</div>
        @endforelse
    </div>
</div>

</div>{{-- /grid2 --}}

<div class="pay-guide" style="margin-top:4px;">
    <div class="pay-guide-title">💡 Nasıl Çalışır?</div>
    <ul>
        <li>Ödeme talebi için önce banka hesabı eklemen gerekiyor.</li>
        <li>Ödenebilir bakiye kazanılan komisyon tutarından oluşur; minimum 100 EUR talep edilebilir.</li>
        <li>Talep onayı Manager ekibine gönderilir; onaylandıktan sonra banka hesabına aktarım yapılır.</li>
        <li>Her ödeme talebinin durumunu bu ekrandan takip edebilirsin.</li>
    </ul>
</div>

@endsection
