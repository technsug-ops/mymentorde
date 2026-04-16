@extends('manager.layouts.app')
@section('title', 'Denetim Kayıtları (Audit Log)')
@section('page_title', 'Denetim Kayıtları')

@push('head')
<style>
/* ─── Audit Log Polish ─── */
.al-hero { background:linear-gradient(135deg,#eef4ff 0%,#f8faff 100%); border:1px solid #dbe4f2; border-radius:12px; padding:14px 18px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.al-hero-info .title { font-size:14px; font-weight:700; color:#0f172a; margin:0 0 3px; }
.al-hero-info .sub { font-size:11px; color:var(--u-muted,#64748b); margin:0; }
.al-hero-badge { font-size:10px; font-weight:700; color:#1d4ed8; background:#dbeafe; border:1px solid #bfdbfe; padding:4px 10px; border-radius:999px; letter-spacing:.3px; text-transform:uppercase; }

.al-filter { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; padding:12px 14px; margin-bottom:12px; }
.al-filter-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.al-filter-title { font-size:11px; font-weight:700; color:var(--u-brand,#1e40af); text-transform:uppercase; letter-spacing:.3px; }
.al-filter-sub { font-size:11px; color:var(--u-muted,#64748b); }
.al-filter-form { display:grid; grid-template-columns:2fr 1fr 1fr auto auto; gap:8px; align-items:stretch; }
.al-filter-form input, .al-filter-form select { font-size:12px; padding:8px 12px; min-height:36px; border:1px solid var(--u-line,#e5e9f0); border-radius:6px; background:#fff; color:var(--u-text,#0f172a); outline:none; }
.al-filter-form input:focus, .al-filter-form select:focus { border-color:#1e40af; box-shadow:0 0 0 2px rgba(30,64,175,.12); }
.al-filter-form .btn { font-size:12px !important; padding:8px 16px !important; min-height:36px !important; }
@media(max-width:900px){ .al-filter-form { grid-template-columns:1fr 1fr; } }

.al-table-wrap { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e9f0); border-radius:10px; overflow:hidden; }
.al-table { width:100%; border-collapse:collapse; font-size:12px; }
.al-table thead th { padding:10px 14px; text-align:left; font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.3px; background:var(--u-bg,#f5f7fa); border-bottom:1px solid var(--u-line,#e5e9f0); white-space:nowrap; }
.al-table tbody td { padding:10px 14px; border-bottom:1px solid var(--u-line,#e5e9f0); vertical-align:top; }
.al-table tbody tr:last-child td { border-bottom:none; }
.al-table tbody tr:hover { background:#f8fafc; }
.al-table .gdpr-row { background:#fffbeb; }
.al-table .gdpr-row:hover { background:#fef3c7; }
.al-table .date-col { color:var(--u-muted,#64748b); font-size:11px; white-space:nowrap; font-variant-numeric:tabular-nums; }
.al-table .actor-col { font-size:11px; color:var(--u-text,#0f172a); }
.al-table .entity-col { font-size:11px; color:var(--u-muted,#64748b); white-space:nowrap; }
.al-table .entity-col code { background:#f1f5f9; padding:1px 6px; border-radius:4px; font-family:monospace; color:#1e40af; font-size:10px; }
.al-table .msg-col { color:var(--u-text,#0f172a); line-height:1.5; }

.al-event-badge { display:inline-block; padding:3px 9px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; white-space:nowrap; }
.al-event-badge.gdpr     { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.al-event-badge.auth     { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.al-event-badge.system   { background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe; }
.al-event-badge.default  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

.al-empty { padding:40px 20px; text-align:center; color:var(--u-muted,#64748b); font-size:13px; }
.al-empty::before { content:'📋'; display:block; font-size:32px; margin-bottom:8px; }
.al-pagination { margin-top:12px; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="al-hero">
    <div class="al-hero-info">
        <h1 class="title">🔍 Denetim Kayıtları</h1>
        <p class="sub">Sistem ve kişisel veri olaylarının kronolojik kaydı — erişim, değişiklik ve admin aksiyonları</p>
    </div>
    <span class="al-hero-badge">GDPR Madde 30</span>
</div>

{{-- Filter --}}
<section class="al-filter">
    <div class="al-filter-head">
        <span class="al-filter-title">🔎 Filtrele</span>
        <span class="al-filter-sub">Mesaj, olay tipi veya aktör ile ara</span>
    </div>
    <form method="GET" class="al-filter-form">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Mesaj veya entity ID ara...">
        <select name="event_type">
            <option value="">Tüm Olay Tipleri</option>
            @foreach($eventTypes as $et)
                <option value="{{ $et }}" {{ $et === $filters['eventType'] ? 'selected' : '' }}>{{ $et }}</option>
            @endforeach
        </select>
        <input type="text" name="actor" value="{{ $filters['actor'] }}" placeholder="Aktör e-posta...">
        <button class="btn btn-primary" type="submit">Filtrele</button>
        <a href="/manager/audit-log" class="btn alt">Temizle</a>
    </form>
</section>

{{-- Table --}}
<div class="al-table-wrap">
    @if($logs->isEmpty())
        <div class="al-empty">Filtrelere uygun kayıt bulunamadı.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="al-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Olay Tipi</th>
                        <th>Aktör</th>
                        <th>Entity</th>
                        <th>Mesaj</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $isGdpr = str_starts_with($log->event_type, 'gdpr');
                        $badgeClass = $isGdpr ? 'gdpr'
                            : (str_starts_with($log->event_type, 'auth') ? 'auth'
                            : (str_starts_with($log->event_type, 'system') ? 'system' : 'default'));
                    @endphp
                    <tr class="{{ $isGdpr ? 'gdpr-row' : '' }}">
                        <td class="date-col">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <span class="al-event-badge {{ $badgeClass }}">{{ $log->event_type }}</span>
                        </td>
                        <td class="actor-col">{{ $log->actor_email ?: '—' }}</td>
                        <td class="entity-col">
                            @if($log->entity_type)<code>{{ $log->entity_type }}</code>@endif
                            @if($log->entity_id) #{{ $log->entity_id }}@endif
                            @if(!$log->entity_type && !$log->entity_id)—@endif
                        </td>
                        <td class="msg-col">{{ $log->message }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="al-pagination">
    {{ $logs->withQueryString()->links('partials.pagination') }}
</div>

@endsection
