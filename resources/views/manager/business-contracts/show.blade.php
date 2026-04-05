@extends('manager.layouts.app')

@section('title', $contract->contract_no)
@section('page_title', 'Sözleşme Detay')

@section('content')
<div style="max-width:900px;">

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="{{ route('manager.business-contracts.index') }}" style="color:var(--u-muted);text-decoration:none;font-size:var(--tx-sm);">← Sözleşmeler</a>
    <h1 style="margin:0;font-size:var(--tx-lg);font-weight:700;flex:1;">{{ $contract->title }}</h1>
    <span class="badge {{ $contract->statusBadge() }}" style="font-size:var(--tx-sm);padding:5px 12px;">{{ $contract->statusLabel() }}</span>
</div>

@if(session('success'))
    <div style="background:var(--badge-ok-bg);color:var(--badge-ok-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="background:var(--badge-danger-bg);color:var(--badge-danger-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('error') }}</div>
@endif

<div class="grid2" style="gap:16px;margin-bottom:16px;">
    {{-- Sözleşme Bilgileri --}}
    <div class="card" style="padding:18px;">
        <h3 style="margin:0 0 12px;font-size:var(--tx-sm);font-weight:700;">Sözleşme Bilgileri</h3>
        <div style="font-size:var(--tx-sm);display:grid;gap:6px;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">No:</span>
                <strong style="font-family:monospace;font-size:var(--tx-xs);">{{ $contract->contract_no }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Tip:</span>
                <strong>{{ ucfirst($contract->contract_type) }}</strong>
            </div>
            @if($contract->contract_type === 'dealer')
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Dealer:</span>
                <strong>{{ $contract->dealer?->name ?? '—' }}</strong>
            </div>
            @else
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Çalışan:</span>
                <strong>{{ $contract->staffUser?->name ?? '—' }}</strong>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Oluşturan:</span>
                <span>{{ $contract->issuedByUser?->name ?? '—' }}</span>
            </div>
            @if($contract->issued_at)
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Gönderildi:</span>
                <span>{{ $contract->issued_at->format('d.m.Y H:i') }}</span>
            </div>
            @endif
            @if($contract->signed_at)
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">İmzalandı:</span>
                <span>{{ $contract->signed_at->format('d.m.Y H:i') }}</span>
            </div>
            @endif
            @if($contract->approved_at)
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--u-muted);">Onaylandı:</span>
                <span>{{ $contract->approved_at->format('d.m.Y H:i') }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Aksiyonlar --}}
    <div class="card" style="padding:18px;">
        <h3 style="margin:0 0 12px;font-size:var(--tx-sm);font-weight:700;">İşlemler</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">

            @if($contract->status === 'draft')
            <form method="POST" action="{{ route('manager.business-contracts.issue', $contract) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn" style="width:100%;">
                    📤 {{ $contract->contract_type === 'dealer' ? "Dealer'a Gönder" : 'Çalışana Gönder' }}
                </button>
            </form>
            @endif

            @if($contract->status === 'issued')
            <form method="POST" action="{{ route('manager.business-contracts.upload-signed', $contract) }}" enctype="multipart/form-data">
                @csrf
                <label style="font-size:var(--tx-xs);font-weight:600;display:block;margin-bottom:4px;">İmzalı PDF Yükle (Manager)</label>
                <input type="file" name="signed_file" accept=".pdf" class="form-control" style="margin-bottom:8px;">
                <button type="submit" class="btn ok" style="width:100%;">📄 Yükle</button>
            </form>
            @endif

            @if($contract->status === 'signed_uploaded')
            <form method="POST" action="{{ route('manager.business-contracts.approve', $contract) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn ok" style="width:100%;">✅ Onayla</button>
            </form>
            @endif

            @if($contract->signed_file_path)
            <a href="{{ route('manager.business-contracts.download-signed', $contract) }}" class="btn alt" style="text-align:center;">⬇ İmzalı Sözleşmeyi İndir</a>
            @endif

            @if(!in_array($contract->status, ['approved','cancelled']))
            <form method="POST" action="{{ route('manager.business-contracts.cancel', $contract) }}" onsubmit="return confirm('İptal et?')">
                @csrf @method('PATCH')
                <button type="submit" class="btn warn" style="width:100%;">✖ İptal Et</button>
            </form>
            @endif

        </div>
        @if($contract->notes)
        <div style="margin-top:14px;padding:10px;background:var(--u-bg);border-radius:6px;font-size:var(--tx-xs);color:var(--u-muted);">
            <strong>Not:</strong> {{ $contract->notes }}
        </div>
        @endif
    </div>
