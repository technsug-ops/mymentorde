@extends('manager.layouts.app')

@section('title', 'Süreç Bilgisi')
@section('page_title', 'Süreç Bilgisi')

@push('head')
<style>
.pi-note { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; color:var(--text,#0f172a); margin-bottom:12px; line-height:1.5; }
.pi-table { width:100%; border-collapse:collapse; font-size:12px; }
.pi-table thead tr { background:var(--bg,#f8fafc); }
.pi-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pi-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.pi-table tbody tr:hover { background:rgba(30,64,175,.03); }
.pi-table td { padding:8px 10px; vertical-align:middle; }
.pi-step { display:inline-block; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; background:rgba(30,64,175,.08); color:#1e40af; white-space:nowrap; }
.pi-step.pi-idle { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }
.pi-btn { display:inline-block; padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; white-space:nowrap; }
.pi-muted { color:var(--muted,#64748b); }
.pi-empty { padding:28px 14px; text-align:center; color:var(--muted,#64748b); font-size:13px; }
</style>
@endpush

@section('content')

<div class="pi-note">
    Öğrencilerinizin süreçlerini <strong>operasyonu yürüten firma</strong> ilerletir.
    Bu ekran bilgi amaçlıdır — buradan bir işlem yapılmaz, yalnızca nerede olunduğu görünür.
</div>

<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="/manager/process-info" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Ara</label>
            <input name="q" value="{{ $q }}" placeholder="Öğrenci adı veya ID..." style="width:240px;">
        </div>
        <button type="submit" class="btn btn-primary" style="font-size:12px;">Ara</button>
        @if($q !== '')
            <a href="/manager/process-info" class="pi-muted" style="font-size:12px;">Temizle</a>
        @endif
    </form>
</section>

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="pi-table">
            <thead>
                <tr>
                    <th>Öğrenci</th>
                    <th>Danışman</th>
                    <th>Bulunduğu Adım</th>
                    <th>Son Hareket</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $last = $latest[$row->student_id] ?? null;
                        $name = $names[$row->student_id] ?? ($row->display_name ?: $row->student_id);
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $name }}</div>
                            <div class="pi-muted" style="font-size:11px;">{{ $row->student_id }}</div>
                        </td>
                        <td>{{ $row->senior_email ?: '—' }}</td>
                        <td>
                            @if($last)
                                <span class="pi-step">{{ $steps[$last->process_step] ?? $last->process_step }}</span>
                            @else
                                <span class="pi-step pi-idle">Henüz kayıt yok</span>
                            @endif
                        </td>
                        <td class="pi-muted">
                            {{ $last?->created_at?->format('d.m.Y') ?? '—' }}
                        </td>
                        <td style="text-align:right;">
                            <a href="/manager/process-info/{{ $row->student_id }}" class="pi-btn">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="pi-empty">
                            Henüz öğrenciniz yok. Aday öğrenci öğrenciye dönüştüğünde süreci burada görürsünüz.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div style="margin-top:12px;">{{ $rows->withQueryString()->links() }}</div>
    @endif
</section>

@endsection
