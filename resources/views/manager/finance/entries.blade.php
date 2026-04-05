@extends('manager.layouts.app')

@section('title', 'Gelir & Gider Kayıtları — MentorDE')
@section('page_title', 'Gelir & Gider Kayıtları')
@section('page_subtitle', 'Manuel kayıt, CSV import · ' . $month)

@section('topbar-actions')
    <a href="{{ route('manager.finance.dashboard') }}" class="btn alt">📊 Özet</a>
@endsection

@section('content')
@php
    $allCategories     = \App\Models\CompanyFinanceEntry::allCategories();
    $incomeCategories  = \App\Models\CompanyFinanceEntry::$incomeCategories;
    $expenseCategories = \App\Models\CompanyFinanceEntry::$expenseCategories;
    $net               = $totalIncome - $totalExpense;
    $filterCats        = $type === 'income' ? $incomeCategories : ($type === 'expense' ? $expenseCategories : $allCategories);
@endphp

{{-- Filtreler --}}
<div class="panel" style="margin-bottom:16px;padding:12px 16px;">
    <form method="GET" action="{{ route('manager.finance.entries') }}"
          style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <label style="font-size:13px;font-weight:600;color:var(--u-muted);">Ay:</label>
        <select name="month" onchange="this.form.submit()" style="padding:6px 10px;border-radius:6px;font-size:13px;min-width:140px;">
            @foreach($months as $m)
                <option value="{{ $m }}" @selected($m === $month)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $m)->locale('tr')->isoFormat('MMMM YYYY') }}
                </option>
            @endforeach
        </select>

        <label style="font-size:13px;font-weight:600;color:var(--u-muted);">Tür:</label>
        <select name="type" onchange="this.form.submit()" style="padding:6px 10px;border-radius:6px;font-size:13px;">
            <option value="" @selected($type === '')>Tümü</option>
            <option value="income" @selected($type === 'income')>Gelir</option>
            <option value="expense" @selected($type === 'expense')>Gider</option>
        </select>

        <label style="font-size:13px;font-weight:600;color:var(--u-muted);">Kategori:</label>
        <select name="category" onchange="this.form.submit()" style="padding:6px 10px;border-radius:6px;font-size:13px;min-width:160px;">
            <option value="">Tüm Kategoriler</option>
            @foreach($filterCats as $key => $label)
                <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
            @endforeach
        </select>

        @if($type || $category)
            <a href="{{ route('manager.finance.entries') }}?month={{ $month }}" class="btn alt" style="padding:6px 12px;font-size:13px;">✕ Temizle</a>
        @endif
    </form>
</div>

{{-- Özet Çubuğu --}}
@php $net = $totalIncome - $totalExpense; @endphp
<div class="grid3" style="margin-bottom:18px;">
    <div class="panel" style="border-left:4px solid var(--u-ok);padding:12px 16px;">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Toplam Gelir</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-ok);">{{ number_format($totalIncome, 0, ',', '.') }} <span style="font-size:12px;font-weight:400;">EUR</span></div>
        @if($contractIncomeTotal > 0)
        <div style="font-size:11px;color:var(--u-muted);margin-top:3px;">Sözleşme: <strong style="color:#16a34a;">{{ number_format($contractIncomeTotal,0,',','.') }}</strong></div>
        @endif
    </div>
    <div class="panel" style="border-left:4px solid var(--u-danger);padding:12px 16px;">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Toplam Gider</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-danger);">{{ number_format($totalExpense, 0, ',', '.') }} <span style="font-size:12px;font-weight:400;">EUR</span></div>
    </div>
    <div class="panel" style="border-left:4px solid {{ $net >= 0 ? 'var(--u-ok)' : 'var(--u-danger)' }};padding:12px 16px;">
        <div style="font-size:11px;color:var(--u-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Net</div>
        <div style="font-size:22px;font-weight:800;color:{{ $net >= 0 ? 'var(--u-ok)' : 'var(--u-danger)' }};">
            {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 0, ',', '.') }} <span style="font-size:12px;font-weight:400;">EUR</span>
        </div>
    </div>
</div>

