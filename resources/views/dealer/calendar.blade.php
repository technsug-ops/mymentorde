@extends('dealer.layouts.app')

@section('title', 'Takvimim')
@section('page_title', 'Takvimim')
@section('page_subtitle', 'Lead, dönüşüm ve ödeme etkinlikleri')

@push('head')
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<script defer src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<style>
/* Legend */
.cal-legend-bar {
    display:flex; gap:16px; flex-wrap:wrap;
    align-items:center; margin-bottom:16px;
    padding:12px 18px;
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:10px;
}
.cal-legend-item { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; }
.cal-legend-dot  { width:10px;height:10px;border-radius:50%;flex-shrink:0; }

/* Calendar wrapper */
.cal-card {
    background:var(--surface,#fff);
    border:1px solid var(--border,#e2e8f0);
    border-radius:12px;
    overflow:hidden;
    padding:20px;
}

/* FullCalendar overrides */
#dealer-calendar .fc-toolbar { margin-bottom:16px; }
#dealer-calendar .fc-toolbar-title { font-size:16px; font-weight:700; color:var(--text,#0f172a); }
#dealer-calendar .fc-button {
    background:var(--surface,#fff) !important;
    border:1px solid var(--border,#e2e8f0) !important;
    color:var(--text,#0f172a) !important;
    font-size:12px !important;
    font-weight:600 !important;
    border-radius:6px !important;
    box-shadow:none !important;
    transition:all .15s;
}
#dealer-calendar .fc-button:hover {
    border-color:#16a34a !important;
    color:#16a34a !important;
}
#dealer-calendar .fc-button-active,
#dealer-calendar .fc-button-primary:not(:disabled):active {
    background:#16a34a !important;
    border-color:#16a34a !important;
    color:#fff !important;
}
#dealer-calendar .fc-day-today { background:rgba(22,163,74,.06) !important; }
#dealer-calendar .fc-event { font-size:11px;border-radius:4px;cursor:pointer; }
#dealer-calendar .fc-col-header-cell { font-size:12px;font-weight:700;color:var(--muted,#64748b); }
#dealer-calendar .fc-daygrid-day-number { font-size:12px;font-weight:600; }
</style>
@endpush

@section('content')

{{-- Legend --}}
<div class="cal-legend-bar">
    <span style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-right:4px;">Renk Kodu:</span>
    <div class="cal-legend-item">
        <span class="cal-legend-dot" style="background:#3b82f6;"></span>
        Lead Oluşturma
    </div>
    <div class="cal-legend-item">
        <span class="cal-legend-dot" style="background:#22c55e;"></span>
        Öğrenci Dönüşümü
    </div>
    <div class="cal-legend-item">
        <span class="cal-legend-dot" style="background:#f97316;"></span>
        Ödeme
    </div>
</div>

{{-- Calendar --}}
<div class="cal-card">
    <div id="dealer-calendar"></div>
</div>

<div style="background:var(--bg,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px 20px;margin-top:16px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:8px;">💡 Takvim Hakkında</div>
    <ul style="margin:0;padding-left:18px;">
        <li style="font-size:var(--tx-sm);color:var(--muted,#64748b);margin-bottom:5px;">Mavi: o gün oluşturulan lead. Yeşil: öğrenciye dönüşüm tarihi. Turuncu: ödeme gerçekleşme tarihi.</li>
        <li style="font-size:var(--tx-sm);color:var(--muted,#64748b);">Etkinliklere tıklayarak ilgili lead detayına gidebilirsiniz.</li>
    </ul>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('dealer-calendar');
    if (!el || typeof FullCalendar === 'undefined') return;

    const cal = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        locale: 'tr',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        events: function (fetchInfo, successCallback, failureCallback) {
            const start = fetchInfo.startStr.substring(0, 10);
            const end   = fetchInfo.endStr.substring(0, 10);
            fetch('/dealer/calendar/events?start=' + start + '&end=' + end, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => successCallback(data.events || []))
            .catch(failureCallback);
        },
        eventClick: function (info) {
            if (info.event.extendedProps.url) {
                window.location.href = info.event.extendedProps.url;
            }
        },
    });
    cal.render();
});
</script>
@endpush
