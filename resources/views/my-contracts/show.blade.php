@extends($layout)

@section('title', $contract->title)

@section('content')
<div style="max-width:860px;">

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="{{ route('my-contracts.index') }}" style="color:var(--u-muted);text-decoration:none;font-size:13px;">← Sözleşmelerim</a>
    <h1 style="margin:0;font-size:20px;font-weight:700;">{{ $contract->title }}</h1>
</div>

@if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;">
        {{ session('error') }}
    </div>
@endif

{{-- Durum Banner --}}
@if($contract->status === 'issued')
    <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:flex-start;gap:14px;">
        <div style="font-size:24px;line-height:1;">✍️</div>
        <div>
            <div style="font-size:14px;font-weight:700;color:#1e40af;margin-bottom:4px;">İmzanız Bekleniyor</div>
            <div style="font-size:13px;color:#1d4ed8;">Lütfen aşağıdaki sözleşmeyi okuyun, yazdırın, ıslak imzalı PDF olarak yükleyin.</div>
        </div>
    </div>
@elseif($contract->status === 'signed_uploaded')
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:flex-start;gap:14px;">
        <div style="font-size:24px;line-height:1;">⏳</div>
        <div>
            <div style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:4px;">Yönetici Onayı Bekleniyor</div>
            <div style="font-size:13px;color:#b45309;">İmzalı sözleşmeniz alındı. Yöneticiniz inceleyip onaylayacak.</div>
        </div>
    </div>
@elseif($contract->status === 'approved')
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:flex-start;gap:14px;">
        <div style="font-size:24px;line-height:1;">✅</div>
        <div>
            <div style="font-size:14px;font-weight:700;color:#166534;margin-bottom:4px;">Sözleşme Onaylandı</div>
            <div style="font-size:13px;color:#15803d;">
                Sözleşmeniz {{ $contract->approved_at ? \Carbon\Carbon::parse($contract->approved_at)->format('d.m.Y') : '' }} tarihinde onaylandı ve yürürlüğe girdi.
            </div>
        </div>
    </div>
@endif

{{-- Sözleşme Detay Bilgisi --}}
<div class="grid2" style="gap:12px;margin-bottom:16px;">
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--u-muted);font-weight:600;text-transform:uppercase;margin-bottom:8px;">Sözleşme Bilgisi</div>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <tr><td style="padding:4px 0;color:var(--u-muted);width:40%;">No</td><td style="font-weight:600;">{{ $contract->contract_no }}</td></tr>
            <tr><td style="padding:4px 0;color:var(--u-muted);">Tür</td><td><span class="badge info">{{ ucfirst($contract->contract_type) }}</span></td></tr>
            <tr><td style="padding:4px 0;color:var(--u-muted);">Durum</td><td><span class="badge {{ $contract->statusBadge() }}">{{ $contract->statusLabel() }}</span></td></tr>
            <tr><td style="padding:4px 0;color:var(--u-muted);">Gönderilme</td><td>{{ $contract->issued_at ? \Carbon\Carbon::parse($contract->issued_at)->format('d.m.Y') : '—' }}</td></tr>
            @if($contract->signed_at)
            <tr><td style="padding:4px 0;color:var(--u-muted);">İmza Tarihi</td><td>{{ \Carbon\Carbon::parse($contract->signed_at)->format('d.m.Y') }}</td></tr>
            @endif
            @if($contract->approved_at)
            <tr><td style="padding:4px 0;color:var(--u-muted);">Onay Tarihi</td><td>{{ \Carbon\Carbon::parse($contract->approved_at)->format('d.m.Y') }}</td></tr>
            @endif
        </table>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--u-muted);font-weight:600;text-transform:uppercase;margin-bottom:8px;">Yapılacaklar</div>
        @if($contract->status === 'issued')
            <ol style="font-size:13px;margin:0;padding-left:18px;line-height:2;">
                <li>Sözleşmeyi tamamen okuyun</li>
                <li>Yazdırın veya PDF editörle imzalayın</li>
                <li>Aşağıdaki alana PDF olarak yükleyin</li>
            </ol>
        @elseif($contract->status === 'signed_uploaded')
            <div style="font-size:13px;color:var(--u-muted);">Yönetici incelemesi bekleniyor. Onaylandığında bildirim alacaksınız.</div>
        @elseif($contract->status === 'approved')
            <div style="font-size:13px;color:var(--u-muted);">Sözleşmeniz aktiftir. Onaylı nüsha için yöneticinize başvurun.</div>
        @else
            <div style="font-size:13px;color:var(--u-muted);">İşlem gerekmemektedir.</div>
        @endif
    </div>
</div>

{{-- İmza Yükleme Formu --}}
@if($contract->status === 'issued')
<div class="card" style="padding:20px;margin-bottom:16px;border:2px solid #93c5fd;">
    <h3 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#1e40af;">İmzalı PDF Yükle</h3>
    <form method="POST" action="{{ route('my-contracts.upload-signed', $contract) }}" enctype="multipart/form-data">
        @csrf
        @if($errors->has('signed_file'))
            <div style="color:#b91c1c;font-size:12px;margin-bottom:8px;">{{ $errors->first('signed_file') }}</div>
        @endif
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <input type="file" name="signed_file" id="signedFile" accept=".pdf" style="display:none;">
            <label for="signedFile" class="btn alt" style="cursor:pointer;">📎 PDF Seç</label>
            <span id="signedFileName" style="font-size:13px;color:var(--u-muted);">Dosya seçilmedi</span>
            <button type="submit" class="btn">Yükle & Gönder</button>
        </div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:8px;">Yalnızca PDF, max 10 MB.</div>
    </form>
</div>
@endif

{{-- Sözleşme Metni --}}
<div class="card" style="padding:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h3 style="margin:0;font-size:15px;font-weight:600;">Sözleşme Metni</h3>
        <button onclick="window.print()" class="btn alt" style="font-size:12px;padding:5px 12px;">🖨 Yazdır</button>
    </div>
    <pre style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:6px;padding:16px;font-size:12px;line-height:1.7;white-space:pre-wrap;word-break:break-word;max-height:600px;overflow-y:auto;font-family:inherit;">{{ $contract->body_text }}</pre>
</div>

</div>

<script>
document.getElementById('signedFile')?.addEventListener('change', function() {
    var fn = document.getElementById('signedFileName');
    fn.textContent = this.files[0] ? this.files[0].name : 'Dosya seçilmedi';
});
</script>

<style>
@media print {
    aside, nav, .btn, form, #signedFileName { display: none !important; }
    pre { max-height: none !important; border: none !important; }
}
</style>
@endsection
