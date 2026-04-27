@extends('manager.layouts.app')
@section('title', 'Lead Pipeline — Oversight')
@section('page_title', 'Lead Pipeline — Oversight')
@section('page_subtitle', 'Tüm ekibin pipeline gözetimi · Operasyonel iş Senior portal\'da')

@push('head')
<style>
.ov-bar    { display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px;padding:12px 14px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e9f0);border-radius:10px; }
.ov-bar select, .ov-bar a.btn-clear { font-size:12px;padding:6px 10px;border-radius:6px;border:1px solid var(--u-line,#e5e9f0);background:#fff; }
.ov-bar label { font-size:11px;font-weight:600;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-right:4px; }

.ov-kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px; }
.ov-kpi { padding:14px 16px;border-radius:10px;border:1px solid var(--u-line,#e5e9f0);background:var(--u-card,#fff);cursor:pointer;transition:all .15s;text-decoration:none;color:inherit;display:block; }
.ov-kpi:hover { border-color:#7c3aed;box-shadow:0 4px 14px rgba(124,58,237,.1);transform:translateY(-1px); }
.ov-kpi.active { border-color:#7c3aed;background:#faf5ff; }
.ov-kpi-label { font-size:10px;font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px; }
.ov-kpi-value { font-size:26px;font-weight:800;line-height:1; }
.ov-kpi-hint  { font-size:10px;color:var(--u-muted,#64748b);margin-top:4px; }
.ov-kpi.alert { border-color:#dc2626;background:#fef2f2; }
.ov-kpi.alert .ov-kpi-value { color:#dc2626; }
.ov-kpi.warn { border-color:#d97706;background:#fffbeb; }
.ov-kpi.warn .ov-kpi-value { color:#d97706; }

.ov-grid { display:grid;grid-template-columns:280px 1fr;gap:16px; }
.ov-side { background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e9f0);border-radius:10px;padding:12px 14px;height:fit-content; }
.ov-side h3 { font-size:11px;font-weight:800;color:var(--u-text,#0f172a);text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;padding-bottom:8px;border-bottom:1px solid var(--u-line,#e5e9f0); }
.ov-senior-row { display:flex;justify-content:space-between;align-items:center;padding:7px 8px;border-radius:6px;margin-bottom:3px;cursor:pointer;font-size:11px;text-decoration:none;color:var(--u-text,#0f172a); }
.ov-senior-row:hover { background:#f5f3ff; }
.ov-senior-row.active { background:#ede9fe;font-weight:700; }
.ov-senior-name { flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ov-senior-stats { display:flex;gap:4px;flex-shrink:0; }
.ov-stat-pill { font-size:9px;padding:2px 6px;border-radius:10px;font-weight:700; }
.ov-stat-pill.total { background:#e0e7ff;color:#3730a3; }
.ov-stat-pill.hot { background:#fef2f2;color:#dc2626; }
.ov-stat-pill.overdue { background:#fffbeb;color:#d97706; }

.pipe-board { display:flex;gap:8px;align-items:flex-start;width:100%; }
.pipe-col { flex:1;min-width:0;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e9f0);border-radius:10px;overflow:hidden;display:flex;flex-direction:column; }
.pipe-col-head { padding:8px 10px;border-bottom:1px solid var(--u-line,#e5e9f0);display:flex;justify-content:space-between;align-items:center; }
.pipe-col-title { font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase; }
.pipe-cnt { font-size:10px;font-weight:800;min-width:20px;height:20px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 6px;background:var(--u-bg,#f5f7fa); }
.pipe-cards { padding:6px;display:flex;flex-direction:column;gap:5px;min-height:60px; }
.pipe-card { background:var(--u-bg,#f9fafb);border:1px solid var(--u-line,#e5e9f0);border-radius:7px;padding:7px 9px;font-size:10px; }
.pipe-card .pc-name { font-weight:700;color:var(--u-text,#0f172a);margin-bottom:3px;line-height:1.3; }
.pipe-card .pc-meta { color:var(--u-muted,#64748b);font-size:9px;margin-bottom:3px; }
.pipe-card .pc-tier { display:inline-block;font-size:8px;padding:1px 5px;border-radius:8px;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.pipe-card .pc-tier.hot { background:#fef2f2;color:#dc2626; }
.pipe-card .pc-tier.warm { background:#fffbeb;color:#d97706; }
.pipe-card .pc-tier.cold { background:#eff6ff;color:#2563eb; }
.pipe-card .pc-tier.sales_ready { background:#f0fdf4;color:#16a34a; }
.pipe-card .pc-tier.champion { background:#faf5ff;color:#7c3aed; }
.pipe-card .pc-foot { display:flex;gap:3px;margin-top:5px; }
.pipe-card .pc-foot a { font-size:9px;padding:2px 6px;border-radius:5px;border:1px solid var(--u-line,#e5e9f0);text-decoration:none;color:var(--u-text,#0f172a);background:#fff; }
.pipe-card .pc-foot a:hover { border-color:#7c3aed;color:#7c3aed; }
.pipe-empty { text-align:center;padding:14px 6px;color:var(--u-muted,#64748b);font-size:10px;font-style:italic; }

@media (max-width:1100px) { .ov-grid { grid-template-columns:1fr; } .ov-kpi-grid { grid-template-columns:repeat(2,1fr); } }
</style>
@endpush

@section('content')

@php
$baseUrl = url('/manager/pipeline/oversight');
$buildUrl = function(array $params) use ($baseUrl, $filterSenior, $filterRisk) {
    $q = array_filter(array_merge(['senior'=>$filterSenior, 'risk'=>$filterRisk], $params), fn($v) => $v !== '' && $v !== null);
    return $baseUrl . (empty($q) ? '' : '?' . http_build_query($q));
};
$colColors  = ['new'=>'#6366f1','contacted'=>'#0891b2','docs_pending'=>'#d97706','in_progress'=>'#7c3aed','evaluating'=>'#9333ea','contract_signed'=>'#16a34a','converted'=>'#059669','lost'=>'#6b7280'];
@endphp

{{-- Bilgilendirme bandı --}}
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#1e40af;display:flex;gap:10px;align-items:center;">
    <span style="font-size:18px;">ℹ️</span>
    <div>
        <strong>Bu sayfa gözetim panelidir.</strong>
        Aşama değiştirme + drag-drop Senior portal'ında. Manager olarak buradan müdahale gereken vakaları görür ve gerektiğinde lead'i başka senior'a yeniden atayabilirsin.
    </div>
</div>

{{-- KPI Strip — tıklanabilir filter shortcut'ları --}}
<div class="ov-kpi-grid">
    <a href="{{ $buildUrl(['risk'=>'']) }}" class="ov-kpi {{ $filterRisk === '' ? 'active' : '' }}">
        <div class="ov-kpi-label">Aktif Lead</div>
        <div class="ov-kpi-value">{{ $kpis['active'] }}</div>
        <div class="ov-kpi-hint">Converted + lost hariç</div>
    </a>
    <a href="{{ $buildUrl(['risk'=>'unassigned']) }}" class="ov-kpi {{ $kpis['unassigned'] > 0 ? 'alert' : '' }} {{ $filterRisk === 'unassigned' ? 'active' : '' }}">
        <div class="ov-kpi-label">Atanmamış</div>
        <div class="ov-kpi-value">{{ $kpis['unassigned'] }}</div>
        <div class="ov-kpi-hint">Senior atanmasını bekliyor</div>
    </a>
    <a href="{{ $buildUrl(['risk'=>'hot_no_contact']) }}" class="ov-kpi {{ $kpis['hot_no_contact'] > 0 ? 'alert' : '' }} {{ $filterRisk === 'hot_no_contact' ? 'active' : '' }}">
        <div class="ov-kpi-label">Hot — Kontak yok</div>
        <div class="ov-kpi-value">{{ $kpis['hot_no_contact'] }}</div>
        <div class="ov-kpi-hint">Acil müdahale</div>
    </a>
    <a href="{{ $buildUrl(['risk'=>'overdue']) }}" class="ov-kpi {{ $kpis['overdue'] > 0 ? 'warn' : '' }} {{ $filterRisk === 'overdue' ? 'active' : '' }}">
        <div class="ov-kpi-label">Geciken (5+ gün)</div>
        <div class="ov-kpi-value">{{ $kpis['overdue'] }}</div>
        <div class="ov-kpi-hint">5+ gün hareketsiz</div>
    </a>
</div>

{{-- Filter bar --}}
<div class="ov-bar">
    <label>Senior:</label>
    <select onchange="window.location.href='{{ $buildUrl([]) }}'.replace(/[?&]senior=[^&]*/, '') + (this.value ? ((window.location.href.indexOf('?')>=0?'&':'?') + 'senior='+encodeURIComponent(this.value)) : '');">
        <option value="">Tümü</option>
        <option value="__unassigned__" @selected($filterSenior === '__unassigned__')>Atanmamış</option>
        @foreach($seniorList as $email)
            <option value="{{ $email }}" @selected($filterSenior === $email)>{{ $email }}</option>
        @endforeach
    </select>

    @if($filterSenior !== '' || $filterRisk !== '')
        <a href="{{ $baseUrl }}" class="btn-clear" style="text-decoration:none;color:#dc2626;font-weight:600;">✕ Filtreyi Temizle</a>
    @endif

    <div style="margin-left:auto;font-size:11px;color:var(--u-muted,#64748b);">
        Toplam: <strong>{{ collect($columns)->sum(fn($c) => $c['cards']->count()) }}</strong> lead
    </div>
</div>

{{-- 2 Kolon: Senior workload + Pipeline board --}}
<div class="ov-grid">

    {{-- Sol: Senior workload --}}
    <aside class="ov-side">
        <h3>Senior İş Yükü</h3>
        @if($seniorWorkload->isEmpty())
            <div style="font-size:11px;color:var(--u-muted,#64748b);">Henüz atanmış lead yok.</div>
        @else
            @foreach($seniorWorkload as $s)
                <a href="{{ $buildUrl(['senior'=>$s['email']]) }}" class="ov-senior-row {{ $filterSenior === $s['email'] ? 'active' : '' }}">
                    <span class="ov-senior-name" title="{{ $s['email'] }}">
                        {{ \Illuminate\Support\Str::before($s['email'], '@') }}
                    </span>
                    <span class="ov-senior-stats">
                        <span class="ov-stat-pill total">{{ $s['count'] }}</span>
                        @if($s['hot'] > 0)
                            <span class="ov-stat-pill hot" title="Hot lead'ler">🔥{{ $s['hot'] }}</span>
                        @endif
                        @if($s['overdue'] > 0)
                            <span class="ov-stat-pill overdue" title="5+ gün hareketsiz">⏰{{ $s['overdue'] }}</span>
                        @endif
                    </span>
                </a>
            @endforeach
        @endif

        <hr style="border:none;border-top:1px solid var(--u-line,#e5e9f0);margin:12px 0;">

        <div style="font-size:10px;color:var(--u-muted,#64748b);line-height:1.5;">
            <strong>Müdahale aksiyonları:</strong><br>
            Lead detay sayfasında <em>"Senior Ata"</em> ile yeniden atama yapabilirsin.
            Aşama değiştirmek senior'ın işidir.
        </div>
    </aside>

    {{-- Sağ: Pipeline board (read-only) --}}
    <div>
        <div class="pipe-board">
            @foreach($columns as $col)
            @php $cc = $colColors[$col['code']] ?? '#7c3aed'; @endphp
            <div class="pipe-col">
                <div class="pipe-col-head" style="border-top:3px solid {{ $cc }};">
                    <span class="pipe-col-title" style="color:{{ $cc }};">{{ $col['label'] }}</span>
                    <span class="pipe-cnt" style="background:{{ $cc }}22;color:{{ $cc }};">{{ $col['cards']->count() }}</span>
                </div>
                <div class="pipe-cards">
                    @forelse($col['cards']->take(20) as $card)
                        <div class="pipe-card">
                            <div class="pc-name">{{ trim($card->first_name . ' ' . $card->last_name) ?: '#' . $card->id }}</div>
                            <div class="pc-meta">
                                @if($card->assigned_senior_email)
                                    👤 {{ \Illuminate\Support\Str::before($card->assigned_senior_email, '@') }}
                                @else
                                    <span style="color:#dc2626;font-weight:700;">⚠ Atanmamış</span>
                                @endif
                            </div>
                            @if($card->lead_score_tier)
                                <span class="pc-tier {{ $card->lead_score_tier }}">{{ $card->lead_score_tier }}</span>
                            @endif
                            <div class="pc-foot">
                                <a href="/manager/guests/{{ $card->id }}">Detay →</a>
                            </div>
                        </div>
                    @empty
                        <div class="pipe-empty">—</div>
                    @endforelse
                    @if($col['cards']->count() > 20)
                        <div class="pipe-empty">+{{ $col['cards']->count() - 20 }} daha</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

@endsection
