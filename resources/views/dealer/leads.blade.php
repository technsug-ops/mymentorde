@extends('dealer.layouts.app')

@section('title', 'Yönlendirmelerim')
@section('page_title', 'Lead Pipeline')
@section('page_subtitle', 'Yönlendirdiğin adayların durumu ve dönüşüm takibi')

@push('head')
<style>
/* KPI strip */
.dl-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .dl-kpi-strip { grid-template-columns:1fr 1fr; } }
@media(max-width:500px){ .dl-kpi-strip { grid-template-columns:1fr; } }

.dl-kpi {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-top: 3px solid var(--border,#e2e8f0);
    border-radius: 12px;
    padding: 16px 18px;
    transition: box-shadow .15s;
}
.dl-kpi:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); }
.dl-kpi.c-total  { border-top-color: #16a34a; }
.dl-kpi.c-conv   { border-top-color: #0891b2; }
.dl-kpi.c-pack   { border-top-color: #d97706; }
.dl-kpi.c-contr  { border-top-color: #7c3aed; }
.dl-kpi-val   { font-size: 28px; font-weight: 900; color: var(--text,#0f172a); line-height:1; margin: 4px 0; }
.dl-kpi-label { font-size: 11px; font-weight: 700; color: var(--muted,#64748b); text-transform: uppercase; letter-spacing:.04em; }
.dl-kpi-sub   { font-size: 11px; color: var(--muted,#64748b); margin-top: 4px; }

/* Pipeline stepbar */
.dl-pipeline {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 0;
    overflow-x: auto;
}
.dl-pipe-step {
    flex: 1;
    min-width: 90px;
    text-align: center;
    position: relative;
}
.dl-pipe-step:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0; top: 50%;
    transform: translateY(-50%);
    width: 1px; height: 32px;
    background: var(--border,#e2e8f0);
}
.dl-pipe-num  { font-size: 24px; font-weight: 900; color: var(--muted,#64748b); line-height: 1; }
.dl-pipe-num.has-data { color: var(--c-accent,#16a34a); }
.dl-pipe-label { font-size: 11px; font-weight: 600; color: var(--muted,#64748b); margin-top: 3px; }
.dl-pipe-dot  { width: 6px; height: 6px; border-radius: 50%; background: var(--border,#e2e8f0); margin: 6px auto 0; }
.dl-pipe-dot.active { background: var(--c-accent,#16a34a); }

/* Filter panel */
.dl-filter-panel {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
}
.dl-filter-row { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
.dl-filter-group { display:flex; flex-direction:column; gap:5px; }
.dl-filter-group.grow { flex:2; min-width:180px; }
.dl-filter-group.md   { flex:1; min-width:130px; }
.dl-filter-group.sm   { flex:1; min-width:110px; }
.dl-filter-label { font-size:11px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
.dl-filter-panel input,
.dl-filter-panel select {
    border: 1.5px solid var(--border,#e2e8f0);
    border-radius: 8px;
    padding: 8px 11px;
    font-size: 13px;
    color: var(--text,#0f172a);
    background: var(--surface,#fff);
    width: 100%;
    box-sizing: border-box;
}
.dl-filter-panel input:focus,
.dl-filter-panel select:focus {
    outline: none;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
}

/* List */
.dl-list-panel {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e2e8f0);
    border-radius: 12px;
    overflow: hidden;
}
.dl-list-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.dl-list-head h3 { margin:0; font-size:14px; font-weight:700; }

.dl-item {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border,#e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    transition: background .12s;
}
.dl-item:last-child { border-bottom: none; }
.dl-item:hover { background: var(--bg,#f8fafc); }

.dl-item-name  { font-size: 14px; font-weight: 700; color: var(--text,#0f172a); margin-bottom: 5px; }
.dl-item-chips { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:5px; }
.dl-item-meta  { font-size: 12px; color: var(--muted,#64748b); }

.dl-item-right { text-align:right; flex-shrink:0; }
.dl-item-date  { font-size:11px; color:var(--muted,#64748b); margin-top:6px; }

.dl-empty { padding: 40px 20px; text-align:center; color: var(--muted,#64748b); font-size:13px; }

/* Badges */
.dl-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; }
.dl-badge.new       { background:rgba(8,145,178,.1);   color:#0e7490; }
.dl-badge.contacted { background:rgba(100,116,139,.12); color:#475569; }
.dl-badge.qualified { background:rgba(217,119,6,.12);   color:#b45309; }
.dl-badge.converted { background:rgba(22,163,74,.12);   color:#15803d; }
.dl-badge.lost      { background:rgba(220,38,38,.1);    color:#b91c1c; }
.dl-badge.neutral   { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }
</style>
@endpush

@section('content')
@php
    $stats  = $dealerStats ?? [];
    $stages = [
        ['label' => 'Yeni',       'count' => $stagesData['new']       ?? 0],
        ['label' => 'İletişimde', 'count' => $stagesData['contacted'] ?? 0],
        ['label' => 'Nitelikli',  'count' => $stagesData['qualified'] ?? 0],
        ['label' => 'Dönüştü',    'count' => $stagesData['converted'] ?? 0],
        ['label' => 'Kayıp',      'count' => $stagesData['lost']      ?? 0],
    ];
@endphp

{{-- KPI --}}
<div class="dl-kpi-strip">
    <div class="dl-kpi c-total">
        <div class="dl-kpi-label">Toplam Lead</div>
        <div class="dl-kpi-val">{{ $stats['guest_total'] ?? 0 }}</div>
    </div>
    <div class="dl-kpi c-conv">
        <div class="dl-kpi-label">Öğrenciye Dönüşen</div>
        <div class="dl-kpi-val">{{ $stats['converted_total'] ?? 0 }}</div>
        @if(($stats['conversion_rate'] ?? 0) > 0)
            <div class="dl-kpi-sub">%{{ $stats['conversion_rate'] }} dönüşüm oranı</div>
        @endif
    </div>
    <div class="dl-kpi c-pack">
        <div class="dl-kpi-label">Paket Seçili</div>
        <div class="dl-kpi-val">{{ $packetCount }}</div>
    </div>
    <div class="dl-kpi c-contr">
        <div class="dl-kpi-label">Sözleşme Aşaması</div>
        <div class="dl-kpi-val">{{ $contractCount }}</div>
    </div>
</div>

{{-- Pipeline --}}
<div class="dl-pipeline">
    @foreach($stages as $stage)
    <div class="dl-pipe-step">
        <div class="dl-pipe-num {{ $stage['count'] > 0 ? 'has-data' : '' }}">{{ $stage['count'] }}</div>
        <div class="dl-pipe-label">{{ $stage['label'] }}</div>
        <div class="dl-pipe-dot {{ $stage['count'] > 0 ? 'active' : '' }}"></div>
    </div>
    @endforeach
</div>

{{-- Filtre --}}
<div class="dl-filter-panel">
    <form method="GET">
        <div class="dl-filter-row">
            <div class="dl-filter-group grow">
                <span class="dl-filter-label">Ara</span>
                <input name="q" value="{{ $filterQ ?? '' }}" placeholder="İsim / e-posta / telefon / student ID">
            </div>
            <div class="dl-filter-group md">
                <span class="dl-filter-label">Durum</span>
                <select name="status">
                    <option value="">Tümü</option>
                    @foreach(['new'=>'Yeni','contacted'=>'İletişimde','qualified'=>'Nitelikli','converted'=>'Dönüştü','lost'=>'Kayboldu'] as $st => $stl)
                        <option value="{{ $st }}" @selected(($filterStatus ?? '')===$st)>{{ $stl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dl-filter-group md">
                <span class="dl-filter-label">Kaynak</span>
                <select name="source">
                    <option value="">Tümü</option>
                    @foreach(['dealer_form'=>'Dealer Formu','dealer_ref'=>'Dealer Ref','instagram'=>'Instagram','google'=>'Google','tiktok'=>'TikTok','facebook'=>'Facebook','web'=>'Web'] as $src => $srcl)
                        <option value="{{ $src }}" @selected(($filterSource ?? '')===$src)>{{ $srcl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dl-filter-group sm">
                <span class="dl-filter-label">Başlangıç</span>
                <input type="date" name="from" value="{{ $filterFrom ?? '' }}">
            </div>
            <div class="dl-filter-group sm">
                <span class="dl-filter-label">Bitiş</span>
                <input type="date" name="to" value="{{ $filterTo ?? '' }}">
            </div>
            <button class="btn btn-primary" type="submit" style="align-self:flex-end;">Filtrele</button>
            @if(($filterQ ?? '') || ($filterStatus ?? '') || ($filterSource ?? '') || ($filterFrom ?? '') || ($filterTo ?? ''))
                <a class="btn" href="{{ route('dealer.leads') }}" style="align-self:flex-end;">Temizle</a>
            @endif
        </div>
    </form>
</div>

{{-- Lead listesi --}}
<div class="dl-list-panel">
    <div class="dl-list-head">
        <h3>Lead Listesi</h3>
        <span class="dl-badge neutral">{{ $rows->total() }} kayıt</span>
    </div>
    @forelse($rows as $r)
        @php
            $statusKey = (string) ($r->lead_status ?? 'new');
            $statusLbl = match($statusKey) {
                'new'       => 'Yeni',
                'contacted' => 'İletişimde',
                'qualified' => 'Nitelikli',
                'converted' => 'Dönüştü',
                'lost'      => 'Kayboldu',
                default     => $statusKey,
            };
        @endphp
        <div class="dl-item">
            <div>
                <div class="dl-item-name">{{ $r->first_name }} {{ $r->last_name }}</div>
                <div class="dl-item-chips">
                    <span class="dl-badge {{ $statusKey }}">{{ $statusLbl }}</span>
                    @if(($r->referral_type ?? '') === 'confirmed_referral')
                        <span class="dl-badge" style="background:#dcfce7;color:#166534;">Kesin</span>
                    @elseif(($r->referral_type ?? '') === 'recommendation')
                        <span class="dl-badge" style="background:#fef9c3;color:#854d0e;">Tavsiye</span>
                    @endif
                    @if($r->application_type)
                        <span class="dl-badge neutral">{{ $r->application_type }}</span>
                    @endif
                    @if($r->lead_source ?: $r->utm_source)
                        <span class="dl-badge new">{{ $r->lead_source ?: $r->utm_source }}</span>
                    @endif
                    @if(filled($r->converted_student_id))
                        <span class="dl-badge converted">{{ $r->converted_student_id }}</span>
                    @endif
                    @if(filled($r->selected_package_code))
                        <span class="dl-badge qualified">{{ $r->selected_package_code }}</span>
                    @endif
                </div>
                <div class="dl-item-meta">{{ $r->email }}@if($r->phone) &middot; {{ $r->phone }}@endif</div>
            </div>
            <div class="dl-item-right">
                <a class="btn" href="{{ route('dealer.leads.show', ['lead' => $r->id]) }}" style="font-size:var(--tx-xs);padding:6px 14px;">Detay →</a>
                <div class="dl-item-date">{{ optional($r->created_at)->format('d.m.Y') }}</div>
            </div>
        </div>
    @empty
        <div class="dl-empty">Filtre kriterlerine uygun yönlendirme kaydı bulunamadı.</div>
    @endforelse
</div>

@if($rows->hasPages())
<div style="margin-top:12px;">{{ $rows->withQueryString()->links() }}</div>
@endif

@include('dealer._partials.usage-guide', [
    'items' => [
        'Pipeline bar, filtrelenen lead\'lerin aşama dağılımını gösterir (Yeni → Dönüştü).',
        'Durum filtresiyle belirli bir aşamadaki lead\'leri listeleyebilirsiniz.',
        'Lead detayına girerek sözleşme, paket ve iletişim geçmişini görüntüleyebilirsiniz.',
    ]
])

@endsection
