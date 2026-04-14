@extends('manager.layouts.app')

@section('title', 'Personel Performans')
@section('page_title', 'Personel Performans')

@push('head')
<style>
.lb-table { width:100%; border-collapse:collapse; font-size:12px; }
.lb-table thead tr { background:var(--u-bg); }
.lb-table th { padding:8px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; cursor:pointer; user-select:none; }
.lb-table th:hover { color:var(--u-text); }
.lb-table th.sorted { color:#1e40af; }
.lb-table tbody tr { border-bottom:1px solid var(--u-line); transition:background .1s; }
.lb-table tbody tr:hover { background:rgba(30,64,175,.03); }
.lb-table td { padding:9px 10px; vertical-align:middle; }
.lb-bar-wrap { width:70px; height:6px; background:var(--u-line); border-radius:999px; overflow:hidden; display:inline-block; vertical-align:middle; margin-right:4px; }
.lb-bar-fill { height:100%; border-radius:999px; }
.rank-badge { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; font-size:11px; font-weight:800; }
.layer-tab { padding:6px 16px; font-size:12px; font-weight:700; border:1.5px solid var(--u-line); border-radius:7px; background:var(--u-card); color:var(--u-muted); text-decoration:none; white-space:nowrap; transition:all .12s; }
.layer-tab.active { border-color:#1e40af; background:#1e40af; color:#fff; }
.dept-btn { padding:4px 12px; font-size:11px; font-weight:700; border:1.5px solid var(--u-line); border-radius:6px; background:var(--u-card); color:var(--u-muted); cursor:pointer; text-decoration:none; white-space:nowrap; transition:all .12s; }
.dept-btn.active, .dept-btn:hover { border-color:#6366f1; background:#6366f1; color:#fff; }
.view-tab-btn { padding:7px 18px; font-size:12px; font-weight:700; border:1.5px solid var(--u-line); border-radius:8px; background:var(--u-card); color:var(--u-muted); cursor:pointer; text-decoration:none; transition:all .12s; }
.view-tab-btn.active { border-color:#1e40af; background:#eff6ff; color:#1e40af; }
</style>
@endpush

@section('content')

@php
    $monthNames = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran',
                   '07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
    $pMonth = substr($period, 5, 2);
    $pYear  = substr($period, 0, 4);
    $periodLabel = ($monthNames[$pMonth] ?? $pMonth) . ' ' . $pYear;
@endphp

{{-- Başlık + Period --}}
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">{{ $periodLabel }}</div>
        <div style="font-size:var(--tx-sm);color:var(--u-muted);">{{ $rows->count() }} kişi listelendi</div>
    </div>
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="/manager/staff/performance" style="display:flex;gap:6px;">
            <input type="hidden" name="layer" value="{{ $layerFilter }}">
            <input type="hidden" name="dept"  value="{{ $deptFilter }}">
            <input type="hidden" name="tab"   value="{{ $activeTab }}">
            <select name="period" onchange="this.form.submit()"
                    style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
                @foreach($periods as $p)
                <option value="{{ $p }}" {{ $p === $period ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $p)->locale('tr')->isoFormat('MMMM YYYY') }}
                </option>
                @endforeach
            </select>
        </form>
        <a href="/manager/staff" class="btn alt" style="font-size:11px;padding:6px 14px;">← Liste</a>
    </div>
</div>

{{-- Katman Sekmeleri --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
    <a href="/manager/staff/performance?layer=admin&period={{ $period }}&tab={{ $activeTab }}"
       class="layer-tab {{ $layerFilter === 'admin' ? 'active' : '' }}">🔑 Admin</a>
    <a href="/manager/staff/performance?layer=senior&period={{ $period }}&tab={{ $activeTab }}"
       class="layer-tab {{ $layerFilter === 'senior' ? 'active' : '' }}">👨‍💼 Eğitim Danışmanı</a>
    <a href="/manager/staff/performance?layer=personel&period={{ $period }}&tab={{ $activeTab }}"
       class="layer-tab {{ $layerFilter === 'personel' ? 'active' : '' }}">👥 Personel</a>
</div>

{{-- Departman Filtresi --}}
@if(!$isSenior)
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;align-items:center;">
    <span style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-right:4px;">Departman:</span>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=hepsi&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'hepsi' ? 'active' : '' }}">Hepsi</a>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=sistem&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'sistem' ? 'active' : '' }}">🖥 Sistem</a>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=operasyon&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'operasyon' ? 'active' : '' }}">⚙ Operasyon</a>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=finans&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'finans' ? 'active' : '' }}">💰 Finans</a>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=pazarlama&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'pazarlama' ? 'active' : '' }}">📣 Pazarlama</a>
    <a href="/manager/staff/performance?layer={{ $layerFilter }}&dept=satis&period={{ $period }}&tab={{ $activeTab }}" class="dept-btn {{ $deptFilter === 'satis' ? 'active' : '' }}">🤝 Satış</a>
</div>
@endif

{{-- Özet Kartlar --}}
@if($isSenior)
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #1e40af;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Aktif Öğrenci</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_students'], 1) }}</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #6366f1;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Aktif Aday</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_guests'], 1) }}</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #0891b2;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Dönem Dönüşümü</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ $summary['total_conversions'] }}</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #16a34a;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Skor</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_score'], 0) }}</div>
    </div>
</div>
@else
@php
    $onTarget  = $rows->filter(fn($r) => $r->score >= 80)->count();
    $atRisk    = $rows->filter(fn($r) => $r->score >= 50 && $r->score < 80)->count();
    $offTarget = $rows->filter(fn($r) => $r->score < 50)->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #1e40af;border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#1e40af;">{{ $rows->count() }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Takip Edilen</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #16a34a;border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ $onTarget }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Hedefte (≥80%)</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #d97706;border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#d97706;">{{ $atRisk }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Risk Altında (50-79%)</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #dc2626;border-radius:10px;padding:12px 14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#dc2626;">{{ $offTarget }}</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">Hedefin Altında (&lt;50%)</div>
    </div>
</div>
@endif

{{-- View Tab Butonları --}}
<div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;">
    <button data-tab="leaderboard" id="tab-btn-leaderboard"
            class="view-tab-btn {{ $activeTab === 'leaderboard' ? 'active' : '' }}">
        🏆 Sıralama
    </button>
    @if(!$isSenior)
    <button data-tab="kpi" id="tab-btn-kpi"
            class="view-tab-btn {{ $activeTab === 'kpi' ? 'active' : '' }}">
        🎯 KPI Hedefleri
    </button>
    @endif
</div>

{{-- TAB: Leaderboard --}}
<div id="tab-leaderboard" style="{{ $activeTab !== 'leaderboard' ? 'display:none;' : '' }}">
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        @if($rows->isEmpty())
        <div style="padding:40px;text-align:center;color:var(--u-muted);font-size:13px;">Bu katman / dönem için veri bulunamadı.</div>
        @else
        <table class="lb-table" id="lb-table">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">#</th>
                    <th onclick="lbSort('name')" data-col="name">Ad Soyad ↕</th>
                    @if(!$isSenior)
                    <th>Departman</th>
                    @endif
                    @if($isSenior)
                    <th onclick="lbSort('students')" data-col="students" style="text-align:center;">Aktif Öğrenci ↕</th>
                    <th onclick="lbSort('guests')"   data-col="guests"   style="text-align:center;">Aktif Aday ↕</th>
                    <th onclick="lbSort('conversions')" data-col="conversions" style="text-align:center;">Dönüşüm ↕</th>
                    @else
                    <th onclick="lbSort('tasks')"   data-col="tasks"   style="text-align:center;">Görev ↕</th>
                    <th onclick="lbSort('tickets')" data-col="tickets" style="text-align:center;">Ticket ↕</th>
                    <th onclick="lbSort('hours')"   data-col="hours"   style="text-align:center;">Saat ↕</th>
                    @endif
                    <th onclick="lbSort('score')" data-col="score" style="text-align:center;" class="sorted">Skor ↕</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="lb-body">
            @foreach($rows as $i => $row)
            @php
                $rank = $i + 1;
                $rankStyle = match($rank) {
                    1 => 'background:#fef9c3;color:#854d0e;',
                    2 => 'background:#f1f5f9;color:#475569;',
                    3 => 'background:#fef3c7;color:#92400e;',
                    default => 'background:var(--u-bg);color:var(--u-muted);',
                };
                $scoreColor = $row->score >= 80 ? '#16a34a' : ($row->score >= 50 ? '#3b82f6' : '#f59e0b');
                $a = $row->actuals;
                $t = $row->target;
                $barFn = fn($actual, $target) => $target > 0 ? min(100, round($actual / $target * 100)) : min(100, $actual * 10);
                $colFn = fn($pct) => $pct >= 80 ? '#16a34a' : ($pct >= 50 ? '#3b82f6' : '#f59e0b');
            @endphp
            @if($isSenior)
            <tr data-name="{{ $row->user->name }}"
                data-students="{{ $a['active_students'] }}"
                data-guests="{{ $a['active_guests'] }}"
                data-conversions="{{ $a['conversions'] }}"
                data-score="{{ $row->score }}">
                <td style="text-align:center;"><span class="rank-badge" style="{{ $rankStyle }}">{{ $rank }}</span></td>
                <td>
                    <div style="font-weight:700;color:var(--u-text);">{{ $row->user->name ?: '—' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">{{ $row->user->email }}</div>
                </td>
                <td style="text-align:center;">
                    @php $maxS = $rows->max(fn($r)=>$r->actuals['active_students']) ?: 1; @endphp
                    <span class="lb-bar-wrap"><span class="lb-bar-fill" style="width:{{ min(100,round($a['active_students']/$maxS*100)) }}%;background:#1e40af;"></span></span>
                    <span style="font-size:12px;font-weight:700;">{{ $a['active_students'] }}</span>
                </td>
                <td style="text-align:center;">
                    @php $maxG = $rows->max(fn($r)=>$r->actuals['active_guests']) ?: 1; @endphp
                    <span class="lb-bar-wrap"><span class="lb-bar-fill" style="width:{{ min(100,round($a['active_guests']/$maxG*100)) }}%;background:#6366f1;"></span></span>
                    <span style="font-size:12px;font-weight:700;">{{ $a['active_guests'] }}</span>
                </td>
                <td style="text-align:center;">
                    <span style="font-size:14px;font-weight:800;color:{{ $a['conversions'] > 0 ? '#16a34a' : 'var(--u-muted)' }};">
                        {{ $a['conversions'] > 0 ? '+'.$a['conversions'] : '0' }}
                    </span>
                </td>
                <td style="text-align:center;"><span style="font-size:15px;font-weight:900;color:{{ $scoreColor }};">{{ $row->score }}</span></td>
                <td>
                    <a href="/manager/hr/persons/{{ $row->user->id }}?tab=kpi"
                       style="display:inline-block;padding:4px 10px;font-size:11px;font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;">Detay →</a>
                </td>
            </tr>
            @else
            <tr data-name="{{ $row->user->name }}"
                data-tasks="{{ $a['tasks_done'] }}"
                data-tickets="{{ $a['tickets_resolved'] }}"
                data-hours="{{ $a['hours_logged'] }}"
                data-score="{{ $row->score }}"
                data-dept="{{ $row->dept }}">
                <td style="text-align:center;"><span class="rank-badge" style="{{ $rankStyle }}">{{ $rank }}</span></td>
                <td>
                    <div style="font-weight:700;color:var(--u-text);">{{ $row->user->name ?: '—' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">{{ $row->user->email }}</div>
                </td>
                <td><span class="badge info" style="font-size:10px;">{{ $row->dept }}</span></td>
                <td style="text-align:center;">
                    @php $pct = $barFn($a['tasks_done'], $t?->target_tasks_done ?? 0); @endphp
                    <span class="lb-bar-wrap"><span class="lb-bar-fill" style="width:{{ $pct }}%;background:{{ $colFn($pct) }};"></span></span>
                    <span style="font-size:12px;font-weight:700;">{{ $a['tasks_done'] }}</span>
                    @if($t && $t->target_tasks_done > 0)<span style="font-size:10px;color:var(--u-muted);">/{{ $t->target_tasks_done }}</span>@endif
                </td>
                <td style="text-align:center;">
                    @php $pct = $barFn($a['tickets_resolved'], $t?->target_tickets_resolved ?? 0); @endphp
                    <span class="lb-bar-wrap"><span class="lb-bar-fill" style="width:{{ $pct }}%;background:{{ $colFn($pct) }};"></span></span>
                    <span style="font-size:12px;font-weight:700;">{{ $a['tickets_resolved'] }}</span>
                    @if($t && $t->target_tickets_resolved > 0)<span style="font-size:10px;color:var(--u-muted);">/{{ $t->target_tickets_resolved }}</span>@endif
                </td>
                <td style="text-align:center;">
                    @php $pct = $barFn($a['hours_logged'], $t?->target_hours_logged ?? 0); @endphp
                    <span class="lb-bar-wrap"><span class="lb-bar-fill" style="width:{{ $pct }}%;background:{{ $colFn($pct) }};"></span></span>
                    <span style="font-size:12px;font-weight:700;">{{ $a['hours_logged'] }}h</span>
                    @if($t && $t->target_hours_logged > 0)<span style="font-size:10px;color:var(--u-muted);">/{{ $t->target_hours_logged }}h</span>@endif
                </td>
                <td style="text-align:center;"><span style="font-size:15px;font-weight:900;color:{{ $scoreColor }};">{{ $row->score }}</span></td>
                <td>
                    <a href="/manager/staff/{{ $row->user->id }}?period={{ $period }}"
                       style="display:inline-block;padding:4px 10px;font-size:11px;font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;">Detay →</a>
                </td>
            </tr>
            @endif
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>
</div>

{{-- TAB: KPI Hedefleri (sadece non-senior) --}}
@if(!$isSenior)
<div id="tab-kpi" style="{{ $activeTab !== 'kpi' ? 'display:none;' : '' }}">
@php
if (!function_exists('kpiBar')) {
    function kpiBar(int|null $pct, string $val, int|float $target, string $unit = ''): string {
        if ($pct === null) {
            return '<span style="font-size:11px;color:var(--u-muted);">' . $val . $unit . ' <span style="opacity:.5;">(hedef yok)</span></span>';
        }
        $color = $pct >= 80 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
        return '<div style="font-size:11px;color:var(--u-text);font-weight:700;margin-bottom:3px;">'
            . $val . $unit . ' / ' . $target . $unit . ' <span style="color:' . $color . ';font-size:10px;">%' . $pct . '</span></div>'
            . '<div style="height:5px;background:var(--u-line);border-radius:999px;overflow:hidden;">'
            . '<div style="height:100%;width:' . $pct . '%;background:' . $color . ';border-radius:999px;transition:width .4s;"></div>'
            . '</div>';
    }
}
@endphp

@if($rows->isEmpty())
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:48px;text-align:center;color:var(--u-muted);">
    <div style="font-size:32px;margin-bottom:10px;">📊</div>
    <div style="font-weight:700;font-size:14px;">Bu dönem için staff çalışan bulunamadı.</div>
</div>
@else
<div style="display:flex;flex-direction:column;gap:10px;">
@foreach($rows as $i => $row)
@php
    $act    = $row->act;
    $target = $row->target;
    $score  = $row->score;
    $user   = $row->user;

    $scoreColor = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#d97706' : '#dc2626');
    $scoreBadge = $score >= 80 ? 'ok' : ($score >= 50 ? 'warn' : 'danger');

    $tTask   = $target?->target_tasks_done      ?? 0;
    $tTicket = $target?->target_tickets_resolved ?? 0;
    $tHours  = $target?->target_hours_logged     ?? 0;

    $pTask   = $tTask   > 0 ? min(100, round($act['tasks_done']       / $tTask   * 100)) : null;
    $pTicket = $tTicket > 0 ? min(100, round($act['tickets_resolved'] / $tTicket * 100)) : null;
    $pHours  = $tHours  > 0 ? min(100, round($act['hours_logged']     / $tHours  * 100)) : null;
@endphp
<div style="background:var(--u-card);border:1px solid var(--u-line);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="width:28px;text-align:center;font-size:13px;font-weight:800;color:var(--u-muted);">{{ $i + 1 }}</div>
    <div style="width:38px;height:38px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;flex-shrink:0;">
        {{ strtoupper(substr($user->name ?: $user->email, 0, 1)) }}
    </div>
    <div style="min-width:130px;">
        <div style="font-size:13px;font-weight:800;color:var(--u-text);">{{ $user->name }}</div>
        <div style="font-size:10px;color:var(--u-muted);">{{ $row->dept }}</div>
    </div>
    <div style="flex:1;min-width:200px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Görevler</div>
            {!! kpiBar($pTask, (string)$act['tasks_done'], $tTask) !!}
        </div>
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Biletler</div>
            {!! kpiBar($pTicket, (string)$act['tickets_resolved'], $tTicket) !!}
        </div>
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Saat</div>
            {!! kpiBar($pHours, (string)$act['hours_logged'], $tHours, 's') !!}
        </div>
    </div>
    <div style="text-align:center;flex-shrink:0;min-width:70px;">
        <div style="font-size:28px;font-weight:900;color:{{ $scoreColor }};line-height:1;">{{ $score }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:1px;">puan</div>
        <span class="badge {{ $scoreBadge }}" style="font-size:10px;margin-top:4px;">
            {{ $score >= 80 ? 'Hedefte' : ($score >= 50 ? 'Risk' : 'Altında') }}
        </span>
    </div>
    <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi&period={{ $period }}"
       style="font-size:11px;font-weight:700;color:#1e40af;text-decoration:none;flex-shrink:0;">Detay →</a>
    @if(!$target)
    <div style="width:100%;margin-top:4px;padding:6px 10px;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;font-size:11px;color:#92400e;">
        ⚠ Bu çalışan için <strong>{{ $period }}</strong> dönemi KPI hedefi tanımlanmamış.
        <a href="/manager/hr/persons/{{ $user->id }}?tab=kpi" style="color:#92400e;font-weight:700;margin-left:4px;">Hedef Belirle →</a>
    </div>
    @endif
</div>
@endforeach
</div>
@endif
</div>
@endif

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var sortCol = 'score', sortDir = -1;

    window.lbSort = function(col) {
        if (sortCol === col) { sortDir *= -1; } else { sortCol = col; sortDir = -1; }
        document.querySelectorAll('.lb-table th[data-col]').forEach(function(th){
            th.classList.toggle('sorted', th.dataset.col === col);
        });
        var tbody = document.getElementById('lb-body');
        if (!tbody) return;
        var rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function(a, b){
            var av = a.dataset[col] || '', bv = b.dataset[col] || '';
            if (!isNaN(av) && !isNaN(bv)) { return (parseFloat(av) - parseFloat(bv)) * sortDir; }
            return av.localeCompare(bv, 'tr') * sortDir;
        });
        var idx = 1;
        rows.forEach(function(r){
            var badge = r.querySelector('.rank-badge');
            if (badge) badge.textContent = idx++;
            tbody.appendChild(r);
        });
    };

    function switchTab(tab) {
        ['leaderboard', 'kpi'].forEach(function(t) {
            var panel = document.getElementById('tab-' + t);
            var btn   = document.getElementById('tab-btn-' + t);
            if (panel) panel.style.display = (t === tab) ? '' : 'none';
            if (btn) btn.classList.toggle('active', t === tab);
        });
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url.toString());
    }

    document.querySelectorAll('[data-tab]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchTab(btn.dataset.tab);
        });
    });
}());
</script>
@endpush
