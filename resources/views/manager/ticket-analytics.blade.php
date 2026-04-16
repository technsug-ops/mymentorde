@extends('manager.layouts.app')
@section('title', 'Destek Talebi Analitik')
@section('page_title', 'Destek Talebi Analitik')

@php
    $priorityColors = ['low'=>'#94a3b8','medium'=>'#3b82f6','high'=>'#f59e0b','urgent'=>'#dc2626'];
    $statusColors   = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','resolved'=>'#16a34a','closed'=>'#94a3b8'];
    $statusLabels   = ['open'=>'Açık','in_progress'=>'İşlemde','resolved'=>'Çözüldü','closed'=>'Kapatıldı'];
@endphp

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;">🎫 Destek Talebi Analitik</h1>
        <div class="u-muted" style="font-size:var(--tx-sm);">Ticket akışı, yanıt/çözüm süreleri ve SLA uyumu</div>
    </div>
</div>

<form method="GET" class="card" style="margin-bottom:20px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;padding:14px;">
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BAŞLANGIÇ</label>
        <input type="date" name="start_date" value="{{ $filters['start_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">BİTİŞ</label>
        <input type="date" name="end_date" value="{{ $filters['end_date'] }}" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">DEPARTMAN</label>
        <select name="department" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
            <option value="all" @selected($filters['department']==='all')>Tümü</option>
            @foreach($departmentOptions as $d)
            <option value="{{ $d }}" @selected($filters['department']===$d)>{{ ucfirst($d) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="u-muted" style="font-size:11px;display:block;margin-bottom:4px;">ÖNCELİK</label>
        <select name="priority" style="padding:6px 8px;border:1px solid var(--u-line);border-radius:6px;">
            <option value="all" @selected($filters['priority']==='all')>Tümü</option>
            <option value="urgent" @selected($filters['priority']==='urgent')>Acil</option>
            <option value="high"   @selected($filters['priority']==='high')>Yüksek</option>
            <option value="medium" @selected($filters['priority']==='medium')>Orta</option>
            <option value="low"    @selected($filters['priority']==='low')>Düşük</option>
        </select>
    </div>
    <button class="btn" type="submit" style="padding:8px 16px;">🔍 Uygula</button>
    <a href="{{ route('manager.ticket-analytics') }}" class="u-muted" style="font-size:12px;text-decoration:none;margin-left:6px;">Sıfırla</a>
</form>

{{-- KPI Cards --}}
<div class="grid5" style="display:grid;grid-template-columns:repeat(5, 1fr);gap:12px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">TOPLAM TİCKET</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ $total }}</div>
        <div class="u-muted" style="font-size:11px;">Seçilen aralık</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">AÇIK / İŞLEMDE</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:#f59e0b;">{{ $openCount }}</div>
        <div class="u-muted" style="font-size:11px;">Çözüm bekliyor</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">ORT. İLK YANIT</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $avgFirstResponseH <= 4 ? '#16a34a' : ($avgFirstResponseH <= 24 ? '#f59e0b' : '#dc2626') }};">
            {{ $avgFirstResponseH }}<span style="font-size:14px;font-weight:600;"> sa</span>
        </div>
        <div class="u-muted" style="font-size:11px;">Ticket açılış → ilk yanıt</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">ORT. ÇÖZÜM SÜRESİ</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">
            {{ $avgResolutionDays }}<span style="font-size:14px;font-weight:600;"> gün</span>
        </div>
        <div class="u-muted" style="font-size:11px;">Kapatılan ticket'lar</div>
    </div>
    <div class="card" style="padding:16px;">
        <div class="u-muted" style="font-size:11px;letter-spacing:.05em;">SLA İHLALİ</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;color:{{ $slaBreachPct <= 10 ? '#16a34a' : ($slaBreachPct <= 25 ? '#f59e0b' : '#dc2626') }};">
            %{{ $slaBreachPct }}
        </div>
        <div class="u-muted" style="font-size:11px;">{{ $slaBreach }} / {{ $slaTotal }} ticket</div>
    </div>
</div>

{{-- Trend Chart --}}
<div class="card" style="padding:16px;margin-bottom:20px;">
    <div class="card-title" style="font-weight:700;margin-bottom:12px;">📈 Günlük Ticket Trendi (30 gün)</div>
    <div style="position:relative;height:180px;width:100%;">
        <canvas id="ticketTrendChart"></canvas>
    </div>
</div>

