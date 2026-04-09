@extends('dealer.layouts.app')

@section('title', $contract->contract_no)

@section('content')
<div style="max-width:860px;">

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="{{ route('dealer.contracts') }}" style="color:var(--u-muted);text-decoration:none;font-size:var(--tx-sm);">← Sözleşmelerim</a>
    <h1 style="margin:0;font-size:var(--tx-lg);font-weight:700;flex:1;">{{ $contract->title }}</h1>
    <span class="badge {{ $contract->statusBadge() }}">{{ $contract->statusLabel() }}</span>
</div>

@if(session('success'))
    <div style="background:var(--badge-ok-bg);color:var(--badge-ok-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="background:var(--badge-danger-bg);color:var(--badge-danger-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('error') }}</div>
@endif

@if($contract->status === 'issued')
<div class="card" style="padding:18px;margin-bottom:16px;border:2px solid var(--u-brand,#1e40af);">
    <h3 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;color:var(--u-brand);">İmza Gerekiyor</h3>
    <p style="margin:0 0 12px;font-size:var(--tx-sm);">Bu sözleşmeyi imzaladıktan sonra PDF'i aşağıdan yükleyin.</p>
    <form method="POST" action="{{ route('dealer.contracts.upload-signed', $contract) }}" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;">
        @csrf
        <input type="file" name="signed_file" accept=".pdf" class="form-control" style="max-width:300px;" required>
        <button type="submit" class="btn">📄 İmzalı Sözleşmeyi Yükle</button>
    </form>
</div>
@endif

@if($contract->status === 'signed_uploaded')
<div style="padding:12px 16px;background:var(--badge-warn-bg,#fef9c3);border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);color:var(--badge-warn-fg,#92400e);">
    ⏳ İmzalı sözleşmeniz {{ config('brand.name', 'MentorDE') }} tarafından inceleniyor. Onay için bekleyiniz.
</div>
@endif

@if($contract->status === 'approved')
<div style="padding:12px 16px;background:var(--badge-ok-bg,#dcfce7);border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);color:var(--badge-ok-fg,#166534);">
    ✅ Sözleşme onaylandı. Ortaklığınız aktif.
    @if($contract->approved_at) Onay tarihi: {{ $contract->approved_at->format('d.m.Y') }}@endif
</div>
@endif

<div class="card" style="padding:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:var(--tx-sm);font-weight:700;">Sözleşme Metni</h3>
        <button onclick="window.print()" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">🖨 Yazdır / PDF</button>
    </div>
    <pre style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:var(--tx-sm);line-height:1.7;background:var(--u-bg);padding:20px;border-radius:8px;border:1px solid var(--u-line);">{{ $contract->body_text }}</pre>
</div>

</div>

<style>
@media print {
    nav, aside, form, .btn, h1, a { display:none!important; }
    pre { border:none!important; background:none!important; }
}
</style>
@endsection
