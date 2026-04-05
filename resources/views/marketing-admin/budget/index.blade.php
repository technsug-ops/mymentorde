@extends('marketing-admin.layouts.app')

@section('title', 'Bütçe Yönetimi')

@section('topbar-actions')
<button onclick="document.getElementById('bg-form-det').open=true;document.getElementById('bg-form-det').scrollIntoView({behavior:'smooth'})" class="btn ok" style="font-size:var(--tx-xs);padding:6px 14px;">+ Yeni Bütçe</button>
@endsection

@section('page_subtitle', 'Bütçe Yönetimi — dönem bazlı pazarlama bütçesi ve harcama takibi')

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.bg-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.bg-stat  { flex:1; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.bg-stat:last-child { border-right:none; }
.bg-val   { font-size:18px; font-weight:700; line-height:1.1; }
.bg-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; min-width:640px; border-collapse:collapse; }
.tl-tbl th {
    text-align:left; padding:9px 12px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b);
    background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));
    border-bottom:1px solid var(--u-line,#e2e8f0);
}
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:top; }
.tl-tbl tr:last-child td { border-bottom:none; }

.wf-field { display:flex; flex-direction:column; gap:4px; }
.wf-field label { font-size:12px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input, .wf-field select {
    width:100%; box-sizing:border-box; height:36px; padding:0 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:13px; outline:none; transition:border-color .15s; appearance:auto;
}
.wf-field input:focus, .wf-field select:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
.bg-alloc {
    width:100%; box-sizing:border-box; min-height:80px; padding:8px 10px;
    border:1px solid var(--u-line,#e2e8f0); border-radius:8px;
    background:var(--u-card,#fff); color:var(--u-text,#0f172a);
    font-size:12px; outline:none; resize:vertical;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    transition:border-color .15s;
}
.bg-alloc:focus { border-color:var(--u-brand,#1e40af); box-shadow:0 0 0 2px rgba(30,64,175,.10); }
</style>

@php
    $isUpdate = !empty($selected);
    $formAction = $isUpdate ? '/mktg-admin/budget/'.$selected->period : '/mktg-admin/budget';
    $periodDefault = old('period', $selected->period ?? now()->format('Y-m'));
    $budgetDefault = old('total_budget', isset($selected) ? number_format((float) $selected->total_budget, 2, '.', '') : '0.00');
    $currencyDefault = old('currency', $selected->currency ?? 'EUR');
    $allocationsDefault = old('allocations_json', isset($selected) ? json_encode($selected->allocations ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : "{}");
@endphp

<div style="display:grid;gap:12px;">

    {{-- Flash --}}
    @if(session('status'))
    <div style="border:1px solid var(--u-ok,#16a34a);background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));color:var(--u-ok,#16a34a);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        {{ session('status') }}
    </div>
    @endif
    @if($errors->any())
    <div style="border:1px solid var(--u-danger,#dc2626);background:color-mix(in srgb,var(--u-danger,#dc2626) 8%,var(--u-card,#fff));color:var(--u-danger,#dc2626);border-radius:10px;padding:10px 14px;font-size:var(--tx-sm);">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    {{-- KPI Bar --}}
    <div class="bg-stats">
        <div class="bg-stat">
            <div class="bg-val" style="color:var(--u-brand,#1e40af);">{{ number_format((float)($totals['budget'] ?? 0), 2, ',', '.') }} €</div>
            <div class="bg-lbl">Listelenen Bütçe</div>
        </div>
        <div class="bg-stat">
            <div class="bg-val" style="color:var(--u-warn,#f59e0b);">{{ number_format((float)($totals['spent'] ?? 0), 2, ',', '.') }} €</div>
            <div class="bg-lbl">Listelenen Harcama</div>
        </div>
        <div class="bg-stat">
            <div class="bg-val" style="color:var(--u-ok,#16a34a);">{{ number_format((float)($totals['remaining'] ?? 0), 2, ',', '.') }} €</div>
            <div class="bg-lbl">Listelenen Kalan</div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="card">
        <div style="display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
            <form method="GET" action="/mktg-admin/budget" style="display:flex;gap:8px;align-items:center;">
                <input type="month" name="period" value="{{ $periodFilter ?? '' }}"
                    style="height:34px;padding:0 10px;border:1px solid var(--u-line,#e2e8f0);border-radius:8px;background:var(--u-card,#fff);color:var(--u-text,#0f172a);font-size:var(--tx-xs);outline:none;">
                <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 16px;">Filtrele</button>
                <a href="/mktg-admin/budget" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 14px;display:flex;align-items:center;">Temizle</a>
            </form>
            <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ number_format((int)($budgets->total() ?? 0), 0, '.', ',') }} dönem</span>
        </div>
    </div>

    {{-- Form --}}
    <details class="card" id="bg-form-det" {{ $isUpdate ? 'open' : '' }}>
        <summary class="det-sum">
            <h3>{{ $isUpdate ? '✏️ Bütçe Güncelle — '.$selected->period : '+ Yeni Bütçe' }}</h3>
            <span class="det-chev">▼</span>
        </summary>
        <form method="POST" action="{{ $formAction }}" style="display:flex;flex-direction:column;gap:10px;">
            @csrf
            @if($isUpdate) @method('PUT') @endif
            <div class="grid3">
                <div class="wf-field">
                    <label>Dönem *</label>
                    <input type="month" name="period" value="{{ $periodDefault }}" required>
                </div>
                <div class="wf-field">
                    <label>Toplam Bütçe *</label>
                    <input type="number" step="0.01" min="0" name="total_budget" value="{{ $budgetDefault }}" placeholder="0.00" required>
                </div>
                <div class="wf-field">
                    <label>Para Birimi</label>
                    <select name="currency">
                        @foreach(['EUR', 'TRY', 'USD'] as $cur)
                        <option value="{{ $cur }}" @selected($currencyDefault === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="wf-field">
                <label>Kanal Dağılımı (JSON)</label>
                <textarea name="allocations_json" class="bg-alloc" placeholder='{"google_ads": 3000, "instagram_ads": 2500}'>{{ $allocationsDefault }}</textarea>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="submit" class="btn ok">{{ $isUpdate ? 'Güncelle' : 'Kaydet' }}</button>
                @if($isUpdate)
                <a class="btn alt" href="/mktg-admin/budget">Yeni Kayıt Modu</a>
                @else
                <button type="button" onclick="document.getElementById('bg-form-det').open=false" class="btn alt">İptal</button>
                @endif
            </div>
            <small style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">Harcama otomatik olarak kampanya <code>spent_amount</code> alanından dönem bazlı hesaplanır.</small>
        </form>
    </details>

    {{-- Tablo --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;">Kayıtlı Dönemler</div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th>Dönem</th>
                    <th>Bütçe</th>
                    <th>Harcama</th>
                    <th>Kalan</th>
                    <th>Kur</th>
                    <th style="text-align:right;">Aksiyon</th>
                </tr></thead>
                <tbody>
                    @forelse(($budgets ?? []) as $row)
                    <tr>
                        <td><strong>{{ $row->period }}</strong></td>
                        <td>{{ number_format((float)$row->total_budget, 2, ',', '.') }}</td>
                        <td style="color:var(--u-warn,#f59e0b);">{{ number_format((float)$row->total_spent, 2, ',', '.') }}</td>
                        <td style="color:var(--u-ok,#16a34a);">{{ number_format((float)$row->total_remaining, 2, ',', '.') }}</td>
                        <td style="color:var(--u-muted,#64748b);">{{ $row->currency }}</td>
                        <td style="text-align:right;">
                            <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 10px;" href="/mktg-admin/budget/{{ urlencode((string)$row->period) }}">Düzenle</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Bütçe kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $budgets->links() }}</div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Pazarlama Bütçesi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li>Dönem bazlı bütçeyi burada tanımla veya güncelle.</li>
            <li>Allocation JSON alanında kanal dağılımını key-value olarak gir.</li>
            <li>Harcama kampanya verisinden otomatik hesaplandığı için manuel girmene gerek yoktur.</li>
        </ol>
    </details>

</div>
@endsection
