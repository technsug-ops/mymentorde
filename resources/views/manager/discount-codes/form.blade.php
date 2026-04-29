@extends('manager.layouts.app')

@section('title', $mode === 'create' ? 'Yeni İndirim Kodu' : 'Kodu Düzenle')
@section('page_title', $mode === 'create' ? 'Yeni İndirim Kodu' : 'Kodu Düzenle: '.$code->code)

@push('head')
<style>
.dc-form { background: var(--u-card); border: 1px solid var(--u-line); border-radius: 10px;
    padding: 20px; max-width: 720px; }
.dc-form .dc-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 12px; }
.dc-form .dc-full { grid-column: 1 / -1; }
.dc-form label { display:block; font-size: 12px; font-weight: 600; color: var(--u-muted); margin-bottom: 4px; }
.dc-form input, .dc-form select, .dc-form textarea {
    width:100%; padding: 7px 10px; border: 1px solid var(--u-line); border-radius: 7px;
    background: var(--u-bg); color: var(--u-text); font-size: 13px; }
.dc-form input:focus, .dc-form select:focus { border-color: var(--u-brand); outline: none; }
.dc-hint { font-size: 11.5px; color: var(--u-muted); margin-top: 3px; }
.dc-actions { display: flex; gap: 10px; margin-top: 16px; }
.dc-btn { padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 7px;
    border: 1px solid var(--u-line); background: var(--u-bg); color: var(--u-text); cursor: pointer; }
.dc-btn.primary { background: var(--u-brand, #2563eb); color: white; border-color: var(--u-brand); }
.dc-checkbox { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.dc-checkbox input { width: auto; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if($errors->any())
        <div style="background:rgba(220,38,38,.08);color:rgb(185,28,28);border:1px solid rgba(220,38,38,.3);padding:10px 14px;border-radius:10px;margin-bottom:14px;">
            @foreach($errors->all() as $e) ⚠ {{ $e }}<br> @endforeach
        </div>
    @endif

    <form class="dc-form" method="POST" action="{{ $mode === 'create' ? route('manager.discount-codes.store') : route('manager.discount-codes.update', $code) }}">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="dc-row">
            <div>
                <label>Kod *</label>
                <input type="text" name="code" required maxlength="64"
                       value="{{ old('code', $code->code) }}" placeholder="Örn: HOSGELDIN10" style="text-transform:uppercase;">
                <div class="dc-hint">Sadece harf/rakam/tire/altçizgi. Otomatik büyük harfe çevrilir.</div>
            </div>
            <div>
                <label>Açıklama</label>
                <input type="text" name="description" maxlength="255"
                       value="{{ old('description', $code->description) }}" placeholder="Manager için iç not (opsiyonel)">
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>İndirim Tipi *</label>
                <select name="discount_type" required>
                    <option value="percent" {{ old('discount_type', $code->discount_type) === 'percent' ? 'selected' : '' }}>Yüzde (%)</option>
                    <option value="fixed"   {{ old('discount_type', $code->discount_type) === 'fixed' ? 'selected' : '' }}>Sabit Tutar (EUR)</option>
                </select>
            </div>
            <div>
                <label>İndirim Değeri *</label>
                <input type="number" step="0.01" min="0" name="discount_value" required
                       value="{{ old('discount_value', $code->discount_value) }}" placeholder="örn: 10 (yüzde) veya 250 (EUR)">
                <div class="dc-hint">Yüzde için 0–100, sabit için EUR tutarı.</div>
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>Geçerlilik başlangıcı</label>
                <input type="date" name="valid_from"
                       value="{{ old('valid_from', $code->valid_from?->format('Y-m-d')) }}">
                <div class="dc-hint">Boş = bugünden itibaren.</div>
            </div>
            <div>
                <label>Son kullanma</label>
                <input type="date" name="valid_until"
                       value="{{ old('valid_until', $code->valid_until?->format('Y-m-d')) }}">
                <div class="dc-hint">Boş = sınırsız tarih.</div>
            </div>
        </div>

        <div class="dc-row">
            <div>
                <label>Toplam max kullanım</label>
                <input type="number" min="1" name="max_redemptions"
                       value="{{ old('max_redemptions', $code->max_redemptions) }}" placeholder="boş = sınırsız">
                <div class="dc-hint">Tüm adaylar dahil toplam kotası.</div>
            </div>
            <div>
                <label>Kişi başına max kullanım *</label>
                <input type="number" min="1" max="100" required name="max_per_user"
                       value="{{ old('max_per_user', $code->max_per_user ?: 1) }}">
                <div class="dc-hint">Aynı aday kaç kez kullanabilir.</div>
            </div>
        </div>

        <div class="dc-checkbox">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $code->is_active) ? 'checked' : '' }}>
            <label for="is_active" style="margin:0;">Aktif</label>
        </div>

        <div class="dc-actions">
            <button type="submit" class="dc-btn primary">{{ $mode === 'create' ? 'Oluştur' : 'Kaydet' }}</button>
            <a href="{{ route('manager.discount-codes.index') }}" class="dc-btn">İptal</a>
        </div>
    </form>
</div>
@endsection
