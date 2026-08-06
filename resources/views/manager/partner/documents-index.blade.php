@extends('manager.layouts.app')

@section('title', 'Belge Listesi')
@section('page_title', 'Belge Listesi')

@push('head')
<style>
.pd-table { width:100%; border-collapse:collapse; font-size:12px; }
.pd-table thead tr { background:var(--bg,#f8fafc); }
.pd-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pd-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.pd-table tbody tr:hover { background:rgba(30,64,175,.03); }
.pd-table td { padding:8px 10px; vertical-align:middle; }
.pd-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.pd-ok    { background:rgba(22,163,74,.1); color:#15803d; }
.pd-warn  { background:rgba(217,119,6,.1); color:#b45309; }
.pd-bad   { background:rgba(220,38,38,.1); color:#b91c1c; }
.pd-muted { color:var(--muted,#64748b); }
.pd-empty { padding:28px 14px; text-align:center; color:var(--muted,#64748b); font-size:13px; line-height:1.6; }
.pd-note  { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.5; }
</style>
@endpush

@section('content')

<div class="pd-note">
    Öğrencilerinizin ve adaylarınızın sisteme yüklenmiş belgeleri.
    Bir belgeyi açmak için kişinin kendi sayfasına gidin.
</div>

<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="/manager/partner-documents" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Ara</label>
            <input name="q" value="{{ $q }}" placeholder="Belge adı..." style="width:240px;">
        </div>
        <button type="submit" class="btn btn-primary" style="font-size:12px;">Ara</button>
        @if($q !== '')
            <a href="/manager/partner-documents" class="pd-muted" style="font-size:12px;">Temizle</a>
        @endif
    </form>
</section>

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="pd-table">
            <thead>
                <tr>
                    <th>Kişi</th>
                    <th>Belge</th>
                    <th>Durum</th>
                    <th>Yüklenme</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $doc)
                    @php
                        $status = (string) ($doc->status ?: 'pending');
                        $label  = ['pending' => 'Bekliyor', 'approved' => 'Onaylı', 'rejected' => 'Reddedildi'][$status] ?? $status;
                        $tone   = match($status) { 'approved' => 'pd-ok', 'rejected' => 'pd-bad', default => 'pd-warn' };
                    @endphp
                    <tr>
                        <td>{{ $people[$doc->student_id] ?? $doc->student_id }}</td>
                        <td>{{ $doc->original_file_name ?: $doc->standard_file_name ?: '—' }}</td>
                        <td><span class="pd-badge {{ $tone }}">{{ $label }}</span></td>
                        <td class="pd-muted">{{ $doc->created_at?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="pd-empty">
                            Henüz yüklenmiş belge yok.<br>
                            Belge istemek için <a href="/manager/partner-documents/requests">Belge Talepleri</a> sayfasına bakın.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
