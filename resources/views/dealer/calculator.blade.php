@extends('dealer.layouts.app')

@section('title', 'Komisyon Hesap Makinesi')
@section('page_title', 'Komisyon Hesap Makinesi')
@section('page_subtitle', 'Kac ogrenci getirirseniz ne kadar kazanirsiniz?')

@section('content')
<style>
.calc-card { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:14px; padding:22px 24px; margin-bottom:16px; }
.calc-info { background:linear-gradient(135deg,rgba(22,163,74,.07),rgba(8,145,178,.07)); border:1px solid rgba(22,163,74,.2); border-radius:12px; padding:16px 20px; margin-bottom:16px; }
.calc-info p { margin:0; font-size:14px; color:var(--u-text,#0f172a); line-height:1.6; }
.calc-form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.calc-field label { display:block; font-size:12px; font-weight:700; color:var(--u-muted,#64748b); margin-bottom:5px; text-transform:uppercase; letter-spacing:.03em; }
.calc-field input { width:100%; box-sizing:border-box; height:44px; padding:0 14px; border:1.5px solid var(--u-line,#e2e8f0); border-radius:9px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:16px; font-weight:600; outline:none; transition:border-color .15s; }
.calc-field input:focus { border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.calc-result-box { background:linear-gradient(to right,#0891b2,#16a34a); border-radius:12px; padding:22px 24px; text-align:center; color:#fff; margin-bottom:16px; }
.calc-result-box .cr-label { font-size:13px; font-weight:600; opacity:.85; margin-bottom:6px; }
.calc-result-box #calc-result { min-height:44px; display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap; }
.ms-table { width:100%; border-collapse:collapse; }
.ms-table th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); padding:8px 10px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.ms-table td { padding:10px 10px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.ms-table tr:last-child td { border-bottom:none; }
.ms-table tr:hover td { background:rgba(22,163,74,.04); }
.rev-list { display:flex; flex-direction:column; gap:8px; margin-top:10px; }
.rev-item { display:flex; align-items:center; gap:12px; padding:10px 12px; border:1px solid var(--u-line,#e2e8f0); border-radius:9px; }
.rev-item .ri-id { font-size:12px; color:var(--u-muted,#64748b); font-weight:600; min-width:60px; }
.rev-item .ri-body { flex:1; min-width:0; }
.rev-item .ri-earned { font-size:15px; font-weight:800; color:#16a34a; }
.rev-item .ri-meta { font-size:11px; color:var(--u-muted,#64748b); margin-top:1px; }
@media(max-width:700px){ .calc-form-row { grid-template-columns:1fr; } }
</style>

<div class="calc-info">
    <p>
        <strong>Komisyon Hesap Makinesi</strong> — Kac ogrenci yonlendirdigınize ve paket tutarına gore
        tahmini komisyon kazancinizi aninda hesaplayin.
        Gercek komisyon oranlariniz asagidaki milestone tablosuna gore belirlenir.
    </p>
</div>

<div class="calc-card">
    <h3 style="margin:0 0 16px;font-size:16px;font-weight:800;color:var(--u-text,#0f172a);">
        Hesap Makinesi
    </h3>
    <div class="calc-form-row">
        <div class="calc-field">
            <label for="calc-students">Öğrenci Sayısı</label>
            <input type="number" id="calc-students" min="1" value="5" placeholder="örnek: 10">
        </div>
        <div class="calc-field">
            <label for="calc-package">Ortalama Paket Tutarı (EUR)</label>
            <input type="number" id="calc-package" min="0" value="5000" placeholder="örnek: 5000">
        </div>
    </div>

    <div class="calc-result-box">
        <div class="cr-label">Tahmini Komisyon Kazanciniz</div>
        <div id="calc-result">
            <span style="font-size:28px;font-weight:900;">€0</span>
        </div>
    </div>
</div>

@if($milestones->isNotEmpty())
<div class="calc-card">
    <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;color:var(--u-text,#0f172a);">
        Komisyon Milestone Tablosu
    </h3>
    <p style="font-size:12px;color:var(--u-muted,#64748b);margin:0 0 12px;">
        Asagidaki oranlar mevcut aktif milestonelarinizi gostermektedir.
        Trigger kosulunu karsilayan milestone uzerinden komisyon hesaplanir.
    </p>
    <div style="overflow-x:auto;">
        <table class="ms-table">
            <thead>
                <tr>
                    <th>Milestone Adi</th>
                    <th>Tetikleyici</th>
                    <th>Gelir Tipi</th>
                    <th>Oran / Tutar</th>
                    <th>Gec. Bayi Tipleri</th>
                </tr>
            </thead>
            <tbody>
                @foreach($milestones as $ms)
                <tr>
                    <td><strong>{{ $ms->name_tr }}</strong></td>
                    <td style="font-size:12px;color:var(--u-muted,#64748b);">
                        {{ $ms->trigger_type }}
                        @if($ms->trigger_condition)
                            <br><span style="font-family:monospace;font-size:11px;">{{ json_encode($ms->trigger_condition) }}</span>
                        @endif
                    </td>
                    <td>{{ $ms->revenue_type }}</td>
                    <td>
                        @if($ms->percentage !== null)
                            <span style="font-weight:700;color:#16a34a;">%{{ $ms->percentage }}</span>
                        @endif
                        @if($ms->fixed_amount !== null)
                            <span style="font-weight:700;color:#0891b2;">{{ $ms->fixed_amount }} {{ $ms->fixed_currency ?? 'EUR' }}</span>
                        @endif
                    </td>
                    <td style="font-size:11px;color:var(--u-muted,#64748b);">
                        @if($ms->applicable_dealer_types)
                            {{ implode(', ', (array) $ms->applicable_dealer_types) }}
                        @else
                            Tüm Bayiler
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($studentRevenues->isNotEmpty())
<div class="calc-card">
    <h3 style="margin:0 0 6px;font-size:15px;font-weight:800;color:var(--u-text,#0f172a);">
        Son Gelir Kayıtları
    </h3>
    <p style="font-size:12px;color:var(--u-muted,#64748b);margin:0 0 10px;">Son 20 öğrenci gelir kaydı</p>
    <div class="rev-list">
        @foreach($studentRevenues as $rev)
        <div class="rev-item">
            <div class="ri-id">Öğr. #{{ $rev->student_id }}</div>
            <div class="ri-body">
                <div class="ri-meta">
                    Tip: {{ $rev->dealer_type ?? '-' }}
                    @if($rev->updated_at) · {{ optional($rev->updated_at)->format('d.m.Y') }} @endif
                </div>
            </div>
            <div>
                <div class="ri-earned">€{{ number_format((float) ($rev->total_earned ?? 0), 0, ',', '.') }}</div>
                @if(($rev->total_pending ?? 0) > 0)
                <div style="font-size:11px;color:var(--u-warn,#d97706);font-weight:600;">
                    + €{{ number_format((float) $rev->total_pending, 0, ',', '.') }} bekleniyor
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <div style="margin-top:12px;">
        <a href="/dealer/earnings" class="btn ok" style="font-size:13px;padding:8px 18px;">Tum Kazanclarim →</a>
    </div>
</div>
@endif

@push('scripts')
<script>
(function(){
    var milestones = @json($milestones->values());

    function calcCommission() {
        var students = parseInt(document.getElementById('calc-students').value) || 0;
        var pkg      = parseFloat(document.getElementById('calc-package').value) || 0;

        // En uygun milestone'u bul (percentage'i olan aktif milestone)
        var rate = 0;
        var matchedName = '';
        for (var i = 0; i < milestones.length; i++) {
            var m = milestones[i];
            if (m.percentage !== null && m.percentage > 0) {
                // trigger_condition'dan min/max ogrenci sayisi cek (eger varsa)
                var cond = m.trigger_condition || {};
                var minS = cond.min_students || cond.student_count_min || 0;
                var maxS = cond.max_students || cond.student_count_max || null;
                if (students >= minS && (maxS === null || students <= maxS)) {
                    rate = parseFloat(m.percentage);
                    matchedName = m.name_tr || '';
                    break;
                }
            }
        }

        // Hicbir milestone eslesmezse ilk percentage'li milestone'u kullan
        if (rate === 0) {
            for (var j = 0; j < milestones.length; j++) {
                if (milestones[j].percentage !== null && milestones[j].percentage > 0) {
                    rate = parseFloat(milestones[j].percentage);
                    matchedName = milestones[j].name_tr || '';
                    break;
                }
            }
        }

        var total = students * pkg * (rate / 100);
        var el = document.getElementById('calc-result');
        if (!el) return;
        el.innerHTML =
            '<span style="font-size:32px;font-weight:900;">€' +
            total.toLocaleString('de-DE', {minimumFractionDigits:0, maximumFractionDigits:0}) +
            '</span>' +
            '<span style="font-size:13px;opacity:.8;margin-left:4px;">(%' + rate + (matchedName ? ' · ' + matchedName : '') + ')</span>';
    }

    var studEl = document.getElementById('calc-students');
    var pkgEl  = document.getElementById('calc-package');
    if (studEl) studEl.addEventListener('input', calcCommission);
    if (pkgEl)  pkgEl.addEventListener('input', calcCommission);

    // Ilk yukleme
    calcCommission();
})();
</script>
@endpush

@endsection
