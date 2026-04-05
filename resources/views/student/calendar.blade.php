@extends('student.layouts.app')

@section('title', 'Takvimim')
@section('page_title', 'Takvimim')

@push('head')
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
#cal-wrap { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:14px; padding:16px; }
.fc .fc-toolbar-title { font-size:16px; font-weight:700; }
.fc .fc-button { font-size:12px; padding:4px 10px; }
.cal-legend { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; font-size:12px; color:#6b7280; }
.cal-legend-dot { width:12px; height:12px; border-radius:50%; display:inline-block; margin-right:4px; vertical-align:middle; }
</style>
@endpush

@section('content')

<div class="cal-legend">
    <span><span class="cal-legend-dot" style="background:#22c55e;"></span>Onaylı Randevu</span>
    <span><span class="cal-legend-dot" style="background:#f59e0b;"></span>Bekleyen Randevu</span>
    <span><span class="cal-legend-dot" style="background:#ef4444;"></span>Son Tarih (Deadline)</span>
    <span><span class="cal-legend-dot" style="background:#6366f1;"></span>Yapılacak Görev</span>
</div>

<div id="cal-wrap" style="margin-top:12px;">
    <div id="calendar"></div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calEl = document.getElementById('calendar');
    const cal = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        locale: 'tr',
        height: 'auto',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        buttonText: {
            today: 'Bugün',
            month: 'Ay',
            list: 'Liste'
        },
        eventClick: function(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        loading: function(isLoading) {
            calEl.style.opacity = isLoading ? '0.6' : '1';
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const initial = @json($initialEvents ?? []);
            if (initial.length > 0) {
                successCallback(initial);
                return;
            }
            fetch('/student/calendar/events')
                .then(r => r.json())
                .then(data => successCallback(data))
                .catch(() => {
                    console.warn('Takvim etkinlikleri yüklenemedi.');
                    failureCallback();
                });
        }
    });
    cal.render();
});
</script>
@endpush
@endsection
