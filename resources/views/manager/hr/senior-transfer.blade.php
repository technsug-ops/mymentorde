@extends('manager.layouts.app')
@section('title', 'Eğitim Danışmanı Devir')
@section('page_title', 'Eğitim Danışmanı Devir')

@section('content')

<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/hr" style="color:#1e40af;text-decoration:none;font-weight:700;">İnsan Kaynakları</a>
    <span>›</span><span>Eğitim Danışmanı Devir</span>
</div>

@if(session('status'))
<div style="margin-bottom:14px;padding:10px 16px;border-radius:8px;background:#dcfce7;color:#166534;font-weight:600;font-size:13px;border:1px solid #bbf7d0;">{{ session('status') }}</div>
@endif

<div class="grid2" style="gap:14px;align-items:start;">

    {{-- Devir Formu --}}
    <section class="panel">
        <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:4px;">🔄 Başvuru Devri</div>
        <div style="font-size:11px;color:var(--u-muted);margin-bottom:16px;">Bir seniora atanmış tüm başvuruları başka bir seniora toplu aktar.</div>

        <form method="POST" action="/manager/hr/senior-transfer" id="transferForm">
            @csrf

            <div style="margin-bottom:14px;">
                <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:5px;">Devirden — Kaynak Eğitim Danışmanı</label>
                <select name="from_senior_id" id="fromSelect" required
                    style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                    <option value="">— Seçin —</option>
                    @foreach($seniors as $s)
                    <option value="{{ $s->id }}" data-email="{{ $s->email }}">
                        {{ $s->name }}
                        ({{ $assignedCounts[$s->email] ?? 0 }} başvuru)
                    </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:11px;font-weight:700;color:var(--u-muted);display:block;margin-bottom:5px;">Devire — Hedef Eğitim Danışmanı</label>
                <select name="to_senior_id" required
                    style="width:100%;padding:8px 12px;border:1.5px solid var(--u-line);border-radius:8px;font-size:13px;background:var(--u-bg);color:var(--u-text);">
                    <option value="">— Seçin —</option>
                    @foreach($seniors as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="transferWarning" style="display:none;padding:10px 14px;background:#fef9c3;border:1px solid #fde68a;border-radius:7px;font-size:12px;color:#92400e;margin-bottom:14px;">
                ⚠ <span id="transferCount">0</span> başvuru devredilecek. Bu işlem geri alınamaz.
            </div>

            <button type="submit" class="btn warn" style="width:100%;font-size:13px;padding:10px;">Devret →</button>
        </form>
    </section>

    {{-- Eğitim Danışmanı Listesi --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">👥 Eğitim Danışmanı Yükü</div>
        @foreach($seniors as $s)
        @php $cnt = $assignedCounts[$s->email] ?? 0; @endphp
        <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;flex-shrink:0;">
                {{ strtoupper(substr($s->name, 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:700;color:var(--u-text);">{{ $s->name }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $s->email }}</div>
            </div>
            <div style="text-align:right;">
                <span style="font-size:18px;font-weight:800;color:{{ $cnt > 0 ? '#1e40af' : 'var(--u-muted)' }};">{{ $cnt }}</span>
                <div style="font-size:10px;color:var(--u-muted);">başvuru</div>
            </div>
        </div>
        @endforeach
    </section>

</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    var counts = @json($assignedCounts);
    var fromSel = document.getElementById('fromSelect');
    var warning = document.getElementById('transferWarning');
    var cntSpan = document.getElementById('transferCount');

    fromSel?.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var email = opt ? opt.dataset.email : '';
        var cnt = email ? (counts[email] || 0) : 0;
        if (cnt > 0) {
            cntSpan.textContent = cnt;
            warning.style.display = 'block';
        } else {
            warning.style.display = 'none';
        }
    });

    document.getElementById('transferForm')?.addEventListener('submit', function(e) {
        var opt = fromSel?.options[fromSel.selectedIndex];
        var email = opt ? opt.dataset.email : '';
        var cnt = email ? (counts[email] || 0) : 0;
        var msg = cnt > 0
            ? cnt + ' başvuruyu devretmek istediğinize emin misiniz?'
            : 'Bu seniora atanmış başvuru yok. Yine de devam etmek istiyor musunuz?';
        if (!confirm(msg)) e.preventDefault();
    });
}());
</script>
@endpush
