@extends('layouts.staff')

@section('title', 'Staff Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Günlük görev ve aktivite özetiniz')

@section('content')
<style>
.sd-grid4 { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
.sd-kpi { border-radius:12px; padding:16px 18px; color:#fff; }
.sd-kpi .sd-kpi-num  { font-size:32px; font-weight:900; line-height:1.1; }
.sd-kpi .sd-kpi-lbl  { font-size:12px; font-weight:600; opacity:.85; margin-top:4px; }
.sd-kpi.blue   { background:linear-gradient(135deg,#1e40af,#3b82f6); }
.sd-kpi.red    { background:linear-gradient(135deg,#b91c1c,#ef4444); }
.sd-kpi.orange { background:linear-gradient(135deg,#c2410c,#f97316); }
.sd-kpi.purple { background:linear-gradient(135deg,#7c3aed,#a78bfa); }
.sd-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
.sd-list  { display:flex; flex-direction:column; gap:8px; margin-top:10px; }
.sd-item  { display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1px solid var(--u-line,#e2e8f0); border-radius:9px; background:var(--u-card,#fff); }
.sd-item-body { flex:1; min-width:0; }
.sd-item-title { font-size:13px; font-weight:600; color:var(--u-text,#0f172a); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sd-item-meta  { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
.badge-status { font-size:10px; font-weight:700; border-radius:999px; padding:2px 8px; }
.bs-open       { background:rgba(220,38,38,.12); color:#b91c1c; }
.bs-in_progress{ background:rgba(217,119,6,.12); color:#b45309; }
.bs-pending    { background:rgba(100,116,139,.12); color:#475569; }
.bs-done       { background:rgba(22,163,74,.12); color:#15803d; }
.sd-warn-card  { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); border-radius:12px; padding:14px 16px; margin-bottom:14px; }
.sd-warn-card h4 { color:#b91c1c; margin:0 0 8px; font-size:14px; }
.sd-section-title { font-size:14px; font-weight:700; color:var(--u-text,#0f172a); margin:0 0 4px; }
.sd-section-sub   { font-size:12px; color:var(--u-muted,#64748b); margin:0 0 10px; }
.sd-empty { text-align:center; padding:20px; color:var(--u-muted,#64748b); font-size:13px; }
@media(max-width:900px){ .sd-grid4 { grid-template-columns:1fr 1fr; } .sd-grid2 { grid-template-columns:1fr; } }
@media(max-width:600px){ .sd-grid4 { grid-template-columns:1fr; } }
</style>

{{-- Karşılama başlığı --}}
<div style="margin-bottom:20px;">
    <h2 style="margin:0 0 4px;font-size:20px;font-weight:800;color:var(--u-text,#0f172a);">
        Merhaba, {{ $user->name ?? 'Staff' }}! 👋
    </h2>
    <p style="margin:0;font-size:13px;color:var(--u-muted,#64748b);">
        {{ now()->locale('tr')->isoFormat('dddd, D MMMM YYYY') }}
    </p>
</div>

{{-- KPI Kartları --}}
<div class="sd-grid4">
    <div class="sd-kpi blue">
        <div class="sd-kpi-num">{{ $kpi['today'] }}</div>
        <div class="sd-kpi-lbl">📋 Bugünkü Görev</div>
    </div>
    <div class="sd-kpi {{ $kpi['overdue'] > 0 ? 'red' : 'blue' }}">
        <div class="sd-kpi-num">{{ $kpi['overdue'] }}</div>
        <div class="sd-kpi-lbl">⚠️ Gecikmiş Görev</div>
    </div>
    <div class="sd-kpi orange">
        <div class="sd-kpi-num">{{ $kpi['tickets'] }}</div>
        <div class="sd-kpi-lbl">🎫 Açık Ticket</div>
    </div>
    <div class="sd-kpi purple">
        <div class="sd-kpi-num">{{ $kpi['unread'] }}</div>
        <div class="sd-kpi-lbl">📢 Okunmamış Duyuru</div>
    </div>
</div>

{{-- Gecikmiş görevler uyarısı (varsa) --}}
@if($kpi['overdue'] > 0)
<div class="sd-warn-card">
    <h4>⚠️ {{ $kpi['overdue'] }} gecikmiş göreviniz var!</h4>
    <div class="sd-list">
        @foreach($overdueTasks as $task)
        <div class="sd-item">
            <span class="badge-status bs-{{ $task->status }}">{{ ucfirst($task->status) }}</span>
            <div class="sd-item-body">
                <div class="sd-item-title">
                    <a href="/tasks/{{ $task->id }}/show" style="color:inherit;text-decoration:none;">{{ $task->title }}</a>
                </div>
                <div class="sd-item-meta">
                    Son tarih: {{ optional($task->due_date)->format('d.m.Y') ?? '-' }}
                    @if($task->priority) · Öncelik: {{ ucfirst($task->priority) }} @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Alt grid: Bugünkü Görevler + Açık Ticketlar --}}
<div class="sd-grid2">

    {{-- Bugünkü Görevler --}}
    <div class="card" style="padding:16px 18px;">
        <p class="sd-section-title">📋 Bugünkü Görevler</p>
        <p class="sd-section-sub">Bugün tamamlanması gereken görevler</p>
        @if($todayTasks->isEmpty())
            <div class="sd-empty">Bugün için görev yok. 🎉</div>
        @else
        <div class="sd-list">
            @foreach($todayTasks as $task)
            <div class="sd-item">
                <span class="badge-status bs-{{ $task->status }}">
                    @php $statusLabel = ['pending'=>'Bekliyor','in_progress'=>'Devam','done'=>'Tamam','open'=>'Açık'][$task->status] ?? ucfirst($task->status); @endphp
                    {{ $statusLabel }}
                </span>
                <div class="sd-item-body">
                    <div class="sd-item-title">
                        <a href="/tasks/{{ $task->id }}/show" style="color:inherit;text-decoration:none;">{{ $task->title }}</a>
                    </div>
                    <div class="sd-item-meta">
                        @if($task->priority)
                            @php $pColors = ['high'=>'#dc2626','medium'=>'#d97706','low'=>'#16a34a']; @endphp
                            <span style="color:{{ $pColors[$task->priority] ?? '#64748b' }};font-weight:700;">{{ ucfirst($task->priority) }}</span> öncelik
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <div style="margin-top:10px;">
            <a href="/tasks" class="btn alt" style="font-size:12px;padding:6px 14px;">Tüm Görevler →</a>
        </div>
    </div>

    {{-- Açık Ticketlar --}}
    <div class="card" style="padding:16px 18px;">
        <p class="sd-section-title">🎫 Açık Ticketlar</p>
        <p class="sd-section-sub">Yanıt bekleyen destek talepleri</p>
        @if($openTickets->isEmpty())
            <div class="sd-empty">Açık ticket yok.</div>
        @else
        <div class="sd-list">
            @foreach($openTickets as $ticket)
            <div class="sd-item">
                <span class="badge-status bs-{{ $ticket->status }}">
                    {{ $ticket->status === 'in_progress' ? 'Devam' : 'Açık' }}
                </span>
                <div class="sd-item-body">
                    <div class="sd-item-title">{{ $ticket->subject ?? '#'.$ticket->id }}</div>
                    <div class="sd-item-meta">
                        @if($ticket->priority)
                            @php $pColors = ['high'=>'#dc2626','medium'=>'#d97706','low'=>'#16a34a','urgent'=>'#7c3aed']; @endphp
                            <span style="color:{{ $pColors[$ticket->priority] ?? '#64748b' }};font-weight:600;">{{ ucfirst($ticket->priority) }}</span> ·
                        @endif
                        {{ optional($ticket->created_at)->diffForHumans() ?? '-' }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <div style="margin-top:10px;">
            <a href="/tickets-center" class="btn alt" style="font-size:12px;padding:6px 14px;">Ticket Merkezi →</a>
        </div>
    </div>

</div>

{{-- Finance Panel (yalnızca finance_admin / finance_staff) --}}
@if($financeData)
<div style="margin-bottom:20px;">
    <div style="font-size:15px;font-weight:800;color:var(--u-text,#0f172a);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
        💰 Finans Özeti
        <span style="font-size:11px;font-weight:500;color:var(--u-muted);background:var(--u-bg);border:1px solid var(--u-line);border-radius:999px;padding:2px 10px;">{{ now()->format('M Y') }}</span>
    </div>

    {{-- Finance KPIs --}}
    <div class="sd-grid4" style="margin-bottom:14px;">
        <div class="sd-kpi" style="background:linear-gradient(135deg,#15803d,#22c55e);">
            <div class="sd-kpi-num">€{{ number_format($financeData['totalPaidThisMonth'],0,',','.') }}</div>
            <div class="sd-kpi-lbl">✅ Bu Ay Tahsil</div>
        </div>
        <div class="sd-kpi" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
            <div class="sd-kpi-num">€{{ number_format($financeData['totalPendingEur'],0,',','.') }}</div>
            <div class="sd-kpi-lbl">⏳ Bekleyen</div>
        </div>
        <div class="sd-kpi {{ $financeData['totalOverdueEur'] > 0 ? 'red' : 'blue' }}">
            <div class="sd-kpi-num">€{{ number_format($financeData['totalOverdueEur'],0,',','.') }}</div>
            <div class="sd-kpi-lbl">🔴 Gecikmiş</div>
        </div>
        <div class="sd-kpi" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
            <div class="sd-kpi-num">{{ $financeData['overduePayments']->count() }}</div>
            <div class="sd-kpi-lbl">📋 Gecikmiş Fatura</div>
        </div>
    </div>

    <div class="sd-grid2">
        {{-- Gecikmiş Faturalar --}}
        <div class="card" style="padding:16px 18px;">
            <p class="sd-section-title">🔴 Gecikmiş Faturalar</p>
            <p class="sd-section-sub">Vadesi geçmiş tahsilatlar</p>
            @if($financeData['overduePayments']->isEmpty())
                <div class="sd-empty">Gecikmiş fatura yok. 🎉</div>
            @else
            <div class="sd-list">
                @foreach($financeData['overduePayments'] as $inv)
                <div class="sd-item">
                    <span class="badge-status" style="background:rgba(220,38,38,.12);color:#b91c1c;">Gecikmiş</span>
                    <div class="sd-item-body">
                        <div class="sd-item-title">{{ $inv->invoice_number }} — €{{ number_format($inv->amount_eur,0,',','.') }}</div>
                        <div class="sd-item-meta">Öğrenci #{{ $inv->student_id }} · Vade: {{ optional($inv->due_date)->format('d.m.Y') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Bekleyen Faturalar --}}
        <div class="card" style="padding:16px 18px;">
            <p class="sd-section-title">⏳ Bekleyen Faturalar</p>
            <p class="sd-section-sub">Henüz ödenmemiş faturalar</p>
            @if($financeData['pendingPayments']->isEmpty())
                <div class="sd-empty">Bekleyen fatura yok.</div>
            @else
            <div class="sd-list">
                @foreach($financeData['pendingPayments'] as $inv)
                <div class="sd-item">
                    <span class="badge-status bs-pending">Bekliyor</span>
                    <div class="sd-item-body">
                        <div class="sd-item-title">{{ $inv->invoice_number }} — €{{ number_format($inv->amount_eur,0,',','.') }}</div>
                        <div class="sd-item-meta">Öğrenci #{{ $inv->student_id }} · Vade: {{ optional($inv->due_date)->format('d.m.Y') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Son Ödemeler --}}
    @if($financeData['recentPaid']->isNotEmpty())
    <div class="card" style="padding:16px 18px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p class="sd-section-title" style="margin:0;">✅ Son Tahsilatlar</p>
        </div>
        <div class="sd-list">
            @foreach($financeData['recentPaid'] as $inv)
            <div class="sd-item">
                <span class="badge-status bs-done">Ödendi</span>
                <div class="sd-item-body">
                    <div class="sd-item-title">{{ $inv->invoice_number }} — €{{ number_format($inv->amount_eur,0,',','.') }}</div>
                    <div class="sd-item-meta">Öğrenci #{{ $inv->student_id }} · {{ optional($inv->paid_at)->format('d.m.Y H:i') }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

{{-- Okunmamış Duyurular + reactions --}}
@if($unreadBulletins->isNotEmpty())
@php $rxnAll = \App\Models\CompanyBulletin::REACTIONS; @endphp
<div class="card" style="padding:16px 18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <p class="sd-section-title" style="margin:0;">📢 Okunmamış Duyurular</p>
        <a href="/bulletins" class="btn alt" style="font-size:12px;padding:5px 12px;">Tümünü Gör</a>
    </div>
    <div class="sd-list">
        @foreach($unreadBulletins as $bulletin)
        @php
            $rxnCounts = $bulletin->reactions->groupBy('emoji')->map->count();
            $myEmoji = $myReactions[$bulletin->id] ?? null;
        @endphp
        <div class="sd-item" style="flex-direction:column;align-items:stretch;gap:6px;">
            <div style="display:flex;gap:8px;align-items:flex-start;">
                @if($bulletin->is_pinned)
                    <span style="font-size:16px;">📌</span>
                @else
                    <span style="font-size:16px;">📣</span>
                @endif
                <div class="sd-item-body" style="flex:1;">
                    <div class="sd-item-title">
                        <a href="/bulletins/{{ $bulletin->id }}" style="color:inherit;text-decoration:none;">{{ $bulletin->title }}</a>
                    </div>
                    <div class="sd-item-meta">
                        @if($bulletin->category) {{ ucfirst($bulletin->category) }} · @endif
                        {{ optional($bulletin->published_at)->diffForHumans() ?? '-' }}
                    </div>
                </div>
            </div>
            <div id="staff-rxn-{{ $bulletin->id }}" style="display:grid;grid-template-columns:repeat(5, minmax(0, 1fr));gap:4px;margin-top:4px;">
                @foreach($rxnAll as $rxnEmo)
                @php $cnt = $rxnCounts[$rxnEmo] ?? 0; @endphp
                <button type="button" data-emoji="{{ $rxnEmo }}" data-bid="{{ $bulletin->id }}"
                        onclick="staffReact({{ $bulletin->id }},'{{ $rxnEmo }}',this)"
                        style="display:flex;align-items:center;justify-content:center;gap:2px;padding:0;border-radius:6px;font-size:13px;border:1px solid {{ $myEmoji === $rxnEmo ? '#1e40af' : 'rgba(0,0,0,.15)' }};background:{{ $myEmoji === $rxnEmo ? 'rgba(30,64,175,.12)' : '#fff' }};cursor:pointer;height:22px;width:100%;min-width:0;box-sizing:border-box;overflow:hidden;font-family:'Segoe UI Emoji','Apple Color Emoji','Noto Color Emoji',sans-serif;">
                    <span>{{ $rxnEmo }}</span>@if($cnt > 0)<span style="font-size:9px;color:rgba(0,0,0,.5);font-weight:700;">{{ $cnt }}</span>@endif
                </button>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
<script nonce="{{ $cspNonce ?? '' }}">
window.staffReact = async function(bid, emoji, btn) {
    try {
        var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
        var res = await fetch('/bulletins/' + bid + '/react', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ emoji: emoji }),
            credentials: 'same-origin'
        });
        if (res.ok) location.reload();
    } catch (e) { console.error(e); }
};
</script>
@endif

@endsection
