@extends('senior.layouts.app')

@section('title', 'Birleşik Gelen Kutusu')
@section('page_title', 'Birleşik Gelen Kutusu')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <h2 style="margin:0;">📬 Birleşik Gelen Kutusu
        @if($unreadCount > 0)
            <span class="badge danger" style="font-size:var(--tx-sm);vertical-align:middle;">{{ $unreadCount }} okunmamış</span>
        @endif
    </h2>
    <div style="display:flex;gap:8px;">
        <a class="btn alt" href="/im">Mesaj Merkezi</a>
        <a class="btn alt" href="/senior/tickets">Ticketlar</a>
    </div>
</div>

@if($items->isEmpty())
<div class="panel" style="text-align:center;padding:40px 20px;">
    <div style="font-size:40px;margin-bottom:12px;">📭</div>
    <div style="font-size:var(--tx-base);font-weight:600;color:#374151;margin-bottom:4px;">Gelen Kutunuz Boş</div>
    <div class="muted">Öğrencilerden mesaj, ticket veya dahili konuşma yok.</div>
</div>
@else

{{-- Type filter chips --}}
@php
    $types = ['all' => 'Tümü', 'dm' => '💬 DM', 'ticket' => '🎫 Ticket', 'internal' => '🔔 Dahili'];
    $filter = trim((string) request()->query('type', 'all'));
    $displayed = $filter === 'all' ? $items : $items->where('type', $filter)->values();
@endphp
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
    @foreach($types as $t => $lbl)
    <a href="/senior/inbox?type={{ $t }}"
       style="padding:5px 12px;border-radius:20px;font-size:var(--tx-xs);font-weight:600;text-decoration:none;
              background:{{ $filter === $t ? '#7c3aed' : '#f3f4f6' }};
              color:{{ $filter === $t ? '#fff' : '#374151' }};
              border:1px solid {{ $filter === $t ? '#7c3aed' : '#e5e7eb' }};">{{ $lbl }}
        @php $cnt = $t === 'all' ? $items->count() : $items->where('type', $t)->count(); @endphp
        ({{ $cnt }})
    </a>
    @endforeach
</div>

@forelse($displayed as $item)
<a href="{{ $item['url'] }}" style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:{{ $item['unread'] ? '#f5f3ff' : 'var(--u-card,#fff)' }};border:{{ $item['unread'] ? '1.5px solid #c4b5fd' : '1px solid var(--u-line,#e5e7eb)' }};border-radius:12px;margin-bottom:8px;text-decoration:none;color:#111827;transition:background .15s;" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='{{ $item['unread'] ? '#f5f3ff' : 'var(--u-card,#fff)' }}'">
    <div style="font-size:var(--tx-xl);flex-shrink:0;line-height:1;margin-top:2px;">{{ $item['icon'] }}</div>
    <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:var(--tx-sm);font-weight:{{ $item['unread'] ? '700' : '600' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px;">{{ $item['subject'] }}</span>
            @if($item['unread'])<span class="badge info">Yeni</span>@endif
            <span class="badge {{ $item['status'] === 'open' || $item['status'] === 'in_progress' ? 'warn' : ($item['status'] === 'resolved' || $item['status'] === 'closed' ? 'ok' : '') }}">{{ $item['status'] }}</span>
        </div>
        <div style="font-size:var(--tx-xs);color:#6b7280;margin-top:3px;">
            <span style="text-transform:uppercase;font-weight:600;letter-spacing:.4px;color:#9ca3af;font-size:var(--tx-xs);">{{ $item['type'] }}</span>
            · {{ $item['from'] }}
        </div>
    </div>
    <div style="font-size:var(--tx-xs);color:#9ca3af;flex-shrink:0;margin-top:4px;">
        {{ $item['at'] ? $item['at']->format('d.m.Y H:i') : '-' }}
    </div>
</a>
@empty
<div class="panel muted">Bu filtrede öğe yok.</div>
@endforelse

@endif
@endsection
