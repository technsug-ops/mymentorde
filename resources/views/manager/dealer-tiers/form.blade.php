@extends('manager.layouts.app')

@section('title', $mode === 'create' ? 'Yeni Tier' : 'Tier Düzenle: '.$tier->tier_name)
@section('page_title', $mode === 'create' ? 'Yeni Bayi Tier' : 'Tier Düzenle: '.$tier->tier_emoji.' '.$tier->tier_name)

@push('head')
<style>
.dtf-form { background:var(--u-card); border:1px solid var(--u-line); border-radius:10px; padding:22px; max-width:760px; }
.dtf-row { display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; margin-bottom:14px; }
.dtf-full { grid-column:1 / -1; }
.dtf-form label { display:block; font-size:12px; font-weight:600; color:var(--u-muted); margin-bottom:4px; }
.dtf-form input, .dtf-form select, .dtf-form textarea {
    width:100%; padding:8px 10px; border:1px solid var(--u-line); border-radius:7px;
    background:var(--u-bg); color:var(--u-text); font-size:13px;
}
.dtf-hint { font-size:11.5px; color:var(--u-muted); margin-top:3px; }
.dtf-btn { padding:9px 18px; font-size:13px; font-weight:600; border-radius:8px; border:1px solid var(--u-line); background:var(--u-bg); color:var(--u-text); cursor:pointer; text-decoration:none; }
.dtf-btn.primary { background:var(--u-brand,#2563eb); color:white; border-color:var(--u-brand); }
.dtf-actions { display:flex; gap:10px; margin-top:18px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if($errors->any())
        <div style="background:rgba(220,38,38,.08);color:rgb(185,28,28);border:1px solid rgba(220,38,38,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <form class="dtf-form" method="POST" action="{{ $mode === 'create' ? route('manager.dealer-tiers.store') : route('manager.dealer-tiers.update', $tier) }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="dtf-row">
            <div>
                <label>Tier kodu *</label>
                <input type="text" name="tier_code" required maxlength="32" pattern="[a-z0-9_]+" value="{{ old('tier_code', $tier->tier_code) }}" placeholder="lg_bronz">
                <div class="dtf-hint">Sadece küçük harf/rakam/altçizgi. Örn: <code>lg_bronz</code>, <code>fl_aktif</code>.</div>
            </div>
            <div>
                <label>Tier adı *</label>
                <input type="text" name="tier_name" required maxlength="64" value="{{ old('tier_name', $tier->tier_name) }}" placeholder="Bronz">
            </div>
        </div>

        <div class="dtf-row">
            <div>
                <label>Emoji</label>
                <input type="text" name="tier_emoji" maxlength="8" value="{{ old('tier_emoji', $tier->tier_emoji) }}" placeholder="🥉">
            </div>
            <div>
                <label>Bayi tipi *</label>
                <select name="dealer_type_code" required>
                    @foreach(['lead_generation' => '💼 Lead Generation', 'freelance_danisman' => '🎓 Freelance Danışman', 'b2b_partner' => '🤝 B2B Partner'] as $code => $label)
                        <option value="{{ $code }}" {{ old('dealer_type_code', $tier->dealer_type_code) === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="dtf-row">
            <div>
                <label>Min kayıt sayısı (eşik) *</label>
                <input type="number" name="min_count" required min="0" value="{{ old('min_count', $tier->min_count ?? 1) }}">
                <div class="dtf-hint">Bayi bu tier'a girmek için en az kaç kümülatif kayıt yapmış olmalı.</div>
            </div>
            <div>
                <label>Max kayıt sayısı</label>
                <input type="number" name="max_count" min="0" value="{{ old('max_count', $tier->max_count) }}" placeholder="boş = sınırsız (en üst tier)">
                <div class="dtf-hint">Boş bırakırsan üst sınır olur (en yüksek tier).</div>
            </div>
        </div>

        <div class="dtf-row">
            <div>
                <label>Komisyon (EUR/kayıt)</label>
                <input type="number" step="0.01" min="0" name="commission_rate_eur" value="{{ old('commission_rate_eur', $tier->commission_rate_eur) }}" placeholder="200">
                <div class="dtf-hint">Sabit € tutar. Yüzde kullanacaksan boş bırak.</div>
            </div>
            <div>
                <label>Komisyon (% yüzde)</label>
                <input type="number" step="0.01" min="0" max="100" name="commission_rate_percent" value="{{ old('commission_rate_percent', $tier->commission_rate_percent) }}" placeholder="20">
                <div class="dtf-hint">Yüzde dolu ise EUR yerine bu kullanılır.</div>
            </div>
        </div>

        <div class="dtf-row">
            <div class="dtf-full">
                <label>Avantajlar (Public sayfada gösterilen kısa metin)</label>
                <textarea name="advantages_text" rows="2" maxlength="500">{{ old('advantages_text', $tier->advantages_text) }}</textarea>
            </div>
        </div>

        <div class="dtf-row">
            <div>
                <label>Görüntülenme sırası</label>
                <input type="number" name="display_order" min="0" max="9999" value="{{ old('display_order', $tier->display_order ?? 1) }}">
            </div>
            <div style="display:flex; align-items:flex-end;">
                <label style="display:flex; align-items:center; gap:8px; margin:0;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" style="width:auto;" {{ old('is_active', $tier->is_active) ? 'checked' : '' }}>
                    Aktif
                </label>
            </div>
        </div>

        <div class="dtf-actions">
            <button type="submit" class="dtf-btn primary">{{ $mode === 'create' ? 'Oluştur' : 'Kaydet' }}</button>
            <a href="{{ route('manager.dealer-tiers.index') }}" class="dtf-btn">İptal</a>
        </div>
    </form>
</div>
@endsection
