@extends('student.layouts.app')
@section('title', 'Bildirimlerim')
@section('page_title', 'Bildirimlerim')

@push('head')
<style>
/* ── notif-* Notifications ── */
.notif-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(to right, #6d28d9, #7c3aed);
    border-radius: 14px; padding: 14px 18px; margin-bottom: 20px; color: #fff;
}
.notif-header-icon { font-size: 24px; }
.notif-header-title { font-size: 16px; font-weight: 800; }
.notif-header-sub   { font-size: 12px; opacity: .75; }
.notif-unread-pill {
    margin-left: auto; background: rgba(255,255,255,.25); border-radius: 999px;
    padding: 4px 12px; font-size: 12px; font-weight: 800;
}

/* Filter bar */
.notif-filters {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 10px; padding: 10px 14px; margin-bottom: 16px;
}
.notif-filters select {
    padding: 6px 10px; border: 1px solid var(--u-line); border-radius: 8px;
    font-size: 12px; background: var(--u-bg); color: var(--u-text); outline: none;
}

/* Section heading */
.notif-section-label {
    font-size: 11px; font-weight: 800; color: var(--u-muted);
    text-transform: uppercase; letter-spacing: .6px;
    display: flex; align-items: center; gap: 8px; margin: 16px 0 8px;
}
.notif-section-label::after { content:''; flex:1; border-top:1px solid var(--u-line); }

/* Notification item */
.notif-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 13px 14px; border-radius: 10px; margin-bottom: 6px;
    border: 1px solid var(--u-line); background: var(--u-card);
    transition: border-color .15s;
}
.notif-item.unread {
    border-left: 3px solid #7c3aed;
    background: linear-gradient(90deg, rgba(124,58,237,.03), var(--u-card));
}
.notif-item.unread .notif-subject { font-weight: 800; }
.notif-item.read .notif-subject { color: var(--u-muted); font-weight: 600; }