</div>

{{-- Sözleşme Metni --}}
<div class="card" style="padding:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:var(--tx-sm);font-weight:700;">Sözleşme Metni</h3>
        <div style="display:flex;gap:8px;">
            @if($contract->status === 'draft')
            <button type="button" onclick="toggleBodyEdit()" id="editBodyBtn" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">✏️ Düzenle</button>
            @endif
            <button type="button" onclick="printContract()" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">🖨 Yazdır / PDF</button>
        </div>
    </div>

    {{-- Görüntüleme modu --}}
    <pre id="bodyPreview" style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:var(--tx-sm);line-height:1.7;background:var(--u-bg);padding:20px;border-radius:8px;border:1px solid var(--u-line);max-height:600px;overflow-y:auto;">{{ $contract->body_text }}</pre>

    {{-- Düzenleme modu (yalnızca draft) --}}
    @if($contract->status === 'draft')
    <div id="bodyEditForm" style="display:none;">
        <form method="POST" action="{{ route('manager.business-contracts.update-body', $contract) }}">
            @csrf @method('PATCH')
            <textarea name="body_text" id="bodyTextarea" class="form-control"
                      rows="30"
                      style="font-family:monospace;font-size:var(--tx-xs);line-height:1.7;resize:vertical;width:100%;">{{ $contract->body_text }}</textarea>
            <div style="display:flex;gap:8px;margin-top:12px;">
                <button type="submit" class="btn">💾 Kaydet</button>
                <button type="button" onclick="toggleBodyEdit()" class="btn alt">İptal</button>
            </div>
        </form>
    </div>
    @endif
</div>

</div>

@endsection

@push('scripts')
<script>
function toggleBodyEdit() {
    var preview = document.getElementById('bodyPreview');
    var form    = document.getElementById('bodyEditForm');
    var btn     = document.getElementById('editBodyBtn');
    if (!form) return;
    var editing = form.style.display !== 'none';
    preview.style.display = editing ? 'block' : 'none';
    form.style.display    = editing ? 'none'  : 'block';
    if (btn) btn.textContent = editing ? '✏️ Düzenle' : '✖ Kapat';
}

function printContract() {
    var preview = document.getElementById('bodyPreview');
    var text    = preview ? preview.innerText : '';
    var title   = @json($contract->title);
    var no      = @json($contract->contract_no);

    var win = window.open('', '_blank', 'width=900,height=700');
    if (!win) { alert('Lütfen tarayıcınızın açılır pencere engelleyicisini bu site için devre dışı bırakın.'); return; }

    win.document.write(
        '<!DOCTYPE html><html><head>' +
        '<meta charset="UTF-8">' +
        '<title>' + no + ' \u2014 ' + title + '</title>' +
        '<style>' +
        '* { box-sizing:border-box; margin:0; padding:0; }' +
        'body { font-family:"Times New Roman",Times,serif; font-size:12pt; line-height:1.9; color:#000; background:#fff; padding:2cm 2.5cm; }' +
        '.ph { border-bottom:2px solid #000; padding-bottom:10pt; margin-bottom:18pt; }' +
        '.ph h1 { font-size:15pt; font-weight:bold; margin-bottom:3pt; }' +
        '.ph p  { font-size:10pt; color:#555; }' +
        'pre { white-space:pre-wrap; word-break:break-word; font-family:inherit; font-size:11pt; line-height:1.9; }' +
        '.pf { border-top:1px solid #bbb; margin-top:20pt; padding-top:6pt; font-size:9pt; color:#777; display:flex; justify-content:space-between; }' +
        '@media print { @page { margin:1.5cm 2cm; } body { padding:0; } }' +
        '</style>' +
        '</head><body>' +
        '<div class="ph"><h1>' + title + '</h1><p>Sözleşme No: <strong>' + no + '</strong> &nbsp;&middot;&nbsp; MentorDE</p></div>' +
        '<pre>' + text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</pre>' +
        '<div class="pf"><span>MentorDE &mdash; Gizli &amp; Resmi Belge</span><span>' + no + '</span></div>' +
        '</body></html>'
    );
    win.document.close();
    win.focus();
    setTimeout(function() { win.print(); }, 500);
}
</script>
@endpush