{{-- Status + Priority + Department --}}
<div class="grid3" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">📊 Duruma Göre</div>
        @php $mxS = $byStatus->max() ?: 1; @endphp
        @forelse($byStatus as $st => $cnt)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                <span>{{ $statusLabels[$st] ?? $st }}</span>
                <span class="u-muted">{{ $cnt }}</span>
            </div>
            <div style="height:8px;background:rgba(0,0,0,.06);border-radius:4px;overflow:hidden;">
                <div style="width:{{ round($cnt/$mxS*100) }}%;height:100%;background:{{ $statusColors[$st] ?? '#64748b' }};"></div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="text-align:center;padding:12px;">Veri yok.</div>
        @endforelse
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">🔺 Önceliğe Göre</div>
        @php $mxP = $byPriority->max() ?: 1; @endphp
        @forelse($byPriority as $pr => $cnt)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                <span>{{ ucfirst($pr ?: '-') }}</span>
                <span class="u-muted">{{ $cnt }}</span>
            </div>
            <div style="height:8px;background:rgba(0,0,0,.06);border-radius:4px;overflow:hidden;">
                <div style="width:{{ round($cnt/$mxP*100) }}%;height:100%;background:{{ $priorityColors[$pr] ?? '#64748b' }};"></div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="text-align:center;padding:12px;">Veri yok.</div>
        @endforelse
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">🏢 Departmana Göre</div>
        @php $mxD = $byDept->max() ?: 1; @endphp
        @forelse($byDept as $d => $cnt)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                <span>{{ ucfirst($d) }}</span>
                <span class="u-muted">{{ $cnt }}</span>
            </div>
            <div style="height:8px;background:rgba(0,0,0,.06);border-radius:4px;overflow:hidden;">
                <div style="width:{{ round($cnt/$mxD*100) }}%;height:100%;background:#3b82f6;"></div>
            </div>
        </div>
        @empty
        <div class="u-muted" style="text-align:center;padding:12px;">Veri yok.</div>
        @endforelse
    </div>
</div>

{{-- Assignee Performance + Recent Tickets --}}
<div class="grid2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--u-line);">
            <div class="card-title" style="font-weight:700;">👥 Atanan Kişi Performansı</div>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead style="background:rgba(0,0,0,.03);">
                <tr>
                    <th style="text-align:left;padding:8px 12px;">Kişi</th>
                    <th style="text-align:right;padding:8px 12px;">Toplam</th>
                    <th style="text-align:right;padding:8px 12px;">Açık</th>
                    <th style="text-align:right;padding:8px 12px;">Çözüm</th>
                    <th style="text-align:right;padding:8px 12px;">Ort. Yanıt (sa)</th>
                </tr>
            </thead>
            <tbody>
            @forelse($byAssignee as $uid => $d)
                <tr style="border-top:1px solid rgba(0,0,0,.06);">
                    <td style="padding:10px 12px;">{{ $d['name'] }}</td>
                    <td style="padding:10px 12px;text-align:right;font-weight:600;">{{ $d['total'] }}</td>
                    <td style="padding:10px 12px;text-align:right;color:#f59e0b;">{{ $d['open'] }}</td>
                    <td style="padding:10px 12px;text-align:right;color:#16a34a;">{{ $d['resolved'] }}</td>
                    <td style="padding:10px 12px;text-align:right;color:{{ $d['avgRespH'] <= 4 ? '#16a34a' : ($d['avgRespH'] <= 24 ? '#f59e0b' : '#dc2626') }};font-weight:600;">
                        {{ $d['avgRespH'] }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding:20px;text-align:center;" class="u-muted">Atanmış ticket yok.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="card" style="padding:16px;">
        <div class="card-title" style="font-weight:700;margin-bottom:12px;">📩 Son Ticket'lar</div>
        @forelse($recent as $t)
        <div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="display:flex;justify-content:space-between;gap:10px;font-size:11px;margin-bottom:4px;">
                <div>
                    <span style="background:{{ $statusColors[$t->status] ?? '#64748b' }}22;color:{{ $statusColors[$t->status] ?? '#64748b' }};padding:2px 6px;border-radius:4px;font-weight:600;">
                        {{ $statusLabels[$t->status] ?? $t->status }}
                    </span>
                    <span style="color:{{ $priorityColors[$t->priority] ?? '#64748b' }};margin-left:6px;font-weight:600;">
                        {{ ucfirst($t->priority ?: '-') }}
                    </span>
                </div>
                <span class="u-muted">{{ optional($t->created_at)->format('d.m.Y H:i') }}</span>
            </div>
            <div style="font-size:13px;font-weight:600;">#{{ $t->id }} · {{ $t->subject }}</div>
            @if($t->assignee)
            <div class="u-muted" style="font-size:11px;">Atanan: {{ $t->assignee }}</div>
            @endif
        </div>
        @empty
        <div class="u-muted" style="padding:12px;text-align:center;">Ticket yok.</div>
        @endforelse
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    const trend = @json($trendDays->values());
    const ctx = document.getElementById('ticketTrendChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trend.map(d => d.label),
                datasets: [{ label: 'Yeni Ticket', data: trend.map(d => d.count), backgroundColor: 'rgba(59, 130, 246, .6)', borderRadius: 4 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } },
            },
        });
    }
</script>

<style>
@media (max-width: 900px) {
    .grid5 { grid-template-columns: repeat(2, 1fr) !important; }
    .grid3 { grid-template-columns: 1fr !important; }
    .grid2 { grid-template-columns: 1fr !important; }
}
</style>
@endsection
