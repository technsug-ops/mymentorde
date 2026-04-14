@extends('manager.layouts.app')

@section('title', 'Manager – Aday Öğrenci Yönetimi')
@section('page_title', 'Aday Öğrenci Yönetimi')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .mgr-kpi-strip { grid-template-columns:1fr 1fr; } }
.mgr-kpi {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-top:3px solid #1e40af;
    border-radius:10px;
    padding:12px 14px;
}
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }

/* tablo */
.mgr-table { width:100%; border-collapse:collapse; font-size:12px; }
.mgr-table thead tr { background:var(--bg,#f8fafc); }
.mgr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:8px 10px; vertical-align:middle; }
</style>
@endpush

@section('content')

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam Aday Öğrenci</div>
        <div class="mgr-kpi-val">{{ $kpis['total'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Dönüşen</div>
        <div class="mgr-kpi-val" style="color:#15803d;">{{ $kpis['converted'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Atanmamış</div>
        <div class="mgr-kpi-val" style="{{ $kpis['unassigned'] > 0 ? 'color:#b45309;' : '' }}">{{ $kpis['unassigned'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Bugün Gelen</div>
        <div class="mgr-kpi-val">{{ $kpis['today'] }}</div>
    </div>
</div>

{{-- Filtreler --}}
<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="/manager/guests" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Ara</label>
            <input name="q" value="{{ $q }}" placeholder="İsim, e-posta, telefon, token…" style="width:220px;">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Durum</label>
            <select name="status">
                <option value="">– Tümü –</option>
                @foreach($statusOptions as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Eğitim Danışmanı</label>
            <select name="senior">
                <option value="">– Tümü –</option>
                @foreach($seniorOptions as $e)
                    <option value="{{ $e }}" @selected($senior === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Dealer</label>
            <input name="dealer" value="{{ $dealer }}" placeholder="Dealer kodu…" style="width:120px;">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Dönüşüm</label>
            <select name="converted">
                <option value="">– Tümü –</option>
                <option value="1" @selected($converted === '1')>Dönüştü</option>
                <option value="0" @selected($converted === '0')>Dönüşmedi</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Filtrele</button>
            <a href="/manager/guests" style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Temizle</a>
            <a href="/manager/guests/export-csv?{{ http_build_query(['q'=>$q,'status'=>$status,'senior'=>$senior,'dealer'=>$dealer,'converted'=>$converted]) }}"
               style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);margin-left:4px;">CSV ↓</a>
        </div>
    </form>
</section>

{{-- Tablo --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="mgr-table">
            <thead>
                <tr>
                    <th>ID / Token</th>
                    <th>Ad Soyad</th>
                    <th>E-posta / Telefon</th>
                    <th>Tür</th>
                    <th>Durum</th>
                    <th>Eğitim Danışmanı</th>
                    <th>Dealer</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $g)
                    @php
                        $badgeCls = match($g->lead_status) {
                            'new'       => 'info',
                            'contacted' => 'warn',
                            'qualified' => 'pending',
                            'converted' => 'ok',
                            'lost'      => 'danger',
                            default     => 'pending',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                                <span style="font-weight:600;color:var(--text,#0f172a);">#{{ $g->id }}</span>
                                @if($g->converted_to_student && $g->converted_student_id)
                                    <a class="badge ok" href="/manager/students?q={{ urlencode($g->converted_student_id) }}">{{ $g->converted_student_id }}</a>
                                @endif
                            </div>
                            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $g->tracking_token }}">{{ $g->tracking_token }}</div>
                        </td>
                        <td style="font-weight:500;color:var(--text,#0f172a);">{{ $g->first_name }} {{ $g->last_name }}</td>
                        <td>
                            <div>{{ $g->email }}</div>
                            <div style="color:var(--muted,#64748b);">{{ $g->phone }}</div>
                        </td>
                        <td style="color:var(--muted,#64748b);">{{ $g->application_type ?: '–' }}</td>
                        <td><span class="badge {{ $badgeCls }}">{{ $g->lead_status ?: '–' }}</span></td>
                        <td style="color:var(--muted,#64748b);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $g->assigned_senior_email ?: '–' }}</td>
                        <td style="color:var(--muted,#64748b);">{{ $g->dealer_code ?: '–' }}</td>
                        <td style="color:var(--muted,#64748b);white-space:nowrap;">{{ optional($g->created_at)->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="/manager/guests/{{ $g->id }}"
                               style="display:inline-block;padding:4px 10px;font-size:var(--tx-xs);font-weight:600;color:#1e40af;border:1px solid rgba(30,64,175,.3);border-radius:6px;background:rgba(30,64,175,.05);text-decoration:none;white-space:nowrap;">
                                Detay →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:28px;text-align:center;color:var(--muted,#64748b);">Kayıt bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border,#e2e8f0);">
        {{ $rows->withQueryString()->links('partials.pagination') }}
    </div>
    @endif
</section>

@endsection
