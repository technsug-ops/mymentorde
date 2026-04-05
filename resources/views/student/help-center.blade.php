@extends('student.layouts.app')

@section('title', 'Yardım Merkezi')
@section('page_title', 'Yardım Merkezi')

@section('content')
{{-- Arama + Kategori --}}
<form method="GET" action="/student/help-center" style="margin-bottom:20px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="SSS içinde ara..."
               style="flex:1;min-width:200px;border:1px solid var(--u-line);border-radius:8px;padding:9px 14px;font-size:var(--tx-sm);background:var(--u-bg,#f8fafc);outline:none;">
        <button class="btn" type="submit">Ara</button>
        @if(!empty($search))
            <a href="/student/help-center" class="btn alt">Temizle</a>
        @endif
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
        <a href="/student/help-center" class="badge {{ ($activeCategory ?? 'all') === 'all' ? 'info' : '' }}" style="text-decoration:none;cursor:pointer;padding:6px 14px;">Tümü</a>
        @foreach($categories ?? [] as $key => $cat)
            <a href="/student/help-center?category={{ $key }}" class="badge {{ ($activeCategory ?? 'all') === $key ? 'info' : '' }}" style="text-decoration:none;cursor:pointer;padding:6px 14px;">
                {{ $cat['icon'] }} {{ $cat['label'] }}
            </a>
        @endforeach
    </div>
</form>

@if(($faqs ?? collect())->isEmpty())
    <div class="card" style="padding:40px;text-align:center;color:var(--u-muted);">
        <div style="font-size:48px;margin-bottom:16px;">🔍</div>
        <div style="font-size:var(--tx-base);font-weight:600;">Sonuç bulunamadı</div>
        <div style="font-size:var(--tx-sm);margin-top:6px;">Farklı bir arama terimi deneyin veya danışmanınıza ticket açın.</div>
        <a href="/student/tickets" class="btn" style="margin-top:16px;">Ticket Aç</a>
    </div>
@else
    <div class="grid2">
        @foreach($faqs ?? [] as $faq)
        <div class="card" x-data="{ open: false }" style="padding:0;overflow:hidden;">
            <button onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';this.querySelector('.faq-arrow').textContent=this.nextElementSibling.style.display==='none'?'▼':'▲';"
                    style="width:100%;text-align:left;padding:16px 20px;background:none;border:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <span style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">{{ $faq->title_tr }}</span>
                <span class="faq-arrow" style="color:var(--u-muted);flex-shrink:0;">▼</span>
            </button>
            <div style="display:none;padding:0 20px 16px;font-size:var(--tx-sm);color:var(--u-muted);line-height:1.7;border-top:1px solid var(--u-line);">
                {!! nl2br(e(strip_tags($faq->content_tr ?? ''))) !!}
            </div>
        </div>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top:20px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">Aradığınızı bulamadınız mı?</div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:2px;">Danışmanınıza doğrudan ulaşın.</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/student/messages" class="btn alt">💬 Mesaj Gönder</a>
        <a href="/student/tickets" class="btn">🎫 Ticket Aç</a>
    </div>
</div>
@endsection
