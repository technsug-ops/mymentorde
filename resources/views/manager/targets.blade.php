@extends('manager.layouts.app')
@section('title', 'Performans Hedefleri')
@section('page_title', 'Performans Hedefleri')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>Performans Hedefleri</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Dönem: {{ $period }}</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="/manager/targets/report?period={{ $period }}" class="btn alt">Hedef vs Gerçek</a>
        <form method="GET">
            <select name="period" onchange="this.form.submit()" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:4px;">
                @foreach($periods as $p)
                <option value="{{ $p }}" {{ $p === $period ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
                <option value="{{ now()->format('Y-m') }}" {{ !in_array(now()->format('Y-m'), $periods->all()) ? 'selected' : '' }}>{{ now()->format('Y-m') }} (Bu Ay)</option>
            </select>
        </form>
    </div>
</div>

@if(session('status'))
<div class="badge ok" style="margin-bottom:12px;display:inline-block;">{{ session('status') }}</div>
@endif

<div class="grid2">
    {{-- Firma Geneli Hedef --}}
    <div class="card">
        <div class="card-title">Firma Geneli Hedef</div>
        @php $companyTarget = $targets->where('target_type', 'company_wide')->first(); @endphp
        <form method="POST" action="/manager/targets">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="target_type" value="company_wide">
            <div class="field"><label>Hedef Gelir (€)</label><input type="number" name="target_revenue" step="0.01" min="0" value="{{ $companyTarget?->target_revenue ?? 0 }}"></div>
            <div class="field"><label>Hedef Dönüşüm (öğrenci)</label><input type="number" name="target_conversions" min="0" value="{{ $companyTarget?->target_conversions ?? 0 }}"></div>
            <div class="field"><label>Hedef Yeni Başvuru</label><input type="number" name="target_new_guests" min="0" value="{{ $companyTarget?->target_new_guests ?? 0 }}"></div>
            <div class="field"><label>Hedef Belge Onayı</label><input type="number" name="target_doc_reviews" min="0" value="{{ $companyTarget?->target_doc_reviews ?? 0 }}"></div>
            <div class="field"><label>Hedef İmzalı Sözleşme</label><input type="number" name="target_contracts_signed" min="0" value="{{ $companyTarget?->target_contracts_signed ?? 0 }}"></div>
            <div class="field"><label>Notlar</label><textarea name="notes" rows="2">{{ $companyTarget?->notes }}</textarea></div>
            <button type="submit" class="btn ok">Kaydet</button>
        </form>
    </div>

    {{-- Eğitim Danışmanı Hedefleri --}}
    <div class="card">
        <div class="card-title">Eğitim Danışmanı Hedefi Ekle</div>
        <form method="POST" action="/manager/targets">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="target_type" value="senior_specific">
            <div class="field">
                <label>Eğitim Danışmanı *</label>
                <select name="senior_email" required>
                    <option value="">-- Seç --</option>
                    @foreach($seniors as $sr)
                    <option value="{{ $sr->email }}">{{ $sr->name }} ({{ $sr->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Hedef Dönüşüm</label><input type="number" name="target_conversions" min="0" value="0"></div>
            <div class="field"><label>Hedef Yeni Başvuru</label><input type="number" name="target_new_guests" min="0" value="0"></div>
            <div class="field"><label>Hedef Belge Onayı</label><input type="number" name="target_doc_reviews" min="0" value="0"></div>
            <button type="submit" class="btn ok" style="margin-top:8px;">Ekle / Güncelle</button>
        </form>

        @if($targets->where('target_type', 'senior_specific')->isNotEmpty())
        <div style="margin-top:16px;border-top:1px solid var(--u-line);padding-top:12px;">
            <div class="u-muted" style="font-size:var(--tx-xs);margin-bottom:8px;">KAYITLI SENIOR HEDEFLERİ</div>
            <div class="list">
                @foreach($targets->where('target_type', 'senior_specific') as $st)
                <div class="item">
                    <div style="flex:1;">
                        <div style="font-weight:500;font-size:var(--tx-sm);">{{ $st->senior_email }}</div>
                        <div class="u-muted" style="font-size:var(--tx-xs);">
                            Dönüşüm: {{ $st->target_conversions }}
                            · Başvuru: {{ $st->target_new_guests }}
                            · Belge: {{ $st->target_doc_reviews }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
