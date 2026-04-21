@extends('manager.layouts.app')

@section('title', 'Manager – Öğrenciler')
@section('page_title', 'Öğrenciler')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .mgr-kpi-strip { grid-template-columns:1fr; } }
.mgr-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-table { width:100%; border-collapse:collapse; font-size:12px; }
.mgr-table thead tr { background:var(--bg,#f8fafc); }
.mgr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:8px 10px; vertical-align:middle; }
.mgr-detail-btn { display:inline-block; padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; white-space:nowrap; }
.mgr-filter-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; }
</style>
@endpush

@section('content')

@include('partials.manager-hero', [
    'label' => 'Aktif Öğrenciler',
    'title' => 'Öğrenci Yönetimi',
    'sub'   => 'Dönüşen tüm öğrenciler, ödeme durumları ve risk seviyeleri. Proaktif müdahale için öncelikleri belirle.',
    'icon'  => '🎓',
    'bg'    => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1400&q=80',
    'tone'  => 'purple',
    'stats' => [
        ['icon' => '🟢', 'text' => ($kpis['active'] ?? 0) . ' aktif'],
        ['icon' => '📁', 'text' => ($kpis['archived'] ?? 0) . ' arşiv'],
        ['icon' => '⚠️', 'text' => ($kpis['high_risk'] ?? 0) . ' yüksek risk'],
    ],
])

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Aktif Öğrenci</div>
        <div class="mgr-kpi-val">{{ $kpis['active'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:var(--border,#e2e8f0);">
        <div class="mgr-kpi-label">Arşivlendi</div>
        <div class="mgr-kpi-val" style="color:var(--muted,#64748b);">{{ $kpis['archived'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['high_risk'] > 0 ? '#dc2626' : '#1e40af' }};">
        <div class="mgr-kpi-label">Yüksek Risk</div>
        <div class="mgr-kpi-val" style="{{ $kpis['high_risk'] > 0 ? 'color:#dc2626;' : '' }}">{{ $kpis['high_risk'] }}</div>
    </div>
</div>

{{-- Filtreler --}}
<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="/manager/students" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Ara</label>
            <input name="q" value="{{ $q }}" placeholder="Öğrenci ID / e-posta..." style="width:230px;" title="Öğrenci ID veya eğitim danışmanı e-posta ile ara">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Eğitim Danışmanı</label>
            <select name="senior">
                <option value="">– Tümü –</option>
                @foreach($seniorOptions as $e)
                    <option value="{{ $e }}" @selected($senior === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Şube</label>
            <select name="branch">
                <option value="">– Tümü –</option>
                @foreach($branchOptions as $b)
                    <option value="{{ $b }}" @selected($branch === $b)>{{ $b }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Risk</label>
            <select name="risk">
                <option value="">– Tümü –</option>
                <option value="high"   @selected($risk === 'high')>Yüksek</option>
                <option value="medium" @selected($risk === 'medium')>Orta</option>
                <option value="low"    @selected($risk === 'low')>Düşük</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Ödeme</label>
            <select name="payment">
                <option value="">– Tümü –</option>
                <option value="paid"    @selected($payment === 'paid')>Ödendi</option>
                <option value="partial" @selected($payment === 'partial')>Kısmi</option>
                <option value="pending" @selected($payment === 'pending')>Bekliyor</option>
                <option value="overdue" @selected($payment === 'overdue')>Gecikmiş</option>
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="mgr-filter-label">Arşiv</label>
            <select name="archived">
                <option value="0" @selected($arch === '0')>Aktif</option>
                <option value="1" @selected($arch === '1')>Arşivlendi</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" style="padding:6px 16px;background:#1e40af;color:#fff;border:none;border-radius:7px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Filtrele</button>
            <a href="/manager/students" style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">Temizle</a>
            <a href="/manager/students/export-csv?{{ http_build_query(['q'=>$q,'senior'=>$senior,'branch'=>$branch,'risk'=>$risk,'payment'=>$payment,'archived'=>$arch]) }}"
               style="padding:6px 12px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;background:var(--surface,#fff);">CSV ↓</a>
        </div>
    </form>
</section>

{{-- Tablo --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="mgr-table">
            <thead>
                <tr>
                    <th>Öğrenci ID</th>
                    <th>Eğitim Danışmanı</th>
                    <th>Şube</th>
                    <th>Risk</th>
                    <th>Ödeme</th>
                    <th>Tür</th>
                    <th>Güncellendi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $s)
                    @php
                        $riskCls = match($s->risk_level) {
                            'high'   => 'danger',
                            'medium' => 'warn',
                            'low'    => 'ok',
                            default  => 'pending',
                        };
                        $payCls = match($s->payment_status) {
                            'paid', 'ok' => 'ok',
                            'partial'    => 'warn',
                            'pending'    => 'info',
                            'overdue'    => 'danger',
                            default      => 'pending',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span style="font-weight:600;color:var(--text,#0f172a);">{{ $s->student_id }}</span>
                            @if($s->is_archived)
                                <span class="badge pending" style="margin-left:4px;font-size:var(--tx-xs);">Arşiv</span>
                            @endif
                        </td>
                        <td style="color:var(--muted,#64748b);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $s->senior_email ?: '–' }}</td>
                        <td style="color:var(--muted,#64748b);">{{ $s->branch ?: '–' }}</td>
                        <td>
                            @if($s->risk_level)
                                <span class="badge {{ $riskCls }}">{{ ucfirst($s->risk_level) }}</span>
                            @else
                                <span style="color:var(--muted,#64748b);">–</span>
                            @endif
                        </td>
                        <td>
                            @if($s->payment_status)
                                <span class="badge {{ $payCls }}">{{ ucfirst($s->payment_status) }}</span>
                            @else
                                <span style="color:var(--muted,#64748b);">–</span>
                            @endif
                        </td>
                        <td style="color:var(--muted,#64748b);">{{ $s->student_type ?: '–' }}</td>
                        <td style="color:var(--muted,#64748b);white-space:nowrap;">{{ optional($s->updated_at)->format('d.m.Y H:i') }}</td>
                        <td>
                            <a class="mgr-detail-btn" href="/manager/students/{{ urlencode($s->student_id) }}">Detay →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:28px;text-align:center;color:var(--muted,#64748b);">Kayıt bulunamadı.</td></tr>
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
