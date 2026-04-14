@extends('senior.layouts.app')
@section('title','Ticketlar')
@section('page_title','Ticketlar')

@section('content')
@php
    $all      = $tickets ?? collect();
    $kOpen    = $all->where('status','open')->count();
    $kPending = $all->where('status','pending')->count();
    $kClosed  = $all->where('status','closed')->count();
    $kUrgent  = $all->where('priority','urgent')->count();
    $kTotal   = $all->count();

    $statusColor = ['open' => 'info', 'pending' => 'warn', 'closed' => 'ok', 'resolved' => 'ok'];
    $prioColor   = ['urgent' => 'danger', 'high' => 'warn', 'normal' => 'info', 'low' => 'ok'];
    $prioIcon    = ['urgent' => '🔴', 'high' => '🟠', 'normal' => '🔵', 'low' => '⚪'];
    $deptIcon    = ['genel' => '📋', 'belge' => '📄', 'vize' => '🛂', 'finans' => '💰', 'teknik' => '🔧'];
@endphp

{{-- KPI Strip --}}
@php
    $activeStatus   = $filters['status']   ?? 'all';
    $activePriority = $filters['priority'] ?? 'all';
    $activeQ        = $filters['q']        ?? '';
    $kpiItems = [
        ['label'=>'Toplam', 'color'=>'#7c3aed', 'val'=>$kTotal,   'icon'=>'🎫', 'param'=>'status',   'value'=>'all'],
        ['label'=>'Açık',   'color'=>'#7c3aed', 'val'=>$kOpen,    'icon'=>'📂', 'param'=>'status',   'value'=>'open'],
        ['label'=>'Bekleyen','color'=>'#d97706','val'=>$kPending,  'icon'=>'⏳', 'param'=>'status',   'value'=>'pending'],
        ['label'=>'Kapalı', 'color'=>'#16a34a', 'val'=>$kClosed,  'icon'=>'✅', 'param'=>'status',   'value'=>'closed'],
        ['label'=>'Acil',   'color'=>'#dc2626', 'val'=>$kUrgent,  'icon'=>'🚨', 'param'=>'priority', 'value'=>'urgent'],
    ];
@endphp
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px;">
    @foreach($kpiItems as $k)
    @php
        $isActive = ($k['param'] === 'status' && $activeStatus === $k['value'])
                 || ($k['param'] === 'priority' && $activePriority === $k['value'] && $k['value'] !== 'all');
        $href = url('/senior/tickets').'?'.$k['param'].'='.$k['value'].($activeQ ? '&q='.urlencode($activeQ) : '');
        $borderStyle = $isActive ? "border:2px solid {$k['color']};" : 'border:1px solid var(--u-line);';
        $bgStyle = $isActive ? "background:{$k['color']}18;" : 'background:var(--u-card);';
    @endphp
    <a href="{{ $href }}" style="{{ $bgStyle }}{{ $borderStyle }}border-radius:10px;padding:12px 14px;text-align:center;text-decoration:none;display:block;cursor:pointer;transition:all .15s;"
        onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'"
        onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="font-size:var(--tx-xl);line-height:1;">{{ $k['icon'] }}</div>
        <div style="font-size:var(--tx-xl);font-weight:800;color:{{ $k['color'] }};margin:4px 0 2px;line-height:1;">{{ $k['val'] }}</div>
        <div style="font-size:var(--tx-xs);color:{{ $isActive ? $k['color'] : 'var(--u-muted)' }};font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $k['label'] }}</div>
        @if($isActive)
            <div style="width:24px;height:3px;background:{{ $k['color'] }};border-radius:2px;margin:6px auto 0;"></div>
        @endif
    </a>
    @endforeach
</div>

{{-- Filter Bar --}}
<form method="GET" style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="🔍  Konu, departman, guest ID..."
        style="flex:1;min-width:180px;border:1px solid var(--u-line);border-radius:7px;padding:8px 12px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">

    <select name="status" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all" @selected(($filters['status']??'all')==='all')>Tüm Durumlar</option>
        <option value="open"    @selected(($filters['status']??'')==='open')>Açık</option>
        <option value="pending" @selected(($filters['status']??'')==='pending')>Bekleyen</option>
        <option value="closed"  @selected(($filters['status']??'')==='closed')>Kapalı</option>
    </select>

    <select name="priority" style="border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);color:var(--u-text);background:var(--u-bg);">
        <option value="all"    @selected(($filters['priority']??'all')==='all')>Tüm Öncelikler</option>
        <option value="urgent" @selected(($filters['priority']??'')==='urgent')>🔴 Acil</option>
        <option value="high"   @selected(($filters['priority']??'')==='high')>🟠 Yüksek</option>
        <option value="normal" @selected(($filters['priority']??'')==='normal')>🔵 Normal</option>
        <option value="low"    @selected(($filters['priority']??'')==='low')>⚪ Düşük</option>
    </select>

    <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:8px 18px;font-size:var(--tx-sm);font-weight:600;cursor:pointer;">Filtrele</button>
    <a href="{{ url('/senior/tickets') }}" style="color:var(--u-muted);font-size:var(--tx-sm);text-decoration:none;padding:8px 10px;border:1px solid var(--u-line);border-radius:7px;background:var(--u-bg);">Temizle</a>
