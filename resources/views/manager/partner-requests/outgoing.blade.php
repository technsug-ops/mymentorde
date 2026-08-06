@extends('manager.layouts.app')

@section('title', 'Partnerden İstenenler')
@section('page_title', 'Partnerden İstenenler')

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
    Öğrencinin eksik bilgi ve belgelerini <strong>partner firmadan</strong> istersiniz;
    partner de kendi öğrencisinden ister. Zincirin her halkası kendi muhatabıyla konuşur.
</div>

<div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
    @if($partners->isEmpty())
        <span class="pr-muted" style="font-size:12px;">Bağlı partner firma yok.</span>
    @else
        <a href="/manager/partner-requests/create" class="btn btn-primary" style="font-size:13px;">+ Yeni Talep</a>
    @endif
</div>

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="pr-table">
            <thead>
                <tr>
                    <th>Kişi</th>
                    <th>Partner Firma</th>
                    <th>Kalemler</th>
                    <th>Durum</th>
                    <th>Son Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $req)
                    @php
                        $total   = $req->items->count();
                        $done    = $req->items->where('status', 'provided')->count();
                        $isLate  = $req->due_at && $req->due_at->isPast() && $req->status !== 'fulfilled';
                        $tone    = $req->status === 'fulfilled' ? 'pr-done' : ($isLate ? 'pr-late' : 'pr-open');
                        $label   = $req->status === 'fulfilled' ? 'Tamamlandı' : ($isLate ? 'Gecikti' : 'Bekliyor');
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $req->subject_name ?: $req->subject_id }}</div>
                            <div class="pr-muted" style="font-size:11px;">
                                {{ $req->subject_type === 'guest' ? 'Aday' : 'Öğrenci' }}
                            </div>
                        </td>
                        <td>{{ $partners[$req->partner_company_id] ?? ('#' . $req->partner_company_id) }}</td>
                        <td>{{ $done }} / {{ $total }}</td>
                        <td><span class="pr-badge {{ $tone }}">{{ $label }}</span></td>
                        <td class="pr-muted">{{ $req->due_at?->format('d.m.Y') ?? '—' }}</td>
                        <td style="text-align:right;">
                            <a href="/manager/partner-requests/{{ $req->id }}" class="pr-btn">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="pr-empty">
                            Henüz talep açılmadı.<br>
                            Bir öğrencinin eksik belgesini partner firmadan istemek için <strong>Yeni Talep</strong>.
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