{{-- Bu aya ait sözleşme gelirleri --}}
@if($contractEntries->isNotEmpty())
<div class="panel" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h3 style="margin:0;font-size:14px;">📄 Bu Aya Ait Sözleşme Gelirleri <span style="font-size:12px;font-weight:400;color:var(--u-muted);">({{ $contractEntries->count() }} adet, canlı)</span></h3>
        <span style="font-size:14px;font-weight:800;color:#16a34a;">+{{ number_format($contractIncomeTotal,0,',','.') }} EUR</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid var(--u-line);">
                <th style="text-align:left;padding:6px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">İMZA TARİHİ</th>
                <th style="text-align:left;padding:6px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">ÖĞRENCİ</th>
                <th style="text-align:left;padding:6px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">PAKET</th>
                <th style="text-align:left;padding:6px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">DURUM</th>
                <th style="text-align:right;padding:6px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TUTAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contractEntries as $c)
            <tr style="border-bottom:1px solid var(--u-line);">
                <td style="padding:7px 10px;color:var(--u-muted);white-space:nowrap;">{{ $c->contract_signed_at?->format('d.m.Y') ?? '—' }}</td>
                <td style="padding:7px 10px;font-weight:600;color:var(--u-text);">{{ $c->first_name }} {{ $c->last_name }}</td>
                <td style="padding:7px 10px;color:var(--u-muted);">{{ $c->selected_package_title ?? '—' }}</td>
                <td style="padding:7px 10px;">
                    <span class="badge {{ $c->contract_status === 'approved' ? 'info' : 'ok' }}" style="font-size:10px;">
                        {{ $c->contract_status === 'approved' ? 'Yönetici Onaylı' : 'İmzalı' }}
                    </span>
                </td>
                <td style="padding:7px 10px;text-align:right;font-weight:800;color:#16a34a;white-space:nowrap;">
                    +{{ number_format((float)$c->contract_amount_eur, 0, ',', '.') }} EUR
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Yeni Manuel Kayıt --}}
<div class="panel" style="margin-bottom:16px;" id="new-entry-panel">
    <h3 style="margin:0 0 14px;font-size:14px;">+ Yeni Manuel Kayıt</h3>
    <div id="new-entry-body">
        <form method="POST" action="{{ route('manager.finance.store') }}">
            @csrf
            <div class="grid3">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Tarih *</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}"
                           style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Tür *</label>
                    <select name="type" id="new-type" onchange="updateCategories('new-type','new-category')"
                            style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                        <option value="income" @selected(old('type','income')==='income')>Gelir</option>
                        <option value="expense" @selected(old('type')==='expense')>Gider</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Kategori *</label>
                    <select name="category" id="new-category"
                            style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                        @foreach($incomeCategories as $k => $v)
                            <option value="{{ $k }}" @selected(old('category')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid3" style="margin-top:0;">
                <div style="grid-column:span 2;">
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Başlık / Açıklama *</label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="200"
                           placeholder="Ör: Öğrenci kayıt ücreti — Ahmet Y."
                           style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Referans No</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}" maxlength="100"
                           placeholder="Fatura / makbuz no"
                           style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;">
                </div>
            </div>
            <div class="grid2" style="margin-top:0;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Tutar *</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01"
                           placeholder="0,00"
                           style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Para Birimi *</label>
                    <select name="currency" style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;" required>
                        <option value="EUR" @selected(old('currency','EUR')==='EUR')>EUR — Euro</option>
                        <option value="TRY" @selected(old('currency')==='TRY')>TRY — Türk Lirası</option>
                        <option value="USD" @selected(old('currency')==='USD')>USD — Dolar</option>
                        <option value="GBP" @selected(old('currency')==='GBP')>GBP — Sterlin</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:6px;">
                <label style="font-size:12px;font-weight:600;color:var(--u-muted);display:block;margin-bottom:4px;">Notlar</label>
                <textarea name="notes" rows="2" maxlength="1000"
                          placeholder="Opsiyonel açıklama..."
                          style="width:100%;padding:8px 10px;border-radius:6px;font-size:13px;resize:vertical;">{{ old('notes') }}</textarea>
            </div>
            <div style="margin-top:12px;display:flex;gap:10px;">
                <button type="submit" class="btn ok">Kaydet</button>
                <button type="button" class="btn alt" onclick="togglePanel('new-entry-body','new-entry-caret')">İptal</button>
            </div>
        </form>
    </div>
</div>

