@extends('dealer.layouts.app')

@section('title', 'Bildirimlerim')
@section('page_title', 'Bildirimlerim')
@section('page_subtitle', 'Lead, komisyon ve ödeme bildirimleri')

@push('head')
<style>
.notif-filter-row { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
.notif-filter-group { display:flex; flex-direction:column; gap:4px; }
.notif-filter-group.grow { flex:2; min-width:180px; }
.notif-filter-group.md   { flex:1; min-width:150px; }
.notif-filter-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted,#64748b); }
.notif-filter-row input,
.notif-filter-row select {
    border:1.5px solid var(--border,#e2e8f0); border-radius:8px;
    padding:8px 11px; font-size:13px; color:var(--text,#0f172a);
    background:var(--surface,#fff); width:100%; box-sizing:border-box;
    transition:border-color .15s;
}
.notif-filter-row input:focus,
.notif-filter-row select:focus { outline:none; border-color:var(--c-accent,#16a34a); box-shadow:0 0 0 3px rgba(22,163,74,.1); }

.notif-list { margin:0; }
.notif-item {
    padding:14px 20px; border-bottom:1px solid var(--border,#e2e8f0);
    transition:background .12s;
}
.notif-item:last-child { border-bottom:none; }
.notif-item:hover { background:var(--bg,#f8fafc); }

.notif-item-head {
    display:flex; justify-content:space-between; align-items:flex-start;
    gap:10px; flex-wrap:wrap; margin-bottom:5px;
}
.notif-subject { font-size:13px; font-weight:700; color:var(--text,#0f172a); }
.notif-chips   { display:flex; gap:4px; flex-wrap:wrap; align-items:center; flex-shrink:0; }
.notif-body    { font-size:12px; color:var(--muted,#64748b); line-height:1.5; margin-bottom:5px; }
.notif-meta    { font-size:11px; color:var(--muted,#64748b); display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

.notif-channel-chip {
    display:inline-flex; align-items:center; gap:3px;
    font-size:10px; font-weight:700; padding:1px 7px; border-radius:5px;
    background:var(--bg,#f1f5f9); color:var(--muted,#64748b);
}

.nb { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700; }
.nb.ok      { background:var(--badge-ok-bg,rgba(22,163,74,.12));    color:var(--badge-ok-fg,#15803d); }
.nb.warn    { background:var(--badge-warn-bg,rgba(217,119,6,.12));   color:var(--badge-warn-fg,#b45309); }
.nb.info    { background:var(--badge-info-bg,rgba(8,145,178,.12));   color:var(--badge-info-fg,#0e7490); }
.nb.danger  { background:var(--badge-danger-bg,rgba(220,38,38,.1));  color:var(--badge-danger-fg,#b91c1c); }
.nb.neutral { background:var(--bg,#f1f5f9); color:var(--muted,#64748b); }

.notif-empty { padding:48px 20px; text-align:center; color:var(--muted,#64748b); font-size:13px; }
</style>
@endpush

@section('content')

@php
    $catColors = [
        'dealer_lead_contacted'   => 'info',
        'dealer_lead_contract'    => 'warn',
        'dealer_lead_converted'   => 'ok',
        'dealer_commission_earned'=> 'ok',
        'dealer_payout_approved'  => 'ok',
        'dealer_payout_paid'      => 'ok',
        'dealer_broadcast'        => 'info',
    ];
    $catLabels = [
        'dealer_lead_contacted'   => 'Lead İletişim',
        'dealer_lead_contract'    => 'Sözleşme',
        'dealer_lead_converted'   => 'Dönüşüm',
        'dealer_commission_earned'=> 'Komisyon',
        'dealer_payout_approved'  => 'Ödeme Onay',
        'dealer_payout_paid'      => 'Ödeme Yapıldı',
        'dealer_broadcast'        => 'Duyuru',
    ];
    $catIcons = [
        'dealer_lead_contacted'   => '📞',
        'dealer_lead_contract'    => '📄',
        'dealer_lead_converted'   => '🎉',
        'dealer_commission_earned'=> '💰',
        'dealer_payout_approved'  => '✅',
        'dealer_payout_paid'      => '💳',
        'dealer_broadcast'        => '📢',
    ];
    $channelIcons = ['email'=>'✉️','whatsapp'=>'💬','inapp'=>'🔔','sms'=>'📱'];
@endphp

{{-- Filtre --}}
<div class="panel" style="margin-bottom:14px;">
    <form method="GET">
        <div class="notif-filter-row">
            <div class="notif-filter-group grow">
                <span class="notif-filter-label">Ara</span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Başlık veya mesaj içinde ara...">
            </div>
            <div class="notif-filter-group md">
                <span class="notif-filter-label">Kategori</span>
                <select name="category">
                    <option value="">Tüm Kategoriler</option>
                    @foreach($categories as $c)
                    <option value="{{ $c }}" @selected($c === $cat)>
                        {{ ($catIcons[$c] ?? '') }} {{ $catLabels[$c] ?? $c }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:6px;align-self:flex-end;">
                <button class="btn btn-primary" type="submit">Filtrele</button>
                @if($q || $cat)
                    <a href="/dealer/notifications" class="btn alt">Temizle</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Liste --}}
<div class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border,#e2e8f0);display:flex;align-items:center;justify-content:space-between;gap:8px;">
        <span style="font-size:var(--tx-sm);font-weight:700;">🔔 Bildirimler</span>
        @if($notifications->total())
            <span class="nb neutral">{{ $notifications->total() }} toplam</span>
        @endif
    </div>
    <div class="notif-list">
        @forelse($notifications as $n)
        <div class="notif-item">
            <div class="notif-item-head">
                <div class="notif-subject">
                    {{ $catIcons[$n->category] ?? '🔔' }} {{ $n->subject ?: '(Başlık yok)' }}
                </div>
                <div class="notif-chips">
                    @if($n->category)
                        <span class="nb {{ $catColors[$n->category] ?? 'info' }}">{{ $catLabels[$n->category] ?? $n->category }}</span>
                    @endif
                    <span class="nb {{ $n->status === 'sent' ? 'ok' : ($n->status === 'failed' ? 'danger' : 'neutral') }}">
                        {{ $n->status === 'sent' ? 'Gönderildi' : ($n->status === 'failed' ? 'Başarısız' : ucfirst($n->status)) }}
                    </span>
                </div>
            </div>
            @if($n->message_body)
            <div class="notif-body">{{ Str::limit($n->message_body, 200) }}</div>
            @endif
            <div class="notif-meta">
                <span class="notif-channel-chip">{{ $channelIcons[$n->channel] ?? '📨' }} {{ $n->channel }}</span>
                <span>{{ optional($n->queued_at ?? $n->created_at)->format('d.m.Y H:i') }}</span>
            </div>
        </div>
        @empty
        <div class="notif-empty">
            @if($q || $cat)
                Arama kriterlerine uygun bildirim bulunamadı.
            @else
                Henüz bildirim yok.
            @endif
        </div>
        @endforelse
    </div>
</div>

<div style="margin-top:12px;">
    {{ $notifications->links('partials.pagination') }}
</div>

@include('dealer._partials.usage-guide', [
    'items' => [
        'Lead durumu değiştiğinde (iletişim, sözleşme, dönüşüm) otomatik bildirim gelir.',
        'Komisyon kazanıldığında ve ödeme talebi onaylandığında da bildirilirsiniz.',
        'Kategori filtresiyle sadece komisyon veya ödeme bildirimlerini listeleyebilirsiniz.',
    ]
])

@endsection
