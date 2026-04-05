@extends('manager.layouts.app')

@section('title', 'Manager – Komisyon Yönetimi')
@section('page_title', 'Komisyon Yönetimi')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .mgr-kpi-strip { grid-template-columns:1fr 1fr; } }
.mgr-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-filter-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }

/* komisyon kart */
.com-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-left:3px solid #1e40af; border-radius:10px; padding:14px 16px; margin-bottom:8px; }
.com-card.requested { border-left-color:#d97706; }
.com-card.approved  { border-left-color:#0891b2; }
.com-card.paid      { border-left-color:#16a34a; }
.com-card.rejected  { border-left-color:#dc2626; }
</style>
@endpush

@section('content')

{{-- Sayfa Açıklaması --}}
<div style="background:rgba(30,64,175,.06);border:1px solid rgba(30,64,175,.18);border-radius:9px;padding:10px 16px;margin-bottom:14px;font-size:12px;color:var(--u-muted);line-height:1.6;">
    💡 <strong style="color:var(--u-text);">Komisyon Yönetimi:</strong>
    Dealer'lar getirdikleri öğrenci başvuruları karşılığında komisyon hak eder. Bu sayfa, dealer'ların oluşturduğu ödeme taleplerini listeler.
    Manager buradan talepleri <strong>onaylar</strong>, <strong>reddeder</strong> veya <strong>ödendi</strong> olarak işaretler.
</div>

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['requested'] > 0 ? '#d97706' : '#1e40af' }};">
        <div class="mgr-kpi-label">Talep Edildi</div>
        <div class="mgr-kpi-val" style="{{ $kpis['requested'] > 0 ? 'color:#b45309;' : '' }}">{{ $kpis['requested'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#0891b2;">
        <div class="mgr-kpi-label">Onaylandı</div>
        <div class="mgr-kpi-val" style="color:#0e7490;">{{ $kpis['approved'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:#16a34a;">
        <div class="mgr-kpi-label">Ödendi</div>
        <div class="mgr-kpi-val" style="color:#15803d;">{{ $kpis['paid'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['rejected'] > 0 ? '#dc2626' : '#1e40af' }};">
        <div class="mgr-kpi-label">Reddedildi</div>
        <div class="mgr-kpi-val" style="{{ $kpis['rejected'] > 0 ? 'color:#dc2626;' : '' }}">{{ $kpis['rejected'] }}</div>
    </div>
</div>

{{-- Filtreler --}}
<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="/manager/commissions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Durum</label>
            <select name="status">
                <option value="">– Tümü –</option>
                <option value="requested" @selected($status === 'requested')>Talep Edildi</option>
                <option value="approved"  @selected($status === 'approved')>Onaylandı</option>
                <option value="paid"      @selected($status === 'paid')>Ödendi</option>
                <option value="rejected"  @selected($status === 'rejected')>Reddedildi</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Dealer</label>
            <select name="dealer">
                <option value="">– Tümü –</option>
                @foreach($dealerOptions as $dc)
                    <option value="{{ $dc }}" @selected($dealer === $dc)>{{ $dc }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Filtrele</button>
            <a href="/manager/commissions" style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Temizle</a>
        </div>
    </form>
</section>

{{-- Ödeme Talepleri --}}
@forelse($rows as $req)
    @php
        $stBadge = match($req->status) {
            'requested' => 'warn',
            'approved'  => 'info',
            'paid'      => 'ok',
            'rejected'  => 'danger',
            default     => 'pending',
        };
        $stLabel = match($req->status) {
            'requested' => 'Talep Edildi',
            'approved'  => 'Onaylandı',
            'paid'      => 'Ödendi',
            'rejected'  => 'Reddedildi',
            default     => ucfirst((string)($req->status ?? '–')),
        };
    @endphp
    <article class="com-card {{ $req->status }}">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
            {{-- Sol: bilgi --}}
            <div style="flex:1;min-width:220px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-size:var(--tx-sm);font-weight:700;color:var(--text,#0f172a);">#{{ $req->id }}</span>
                    <a href="/manager/dealers/{{ $req->dealer_code }}" style="font-weight:600;color:#1e40af;font-size:var(--tx-sm);text-decoration:none;">{{ $req->dealer_code }}</a>
                    <span class="badge {{ $stBadge }}">{{ $stLabel }}</span>
                </div>
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--text,#0f172a);margin-bottom:4px;">
                    {{ number_format((float)($req->amount ?? 0), 2, ',', '.') }} <span style="font-size:var(--tx-sm);font-weight:500;color:var(--muted,#64748b);">{{ $req->currency ?: 'EUR' }}</span>
                </div>
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);line-height:1.8;">
                    <span>Talep Eden: {{ $req->requested_by_email ?? '–' }}</span><br>
                    <span>Talep: {{ optional($req->created_at)->format('d.m.Y H:i') }}</span>
                    @if($req->approved_by)
                        &nbsp;·&nbsp; Onaylayan: {{ $req->approved_by }}
                        @if($req->approved_at) ({{ \Carbon\Carbon::parse($req->approved_at)->format('d.m.Y') }}) @endif
                    @endif
                    @if($req->paid_at)
                        &nbsp;·&nbsp; Ödeme: {{ \Carbon\Carbon::parse($req->paid_at)->format('d.m.Y') }}
                    @endif
                    @if($req->rejection_reason)
                        <br><span style="color:#b91c1c;">Red sebebi: {{ $req->rejection_reason }}</span>
                    @endif
                </div>
                @if($req->receipt_url)
                    <a href="{{ $req->receipt_url }}" target="_blank" rel="noopener"
                       style="display:inline-block;margin-top:4px;font-size:var(--tx-xs);font-weight:600;color:#1e40af;text-decoration:none;">
                        Dekont →
                    </a>
                @endif
            </div>

            {{-- Sağ: aksiyonlar --}}
            <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;flex-shrink:0;">
                @if($req->status === 'requested')
                    <form method="POST" action="/manager/commissions/{{ $req->id }}/approve">
                        @csrf @method('PATCH')
                        <button type="submit" onclick="return confirm('Bu talebi onaylamak istiyor musun?')"
                            style="padding:6px 16px;background:#16a34a;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;white-space:nowrap;">
                            Onayla
                        </button>
                    </form>
                    <form method="POST" action="/manager/commissions/{{ $req->id }}/reject" style="display:flex;gap:5px;align-items:center;">
                        @csrf @method('PATCH')
                        <input name="rejection_reason" placeholder="Red sebebi (opsiyonel)"
                               style="width:160px;font-size:var(--tx-xs);padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:6px;">
                        <button type="submit" onclick="return confirm('Bu talebi reddetmek istiyor musun?')"
                            style="padding:6px 12px;background:#dc2626;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;white-space:nowrap;">
                            Reddet
                        </button>
                    </form>
                @endif
                @if($req->status === 'approved')
                    <form method="POST" action="/manager/commissions/{{ $req->id }}/mark-paid" style="display:flex;gap:5px;align-items:center;">
                        @csrf @method('PATCH')
                        <input name="receipt_url" placeholder="Dekont URL (opsiyonel)"
                               style="width:180px;font-size:var(--tx-xs);padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:6px;">
                        <button type="submit" onclick="return confirm('Ödeme tamamlandı olarak işaretlensin mi?')"
                            style="padding:6px 14px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;white-space:nowrap;">
                            Ödendi ✓
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </article>
@empty
    <div style="padding:32px;text-align:center;color:var(--muted,#64748b);background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;">
        Komisyon talebi bulunamadı.
    </div>
@endforelse

@if($rows->hasPages())
<div style="margin-top:12px;">
    {{ $rows->withQueryString()->links('partials.pagination') }}
</div>
@endif

@endsection
