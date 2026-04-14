@extends('marketing-admin.layouts.app')

@section('title', 'Sales Dashboard')

@push('head')
<style>
/* ── Sales Hero ── */
.sld-hero {
    background: linear-gradient(to right, #0c2340 0%, #1e3a5f 55%, #1f6fd9 100%);
    border-radius: 14px; padding: 26px 28px 22px;
    position: relative; overflow: hidden; margin-bottom: 16px;
}
.sld-hero::before { content:''; position:absolute; top:-40px; right:-40px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none; }
.sld-hero-top { display:flex; align-items:center; gap:16px; flex-wrap:wrap; position:relative; z-index:1; margin-bottom:14px; }
.sld-avatar { width:52px; height:52px; border-radius:50%; background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.35); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; flex-shrink:0; }
.sld-hero-name  { font-size:18px; font-weight:700; color:#fff; margin-bottom:4px; }
.sld-hero-badge { display:inline-block; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:999px; padding:2px 10px; font-size:11px; color:#fff; font-weight:600; margin-right:4px; }
.sld-hero-stats { display:flex; gap:18px; flex-wrap:wrap; margin-left:auto; flex-shrink:0; }
.sld-hstat { text-align:center; }
.sld-hstat-val   { font-size:18px; font-weight:700; color:#fff; line-height:1; margin-bottom:2px; }
.sld-hstat-label { font-size:10px; color:rgba(255,255,255,.65); }
.sld-hstat-sep   { width:1px; background:rgba(255,255,255,.2); align-self:stretch; }
.sld-hero-actions { display:flex; gap:6px; flex-wrap:wrap; position:relative; z-index:1; }
.sld-btn { padding:7px 14px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:all .15s; }
.sld-btn.primary { background:#fff; color:#1e3a5f; }
.sld-btn.ghost   { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.25); }
.sld-btn.ghost:hover { background:rgba(255,255,255,.22); }

/* ── Mode tabs ── */
.sld-mode-tabs { display:flex; gap:8px; align-items:center; margin-bottom:16px; }
.sld-mode-tab { padding:8px 20px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; border:1px solid var(--border,#e2e8f0); color:var(--muted,#64748b); background:var(--surface,#fff); transition:all .15s; }
.sld-mode-tab.active { background:#1e3a5f; color:#fff; border-color:#1e3a5f; }
.sld-mode-tab:hover:not(.active) { border-color:#1e3a5f; color:#1e3a5f; }

/* ── KPI strip ── */
.sld-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:900px){ .sld-kpis{ grid-template-columns:repeat(2,1fr); } }
.sld-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid var(--border,#e2e8f0); border-radius:12px; padding:14px 16px; }
.sld-kpi.c1{ border-top-color:#1e3a5f; }
.sld-kpi.c2{ border-top-color:#16a34a; }
.sld-kpi.c3{ border-top-color:#0891b2; }
.sld-kpi.c4{ border-top-color:#d97706; }
.sld-kpi-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted,#64748b); margin-bottom:5px; }
.sld-kpi-val   { font-size:26px; font-weight:900; color:var(--text,#0f172a); line-height:1; }
.sld-kpi-delta { font-size:11px; margin-top:4px; }

/* ── Content card ── */
.sld-card { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-radius:12px; overflow:hidden; margin-bottom:14px; }
.sld-card-head { padding:13px 18px; border-bottom:1px solid var(--border,#e2e8f0); display:flex; align-items:center; justify-content:space-between; gap:8px; }
.sld-card-head h3 { margin:0; font-size:14px; font-weight:700; color:var(--text,#0f172a); }

/* ── Pipeline bar ── */
.sld-stage-row { display:flex; flex-direction:column; gap:4px; padding:9px 18px; border-bottom:1px solid var(--border,#e2e8f0); }
.sld-stage-row:last-child { border-bottom:none; }
.sld-stage-top { display:flex; justify-content:space-between; align-items:center; }
.sld-stage-label { font-size:13px; color:var(--text,#0f172a); }
.sld-stage-count { font-size:14px; font-weight:700; }
.sld-stage-track { height:5px; background:var(--bg,#f1f5f9); border-radius:999px; overflow:hidden; }
.sld-stage-fill  { height:100%; border-radius:999px; }

/* ── Source table ── */
.sld-src-row { display:flex; align-items:center; gap:10px; padding:8px 18px; border-bottom:1px solid var(--border,#e2e8f0); font-size:12px; }
.sld-src-row:last-child { border-bottom:none; }
.sld-src-name  { flex:2; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sld-src-total { width:40px; text-align:right; font-weight:700; color:var(--text,#0f172a); }
.sld-src-conv  { width:55px; text-align:right; color:#15803d; font-weight:600; }

/* ── Task rows ── */
.sld-task-item { display:flex; align-items:center; gap:8px; padding:9px 18px; border-bottom:1px solid var(--border,#e2e8f0); text-decoration:none; color:inherit; transition:background .12s; }
.sld-task-item:last-child { border-bottom:none; }
.sld-task-item:hover { background:var(--bg,#f8fafc); }
.sld-notif-item { display:flex; align-items:flex-start; gap:8px; padding:10px 18px; border-bottom:1px solid var(--border,#e2e8f0); }
.sld-notif-item:last-child { border-bottom:none; }

/* hide default top bar */
.top { display:none!important; }
</style>
@endpush

@section('content')
@php
    $bm       = $benchmark ?? [];
    $bmLeads  = $bm['leads'] ?? null;
    $bmConv   = $bm['conv']  ?? null;
    $bmRate   = $bm['rate']  ?? null;

    $stageColors = [
        'not_requested'    => '#94a3b8',
        'pending_manager'  => '#d97706',
        'requested'        => '#0369a1',
        'signed_uploaded'  => '#7c3aed',
        'approved'         => '#16a34a',
        'cancelled'        => '#dc2626',
        'reopen_requested' => '#b45309',
    ];
    $totalPipeline = array_sum(array_column($pipelineStages ?? [], 'count'));

    $myTasks         = $myTasks ?? collect();
    $myNotifications = $myNotifications ?? collect();
    $today           = $today ?? now()->toDateString();
    $overdueTasks    = $myTasks->filter(fn($t) => $t->due_date && (string)$t->due_date < $today);
    $todayTasks      = $myTasks->filter(fn($t) => $t->due_date && (string)$t->due_date === $today);

    $prioColors = [
        'urgent' => ['bg'=>'#fef2f2','border'=>'#fca5a5','text'=>'#b91c1c','dot'=>'#ef4444'],
        'high'   => ['bg'=>'#fffbeb','border'=>'#fcd34d','text'=>'#92400e','dot'=>'#f59e0b'],
        'normal' => ['bg'=>'#eff6ff','border'=>'#93c5fd','text'=>'#1e40af','dot'=>'#3b82f6'],
        'low'    => ['bg'=>'#f9fafb','border'=>'#d1d5db','text'=>'#6b7280','dot'=>'#9ca3af'],
    ];

    $salesName = auth()->user()?->name ?? 'Sales';
    $salesInit = strtoupper(substr($salesName, 0, 2));
    $isAdmin   = in_array(auth()->user()?->role, ['marketing_admin','sales_admin','manager','system_admin']);

    $tierLabels = ['cold'=>'Cold','warm'=>'Warm','hot'=>'Hot','sales_ready'=>'Sales Ready','champion'=>'Champion'];
    $tierBadgeColor = ['cold'=>'#94a3b8','warm'=>'#f59e0b','hot'=>'#ef4444','sales_ready'=>'#16a34a','champion'=>'#7c3aed'];
    $tierTotal = collect($scoreTierRows ?? [])->sum();

    // ── Donut builder closure ──
    $buildDonut = function(array $items, string $labelKey, string $valueKey, array $colors, float $R = 70) use (&$buildDonut): array {
        $Cx = 110; $Cy = 110;
        $C  = 2 * M_PI * $R;
        $total = array_sum(array_column($items, $valueKey));
        if ($total <= 0) return ['segments'=>[],'total'=>0,'C'=>$C,'R'=>$R,'Cx'=>$Cx,'Cy'=>$Cy];
        $gap = 3; $cum = 0; $offset = $C * 0.25; $segs = [];
        foreach ($items as $i => $row) {
            $frac = $row[$valueKey] / $total;
            $dash = max(0, $frac * $C - $gap);
            $off  = $C - $cum * $C + $offset;
            $segs[] = [
                'color'   => $colors[$i % count($colors)],
                'dash'    => $dash,
                'gapDash' => $C - $dash,
                'offset'  => $off,
                'label'   => $row[$labelKey],
                'count'   => $row[$valueKey],
                'pct'     => round($frac * 100),
            ];
            $cum += $frac;
        }
        return ['segments'=>$segs,'total'=>$total,'C'=>$C,'R'=>$R,'Cx'=>$Cx,'Cy'=>$Cy];
    };

    // Source donut
    $srcColors   = ['#1e3a5f','#0891b2','#f59e0b','#16a34a','#7c3aed','#ef4444','#f97316','#84cc16'];
    $srcItems    = array_map(fn($r) => ['label'=>($r['source']?:'(doğrudan)'),'value'=>$r['total']], $sourceBreakdown ?? []);
    $srcDonut    = $buildDonut($srcItems, 'label', 'value', $srcColors);

    // Tier donut
    $tierColors  = ['#94a3b8','#f59e0b','#ef4444','#16a34a','#7c3aed'];
    $tierItems   = [];
    foreach ($tierLabels as $key => $label) {
        $cnt = $scoreTierRows[$key] ?? 0;
        if ($cnt > 0) $tierItems[] = ['label'=>$label,'value'=>$cnt,'color'=>$tierBadgeColor[$key]];
    }
    $tierDonut = $buildDonut($tierItems, 'label', 'value', array_column($tierItems, 'color'));
@endphp

{{-- Hero --}}
<div class="sld-hero">
    <div class="sld-hero-top">
        <div class="sld-avatar">{{ $salesInit }}</div>
        <div>
            <div class="sld-hero-name">{{ $salesName }}</div>
            <span class="sld-hero-badge">Satış Paneli</span>
            <span class="sld-hero-badge">Son 30 Gün</span>
        </div>
        <div class="sld-hero-stats">
            <div class="sld-hstat">
                <div class="sld-hstat-val">{{ number_format($newLeads ?? 0) }}</div>
                <div class="sld-hstat-label">Yeni Lead</div>
            </div>
            <div class="sld-hstat-sep"></div>
            <div class="sld-hstat">
                <div class="sld-hstat-val">{{ number_format($convRate ?? 0, 1) }}%</div>
                <div class="sld-hstat-label">Dönüşüm</div>
            </div>
            <div class="sld-hstat-sep"></div>
            <div class="sld-hstat">
                <div class="sld-hstat-val">{{ $totalPipeline }}</div>
                <div class="sld-hstat-label">Pipeline</div>
            </div>
        </div>
    </div>
    <div class="sld-hero-actions">
        <a class="sld-btn primary" href="/mktg-admin/pipeline">Pipeline</a>
        <a class="sld-btn ghost"   href="/mktg-admin/lead-sources">Lead Kaynakları</a>
        <a class="sld-btn ghost"   href="/mktg-admin/tasks">Görevlerim</a>
        @if($isAdmin)
        <a class="sld-btn ghost"   href="/mktg-admin/kpi">KPI Raporu</a>
        <a class="sld-btn ghost"   href="/mktg-admin/dealers">Bayiler</a>
        @endif
    </div>
</div>


{{-- KPI --}}
<div class="sld-kpis">
    <div class="sld-kpi c1">
        <div class="sld-kpi-label">Yeni Lead (30g)</div>
        <div class="sld-kpi-val">{{ number_format($newLeads ?? 0) }}</div>
        @if($bmLeads)
        <div class="sld-kpi-delta">
            <span style="color:{{ $bmLeads['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmLeads['up'] ? '↑' : '↓' }} {{ abs($bmLeads['delta']) }}%</span>
            <span style="color:var(--muted,#64748b);font-size:var(--tx-xs);"> önceki: {{ (int)$bmLeads['prev'] }}</span>
        </div>
        @endif
    </div>
    <div class="sld-kpi c2">
        <div class="sld-kpi-label">Sözleşme Onayı</div>
        <div class="sld-kpi-val">{{ number_format($converted ?? 0) }}</div>
        @if($bmConv)
        <div class="sld-kpi-delta">
            <span style="color:{{ $bmConv['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmConv['up'] ? '↑' : '↓' }} {{ abs($bmConv['delta']) }}%</span>
        </div>
        @endif
    </div>
    <div class="sld-kpi c3">
        <div class="sld-kpi-label">Dönüşüm Oranı</div>
        <div class="sld-kpi-val">{{ number_format($convRate ?? 0, 1) }}%</div>
        @if($bmRate)
        <div class="sld-kpi-delta">
            <span style="color:{{ $bmRate['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmRate['up'] ? '↑' : '↓' }} {{ abs($bmRate['delta']) }}%</span>
        </div>
        @endif
    </div>
    <div class="sld-kpi c4">
        <div class="sld-kpi-label">Aylık Gelir</div>
        <div class="sld-kpi-val" style="font-size:var(--tx-lg);">{{ number_format($monthlyRevenue ?? 0, 0, '.', ',') }} €</div>
    </div>
</div>

{{-- Pipeline + Kaynak --}}
<div class="grid2" style="gap:14px;margin-bottom:14px;">

    <div class="sld-card">
        <div class="sld-card-head">
            <h3>🔄 Pipeline Aşamaları</h3>
            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $totalPipeline }} toplam</span>
        </div>
        @forelse($pipelineStages ?? [] as $stage)
        @php
            $label = ($stageLabels[$stage['stage']] ?? $stage['stage']);
            $color = $stageColors[$stage['stage']] ?? '#94a3b8';
            $pct   = $totalPipeline > 0 ? round($stage['count'] / $totalPipeline * 100) : 0;
        @endphp
        <div class="sld-stage-row">
            <div class="sld-stage-top">
                <span class="sld-stage-label">{{ $label }}</span>
                <strong class="sld-stage-count" style="color:{{ $color }};">{{ $stage['count'] }}</strong>
            </div>
            <div class="sld-stage-track">
                <div class="sld-stage-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
            </div>
        </div>
        @empty
        <div style="padding:32px 18px;text-align:center;color:var(--muted,#64748b);font-size:var(--tx-sm);">Henüz pipeline verisi yok.</div>
        @endforelse
    </div>

    <div class="sld-card">
        <div class="sld-card-head">
            <h3>📡 Lead Kaynakları (30g)</h3>
            @if($srcDonut['total'] > 0)<span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $srcDonut['total'] }} lead</span>@endif
        </div>
        @if(empty($srcDonut['segments']))
        <div style="padding:32px 18px;text-align:center;color:var(--muted,#64748b);font-size:var(--tx-sm);">Henüz kaynak verisi yok.</div>
        @else
        @php $sd = $srcDonut; @endphp
        <div style="display:flex;align-items:stretch;">
            <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:16px;background:var(--bg,#f8fafc);border-right:1px solid var(--border,#e2e8f0);">
                <svg viewBox="0 0 220 220" style="width:100%;max-width:200px;height:auto;display:block;">
                    @foreach($sd['segments'] as $seg)
                    <circle cx="{{ $sd['Cx'] }}" cy="{{ $sd['Cy'] }}" r="{{ $sd['R'] }}"
                        fill="none" stroke="{{ $seg['color'] }}" stroke-width="30"
                        stroke-dasharray="{{ number_format($seg['dash'],4,'.','') }} {{ number_format($seg['gapDash'],4,'.','') }}"
                        stroke-dashoffset="{{ number_format($seg['offset'],4,'.','') }}"
                        stroke-linecap="butt"/>
                    @endforeach
                    <text x="{{ $sd['Cx'] }}" y="{{ $sd['Cy'] - 8 }}" text-anchor="middle" font-size="28" font-weight="700" fill="#1e3a5f">{{ $sd['total'] }}</text>
                    <text x="{{ $sd['Cx'] }}" y="{{ $sd['Cy'] + 14 }}" text-anchor="middle" font-size="11" fill="#6b7280">lead</text>
                </svg>
            </div>
            <div style="flex:1;padding:12px 16px;overflow:hidden;">
                @foreach($sd['segments'] as $seg)
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="width:9px;height:9px;border-radius:50%;background:{{ $seg['color'] }};flex-shrink:0;"></span>
                    <span style="flex:1;font-size:var(--tx-xs);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $seg['label'] }}">{{ $seg['label'] }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:var(--text,#0f172a);">{{ $seg['count'] }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:{{ $seg['color'] }};width:28px;text-align:right;">{{ $seg['pct'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Lead Score + Tier --}}
<div class="grid2" style="gap:14px;margin-bottom:14px;">

    <div class="sld-card">
        <div class="sld-card-head"><h3>🎯 Ortalama Lead Skoru</h3></div>
        <div style="padding:20px;text-align:center;">
            <div style="font-size:48px;font-weight:900;color:#1e3a5f;line-height:1;">{{ $avgLeadScore ?? '0.0' }}</div>
            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:6px;">Tüm aktif adayların ortalaması</div>
            @if($isAdmin)
            <a href="/mktg-admin/scoring" class="btn alt" style="margin-top:14px;font-size:var(--tx-xs);">Lead Scoring →</a>
            @endif
        </div>
    </div>

    <div class="sld-card">
        <div class="sld-card-head">
            <h3>📊 Tier Dağılımı</h3>
            @if($tierDonut['total'] > 0)<span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $tierDonut['total'] }} kayıt</span>@endif
        </div>
        @if(empty($tierDonut['segments']))
        <div style="padding:32px 18px;text-align:center;color:var(--muted,#64748b);font-size:var(--tx-sm);">Henüz veri yok.</div>
        @else
        @php $td = $tierDonut; @endphp
        <div style="display:flex;align-items:stretch;">
            <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:16px;background:var(--bg,#f8fafc);border-right:1px solid var(--border,#e2e8f0);">
                <svg viewBox="0 0 220 220" style="width:100%;max-width:200px;height:auto;display:block;">
                    @foreach($td['segments'] as $seg)
                    <circle cx="{{ $td['Cx'] }}" cy="{{ $td['Cy'] }}" r="{{ $td['R'] }}"
                        fill="none" stroke="{{ $seg['color'] }}" stroke-width="30"
                        stroke-dasharray="{{ number_format($seg['dash'],4,'.','') }} {{ number_format($seg['gapDash'],4,'.','') }}"
                        stroke-dashoffset="{{ number_format($seg['offset'],4,'.','') }}"
                        stroke-linecap="butt"/>
                    @endforeach
                    <text x="{{ $td['Cx'] }}" y="{{ $td['Cy'] - 8 }}" text-anchor="middle" font-size="28" font-weight="700" fill="#1e3a5f">{{ $td['total'] }}</text>
                    <text x="{{ $td['Cx'] }}" y="{{ $td['Cy'] + 14 }}" text-anchor="middle" font-size="11" fill="#6b7280">kayıt</text>
                </svg>
            </div>
            <div style="flex:1;padding:12px 16px;overflow:hidden;">
                @foreach($td['segments'] as $seg)
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="width:9px;height:9px;border-radius:50%;background:{{ $seg['color'] }};flex-shrink:0;"></span>
                    <span style="flex:1;font-size:var(--tx-xs);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $seg['label'] }}">{{ $seg['label'] }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:var(--text,#0f172a);">{{ $seg['count'] }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:{{ $seg['color'] }};width:28px;text-align:right;">{{ $seg['pct'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Görevler + Bildirimler --}}
@if($myTasks->isNotEmpty() || $myNotifications->isNotEmpty())
<div class="grid2" style="gap:14px;">

    @if($myTasks->isNotEmpty())
    <div class="sld-card">
        <div class="sld-card-head" style="background:linear-gradient(135deg,#1e3a5f 0%,#1f6fd9 100%);">
            <h3 style="color:#fff;">📋 Görevlerim <span style="font-size:var(--tx-xs);font-weight:400;color:rgba(255,255,255,.75);">{{ $myTasks->count() }} açık</span></h3>
            <div style="display:flex;gap:5px;">
                @if($overdueTasks->isNotEmpty())<span style="background:#ef4444;color:#fff;font-size:var(--tx-xs);font-weight:700;padding:2px 8px;border-radius:999px;">{{ $overdueTasks->count() }} gecikmiş</span>@endif
                @if($todayTasks->isNotEmpty())<span style="background:#f59e0b;color:#fff;font-size:var(--tx-xs);font-weight:700;padding:2px 8px;border-radius:999px;">{{ $todayTasks->count() }} bugün</span>@endif
                <a href="/mktg-admin/tasks" style="color:rgba(255,255,255,.85);font-size:var(--tx-xs);text-decoration:none;">Tümü →</a>
            </div>
        </div>
        @foreach($myTasks->take(8) as $task)
        @php
            $dueStr  = $task->due_date ? (string)$task->due_date : null;
            $isOver  = $dueStr && $dueStr < $today;
            $isToday = $dueStr && $dueStr === $today;
            $pc      = $prioColors[$task->priority] ?? $prioColors['normal'];
        @endphp
        <a href="/mktg-admin/tasks?highlight={{ $task->id }}" class="sld-task-item" style="background:{{ $isOver ? '#fff5f5' : ($isToday ? '#fffdf0' : '#fff') }}">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $pc['dot'] }};flex-shrink:0;"></span>
            <span style="font-size:var(--tx-xs);font-weight:700;padding:1px 6px;border-radius:4px;background:{{ $pc['bg'] }};color:{{ $pc['text'] }};border:1px solid {{ $pc['border'] }};flex-shrink:0;">{{ strtoupper($task->priority) }}</span>
            <span style="flex:1;font-size:var(--tx-xs);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $task->title }}</span>
            @if($dueStr)
            <span style="font-size:var(--tx-xs);font-weight:600;color:{{ $isOver ? '#ef4444' : ($isToday ? '#f59e0b' : 'var(--muted,#94a3b8)') }};flex-shrink:0;">{{ $isOver ? '⚠ Gecikmiş' : ($isToday ? '⏰ Bugün' : $dueStr) }}</span>
            @endif
        </a>
        @endforeach
        @if($myTasks->count() > 8)
        <div style="padding:8px 18px;text-align:center;background:var(--bg,#f8fafc);border-top:1px solid var(--border,#e2e8f0);">
            <a href="/mktg-admin/tasks" style="font-size:var(--tx-xs);color:var(--c-accent,#4f46e5);">+ {{ $myTasks->count() - 8 }} görev daha →</a>
        </div>
        @endif
    </div>
    @endif

    @if($myNotifications->isNotEmpty())
    <div class="sld-card">
        <div class="sld-card-head" style="background:linear-gradient(135deg,#312e81 0%,#4f46e5 100%);">
            <h3 style="color:#fff;">🔔 Bildirimler <span style="font-size:var(--tx-xs);font-weight:400;color:rgba(255,255,255,.75);">{{ $myNotifications->count() }} yeni</span></h3>
        </div>
        @foreach($myNotifications as $notif)
        <div class="sld-notif-item">
            <span style="width:8px;height:8px;border-radius:50%;background:#3b82f6;flex-shrink:0;margin-top:4px;"></span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-xs);font-weight:600;color:var(--text,#0f172a);">{{ $notif->subject }}</div>
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $notif->body }}</div>
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">{{ $notif->created_at?->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endif

<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;">
        <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Satış Dashboard — Sales Görünümü</h4>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Gelir, dönüşüm ve dealer performansını satış odaklı izle.</p>
        <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
            <li><strong>Aylık Gelir Grafiği:</strong> Son 12 ayın ödeme tahsilat trendi</li>
            <li><strong>Dealer Sıralaması:</strong> En çok lead getiren bayi listesi — alt sıradakilerle iletişime geç</li>
            <li><strong>Dönüşüm Hunisi:</strong> Lead → Aday Öğrenci → Öğrenci geçiş oranları</li>
            <li><strong>Hedef vs Gerçek:</strong> Aylık gelir hedefine ne kadar yakınsın?</li>
            <li><strong>Bekleyen Komisyonlar:</strong> Ödenmemiş dealer komisyonları</li>
        </ul>
        <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-ok,#16a34a) 8%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
            💡 <strong>İpucu:</strong> Satış dashboard'unu haftalık ekip toplantısında paylaş — hangi bayinin takip gerektirdiğini anında görürsün.
        </div>
    </div>
</details>

@endsection
