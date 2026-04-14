@extends('student.layouts.app')

@section('title', 'Öğrenci - ' . ($title ?? 'Portal'))
@section('page_title', $title ?? 'Öğrenci Paneli')

@push('head')
<style>
    .guide { border:1px dashed #8eb3e3; background:#e8f1ff; }
</style>
@endpush

@section('content')
    <section class="panel">
        <div class="muted">{{ $description ?? 'Öğrenci modülü' }}</div>
    </section>

    <section class="grid4">
        <div class="panel"><div class="muted">Öğrenci ID</div><div class="kpi" style="font-size:var(--tx-xl);">{{ $studentId ?: '-' }}</div></div>
        <div class="panel"><div class="muted">Atanan Eğitim Danışmanı</div><div class="kpi" style="font-size:var(--tx-xl);">{{ $assignment?->senior_email ?: '-' }}</div></div>
        <div class="panel"><div class="muted">Belge Durumu</div><div class="kpi">{{ (int)($docSummary['required_done'] ?? 0) }}/{{ (int)($docSummary['required_total'] ?? 0) }}</div></div>
        <div class="panel"><div class="muted">Bildirim</div><div class="kpi">{{ (int)($notificationCount ?? 0) }}</div></div>
    </section>

    <section class="panel guide">
        <strong>Nasıl Çalışır?</strong>
        <ul style="margin:8px 0 0 18px; padding:0;">
            <li>Bu ekran Öğrenci modülü için ortak fallback sayfadır.</li>
            <li>Aktif menülerde özel sayfa varsa doğrudan o görünür.</li>
            <li>Özel sayfa yoksa bu ekranda temel KPI ve yönlendirme görünür.</li>
        </ul>
    </section>
@endsection

