@extends('dealer.layouts.app')

@section('title', 'Sözleşmelerim')
@section('page_title', 'Sözleşmelerim')

@section('content')

@include('partials.manager-hero', [
    'label' => 'Ortaklık Belgeleri',
    'title' => 'Sözleşmelerim',
    'sub'   => config('brand.name', 'MentorDE') . ' ile imzaladığın ortaklık ve operasyon sözleşmeleri — durum, son tarih ve imza akışı bir arada.',
    'icon'  => '📜',
    'bg'    => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1400&q=80',
    'tone'  => 'slate',
    'stats' => [
        ['icon' => '📄', 'text' => $contracts->count() . ' sözleşme'],
    ],
])

@if(session('success'))
    <div style="background:var(--badge-ok-bg);color:var(--badge-ok-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="background:var(--badge-danger-bg);color:var(--badge-danger-fg);padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:var(--tx-sm);">{{ session('error') }}</div>
@endif

@forelse($contracts as $c)
<div class="card" style="padding:18px;margin-bottom:12px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-size:var(--tx-base);font-weight:700;">{{ $c->title }}</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;font-family:monospace;">{{ $c->contract_no }}</div>
            @if($c->issued_at)
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:4px;">Gönderildi: {{ $c->issued_at->format('d.m.Y') }}</div>
            @endif
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span>
            <a href="{{ route('dealer.contracts.show', $c) }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 12px;">Görüntüle</a>
        </div>
    </div>

    @if($c->status === 'issued')
    <div style="margin-top:14px;padding:12px;background:var(--u-bg);border-radius:6px;border:1px solid var(--u-line);">
        <p style="margin:0 0 10px;font-size:var(--tx-sm);color:var(--u-text);">
            Bu sözleşme imzalanmayı bekliyor. İmzalı PDF'i aşağıdan yükleyebilirsiniz.
        </p>
        <form method="POST" action="{{ route('dealer.contracts.upload-signed', $c) }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @csrf
            <input type="file" name="signed_file" accept=".pdf" class="form-control" style="max-width:280px;" required>
            <button type="submit" class="btn ok">📄 İmzalı Sözleşme Yükle</button>
        </form>
    </div>
    @endif

    @if($c->status === 'signed_uploaded')
    <div style="margin-top:10px;padding:10px;background:var(--badge-warn-bg,#fef9c3);border-radius:6px;font-size:var(--tx-xs);color:var(--badge-warn-fg,#92400e);">
        ⏳ İmzalı sözleşmeniz {{ config('brand.name', 'MentorDE') }} tarafından inceleniyor.
    </div>
    @endif

    @if($c->status === 'approved')
    <div style="margin-top:10px;padding:10px;background:var(--badge-ok-bg,#dcfce7);border-radius:6px;font-size:var(--tx-xs);color:var(--badge-ok-fg,#166534);">
        ✅ Sözleşme onaylandı — {{ $c->approved_at?->format('d.m.Y') }}
    </div>
    @endif
</div>
@empty
<div class="card" style="padding:40px;text-align:center;color:var(--u-muted);">
    <div style="font-size:32px;margin-bottom:12px;">📋</div>
    <div style="font-size:var(--tx-base);font-weight:600;margin-bottom:6px;">Henüz sözleşme yok</div>
    <div style="font-size:var(--tx-sm);">{{ config('brand.name', 'MentorDE') }} tarafından sözleşme gönderildiğinde burada görünecek.</div>
</div>
@endforelse
@endsection