{{-- CSV Import --}}
<div class="panel" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;"
         onclick="togglePanel('csv-body','csv-caret')">
        <h3 style="margin:0;font-size:14px;">📥 Banka CSV İçe Aktar</h3>
        <span id="csv-caret" style="font-size:12px;transition:transform .2s;">▾</span>
    </div>
    <div id="csv-body" style="display:none;margin-top:14px;">
        <div style="background:var(--u-bg);border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:12px;color:var(--u-muted);line-height:1.7;">
            <strong style="color:var(--u-text);">CSV Format (noktalı virgül ayırıcı, Avrupa standardı):</strong><br>
            <code style="font-family:monospace;background:var(--u-line);padding:2px 6px;border-radius:4px;">Tarih;Başlık;Tutar;Para Birimi;Tür;Referans No;İşlem ID;Kategori</code><br>
            Örnek: <code style="font-family:monospace;background:var(--u-line);padding:2px 6px;border-radius:4px;">2026-04-01;Öğrenci Ücreti;1.500,00;EUR;income;INV-001;TXN123;student_fee</code><br>
            Tür: <strong>income</strong> veya <strong>gelir</strong> = Gelir, diğerleri = Gider<br>
            İşlem ID mevcutsa aynı kayıt tekrar aktarılmaz.
        </div>
        <form method="POST" action="{{ route('manager.finance.import-csv') }}" enctype="multipart/form-data"
              style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            @csrf
            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" style="display:none;" required>
            <label for="csv_file" class="btn alt" style="cursor:pointer;">📂 CSV Dosyası Seç</label>
            <span id="csv-filename" style="font-size:13px;color:var(--u-muted);">Dosya seçilmedi</span>
            <button type="submit" class="btn ok">İçe Aktar</button>
        </form>
    </div>
</div>

{{-- Kayıt Tablosu --}}
<div class="panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h3 style="margin:0;">Kayıtlar <span style="font-size:13px;font-weight:400;color:var(--u-muted);">({{ $entries->count() }} adet)</span></h3>
    </div>

    @if($entries->isEmpty())
        <div style="text-align:center;padding:40px 20px;color:var(--u-muted);">
            <div style="font-size:32px;margin-bottom:8px;">📒</div>
            <div style="font-size:14px;font-weight:600;">Bu dönemde kayıt bulunamadı.</div>
            <div style="font-size:13px;margin-top:4px;">Yeni kayıt eklemek için yukarıdaki formu kullanın.</div>
        </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:2px solid var(--u-line);">
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TARİH</th>
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">BAŞLIK</th>
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">KATEGORİ</th>
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TÜR</th>
                    <th style="text-align:right;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">TUTAR</th>
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">REF NO</th>
                    <th style="text-align:left;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">KAYNAK</th>
                    <th style="text-align:right;padding:7px 10px;color:var(--u-muted);font-size:11px;font-weight:700;">İŞLEM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr style="border-bottom:1px solid var(--u-line);" id="row-{{ $entry->id }}">
                    <td style="padding:8px 10px;color:var(--u-muted);white-space:nowrap;">{{ $entry->entry_date->format('d.m.Y') }}</td>
                    <td style="padding:8px 10px;color:var(--u-text);font-weight:500;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $entry->title }}">{{ $entry->title }}</td>
                    <td style="padding:8px 10px;white-space:nowrap;">
                        <span style="font-size:11px;padding:2px 8px;border-radius:999px;background:var(--u-line);color:var(--u-muted);font-weight:600;">
                            {{ $allCategories[$entry->category] ?? $entry->category }}
                        </span>
                    </td>
                    <td style="padding:8px 10px;">
                        @if($entry->type === 'income')
                            <span class="badge ok">Gelir</span>
                        @else
                            <span class="badge danger">Gider</span>
                        @endif
                    </td>
                    <td style="padding:8px 10px;text-align:right;font-weight:700;color:{{ $entry->type==='income' ? 'var(--u-ok)' : 'var(--u-danger)' }};white-space:nowrap;">
                        {{ $entry->type === 'income' ? '+' : '−' }}{{ number_format($entry->amount, 2, ',', '.') }}
                        <span style="font-size:11px;font-weight:400;color:var(--u-muted);">{{ $entry->currency }}</span>
                    </td>
                    <td style="padding:8px 10px;color:var(--u-muted);font-size:12px;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $entry->reference_no ?? '—' }}
                    </td>
                    <td style="padding:8px 10px;">
                        @if($entry->source === 'manual')
                            <span style="font-size:11px;color:var(--u-muted);">Manuel</span>
                        @elseif($entry->source === 'bank_import')
                            <span style="font-size:11px;color:var(--u-info);">Banka</span>
                        @else
                            <span style="font-size:11px;color:var(--u-warn);">API</span>
                        @endif
                    </td>
                    <td style="padding:8px 10px;text-align:right;white-space:nowrap;">
                        <button type="button" class="btn alt" style="padding:4px 10px;font-size:12px;"
                                onclick="toggleEdit({{ $entry->id }})">Düzenle</button>
                        <form method="POST" action="{{ route('manager.finance.destroy', $entry) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Kaydı silmek istediğinizden emin misiniz?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn warn" style="padding:4px 10px;font-size:12px;">Sil</button>
                        </form>
                    </td>
                </tr>
                {{-- Inline Edit Row --}}
                <tr id="edit-row-{{ $entry->id }}" style="display:none;background:var(--u-bg);">
                    <td colspan="8" style="padding:14px 10px;">
                        <form method="POST" action="{{ route('manager.finance.update', $entry) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid4" style="margin-bottom:8px;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Tarih</label>
                                    <input type="date" name="entry_date" value="{{ $entry->entry_date->format('Y-m-d') }}"
                                           style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Tür</label>
                                    <select name="type" id="edit-type-{{ $entry->id }}"
                                            onchange="updateCategories('edit-type-{{ $entry->id }}','edit-cat-{{ $entry->id }}')"
                                            style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                        <option value="income" @selected($entry->type==='income')>Gelir</option>
                                        <option value="expense" @selected($entry->type==='expense')>Gider</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Kategori</label>
                                    <select name="category" id="edit-cat-{{ $entry->id }}"
                                            style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                        @foreach($allCategories as $k => $v)
                                            <option value="{{ $k }}" @selected($entry->category===$k)>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Para Birimi</label>
                                    <select name="currency" style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                        <option value="EUR" @selected($entry->currency==='EUR')>EUR</option>
                                        <option value="TRY" @selected($entry->currency==='TRY')>TRY</option>
                                        <option value="USD" @selected($entry->currency==='USD')>USD</option>
                                        <option value="GBP" @selected($entry->currency==='GBP')>GBP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid3" style="margin-bottom:8px;">
                                <div style="grid-column:span 2;">
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Başlık</label>
                                    <input type="text" name="title" value="{{ $entry->title }}" maxlength="200"
                                           style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Tutar</label>
                                    <input type="number" name="amount" value="{{ $entry->amount }}" step="0.01" min="0.01"
                                           style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;" required>
                                </div>
                            </div>
                            <div class="grid2" style="margin-bottom:8px;">
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Referans No</label>
                                    <input type="text" name="reference_no" value="{{ $entry->reference_no }}" maxlength="100"
                                           style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;">
                                </div>
                                <div>
                                    <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:3px;">Notlar</label>
                                    <input type="text" name="notes" value="{{ $entry->notes }}" maxlength="1000"
                                           style="width:100%;padding:6px 8px;border-radius:6px;font-size:12px;">
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <button type="submit" class="btn ok" style="padding:5px 14px;font-size:12px;">Güncelle</button>
                                <button type="button" class="btn alt" style="padding:5px 14px;font-size:12px;"
                                        onclick="toggleEdit({{ $entry->id }})">İptal</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Kategori verileri
