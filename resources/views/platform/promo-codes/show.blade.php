@extends('platform.layouts.app')

@section('title', $code->code . ' — İndirim Kodu Detay')

@push('styles')
<style>
    .pc-badge-active    { background: rgba(74,222,128,.16); color: var(--plat-ok); }
    .pc-badge-expired   { background: rgba(248,113,113,.16); color: var(--plat-danger); }
    .pc-badge-exhausted { background: rgba(251,191,36,.16); color: var(--plat-warn); }
    .pc-badge-inactive  { background: rgba(160,155,181,.14); color: var(--plat-muted); }

    .pc-code-display {
        background: linear-gradient(135deg, var(--plat-accent), var(--plat-accent-2));
        color: #fff; padding: 18px 22px; border-radius: 12px;
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-weight: 800; font-size: 24px; letter-spacing: 1.5px;
        display: inline-block; margin-bottom: 12px;
    }
    .pc-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 800px) { .pc-detail-grid { grid-template-columns: 1fr; } }

    .pc-edit-section { background: var(--plat-panel); border: 1px solid var(--plat-border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .pc-edit-section h3 { margin: 0 0 14px; color: #fff; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }

    .pc-meta-row { display: flex; padding: 8px 0; border-bottom: 1px solid var(--plat-border); font-size: 13px; }
    .pc-meta-row:last-child { border-bottom: none; }
    .pc-meta-label { color: var(--plat-muted); width: 160px; flex-shrink: 0; }
    .pc-meta-value { color: #fff; font-weight: 600; }

    .pc-checkbox-row { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: var(--plat-panel-2); border: 1px solid var(--plat-border); border-radius: 8px; }
    .pc-checkbox-row input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--plat-accent); cursor: pointer; }
    .pc-checkbox-row label { color: #fff; font-weight: 600; cursor: pointer; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <a href="{{ route('platform.promo-codes') }}" style="font-size:12px;color:var(--plat-muted);">
            <x-icon name="arrow-left" size="12" /> İndirim Kodları
        </a>
        <h1 class="plat-page-title" style="margin-top:6px;">{{ $code->code }}</h1>
        <p class="plat-page-sub">{{ $code->description ?: '—' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <span class="plat-badge pc-badge-{{ $code->uiStatus() }}" style="font-size:12px;padding:6px 12px;">{{ $code->uiStatusLabel() }}</span>
    </div>
</div>

{{-- KPI ROW (stats) --}}
<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="check" size="12" /> Toplam Kullanım</div>
        <div class="plat-kpi-value">{{ number_format($stats['redemption_count']) }}</div>
        <div class="plat-kpi-sub">
            @if($code->max_uses) {{ $code->current_uses }} / {{ $code->max_uses }} (max) @else sınırsız @endif
        </div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-warn);">
        <div class="plat-kpi-label" style="color:var(--plat-warn);"><x-icon name="dollar-sign" size="12" /> Verilen İndirim</div>
        <div class="plat-kpi-value">€{{ number_format($stats['total_discount'], 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">kümülatif</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-info);">
        <div class="plat-kpi-label" style="color:var(--plat-info);"><x-icon name="building-2" size="12" /> Unique Şirket</div>
        <div class="plat-kpi-value">{{ number_format($stats['companies_count']) }}</div>
        <div class="plat-kpi-sub">farklı company kullandı</div>
    </div>
    <div class="plat-kpi" style="--accent:var(--plat-accent-2);">
        <div class="plat-kpi-label" style="color:var(--plat-accent-2);"><x-icon name="bar-chart-3" size="12" /> Avg / Kullanım</div>
        <div class="plat-kpi-value">€{{ number_format($stats['avg_discount'], 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">{{ $stats['last_redeemed_at'] ? 'son: ' . $stats['last_redeemed_at']->format('d.m.Y') : 'henüz kullanılmadı' }}</div>
    </div>
</div>

<div class="pc-detail-grid">
    {{-- Kod Detayı --}}
    <div class="pc-edit-section">
        <h3><x-icon name="tag" size="16" /> Kod Bilgileri</h3>
        <div style="text-align:center;margin-bottom:14px;">
            <div class="pc-code-display">{{ $code->code }}</div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Tip</div>
            <div class="pc-meta-value">{{ $code->typeLabel() }}</div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Değer</div>
            <div class="pc-meta-value">{{ $code->valueLabel() }}</div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Süre</div>
            <div class="pc-meta-value">{{ $code->duration_months ? $code->duration_months . ' ay' : 'Tek seferlik' }}</div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Geçerli Tier</div>
            <div class="pc-meta-value">{{ $code->applies_to_tier ? ucfirst($code->applies_to_tier) : 'Tüm tier\'lar' }}</div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Geçerlilik</div>
            <div class="pc-meta-value">
                {{ optional($code->valid_from)->format('d.m.Y') }} → {{ optional($code->valid_until)->format('d.m.Y') }}
            </div>
        </div>
        <div class="pc-meta-row">
            <div class="pc-meta-label">Oluşturma</div>
            <div class="pc-meta-value">{{ $code->created_at?->format('d.m.Y H:i') }}</div>
        </div>
    </div>

    {{-- Hızlı Düzenleme --}}
    <div class="pc-edit-section">
        <h3><x-icon name="settings" size="16" /> Hızlı Düzenleme</h3>
        <form method="POST" action="{{ route('platform.promo-codes.update', $code->id) }}">
            @csrf
            <div class="plat-form-group">
                <label class="plat-form-label" for="ed-desc">Açıklama</label>
                <textarea id="ed-desc" name="description" class="plat-textarea" rows="2" maxlength="300">{{ old('description', $code->description) }}</textarea>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="ed-tier">Geçerli Tier</label>
                <select id="ed-tier" name="applies_to_tier" class="plat-select">
                    @foreach($tiers as $t)
                        <option value="{{ $t['value'] }}" {{ ($code->applies_to_tier ?? '') === $t['value'] ? 'selected' : '' }}>{{ $t['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="ed-max">Max Kullanım (boş = sınırsız)</label>
                <input type="number" min="1" id="ed-max" name="max_uses" class="plat-input" value="{{ old('max_uses', $code->max_uses) }}">
                @if($code->current_uses > 0)
                    <small style="font-size:11px;color:var(--plat-muted);">Mevcut: {{ $code->current_uses }} — bunun altına düşemez.</small>
                @endif
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="ed-from">Başlangıç</label>
                <input type="date" id="ed-from" name="valid_from" class="plat-input" value="{{ old('valid_from', optional($code->valid_from)->format('Y-m-d')) }}" required>
            </div>

            <div class="plat-form-group">
                <label class="plat-form-label" for="ed-until">Bitiş</label>
                <input type="date" id="ed-until" name="valid_until" class="plat-input" value="{{ old('valid_until', optional($code->valid_until)->format('Y-m-d')) }}" required>
            </div>

            <div class="pc-checkbox-row" style="margin-bottom:14px;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="ed-active" name="is_active" value="1" {{ $code->is_active ? 'checked' : '' }}>
                <label for="ed-active">Kod aktif</label>
            </div>

            <button type="submit" class="plat-btn plat-btn-primary" style="width:100%;">
                <x-icon name="check" size="14" /> Güncelle
            </button>
        </form>

        @if($code->is_active)
            <form method="POST" action="{{ route('platform.promo-codes.destroy', $code->id) }}" data-confirm="Kodu kalıcı olarak devre dışı bırakmak istiyor musun? (Veri silinmez, sadece is_active=false olur)" style="margin-top:10px;">
                @csrf
                @method('DELETE')
                <button type="submit" class="plat-btn plat-btn-danger" style="width:100%;">
                    <x-icon name="x" size="14" /> Devre Dışı Bırak
                </button>
            </form>
        @endif
    </div>
</div>

{{-- Redemption Listesi --}}
<div class="plat-card" style="padding:0;overflow:hidden;margin-top:8px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--plat-border);background:var(--plat-panel-2);">
        <h3 class="plat-card-title" style="margin:0;"><x-icon name="check" size="16" /> Kullanım Geçmişi ({{ $stats['redemption_count'] }})</h3>
    </div>
    <table class="plat-table">
        <thead>
            <tr>
                <th>Şirket</th>
                <th>Tier</th>
                <th>Tarih</th>
                <th style="text-align:right;">Verilen İndirim</th>
                <th>Etkilenen Fatura(lar)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($redemptions as $r)
                <tr>
                    <td>
                        @if($r->company)
                            <a href="{{ route('platform.companies.show', $r->company_id) }}" style="font-weight:700;color:#fff;">{{ $r->company->name }}</a>
                        @else
                            <span style="color:var(--plat-muted);">— (silinmiş)</span>
                        @endif
                    </td>
                    <td>
                        @if($r->company && $r->company->subscription_tier)
                            <span class="plat-badge plat-badge-{{ $r->company->subscription_tier }}">{{ ucfirst($r->company->subscription_tier) }}</span>
                        @else
                            <span style="color:var(--plat-muted);">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--plat-muted);">
                        {{ optional($r->applied_at)->format('d.m.Y H:i') }}
                    </td>
                    <td style="text-align:right;font-weight:700;color:#fff;">
                        €{{ number_format((float) $r->discount_applied_eur, 2, ',', '.') }}
                    </td>
                    <td style="font-size:11px;color:var(--plat-muted);">
                        @php $ids = is_array($r->invoice_ids) ? $r->invoice_ids : []; @endphp
                        @if(empty($ids))
                            <span>—</span>
                        @else
                            {{ count($ids) }} fatura ({{ implode(', ', array_slice($ids, 0, 5)) }}@if(count($ids) > 5)…@endif)
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:32px;color:var(--plat-muted);">
                        Henüz kimse bu kodu kullanmadı.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    {{ $redemptions->onEachSide(1)->links() }}
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    document.querySelectorAll('form[data-confirm]').forEach(function(f){
        f.addEventListener('submit', function(e){
            if (!confirm(f.getAttribute('data-confirm') || 'Emin misiniz?')) e.preventDefault();
        });
    });
})();
</script>
@endpush
