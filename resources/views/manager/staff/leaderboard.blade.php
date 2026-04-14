@extends('manager.layouts.app')

@section('title', 'Personel Leaderboard')
@section('page_title', 'Personel Leaderboard')

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
        <div style="font-size:var(--tx-sm);color:var(--u-muted);">{{ $rows->count() }} kişi sıralandı</div>
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <form method="GET" action="/manager/staff/leaderboard" style="display:flex;gap:6px;">
            <input type="hidden" name="layer" value="{{ $layerFilter }}">
            <input type="hidden" name="dept"  value="{{ $deptFilter }}">
            <input type="month" name="period" value="{{ $period }}"
                   style="padding:6px 10px;border:1.5px solid var(--u-line);border-radius:7px;font-size:12px;background:var(--u-bg);color:var(--u-text);">
            <button type="submit" class="btn alt" style="font-size:11px;padding:6px 14px;">Göster</button>
        </form>
        <a href="/manager/staff" class="btn alt" style="font-size:11px;padding:6px 14px;">← Liste</a>
    </div>
</div>

{{-- Katman Sekmeleri (birincil filtre) --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
    <a href="/manager/staff/leaderboard?layer=admin&period={{ $period }}"
       class="layer-tab {{ $layerFilter === 'admin' ? 'active' : '' }}">🔑 Admin</a>
    <a href="/manager/staff/leaderboard?layer=senior&period={{ $period }}"
       class="layer-tab {{ $layerFilter === 'senior' ? 'active' : '' }}">👨‍💼 Eğitim Danışmanı</a>
    <a href="/manager/staff/leaderboard?layer=personel&period={{ $period }}"
       class="layer-tab {{ $layerFilter === 'personel' ? 'active' : '' }}">👥 Personel</a>
</div>

{{-- Departman Filtresi (ikincil — sadece admin/personel katmanında) --}}
@if(!$isSenior)
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;align-items:center;">
    <span style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-right:4px;">Departman:</span>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=hepsi&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'hepsi' ? 'active' : '' }}">Hepsi</a>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=sistem&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'sistem' ? 'active' : '' }}">🖥 Sistem</a>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=operasyon&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'operasyon' ? 'active' : '' }}">⚙ Operasyon</a>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=finans&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'finans' ? 'active' : '' }}">💰 Finans</a>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=pazarlama&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'pazarlama' ? 'active' : '' }}">📣 Pazarlama</a>
    <a href="/manager/staff/leaderboard?layer={{ $layerFilter }}&dept=satis&period={{ $period }}"
       class="dept-btn {{ $deptFilter === 'satis' ? 'active' : '' }}">🤝 Satış</a>
</div>
@endif

{{-- Özet KPI Kartları --}}
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
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #1e40af;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Görev</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_tasks'], 1) }}</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #6366f1;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Ticket</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_tickets'], 1) }}</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #0891b2;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Toplam Saat</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['total_hours'], 1) }}h</div>
    </div>
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #16a34a;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ort. Skor</div>
        <div style="font-size:22px;font-weight:800;color:var(--u-text);line-height:1;">{{ number_format($summary['avg_score'], 0) }}</div>
    </div>
</div>
@endif

{{-- Leaderboard Tablosu --}}
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

@endsection

@push('scripts')
<script>
(function(){
    var sortCol = 'score', sortDir = -1;

    window.lbSort = function(col) {
        if (sortCol === col) { sortDir *= -1; } else { sortCol = col; sortDir = -1; }
        document.querySelectorAll('.lb-table th[data-col]').forEach(function(th){
            th.classList.toggle('sorted', th.dataset.col === col);
        });
        var tbody = document.getElementById('lb-body');
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
}());
</script>
@endpush