.notif-cat-icon {
    width: 38px; height: 38px; flex-shrink: 0; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.notif-cat-icon.appointment { background: rgba(124,58,237,.1); }
.notif-cat-icon.document    { background: rgba(5,150,105,.1); }
.notif-cat-icon.payment     { background: rgba(234,179,8,.12); }
.notif-cat-icon.process     { background: rgba(59,130,246,.1); }
.notif-cat-icon.system      { background: rgba(107,114,128,.1); }
.notif-cat-icon.reminder    { background: rgba(239,68,68,.08); }
.notif-cat-icon.default     { background: rgba(124,58,237,.07); }

.notif-body-area { flex: 1; min-width: 0; }
.notif-subject { font-size: 13px; color: var(--u-text); line-height: 1.4; margin-bottom: 4px; }
.notif-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.notif-time { font-size: 11px; color: var(--u-muted); }
.notif-ch-badge {
    font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px;
}
.notif-ch-badge.email     { background: #eff6ff; color: #1d4ed8; }
.notif-ch-badge.sms       { background: #fef3c7; color: #d97706; }
.notif-ch-badge.whatsapp  { background: #f0fdf4; color: #16a34a; }
.notif-ch-badge.in_app    { background: #f5f3ff; color: #7c3aed; }
.notif-ch-badge.push      { background: #fef2f2; color: #dc2626; }
.notif-ch-badge.default   { background: var(--u-bg); color: var(--u-muted); }

/* Expandable body */
.notif-expand-btn {
    background: none; border: none; cursor: pointer; font-size: 11px;
    color: #7c3aed; font-weight: 700; padding: 0; margin-top: 4px;
}
.notif-body-text {
    display: none; font-size: 12px; color: var(--u-muted); line-height: 1.6;
    margin-top: 6px; padding: 8px 10px; background: var(--u-bg);
    border: 1px solid var(--u-line); border-radius: 8px;
}
.notif-body-text.open { display: block; }

/* Actions */
.notif-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
.notif-mark-btn {
    width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--u-line);
    background: var(--u-bg); cursor: pointer; display: flex; align-items: center;
    justify-content: center; font-size: 12px; color: var(--u-muted);
    transition: background .15s, border-color .15s; flex-shrink: 0;
}
.notif-mark-btn:hover { background: rgba(124,58,237,.08); border-color: #7c3aed; color: #7c3aed; }

/* Empty */
.notif-empty {
    text-align: center; padding: 48px 20px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; color: var(--u-muted);
}
</style>
@endpush

@section('content')
@php
    $all = $notifications ?? collect();
    $unreadCount = $unreadCount ?? 0;
    $total = $all->count();

    $catMeta = [
        'appointment' => ['icon'=>'📅','cls'=>'appointment','label'=>'Randevu'],
        'document'    => ['icon'=>'📄','cls'=>'document',   'label'=>'Belge'],
        'payment'     => ['icon'=>'💰','cls'=>'payment',    'label'=>'Ödeme'],
        'process'     => ['icon'=>'🔄','cls'=>'process',    'label'=>'Süreç'],
        'system'      => ['icon'=>'⚙️','cls'=>'system',    'label'=>'Sistem'],
        'reminder'    => ['icon'=>'🔔','cls'=>'reminder',   'label'=>'Hatırlatma'],
    ];
    $chLabels = [
        'email'=>'📧 E-posta','sms'=>'📱 SMS','whatsapp'=>'💬 WhatsApp',
        'in_app'=>'🔔 Uygulama','push'=>'📲 Push',
    ];

    // Group by time period
    $now = \Carbon\Carbon::now();
    $groups = [
        'today'   => ['label'=>'Bugün',       'items'=>collect()],
        'week'    => ['label'=>'Bu Hafta',     'items'=>collect()],
        'month'   => ['label'=>'Bu Ay',        'items'=>collect()],
        'earlier' => ['label'=>'Daha Önce',    'items'=>collect()],
    ];
    foreach ($all as $n) {
        $dt = $n->sent_at ?? $n->queued_at ?? $n->failed_at ?? null;
        $parsed = $dt ? \Carbon\Carbon::parse($dt) : null;
        if (!$parsed) { $groups['earlier']['items']->push($n); continue; }
        if ($parsed->isToday())          $groups['today']['items']->push($n);
        elseif ($parsed->isCurrentWeek()) $groups['week']['items']->push($n);
        elseif ($parsed->isCurrentMonth()) $groups['month']['items']->push($n);
        else                               $groups['earlier']['items']->push($n);
    }
@endphp

{{-- Header --}}
<div class="notif-header">
    <div class="notif-header-icon">🔔</div>
    <div>
        <div class="notif-header-title">Bildirimlerim</div>
        <div class="notif-header-sub">Danışmanınızdan gelen mesajlar ve sistem bildirimleri</div>
    </div>
    @if($unreadCount > 0)
    <div class="notif-unread-pill">{{ $unreadCount }} okunmamış</div>
    @endif
</div>

@if(session('notif_success'))
<div class="badge ok" style="padding:10px 16px;border-radius:10px;margin-bottom:12px;font-size:var(--tx-sm);display:block;">
    ✓ {{ session('notif_success') }}
</div>
@endif

{{-- Filter + actions bar --}}
<div class="notif-filters">
    <form method="GET" style="display:contents;">
        <select name="channel" onchange="this.form.submit()">
            <option value="">Tüm kanallar</option>
            @foreach(['email','sms','whatsapp','in_app','push'] as $ch)
            <option value="{{ $ch }}" @selected(($filterChannel??'')===$ch)>
                {{ $chLabels[$ch] ?? ucfirst($ch) }}
            </option>
            @endforeach
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">Tüm durumlar</option>
            <option value="sent"   @selected(($filterStatus??'')==='sent')>✓ Gönderildi</option>
            <option value="queued" @selected(($filterStatus??'')==='queued')>⏳ Kuyrukta</option>
            <option value="failed" @selected(($filterStatus??'')==='failed')>✕ Başarısız</option>
        </select>
        @if(($filterChannel??'')||($filterStatus??''))
        <a href="{{ route('student.notifications') }}" class="btn" style="padding:5px 12px;font-size:var(--tx-xs);background:var(--u-bg);color:var(--u-muted);border:1px solid var(--u-line);">✕ Temizle</a>
        @endif
    </form>
    <span style="margin-left:auto;font-size:var(--tx-xs);color:var(--u-muted);">{{ $total }} bildirim</span>
    @if($unreadCount > 0)
    <form method="POST" action="{{ route('student.notifications.read-all') }}" style="margin:0;">
        @csrf
        <button type="submit" class="btn" style="padding:5px 14px;font-size:var(--tx-xs);background:rgba(124,58,237,.08);color:#7c3aed;border:1px solid rgba(124,58,237,.2);">
            ✓ Tümünü Okundu İşaretle
        </button>
    </form>
    @endif
</div>

{{-- Grouped list --}}
@if($all->isEmpty())
<div class="notif-empty">
    <div style="font-size:40px;margin-bottom:10px;">🔔</div>
    <div style="font-size:var(--tx-base);font-weight:700;margin-bottom:6px;">Henüz bildirim yok</div>
    <div style="font-size:var(--tx-sm);">Danışmanınız bildirim gönderdiğinde burada görünecek.</div>
</div>
@else
@foreach($groups as $gKey => $group)
@if($group['items']->isNotEmpty())
<div class="notif-section-label">{{ $group['label'] }} ({{ $group['items']->count() }})</div>

@foreach($group['items'] as $n)
@php
    $dt = $n->sent_at ?? $n->queued_at ?? $n->failed_at ?? null;
    $parsed = $dt ? \Carbon\Carbon::parse($dt) : null;
    $timeStr = $parsed
        ? ($parsed->isToday()
            ? $parsed->format('H:i')
            : ($parsed->isCurrentWeek() ? $parsed->isoFormat('ddd H:i') : $parsed->format('d.m.Y H:i')))
        : '-';
    $cat = (string)($n->category ?: 'default');
    $cm = $catMeta[$cat] ?? ['icon'=>'🔔','cls'=>'default','label'=>ucfirst($cat)];
    $ch = (string)($n->channel ?: 'default');
    $chBadgeCls = in_array($ch, ['email','sms','whatsapp','in_app','push']) ? $ch : 'default';
    $isUnread = !$n->is_read && $n->status === 'sent';
    $hasBody = !empty(trim((string)$n->body));
    $bodyId = 'nb-'.$n->id;
@endphp
<div class="notif-item {{ $isUnread ? 'unread' : 'read' }}">
    {{-- Category icon --}}
    <div class="notif-cat-icon {{ $cm['cls'] }}">{{ $cm['icon'] }}</div>

    {{-- Content --}}
    <div class="notif-body-area">
        <div class="notif-subject">{{ $n->subject ?: '(Konu belirtilmemiş)' }}</div>
        <div class="notif-meta">
            <span class="notif-ch-badge {{ $chBadgeCls }}">{{ $chLabels[$ch] ?? strtoupper($ch) }}</span>
            @if($cat !== 'default')<span class="badge" style="font-size:var(--tx-xs);padding:1px 6px;">{{ $cm['label'] }}</span>@endif
            @if($n->status === 'failed')<span class="badge danger" style="font-size:var(--tx-xs);padding:1px 6px;">✕ Başarısız</span>@endif
            @if($n->status === 'queued')<span class="badge pending" style="font-size:var(--tx-xs);padding:1px 6px;">⏳ Kuyrukta</span>@endif
            <span class="notif-time">{{ $timeStr }}</span>
        </div>
        @if($hasBody)
        <button class="notif-expand-btn" onclick="notifToggle('{{ $bodyId }}', this)">▼ İçeriği göster</button>
        <div class="notif-body-text" id="{{ $bodyId }}">{{ $n->body }}</div>
        @endif
    </div>

    {{-- Mark as read --}}
    <div class="notif-actions">
        <span class="notif-time" style="font-size:var(--tx-xs);">{{ $timeStr }}</span>
        @if($isUnread)
        <form method="POST" action="{{ route('student.notifications.read', $n->id) }}" style="margin:0;">
            @csrf
            <button type="submit" class="notif-mark-btn" title="Okundu işaretle">✓</button>
        </form>
        @else
        <span style="font-size:var(--tx-sm);color:var(--u-line);">✓</span>
        @endif
    </div>
</div>
@endforeach
@endif
@endforeach
@endif

<script>
function notifToggle(id, btn) {
    var el = document.getElementById(id);
    var open = el.classList.toggle('open');
    btn.textContent = open ? '▲ Gizle' : '▼ İçeriği göster';
}
</script>
@endsection
