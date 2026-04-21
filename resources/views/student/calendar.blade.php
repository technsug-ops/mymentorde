@extends('student.layouts.app')

@section('title', 'Takvimim')
@section('page_title', 'Takvimim')

@push('head')
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
/* ══════ Hero (Option B) ══════ */
.cal-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#312e81 url('https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=1400&q=80') center/cover; }
.cal-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(49,46,129,.92) 0%, rgba(99,102,241,.85) 100%); }
.cal-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.cal-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.cal-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.cal-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.cal-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.cal-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.cal-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.cal-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.cal-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .cal-hero-body { gap:14px; padding:18px; align-items:flex-start; } .cal-hero-title { font-size:20px; } .cal-hero-sub { font-size:12px; } .cal-hero-icon { font-size:36px; } }

#cal-wrap { background:var(--u-card,#fff); border:1px solid var(--u-line,#e5e7eb); border-radius:14px; padding:16px; }
.fc .fc-toolbar-title { font-size:16px; font-weight:700; }
.fc .fc-button { font-size:12px; padding:4px 10px; }
.cal-legend { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; font-size:12px; color:#6b7280; }
.cal-legend-dot { width:12px; height:12px; border-radius:50%; display:inline-block; margin-right:4px; vertical-align:middle; }
</style>
@endpush

@section('content')
@php
    $evCount = is_array($initialEvents ?? null) ? count($initialEvents) : 0;
@endphp

{{-- ══════ Hero ══════ --}}
<div class="cal-hero">
    <div class="cal-hero-body">
        <div class="cal-hero-main">
            <div class="cal-hero-label"><span class="cal-hero-marker"></span>Planlama Merkezi</div>
            <h1 class="cal-hero-title">Takvimim</h1>
            <div class="cal-hero-sub">Randevular, deadline'lar ve yapılacak görevler tek bir yerde. Bu ay ve önümüzdeki haftaları planla.</div>
            <div class="cal-hero-stats">
                <span class="cal-hero-stat">📅 {{ $evCount }} yaklaşan etkinlik</span>
                <span class="cal-hero-stat">🟢 Randevu · ⏰ Deadline · 📝 Görev</span>
            </div>
        </div>
        <div class="cal-hero-icon">📅</div>
    </div>
</div>

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
