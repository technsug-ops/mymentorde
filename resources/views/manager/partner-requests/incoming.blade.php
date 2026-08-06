@extends('manager.layouts.app')

@section('title', 'Bize Gelen Talepler')
@section('page_title', 'Bize Gelen Talepler')

@push('head')
    @include('manager.partner-requests._styles')
@endpush

@section('content')

@if(session('status'))
    <div class="pr-note" style="border-left-color:#16a34a;background:rgba(22,163,74,.06);border-color:rgba(22,163,74,.25);">
        {{ session('status') }}
    </div>
@endif

<div class="pr-note">
    Operasyonu yürüten firmanın sizden istediği eksik bilgi ve belgeler.
    Her kalemi ya kendiniz yanıtlarsınız ya da <strong>öğrencinizden istersiniz</strong>.
</div>

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="pr-table">
            <thead>
                <tr>
                    <th>Kişi</th>
                    <th>İstenen</th>
                    <th>Durum</th>
                    <th>Son Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $req)
                    @php
                        $total  = $req->items->count();
                        $done   = $req->items->where('status', 'provided')->count();
                        $isLate = $req->due_at && $req->due_at->isPast() && $req->status !== 'fulfilled';
                        $tone   = $req->status === 'fulfilled' ? 'pr-done' : ($isLate ? 'pr-late' : 'pr-open');
                        $label  = $req->status === 'fulfilled' ? 'Tamamlandı' : ($isLate ? 'Gecikti' : 'Bekliyor');
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $req->subject_name ?: $req->subject_id }}</div>
                            <div class="pr-muted" style="font-size:11px;">
                                {{ $req->subject_type === 'guest' ? 'Aday' : 'Öğrenci' }}
                            </div>
                        </td>
                        <td>{{ $done }} / {{ $total }} kalem geldi</td>
                        <td><span class="pr-badge {{ $tone }}">{{ $label }}</span></td>
                        <td class="pr-muted">{{ $req->due_at?->format('d.m.Y') ?? '—' }}</td>
                        <td style="text-align:right;">
                            <a href="/manager/partner-requests/{{ $req->id }}" class="pr-btn">Aç</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="pr-empty">
                            Şu an sizden istenen bir bilgi veya belge yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div style="margin-top:12px;">{{ $rows->links() }}</div>
    @endif
</section>

@endsection