var incCats = @json(\App\Models\CompanyFinanceEntry::$incomeCategories);
var expCats = @json(\App\Models\CompanyFinanceEntry::$expenseCategories);

function updateCategories(typeSelectId, catSelectId) {
    var type = document.getElementById(typeSelectId).value;
    var cats = type === 'income' ? incCats : expCats;
    var sel  = document.getElementById(catSelectId);
    var prev = sel.value;
    sel.innerHTML = '';
    Object.entries(cats).forEach(function([k, v]) {
        var opt = document.createElement('option');
        opt.value = k; opt.textContent = v;
        if (k === prev) opt.selected = true;
        sel.appendChild(opt);
    });
    if (!sel.value && sel.options.length) sel.options[0].selected = true;
}

function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    row.style.display = (row.style.display === 'none' || !row.style.display) ? 'table-row' : 'none';
}

function togglePanel(bodyId, caretId) {
    var body  = document.getElementById(bodyId);
    var caret = document.getElementById(caretId);
    var open  = body.style.display !== 'none';
    body.style.display    = open ? 'none' : 'block';
    caret.style.transform = open ? '' : 'rotate(180deg)';
}

// CSV dosya adı göster
document.getElementById('csv_file') && document.getElementById('csv_file').addEventListener('change', function() {
    var label = document.getElementById('csv-filename');
    if (label) label.textContent = this.files[0] ? this.files[0].name : 'Dosya seçilmedi';
});

// Sayfa açılışında new-entry-body'yi request'e göre handle et
(function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'new') {
        var body  = document.getElementById('new-entry-body');
        var caret = document.getElementById('new-entry-caret');
        if (body)  body.style.display = 'block';
        if (caret) caret.style.transform = 'rotate(180deg)';
    }
})();
</script>
@endpush