</form>

{{-- Ticket List --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;overflow:hidden;">
    @forelse($all as $row)
    @php
        $guest     = ($guestMap ?? collect())->get((int)($row->guest_application_id ?? 0));
        $studentId = trim((string)($guest->converted_student_id ?? ''));
        $guestName = $guest ? trim($guest->first_name.' '.$guest->last_name) : '';
        $sc        = $statusColor[$row->status] ?? 'info';
        $pc        = $prioColor[$row->priority] ?? 'info';
        $pi        = $prioIcon[$row->priority] ?? '🔵';
        $di        = $deptIcon[strtolower($row->department ?? '')] ?? '📋';
        $isUrgent  = ($row->priority === 'urgent');
    @endphp
    <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);transition:background .12s;{{ $isUrgent ? 'border-left:3px solid #dc2626;' : '' }}"
        onmouseover="this.style.background='var(--u-bg)'" onmouseout="this.style.background=''">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            {{-- Left: subject + meta --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);font-weight:700;background:var(--u-line);border-radius:5px;padding:1px 7px;">#{{ $row->id }}</span>
                    <span style="font-size:var(--tx-sm);font-weight:700;color:var(--u-text);">{{ $row->subject }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:var(--tx-xs);color:var(--u-muted);">
                    <span>{{ $di }} {{ $row->department }}</span>
                    @if($guestName)
                        <span>·</span>
                        <span>👤 {{ $guestName }}</span>
                    @endif
                    @if($studentId)
                        <span>·</span>
                        <span>🎓 {{ $studentId }}</span>
                    @elseif($row->guest_application_id)
                        <span>·</span>
                        <span>Aday Öğrenci #{{ $row->guest_application_id }}</span>
                    @endif
                    @if($guest?->email)
                        <span>·</span>
                        <span>{{ $guest->email }}</span>
                    @endif
                </div>
            </div>

            {{-- Right: badges + date --}}
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
                <div style="display:flex;gap:6px;align-items:center;">
                    <span class="badge {{ $pc }}">{{ $pi }} {{ ucfirst($row->priority) }}</span>
                    <span class="badge {{ $sc }}">{{ ucfirst($row->status) }}</span>
                </div>
                @if($row->last_replied_at)
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);">
                        Son yanıt: {{ \Carbon\Carbon::parse($row->last_replied_at)->diffForHumans() }}
                    </span>
                @else
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);">Yanıt yok</span>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
            <a href="/im" style="font-size:var(--tx-xs);padding:5px 11px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">💬 Mesaj Merkezi</a>
            @if($studentId)
                <a href="/senior/students?q={{ urlencode($studentId) }}" style="font-size:var(--tx-xs);padding:5px 11px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">🎓 Öğrenci</a>
                <a href="/senior/notes?q={{ urlencode($studentId) }}" style="font-size:var(--tx-xs);padding:5px 11px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">📝 Notlar</a>
                <a href="/im" style="font-size:var(--tx-xs);padding:5px 11px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">🔍 Mesajlarda Ara</a>
            @elseif($row->guest_application_id)
                <a href="/im" style="font-size:var(--tx-xs);padding:5px 11px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">🔍 Aday Öğrenci Mesajları</a>
            @endif
        </div>
    </div>
    @empty
    <div style="padding:48px 20px;text-align:center;color:var(--u-muted);">
        <div style="font-size:40px;margin-bottom:10px;">🎫</div>
        <div style="font-size:var(--tx-base);font-weight:600;margin-bottom:4px;">Ticket bulunamadı</div>
        <div style="font-size:var(--tx-sm);">Filtre kriterlerinizi değiştirmeyi deneyin.</div>
    </div>
    @endforelse
</div>

{{-- Sonuç sayısı --}}
@if($kTotal > 0)
<div style="text-align:right;font-size:var(--tx-xs);color:var(--u-muted);margin-top:8px;">
    {{ $kTotal }} ticket gösteriliyor
    @if(($filters['q']??'') || ($filters['status']??'all')!=='all' || ($filters['priority']??'all')!=='all')
        — <a href="{{ url('/senior/tickets') }}" style="color:#7c3aed;text-decoration:none;">filtreyi temizle</a>
    @endif
</div>
@endif
@endsection
