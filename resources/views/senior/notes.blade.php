@extends('senior.layouts.app')
@section('title','Gizli Notlar')
@section('page_title','Gizli Notlar')

@section('content')
@php
    $notesColl   = $notes ?? collect();
    $totalCnt    = $notesColl->count();
    $pinnedCnt   = $notesColl->where('is_pinned', true)->count();
    $criticalCnt = $notesColl->where('priority','critical')->count();
    $highCnt     = $notesColl->where('priority','high')->count();
    $filterQ     = $filters['q']        ?? '';
    $filterPrio  = $filters['priority'] ?? 'all';

    $priorityBadge = ['critical'=>'danger','high'=>'warn','normal'=>'info','low'=>''];
    $priorityLabel = ['critical'=>'Kritik','high'=>'Yüksek','normal'=>'Normal','low'=>'Düşük'];
@endphp

{{-- Gradient Header --}}
<div style="background:linear-gradient(to right,#6d28d9,#7c3aed);border-radius:14px;padding:14px 16px;margin-bottom:14px;color:#fff;">
    <div style="font-size:16px;font-weight:800;margin-bottom:2px;">📝 Gizli Notlar</div>
    <div style="font-size:var(--tx-sm);opacity:.8;margin-bottom:10px;">Öğrenci bazlı iç notlar — yalnızca senior ekibi görür</div>
    <div class="mob-chip-grid" style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach([
            ['label'=>'Toplam',   'count'=>$totalCnt,    'prio'=>'all'],
            ['label'=>'Pinned',   'count'=>$pinnedCnt,   'prio'=>'pinned'],
            ['label'=>'Kritik',   'count'=>$criticalCnt, 'prio'=>'critical'],
            ['label'=>'Yüksek',   'count'=>$highCnt,     'prio'=>'high'],
        ] as $chip)
        @php
            $active = $filterPrio === $chip['prio'];
            $href   = url('/senior/notes').'?priority='.$chip['prio'].($filterQ ? '&q='.urlencode($filterQ) : '');
        @endphp
        <a href="{{ $href }}" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;font-size:var(--tx-xs);font-weight:700;text-decoration:none;transition:all .15s;
            background:{{ $active ? 'rgba(255,255,255,.3)' : 'rgba(255,255,255,.12)' }};
            color:#fff;
            border:1.5px solid {{ $active ? 'rgba(255,255,255,.7)' : 'rgba(255,255,255,.2)' }};">
            {{ $chip['label'] }}
            <span style="background:rgba(255,255,255,.22);border-radius:999px;padding:1px 8px;font-size:var(--tx-xs);">{{ $chip['count'] }}</span>
        </a>
        @endforeach
    </div>
</div>

{{-- Filter Bar --}}
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;margin-bottom:14px;">
    <form method="GET" action="{{ url('/senior/notes') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:2;min-width:200px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Ara (öğrenci / kategori / içerik)</div>
            <input type="text" name="q" value="{{ $filterQ }}" placeholder="Ara…"
                   style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
        </div>
        <div style="flex:1;min-width:150px;">
            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);margin-bottom:4px;">Öncelik</div>
            <select name="priority" style="width:100%;border:1px solid var(--u-line);border-radius:7px;padding:8px 10px;font-size:var(--tx-sm);background:var(--u-bg);color:var(--u-text);">
                <option value="all"      @selected($filterPrio === 'all')>Tüm Öncelikler</option>
                <option value="critical" @selected($filterPrio === 'critical')>Kritik</option>
                <option value="high"     @selected($filterPrio === 'high')>Yüksek</option>
                <option value="normal"   @selected($filterPrio === 'normal')>Normal</option>
                <option value="low"      @selected($filterPrio === 'low')>Düşük</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:var(--tx-sm);font-weight:700;cursor:pointer;">Filtrele</button>
            <a href="{{ url('/senior/notes') }}" style="background:var(--u-bg);color:var(--u-text);border:1px solid var(--u-line);border-radius:7px;padding:9px 14px;font-size:var(--tx-sm);font-weight:600;text-decoration:none;">Temizle</a>
        </div>
    </form>
</div>

{{-- Note List --}}
<div style="display:flex;flex-direction:column;gap:10px;">
    @forelse($notesColl as $row)
    @php
        $prio       = $row->priority ?? 'normal';
        $bCls       = $priorityBadge[$prio] ?? '';
        $prioLabel  = $priorityLabel[$prio]  ?? $prio;
        $isPinned   = (bool)($row->is_pinned ?? false);
        $borderColor= $prio === 'critical' ? '#dc2626' : ($prio === 'high' ? '#f59e0b' : 'var(--u-line)');
    @endphp
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-left:4px solid {{ $borderColor }};border-radius:10px;padding:14px 18px;">

        {{-- Header row --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if($isPinned)
                    <span style="font-size:var(--tx-sm);" title="Sabitlenmiş">📌</span>
                @endif
                <span style="font-weight:800;font-size:var(--tx-sm);color:var(--u-text);">{{ ($nameMap[$row->student_id] ?? '') ?: $row->student_id }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);font-family:monospace;">{{ $row->student_id }}</span>
                @if($row->category && $row->category !== 'system')
                    <span style="background:var(--u-bg);border:1px solid var(--u-line);border-radius:999px;padding:2px 10px;font-size:var(--tx-xs);font-weight:600;color:var(--u-muted);">{{ $row->category }}</span>
                @elseif($row->category === 'system')
                    <span style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:999px;padding:2px 10px;font-size:var(--tx-xs);font-weight:600;color:#16a34a;">⚙ Otomatik</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                @if($bCls)
                    <span class="badge {{ $bCls }}" style="font-size:var(--tx-xs);">{{ $prioLabel }}</span>
                @else
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $prioLabel }}</span>
                @endif
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">{{ optional($row->created_at)->format('d.m.Y H:i') }}</span>
            </div>
        </div>

        {{-- Content --}}
        <div style="font-size:var(--tx-sm);color:var(--u-text);line-height:1.6;margin-bottom:12px;white-space:pre-wrap;">{{ $row->content }}</div>

        {{-- Action links --}}
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="/senior/students?q={{ urlencode((string)$row->student_id) }}"
               style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">👤 Öğrenci</a>
            <a href="/senior/tickets?q={{ urlencode((string)$row->student_id) }}"
               style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">🎫 Ticketlar</a>
            <a href="/im"
               style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">💬 Mesajlar</a>
            <a href="/senior/process-tracking?student_id={{ urlencode((string)$row->student_id) }}"
               style="font-size:var(--tx-xs);padding:4px 10px;border:1px solid var(--u-line);border-radius:6px;background:var(--u-bg);color:var(--u-text);text-decoration:none;font-weight:600;">🗂 Süreç</a>
        </div>
    </div>
    @empty
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:48px;text-align:center;color:var(--u-muted);font-size:var(--tx-sm);">
        Not bulunamadı.
    </div>
    @endforelse
</div>

@endsection
