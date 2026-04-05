@extends('manager.layouts.app')

@section('title', 'Öğrenci Ödemeleri & Faturalar')
@section('page_title', 'Öğrenci Ödemeleri')
@section('page_subtitle', 'Ödeme kaydı oluştur · Fatura indir · Durum takibi')

@push('head')
<style>
.pay-kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 24px; }
@media(max-width:900px){ .pay-kpi-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px){ .pay-kpi-row { grid-template-columns: 1fr; } }

.pay-kpi {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; padding: 14px 16px;
}
.pay-kpi-label { font-size: 11px; font-weight: 700; color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px; margin-bottom: 6px; }
.pay-kpi-value { font-size: 24px; font-weight: 800; color: var(--u-text); }
.pay-kpi-sub   { font-size: 11px; color: var(--u-muted); margin-top: 3px; }
.pay-kpi.ok    { border-color: rgba(22,163,74,.25); }
.pay-kpi.warn  { border-color: rgba(217,119,6,.25); }
.pay-kpi.danger{ border-color: rgba(220,38,38,.2); }

.pay-filters {
    display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;
}
.pay-filters input, .pay-filters select {
    background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 7px; padding: 6px 10px; font-size: 13px;
    color: var(--u-text); outline: none;
}
.pay-filters input:focus, .pay-filters select:focus { border-color: var(--u-brand); }

.pay-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pay-table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700;
    color: var(--u-muted); text-transform: uppercase; letter-spacing:.4px;
    border-bottom: 2px solid var(--u-line); }
.pay-table td { padding: 11px 12px; border-bottom: 1px solid var(--u-line); color: var(--u-text); vertical-align: middle; }
.pay-table tr:hover td { background: var(--u-bg); }

.pay-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.pay-actions form { display: inline; }

.modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 1000;
    align-items: center; justify-content: center;
}
.modal-backdrop.open { display: flex; }
.modal-box {
    background: var(--u-card); border-radius: 14px; padding: 24px;
    width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,.3);
    max-height: 90vh; overflow-y: auto;
}
.modal-title { font-size: 17px; font-weight: 800; color: var(--u-text); margin-bottom: 16px; }
.form-row { margin-bottom: 14px; }
.form-row label { display: block; font-size: 12px; font-weight: 700; color: var(--u-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .3px; }
.form-row input, .form-row select, .form-row textarea {
    width: 100%; background: var(--u-bg); border: 1px solid var(--u-line);
    border-radius: 8px; padding: 9px 11px; font-size: 13px; color: var(--u-text);
    outline: none; box-sizing: border-box;
}
.form-row input:focus, .form-row select:focus, .form-row textarea:focus { border-color: var(--u-brand); }
</style>
@endpush

@section('content')

{{-- Flash --}}
@if(session('status'))
    <div class="card" style="border-color:var(--u-ok);background:rgba(22,163,74,.06);margin-bottom:16px;padding:12px 16px;font-size:13px;color:var(--u-ok-fg);">
        ✓ {{ session('status') }}
    </div>
@endif

{{-- KPI Satırı --}}
<div class="pay-kpi-row">
    <div class="pay-kpi">
        <div class="pay-kpi-label">Toplam Tahakkuk</div>
        <div class="pay-kpi-value">€ {{ number_format($kpi['total'], 0, ',', '.') }}</div>
        <div class="pay-kpi-sub">Tüm kayıtlar</div>
    </div>
    <div class="pay-kpi ok">
        <div class="pay-kpi-label">Tahsil Edilen</div>
        <div class="pay-kpi-value" style="color:var(--u-ok-fg)">€ {{ number_format($kpi['paid'], 0, ',', '.') }}</div>
        <div class="pay-kpi-sub">Ödendi</div>
    </div>
    <div class="pay-kpi warn">
        <div class="pay-kpi-label">Bekleyen</div>
        <div class="pay-kpi-value" style="color:#d97706">€ {{ number_format($kpi['pending'], 0, ',', '.') }}</div>
        <div class="pay-kpi-sub">{{ $kpi['count_pending'] }} kayıt</div>
    </div>
    <div class="pay-kpi danger">
        <div class="pay-kpi-label">Vadesi Geçmiş</div>
        <div class="pay-kpi-value" style="color:#dc2626">€ {{ number_format($kpi['overdue'], 0, ',', '.') }}</div>
        <div class="pay-kpi-sub">{{ $kpi['count_overdue'] }} kayıt</div>
    </div>
</div>

{{-- Üst Bar --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
    <h2 style="font-size:16px;font-weight:800;color:var(--u-text);margin:0;">Ödeme Kayıtları</h2>
    <button onclick="document.getElementById('newPaymentModal').classList.add('open')"
        class="btn" style="font-size:13px;padding:8px 16px;">
        + Yeni Ödeme Kaydı
    </button>
</div>

{{-- Filtreler --}}
<form method="GET" action="/manager/payments">
<div class="pay-filters">
    <input type="text" name="search" value="{{ $search }}" placeholder="Öğrenci ID veya fatura no..." style="min-width:200px;">
    <select name="status">
        <option value="">Tüm Durumlar</option>
        <option value="pending"   {{ $status==='pending'   ? 'selected' : '' }}>Bekleyen</option>
        <option value="paid"      {{ $status==='paid'      ? 'selected' : '' }}>Ödendi</option>
        <option value="overdue"   {{ $status==='overdue'   ? 'selected' : '' }}>Vadesi Geçmiş</option>
        <option value="cancelled" {{ $status==='cancelled' ? 'selected' : '' }}>İptal</option>
    </select>
    <select name="month">
        <option value="">Tüm Aylar</option>
        @foreach($months as $m)
            <option value="{{ $m }}" {{ $month===$m ? 'selected' : '' }}>{{ $m }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn alt" style="font-size:13px;padding:7px 14px;">Filtrele</button>
    <a href="/manager/payments" class="btn" style="font-size:13px;padding:7px 14px;background:transparent;border:1px solid var(--u-line);color:var(--u-muted);">Sıfırla</a>
</div>
</form>

{{-- Tablo --}}
<div class="card" style="padding:0;overflow:hidden;">
    @if($payments->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:14px;">
            Kayıt bulunamadı.
            @if(!$search && !$status)
                <br><small>Sağ üstten yeni ödeme kaydı oluşturun.</small>
            @endif
        </div>
    @else
        <div style="overflow-x:auto;">
        <table class="pay-table">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Öğrenci</th>
                    <th>Açıklama</th>
                    <th>Tutar</th>
                    <th>Vade</th>
                    <th>Durum</th>
                    <th>İndirme</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
            @foreach($payments as $p)
                @php
                    $badgeClass = match($p->status) {
                        'paid'      => 'ok',
                        'overdue'   => 'danger',
                        'cancelled' => 'info',
                        default     => 'warn',
                    };
                    $badgeLabel = match($p->status) {
                        'paid'      => 'Ödendi',
                        'overdue'   => 'Vadesi Geçti',
                        'cancelled' => 'İptal',
                        default     => 'Bekliyor',
                    };
                @endphp
                <tr style="{{ $p->contract_updated_at ? 'background:rgba(234,179,8,.05);' : '' }}">
                    <td>
                        <code style="font-size:12px;background:var(--u-bg);padding:2px 6px;border-radius:4px;">{{ $p->invoice_number }}</code>
                        @if($p->contract_updated_at)
                            <div style="margin-top:4px;">
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:5px;padding:2px 7px;font-size:10px;font-weight:700;">
                                    ⚠ Sözleşme güncellendi
                                </span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:700;font-size:13px;">{{ $p->student_id }}</div>
                        <div style="font-size:11px;color:var(--u-muted);">{{ $studentNames[$p->student_id] ?? (str_starts_with($p->student_id,'GUEST-') ? 'Misafir Başvuru' : '—') }}</div>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $p->description }}">
                        {{ $p->description }}
                    </td>
                    <td style="font-weight:700;">
                        € {{ number_format($p->amount_eur, 2, ',', '.') }}
                        @if($p->contract_updated_at)
                            <div style="font-size:10px;color:#92400e;margin-top:2px;">
                                Güncellendi: {{ $p->contract_updated_at->format('d.m.Y') }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:13px;">{{ $p->due_date->format('d.m.Y') }}</div>
                        @if($p->paid_at)
                            <div style="font-size:11px;color:var(--u-muted);">Ödendi: {{ $p->paid_at->format('d.m.Y') }}</div>
                        @endif
                    </td>
                    <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>

                    {{-- İndirme İzleme --}}
                    <td style="white-space:nowrap;">
                        @if($p->download_count > 0)
                            <div style="font-size:12px;font-weight:700;color:var(--u-text);">
                                {{ $p->download_count }}×
                            </div>
                            <div style="font-size:10px;color:var(--u-muted);">
                                Son: {{ $p->last_downloaded_at->format('d.m.Y H:i') }}
                            </div>
                        @else
                            <span style="font-size:11px;color:var(--u-muted);">—</span>
                        @endif
                    </td>

                    <td>
                        <div class="pay-actions">
                            {{-- Ön İzleme --}}
                            <a href="{{ route('manager.payments.preview', $p) }}"
                               target="_blank"
                               class="btn alt" style="font-size:12px;padding:5px 10px;" title="Faturayı Önizle">
                                👁 Önizle
                            </a>

                            {{-- PDF İndir --}}
                            <a href="{{ route('manager.payments.invoice', $p) }}"
                               class="btn" style="font-size:12px;padding:5px 10px;background:#6d28d9;color:#fff;" title="PDF İndir">
                                ↓ PDF
                            </a>

                            {{-- Değişiklik detayı --}}
                            @if($p->contract_updated_at && $p->contract_change_log)
                                <button onclick="openChangeLog({{ $p->id }}, {{ json_encode($p->contract_change_log) }})"
                                    style="font-size:12px;padding:5px 10px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:6px;cursor:pointer;">
                                    ⚠ Değişiklik
                                </button>
                            @endif

                            {{-- Ödendi İşaretle --}}
                            @if(in_array($p->status, ['pending','overdue']))
                                <button onclick="openMarkPaid({{ $p->id }}, '{{ $p->invoice_number }}')"
                                    class="btn ok" style="font-size:12px;padding:5px 10px;">
                                    ✓ Ödendi
                                </button>
                            @endif

                            {{-- İptal --}}
                            @if($p->status !== 'paid')
                                <form method="POST" action="{{ route('manager.payments.cancel', $p) }}"
                                      onsubmit="return confirm('{{ $p->invoice_number }} iptal edilsin mi?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn warn" style="font-size:12px;padding:5px 10px;">İptal</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:14px 16px;">
            {{ $payments->links() }}
        </div>
    @endif
</div>

{{-- ══ Modal: Yeni Ödeme Kaydı ═══════════════════════════════════════════ --}}
<div class="modal-backdrop" id="newPaymentModal">
    <div class="modal-box">
        <div class="modal-title">Yeni Ödeme Kaydı</div>
        <form method="POST" action="{{ route('manager.payments.store') }}">
            @csrf
            <div class="form-row">
                <label>Öğrenci</label>
                <select name="student_id" required>
                    <option value="">— Öğrenci seçin —</option>
                    @foreach($students as $s)
                        <option value="{{ $s->student_id }}">
                            {{ $s->name }} ({{ $s->student_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <label>Açıklama</label>
                <input type="text" name="description" required placeholder="ör. Basic Paket — 1. Taksit">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="form-row">
                    <label>Tutar</label>
                    <input type="number" name="amount_eur" step="0.01" min="0.01" required placeholder="1490.00">
                </div>
                <div class="form-row">
                    <label>Para Birimi</label>
                    <select name="currency">
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                        <option value="TRY">TRY</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <label>Vade Tarihi</label>
                <input type="date" name="due_date" required value="{{ now()->addDays(14)->format('Y-m-d') }}">
            </div>
            <div class="form-row">
                <label>Notlar (isteğe bağlı)</label>
                <textarea name="notes" rows="2" placeholder="Ek bilgi..."></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('newPaymentModal').classList.remove('open')"
                    class="btn" style="background:transparent;border:1px solid var(--u-line);color:var(--u-muted);">
                    Vazgeç
                </button>
                <button type="submit" class="btn">Kaydet</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ Modal: Ödendi İşaretle ═════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="markPaidModal">
    <div class="modal-box">
        <div class="modal-title">Ödendi Olarak İşaretle</div>
        <form method="POST" id="markPaidForm">
            @csrf @method('PATCH')
            <div class="form-row">
                <label>Ödeme Yöntemi</label>
                <select name="payment_method" required>
                    <option value="bank_transfer">Banka Transferi</option>
                    <option value="credit_card">Kredi Kartı</option>
                    <option value="cash">Nakit</option>
                    <option value="other">Diğer</option>
                </select>
            </div>
            <div class="form-row">
                <label>Ödeme Tarihi</label>
                <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="form-row">
                <label>Not (isteğe bağlı)</label>
                <input type="text" name="notes" placeholder="Banka dekontu no, vb.">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('markPaidModal').classList.remove('open')"
                    class="btn" style="background:transparent;border:1px solid var(--u-line);color:var(--u-muted);">
                    Vazgeç
                </button>
                <button type="submit" class="btn ok">Ödendi Onayla</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ Modal: Değişiklik Geçmişi ══════════════════════════════════════════ --}}
<div class="modal-backdrop" id="changeLogModal">
    <div class="modal-box" style="max-width:520px;">
        <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
            <span style="background:#fef3c7;border-radius:6px;padding:4px 8px;font-size:14px;">⚠</span>
            Sözleşme Değişiklik Geçmişi
        </div>
        <div id="changeLogContent"
             style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;padding:14px;
                    font-family:monospace;font-size:12px;line-height:1.7;color:var(--u-text);
                    white-space:pre-wrap;max-height:320px;overflow-y:auto;">
        </div>
        <div style="margin-top:14px;font-size:12px;color:var(--u-muted);">
            Bu kayıt sözleşmedeki değişiklikler nedeniyle otomatik güncellenmiştir.
            Yeni fatura PDF'i indirmenizi öneririz.
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
            <button type="button" onclick="document.getElementById('changeLogModal').classList.remove('open')"
                class="btn" style="background:transparent;border:1px solid var(--u-line);color:var(--u-muted);">
                Kapat
            </button>
            <form method="POST" id="acknowledgeForm">
                @csrf @method('PATCH')
                <button type="submit" class="btn ok" style="font-size:13px;">✓ Okundu, uyarıyı kaldır</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function openMarkPaid(id, invoiceNo) {
    var form = document.getElementById('markPaidForm');
    form.action = '/manager/payments/' + id + '/mark-paid';
    document.getElementById('markPaidModal').classList.add('open');
}

function openChangeLog(id, logText) {
    document.getElementById('changeLogContent').textContent = logText;
    document.getElementById('acknowledgeForm').action = '/manager/payments/' + id + '/acknowledge';
    document.getElementById('changeLogModal').classList.add('open');
}

// Modal dışına tıklayınca kapat
document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) el.classList.remove('open');
    });
});
</script>
@endpush
