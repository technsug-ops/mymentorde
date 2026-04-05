@extends('manager.layouts.app')

@php
    $typeFilter = request('type', '');
    $pageTitle  = match($typeFilter) {
        'staff'  => 'Staff Sözleşmeleri',
        'dealer' => 'Dealer Sözleşmeleri',
        default  => 'Sözleşme Yönetimi',
    };
    $pageSubtitle = match($typeFilter) {
        'staff'  => 'Çalışan iş sözleşmeleri — imza süreci',
        'dealer' => 'Bayi ortaklık ve operasyon sözleşmeleri',
        default  => 'Tüm dealer ve staff sözleşmeleri',
    };
@endphp

@section('title', 'Manager – ' . $pageTitle)
@section('page_title', $pageTitle)
@section('page_subtitle', $pageSubtitle)

@push('head')
<style>
.bc-kpi-strip { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; margin-bottom:16px; }
@media(max-width:900px){ .bc-kpi-strip { grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px){ .bc-kpi-strip { grid-template-columns:1fr 1fr; } }
.bc-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:10px 12px; }
.bc-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
.bc-kpi-val   { font-size:20px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.bc-type-tabs { display:flex; gap:6px; margin-bottom:16px; flex-wrap:wrap; }
.bc-type-tab  { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; border:1px solid var(--border,#e2e8f0); color:var(--muted,#64748b); background:var(--surface,#fff); transition:all .15s; }
.bc-type-tab:hover  { border-color:#1e40af; color:#1e40af; }
.bc-type-tab.active { background:#1e40af; border-color:#1e40af; color:#fff; }
.bc-filter-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
.bc-filter-row select, .bc-filter-row input { height:36px; padding:0 10px; border:1px solid var(--border,#e2e8f0); border-radius:8px; font-size:13px; color:var(--text,#0f172a); background:var(--surface,#fff); outline:none; }
.bc-filter-row select:focus, .bc-filter-row input:focus { border-color:#1e40af; }
.mgr-table { width:100%; border-collapse:collapse; font-size:13px; }
.mgr-table thead tr { background:var(--bg,#f8fafc); }
.mgr-table th { padding:9px 12px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); transition:background .1s; }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:10px 12px; vertical-align:middle; }
.bc-detail-btn { display:inline-block; padding:4px 12px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; white-space:nowrap; }
.bc-detail-btn:hover { background:rgba(30,64,175,.1); }
</style>
@endpush

@section('topbar-actions')
    <a href="{{ route('manager.business-contracts.create', ['type' => $typeFilter ?: null]) }}" class="btn btn-primary" style="font-size:var(--tx-sm);">
        + Yeni {{ $typeFilter === 'staff' ? 'Staff' : ($typeFilter === 'dealer' ? 'Dealer' : '') }} Sözleşme
    </a>
@endsection

@section('content')

@if(session('success'))
    <div style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.3);color:#15803d;padding:10px 14px;border-radius:8px;font-size:var(--tx-sm);margin-bottom:16px;">
        ✓ {{ session('success') }}
    </div>
@endif

{{-- KPI Strip --}}
@php
    $all      = $contracts->total();
    $draft    = $contracts->getCollection()->where('status','draft')->count();
    $issued   = $contracts->getCollection()->where('status','issued')->count();
    $signed   = $contracts->getCollection()->where('status','signed_uploaded')->count();
    $approved = $contracts->getCollection()->where('status','approved')->count();
@endphp
<div class="bc-kpi-strip">
    <div class="bc-kpi">
        <div class="bc-kpi-label">Toplam</div>
        <div class="bc-kpi-val">{{ $contracts->total() }}</div>
    </div>
    <div class="bc-kpi" style="border-top-color:#94a3b8;">
        <div class="bc-kpi-label">Taslak</div>
        <div class="bc-kpi-val" style="color:#64748b;">{{ $draft }}</div>
    </div>
    <div class="bc-kpi" style="border-top-color:#0891b2;">
        <div class="bc-kpi-label">Gönderildi</div>
        <div class="bc-kpi-val" style="color:#0891b2;">{{ $issued }}</div>
    </div>
    <div class="bc-kpi" style="border-top-color:#d97706;">
        <div class="bc-kpi-label">İmzalı Bekliyor</div>
        <div class="bc-kpi-val" style="color:#b45309;">{{ $signed }}</div>
    </div>
    <div class="bc-kpi" style="border-top-color:#16a34a;">
        <div class="bc-kpi-label">Onaylandı</div>
        <div class="bc-kpi-val" style="color:#15803d;">{{ $approved }}</div>
    </div>
</div>

{{-- Tip Sekmeleri --}}
<div class="bc-type-tabs">
    <a href="{{ route('manager.business-contracts.index') }}"
       class="bc-type-tab {{ $typeFilter === '' ? 'active' : '' }}">
        📋 Tümü
    </a>
    <a href="{{ route('manager.business-contracts.index', ['type' => 'staff']) }}"
       class="bc-type-tab {{ $typeFilter === 'staff' ? 'active' : '' }}">
        👥 Staff
    </a>
    <a href="{{ route('manager.business-contracts.index', ['type' => 'dealer']) }}"
       class="bc-type-tab {{ $typeFilter === 'dealer' ? 'active' : '' }}">
        🤝 Dealer
    </a>
</div>

{{-- Filtreler --}}
<form method="GET" class="bc-filter-row">
    <input type="hidden" name="type" value="{{ $typeFilter }}">
    <select name="status">
        <option value="">Tüm Durumlar</option>
        <option value="draft"           @selected(request('status')==='draft')>Taslak</option>
        <option value="issued"          @selected(request('status')==='issued')>Gönderildi</option>
        <option value="signed_uploaded" @selected(request('status')==='signed_uploaded')>İmzalı Yüklendi</option>
        <option value="approved"        @selected(request('status')==='approved')>Onaylandı</option>
        <option value="cancelled"       @selected(request('status')==='cancelled')>İptal</option>
    </select>
    @if($typeFilter !== 'staff')
    <select name="dealer_id" style="min-width:180px;">
        <option value="">Tüm Dealerlar</option>
        @foreach($dealers as $d)
            <option value="{{ $d->id }}" @selected(request('dealer_id')==$d->id)>{{ $d->name }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="btn alt" style="height:36px;padding:0 16px;font-size:var(--tx-sm);">Filtrele</button>
    @if(request()->hasAny(['status','dealer_id']))
        <a href="{{ route('manager.business-contracts.index', ['type' => $typeFilter ?: null]) }}"
           class="btn alt" style="height:36px;padding:0 14px;font-size:var(--tx-sm);">✕ Sıfırla</a>
    @endif
</form>

{{-- Tablo --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <table class="mgr-table">
        <thead>
        <tr>
            <th>Sözleşme No</th>
            <th>Başlık</th>
            <th>{{ $typeFilter === 'staff' ? 'Çalışan' : ($typeFilter === 'dealer' ? 'Dealer' : 'Taraf') }}</th>
            <th>Durum</th>
            <th>Tarih</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($contracts as $c)
            <tr>
                <td style="font-family:monospace;font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $c->contract_no }}</td>
                <td style="font-weight:500;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $c->title }}</td>
                <td style="color:var(--muted,#64748b);">
                    @if($c->contract_type === 'staff')
                        {{ $c->issuedByUser?->name ?? '—' }}
                    @else
                        {{ $c->dealer?->name ?? '—' }}
                    @endif
                </td>
                <td><span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span></td>
                <td style="color:var(--muted,#64748b);font-size:var(--tx-xs);">{{ $c->created_at->format('d.m.Y') }}</td>
                <td><a href="{{ route('manager.business-contracts.show', $c) }}" class="bc-detail-btn">Detay →</a></td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="padding:40px;text-align:center;color:var(--muted,#64748b);">
                    <div style="font-size:var(--tx-2xl);margin-bottom:8px;">📄</div>
                    Henüz sözleşme yok.
                    <a href="{{ route('manager.business-contracts.create') }}" style="color:#1e40af;text-decoration:none;margin-left:8px;">+ Yeni oluştur</a>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</section>

<div style="margin-top:14px;">
    {{ $contracts->appends(request()->query())->links() }}
</div>

@endsection
