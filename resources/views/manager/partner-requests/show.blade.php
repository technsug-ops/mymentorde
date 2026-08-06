@extends('manager.layouts.app')

@section('title', 'Talep #' . $req->id)
@section('page_title', 'Talep Detayı')

@push('head')
    @include('manager.partner-requests._styles')
@endpush

@section('content')

@php
    // Talebi açan taraf izler, talep edilen taraf işlem yapar.
    $back = $isPartner ? '/manager/partner-requests/incoming' : '/manager/partner-requests';
@endphp

<a href="{{ $back }}" class="pr-muted" style="font-size:12px;text-decoration:none;">← Geri</a>

@if(session('status'))
    <div class="pr-note" style="border-left-color:#16a34a;background:rgba(22,163,74,.06);border-color:rgba(22,163,74,.25);margin-top:10px;">
        {{ session('status') }}
    </div>
@endif

<section class="panel" style="margin-top:12px;margin-bottom:12px;">
    <div style="display:flex;flex-wrap:wrap;gap:24px;font-size:12px;">
        <div>
            <span class="pr-label">Kişi</span>
            <div style="font-weight:700;font-size:14px;">{{ $req->subject_name ?: $req->subject_id }}</div>
            <div class="pr-muted">{{ $req->subject_type === 'guest' ? 'Aday' : 'Öğrenci' }}</div>
        </div>
        <div>
            <span class="pr-label">Durum</span>
            <span class="pr-badge {{ $req->status === 'fulfilled' ? 'pr-done' : 'pr-open' }}">
                {{ $req->status === 'fulfilled' ? 'Tamamlandı' : 'Bekliyor' }}
            </span>
        </div>
        <div>
            <span class="pr-label">Son Tarih</span>
            <div>{{ $req->due_at?->format('d.m.Y') ?? '—' }}</div>
        </div>
        <div>
            <span class="pr-label">Açan</span>
            <div>{{ $req->created_by ?: '—' }}</div>
        </div>
    </div>

    @if($req->note)
        <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border,#e2e8f0);font-size:12px;line-height:1.55;">
            {{ $req->note }}
        </div>
    @endif
</section>

<section class="panel">
    <h2 style="font-size:14px;margin-bottom:10px;">İstenen Kalemler</h2>

    @foreach($req->items as $item)
        @php $provided = $item->status === 'provided'; @endphp

        <div class="pr-item {{ $provided ? 'done' : '' }}">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
                <div style="flex:1;min-width:200px;">
                    <div style="font-weight:600;font-size:13px;">{{ $item->label }}</div>
                    <div class="pr-muted" style="font-size:11px;margin-top:2px;">
                        {{ $item->kind === 'info' ? 'Bilgi' : 'Belge' }}
                        @if($item->isForwarded())
                            · öğrenciden istendi ({{ $item->forwarded_at->format('d.m.Y') }})
                        @endif
                    </div>
                    @if($item->response_text)
                        <div style="margin-top:6px;font-size:12px;line-height:1.5;">{{ $item->response_text }}</div>
                    @endif
                </div>

                <span class="pr-badge {{ $provided ? 'pr-done' : 'pr-open' }}">
                    {{ $provided ? 'Geldi' : 'Bekliyor' }}
                </span>
            </div>

            {{-- İşlem yalnızca TALEP EDİLEN tarafta: talebi açan firma kendi
                 talebini kendi kapatamaz. --}}
            @if($isPartner && !$provided)
                <div style="margin-top:10px;padding-top:10px;border-top:1px dashed var(--border,#e2e8f0);display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">
                    <form method="POST" action="/manager/partner-requests/{{ $req->id }}/items/{{ $item->id }}/respond"
                          style="display:flex;gap:6px;flex:1;min-width:260px;">
                        @csrf
                        <input type="text" name="response_text" placeholder="{{ $item->kind === 'info' ? 'Cevabınız' : 'Not (opsiyonel)' }}" style="flex:1;">
                        <button type="submit" class="pr-btn">Sağlandı olarak işaretle</button>
                    </form>

                    @if($item->kind === 'document')
                        <form method="POST" action="/manager/partner-requests/{{ $req->id }}/items/{{ $item->id }}/forward">
                            @csrf
                            <button type="submit" class="pr-btn">Öğrenciden iste</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</section>

@endsection
