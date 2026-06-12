@extends('platform.layouts.app')

@section('title', 'İndirim Kodları — Platform Owner')

@push('styles')
<style>
    .pc-badge-active    { background: rgba(74,222,128,.16); color: var(--plat-ok); }
    .pc-badge-expired   { background: rgba(248,113,113,.16); color: var(--plat-danger); }
    .pc-badge-exhausted { background: rgba(251,191,36,.16); color: var(--plat-warn); }
    .pc-badge-inactive  { background: rgba(160,155,181,.14); color: var(--plat-muted); text-decoration: line-through; }

    .pc-filter-bar { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 18px; padding: 16px; background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; }
    @media (max-width: 900px) { .pc-filter-bar { grid-template-columns: 1fr; } }

    .pc-code-cell { font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace; font-weight: 800; color: var(--plat-accent-2); letter-spacing: .5px; }
    .pc-usage-bar { display: flex; align-items: center; gap: 8px; }
    .pc-usage-bar-track { width: 70px; height: 6px; background: var(--plat-panel-2); border-radius: 999px; overflow: hidden; }
    .pc-usage-bar-fill  { height: 100%; background: linear-gradient(90deg, var(--plat-accent), var(--plat-accent-2)); border-radius: 999px; }
    .pc-actions-cell { display: flex; gap: 6px; flex-wrap: nowrap; justify-content: flex-end; }
    .pc-actions-cell .plat-btn { padding: 5px 8px; font-size: 11px; }
    .pc-inline-form { display: inline; margin: 0; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">İndirim Kodları</h1>
        <p class="plat-page-sub">Promo code / discount yönetimi — abonelik faturalarına otomatik uygulanır</p>
    </div>
    <a href="{{ route('platform.promo-codes.create') }}" class="plat-btn plat-btn-primary">
        <x-icon name="plus" size="16" /> Yeni Kod
    </a>
</div>

{{-- KPI ROW --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="tag" size="12" /> Aktif Kod</div>
        <div class="plat-kpi-value">{{ number_format($activeCount) }}</div>
        <div class="plat-kpi-sub">geçerli, kullanıma açık</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-info);">
        <div class="plat-kpi-label" style="color:var(--plat-info);"><x-icon name="check" size="12" /> Toplam Redemption</div>
        <div class="plat-kpi-value">{{ number_format($totalRedemptions) }}</div>
        <div class="plat-kpi-sub">tüm dönemler</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-warn);">
        <div class="plat-kpi-label" style="color:var(--plat-warn);"><x-icon name="dollar-sign" size="12" /> Verilen Toplam İndirim</div>
        <div class="plat-kpi-value">€{{ number_format($totalDiscount, 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">kümülatif</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-accent-2);">
        <div class="plat-kpi-label" style="color:var(--plat-accent-2);"><x-icon name="bar-chart-3" size="12" /> Avg / Kullanım</div>
        <div class="plat-kpi-value">€{{ number_format($avgDiscount, 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">redemption başına ortalama</div>
    </div>
</div>

{{-- FILTRE --}}
<form method="GET" action="{{ route('platform.promo-codes') }}" class="pc-filter-bar">
    <div>
        <label class="plat-form-label">Durum</label>
        <select name="active" class="plat-select">
            <option value="">Hepsi</option>
            <option value="active"   {{ $filters['active'] === 'active'   ? 'selected' : '' }}>Sadece aktif</option>
            <option value="inactive" {{ $filters['active'] === 'inactive' ? 'selected' : '' }}>Sadece devre dışı</option>
        </select>
    </div>
    <div>
        <label class="plat-form-label">Tip</label>
        <select name="type" class="plat-select">
            <option value="">Hepsi</option>
            @foreach($types as $t)
                <option value="{{ $t }}" {{ $filters['type'] === $t ? 'selected' : '' }}>
                    {{ $t === 'percentage' ? 'Yüzde indirim' : ($t === 'fixed_amount' ? 'Sabit EUR' : 'İlk N ay ücretsiz') }}
                </option>
            @endforeach
        </select>
    </div>
    <div style="display:flex;gap:6px;">
        <button type="submit" class="plat-btn plat-btn-primary"><x-icon name="filter" size="14" /> Filtrele</button>
        <a href="{{ route('platform.promo-codes') }}" class="plat-btn plat-btn-ghost" title="Temizle"><x-icon name="refresh-cw" size="14" /></a>
    </div>
</form>

{{-- TABLO --}}
<div class="plat-card" style="padding: 0; overflow: hidden;">
    <table class="plat-table">
        <thead>
            <tr>
                <th>Kod</th>
                <th>Tip</th>
                <th>Değer</th>
                <th>Tier</th>
                <th>Kullanım</th>
                <th>Geçerlilik</th>
                <th>Durum</th>
                <th style="text-align:right;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @forelse($codes as $code)
                @php
                    $usagePct = $code->max_uses ? min(100, round(($code->current_uses / max(1,$code->max_uses)) * 100)) : null;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('platform.promo-codes.show', $code->id) }}" class="pc-code-cell">
                            {{ $code->code }}
                        </a>
                        @if($code->description)
                            <div style="font-size:11px;color:var(--plat-muted);margin-top:2px;">{{ \Illuminate\Support\Str::limit($code->description, 60) }}</div>
                        @endif
                    </td>
                    <td><span style="font-size:12px;">{{ $code->typeLabel() }}</span></td>
                    <td style="font-weight:700;color:#fff;">{{ $code->valueLabel() }}</td>
                    <td>
                        @if($code->applies_to_tier)
                            <span class="plat-badge plat-badge-{{ $code->applies_to_tier }}">{{ ucfirst($code->applies_to_tier) }}</span>
                        @else
                            <span style="color:var(--plat-muted);font-size:11px;">Hepsi</span>
                        @endif
                    </td>
                    <td>
                        @if($code->max_uses === null)
                            <span style="font-size:12px;color:var(--plat-muted);">{{ $code->current_uses }} / ∞</span>
                        @else
                            <div class="pc-usage-bar">
                                <div class="pc-usage-bar-track"><div class="pc-usage-bar-fill" style="width:{{ $usagePct }}%;"></div></div>
                                <span style="font-size:11px;color:var(--plat-muted);white-space:nowrap;">{{ $code->current_uses }} / {{ $code->max_uses }}</span>
                            </div>
                        @endif
                    </td>
                    <td style="font-size:11px;color:var(--plat-muted);">
                        {{ optional($code->valid_from)->format('d.m.Y') }}
                        <span>→</span>
                        {{ optional($code->valid_until)->format('d.m.Y') }}
                    </td>
                    <td>
                        <span class="plat-badge pc-badge-{{ $code->uiStatus() }}">{{ $code->uiStatusLabel() }}</span>
                    </td>
                    <td class="pc-actions-cell">
                        <a href="{{ route('platform.promo-codes.show', $code->id) }}" class="plat-btn plat-btn-ghost plat-btn-sm" title="Detay">
                            <x-icon name="eye" size="12" />
                        </a>
                        @if($code->is_active)
                            <form method="POST" action="{{ route('platform.promo-codes.destroy', $code->id) }}" class="pc-inline-form" data-confirm="Kodu devre dışı bırakmak istediğinize emin misiniz?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="plat-btn plat-btn-danger plat-btn-sm" title="Devre dışı bırak">
                                    <x-icon name="x" size="12" />
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--plat-muted);">
                        Filtreye uyan promo kodu yok. <a href="{{ route('platform.promo-codes.create') }}">+ Yeni kod oluştur</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $codes->onEachSide(1)->links() }}
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    // Confirm-on-submit (CSP-safe)
    document.querySelectorAll('form[data-confirm]').forEach(function(f){
        f.addEventListener('submit', function(e){
            if (!confirm(f.getAttribute('data-confirm') || 'Emin misiniz?')) e.preventDefault();
        });
    });
})();
</script>
@endpush
