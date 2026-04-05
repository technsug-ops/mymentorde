@extends('student.layouts.app')

@section('title', 'Student Dashboard')
@section('page_title', 'Student Dashboard')

@push('head')
<style>
/* ── Welcome Hero ── */
.sd-hero {
    background: linear-gradient(to right, #4c1d95 0%, #7c3aed 60%, #8b5cf6 100%);
    border-radius: 0 0 16px 16px;
    padding: 20px 24px 16px;
    position: relative;
    overflow: hidden;
    margin: -20px -20px 20px 0;
}
.sd-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.sd-hero::after {
    content: '';
    position: absolute;
    bottom: -70px; left: 38%;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.sd-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}
.sd-hero-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
    border: 2px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 18px;
    flex-shrink: 0;
}
.sd-hero-text { flex: 1; min-width: 0; }
.sd-hero-greeting {
    font-size: 12px; font-weight: 600;
    color: rgba(255,255,255,.7);
    text-transform: uppercase; letter-spacing: .8px;
    margin-bottom: 2px;
}
.sd-hero-name {
    font-size: 20px; font-weight: 700;
    color: #fff; line-height: 1.2;
    margin-bottom: 6px;
}
.sd-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.sd-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 3px 10px; font-size: 11px;
    color: #fff; font-weight: 600;
}
.sd-hero-badge.new {
    background: rgba(52,211,153,.25);
    border-color: rgba(52,211,153,.5);
}
.sd-hero-actions {
    display: flex; gap: 8px; flex-wrap: wrap;
    position: relative; z-index: 1;
    margin-bottom: 12px;
}
.sd-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .15s;
}
.sd-hero-btn.primary {
    background: #fff; color: #1d4ed8;
}
.sd-hero-btn.primary:hover { background: #eff6ff; }
.sd-hero-btn.ghost {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
}
.sd-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* ── Step Progress in Hero ── */
.sd-steps {
    display: flex;
    align-items: flex-start;
    position: relative;
    z-index: 1;
}
.sd-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}
.sd-step + .sd-step::before {
    content: '';
    position: absolute;
    top: 14px;
    right: 50%;
    left: -50%;
    height: 2px;
    background: rgba(255,255,255,.2);
}
.sd-step.done + .sd-step.done::before,
.sd-step.done + .sd-step::before {
    background: rgba(255,255,255,.55);
}
.sd-step-dot {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(255,255,255,.35);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.7);
    font-size: 12px; font-weight: 700;
    position: relative; z-index: 1;
    margin-bottom: 6px;
    transition: all .2s;
}
.sd-step.done .sd-step-dot {
    background: rgba(52,211,153,.3);
    border-color: rgba(52,211,153,.7);
    color: #fff;
}
.sd-step-label {
    font-size: 12px; font-weight: 700;
    color: rgba(255,255,255,.85);
    text-align: center; line-height: 1.3;
    max-width: 90px;
    text-shadow: 0 1px 3px rgba(0,0,0,.25);
    background: transparent !important;
}
.sd-step.done .sd-step-label { color: #fff; }
.sd-step-status {
    font-size: 11px; color: rgba(255,255,255,.65);
    margin-top: 2px; font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
    background: transparent !important;
}
.sd-step.done .sd-step-status { color: rgba(134,239,172,1); }

/* ── KPI Cards ── */
.sd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .sd-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
.sd-kpi-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 12px;
    padding: 16px;
}
.sd-kpi-label {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px;
    color: #6b7280; margin-bottom: 8px;
}
.sd-kpi-value {
    font-size: 20px; font-weight: 700;
    color: #111827; line-height: 1;
    margin-bottom: 4px;
}
.sd-kpi-sub { font-size: 12px; color: #6b7280; }

/* ── Content Grid ── */
.sd-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 800px) { .sd-content-grid { grid-template-columns: 1fr; } }

/* ── Section Card ── */
.sd-card {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 20px;
}
.sd-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}
.sd-card-title {
    font-size: 14px; font-weight: 700;
    color: #111827; margin: 0;
}
.sd-card-link {
    font-size: 12px; font-weight: 600;
    color: #2563eb; text-decoration: none;
}
.sd-card-link:hover { text-decoration: underline; }

/* ── Checklist ── */
.sd-checklist { display: flex; flex-direction: column; gap: 6px; }
.sd-check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    background: var(--u-bg);
    border: 1px solid var(--u-line);
}
.sd-check-dot {
    width: 20px; height: 20px; flex-shrink: 0;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
}
.sd-check-dot.done { background: #dcfce7; color: #16a34a; }
.sd-check-dot.miss { background: #fef3c7; color: #d97706; }
.sd-check-name { font-size: 13px; color: #374151; flex: 1; }

/* ── Notification Items ── */
.sd-notif-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--u-line);
}
.sd-notif-item:last-child { border-bottom: none; }
.sd-notif-title { font-size: 13px; font-weight: 600; color: #111827; }
.sd-notif-ch { font-size: 11px; color: #6b7280; margin-top: 2px; }
.sd-notif-right { text-align: right; flex-shrink: 0; }
.sd-notif-date { font-size: 11px; color: #9ca3af; margin-top: 3px; }

/* ── Outcome Timeline ── */
.sd-timeline { display: flex; flex-direction: column; gap: 0; }
.sd-tl-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--u-line);
}
.sd-tl-item:last-child { border-bottom: none; }
.sd-tl-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #2563eb;
    flex-shrink: 0;
    margin-top: 5px;
}
.sd-tl-step { font-size: 13px; font-weight: 700; color: #111827; }
.sd-tl-detail { font-size: 12px; color: #6b7280; margin-top: 2px; line-height: 1.4; }
.sd-tl-date { font-size: 11px; color: #9ca3af; margin-top: 3px; }

/* ── Onboarding Tasks ── */
.sd-task-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    background: var(--u-bg);
    border: 1px solid var(--u-line);
    margin-bottom: 6px;
}
.sd-task-title { font-size: 13px; font-weight: 600; color: #111827; }
.sd-task-due { font-size: 11px; color: #6b7280; margin-top: 2px; }

/* Welcome banner */
.sd-welcome-banner {
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
}
.sd-welcome-icon { font-size: 32px; flex-shrink: 0; }
.sd-welcome-title { font-size: 15px; font-weight: 700; color: #166534; margin: 0 0 4px; }
.sd-welcome-text { font-size: 13px; color: #374151; line-height: 1.6; margin: 0; }
/* hide default top bar on dashboard */
.top { display: none !important; }

/* ── Hızlı Erişim ── */
.sd-quick-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .sd-quick-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px) { .sd-quick-grid { grid-template-columns: repeat(2, 1fr); } }
.sd-quick-link {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 8px; padding: 14px 8px 12px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; text-decoration: none;
    color: #374151; font-size: 12px; font-weight: 600;
    text-align: center; line-height: 1.3;
    transition: background .15s, border-color .15s, transform .12s;
}
.sd-quick-link:hover {
    background: #eef3fb; border-color: #93c5fd;
    color: #1d4ed8; transform: translateY(-2px);
    text-decoration: none;
}
.sd-quick-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}

/* ── Belge Accordion ── */
.sd-acc { display:flex; flex-direction:column; gap:6px; }
.sd-acc-group { border:1px solid var(--u-line); border-radius:10px; overflow:hidden; }
.sd-acc-group summary {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 14px; cursor:pointer; user-select:none;
    background:#f8fafd; font-size:13px; font-weight:700; color:#1e3a5f;
    list-style:none; gap:8px;
}
.sd-acc-group summary::-webkit-details-marker { display:none; }
.sd-acc-group summary:hover { background:#eef3fb; }
.sd-acc-group[open] summary { background:#eef3fb; border-bottom:1px solid var(--u-line); }
.sd-acc-group-left { display:flex; align-items:center; gap:8px; flex:1; min-width:0; }
.sd-acc-group-icon { font-size:15px; }
.sd-acc-group-title { font-size:13px; font-weight:700; color:#1e3a5f; }
.sd-acc-group-count { font-size:11px; color:#6b7280; font-weight:400; margin-left:4px; }
.sd-acc-group-caret { font-size:10px; color:#9ca3af; transition:transform .2s; }
.sd-acc-group[open] .sd-acc-group-caret { transform:rotate(180deg); }
.sd-acc-group-right { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.sd-acc-body { padding:8px 10px; display:flex; flex-direction:column; gap:4px; }
.sd-acc-item {
    display:flex; align-items:center; gap:10px;
    padding:7px 8px; border-radius:7px;
    background:var(--u-bg);
}
.sd-acc-item-dot {
    width:18px; height:18px; flex-shrink:0; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:700;
}
.sd-acc-item-dot.done { background:#dcfce7; color:#16a34a; }
.sd-acc-item-dot.miss { background:#fef3c7; color:#d97706; }
.sd-acc-item-name { font-size:12.5px; color:#374151; flex:1; min-width:0; line-height:1.3; }
.sd-acc-item-badge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:999px; flex-shrink:0; }
.sd-acc-item-badge.ok { background:#dcfce7; color:#16a34a; }
.sd-acc-item-badge.miss { background:#fef3c7; color:#b45309; }

/* ── Hizmet Kartları (gd-svc-*) ── */
.gd-svc-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:1100px){ .gd-svc-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:680px){  .gd-svc-grid { grid-template-columns:repeat(2,1fr); } }
.gd-svc-card {
    border:1.5px solid #e5e7eb; border-radius:14px;
    display:flex; flex-direction:column;
    text-decoration:none; position:relative; overflow:hidden;
    background:var(--u-card,#fff);
    transition:transform .18s, box-shadow .18s;
}
.gd-svc-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.12); text-decoration:none; }
.gd-svc-accent { height:4px; width:100%; }
.gd-svc-body   { display:flex; flex-direction:column; gap:8px; padding:18px 16px 16px; flex:1; }
.gd-svc-icon   { width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:4px; }
.gd-svc-title  { font-size:var(--tx-sm); font-weight:800; margin-bottom:2px; }
.gd-svc-desc   { font-size:var(--tx-xs); color:var(--u-muted,#6b7280); line-height:1.5; flex:1; }
.gd-svc-link   { font-size:var(--tx-xs); font-weight:700; margin-top:8px; display:inline-flex; align-items:center; gap:4px; }
.gd-svc-link::after { content:'→'; transition:transform .15s; }
.gd-svc-card:hover .gd-svc-link::after { transform:translateX(3px); }
.gd-svc-card.blue   { border-color:#bfdbfe; }
.gd-svc-card.blue   .gd-svc-accent { background:linear-gradient(90deg,#2563eb,#0891b2); }
.gd-svc-card.blue   .gd-svc-icon   { background:#dbeafe; }
.gd-svc-card.blue   .gd-svc-title  { color:#1d4ed8; }
.gd-svc-card.blue   .gd-svc-link   { color:#2563eb; }
.gd-svc-card.purple { border-color:#ddd6fe; }
.gd-svc-card.purple .gd-svc-accent { background:linear-gradient(90deg,#7c3aed,#a855f7); }
.gd-svc-card.purple .gd-svc-icon   { background:#ede9fe; }
.gd-svc-card.purple .gd-svc-title  { color:#6d28d9; }
.gd-svc-card.purple .gd-svc-link   { color:#7c3aed; }
.gd-svc-card.green  { border-color:#bbf7d0; }
.gd-svc-card.green  .gd-svc-accent { background:linear-gradient(90deg,#059669,#0891b2); }
.gd-svc-card.green  .gd-svc-icon   { background:#dcfce7; }
.gd-svc-card.green  .gd-svc-title  { color:#15803d; }
.gd-svc-card.green  .gd-svc-link   { color:#16a34a; }
.gd-svc-card.orange { border-color:#fed7aa; }
.gd-svc-card.orange .gd-svc-accent { background:linear-gradient(90deg,#d97706,#dc2626); }
.gd-svc-card.orange .gd-svc-icon   { background:#fef3c7; }
.gd-svc-card.orange .gd-svc-title  { color:#b45309; }
.gd-svc-card.orange .gd-svc-link   { color:#d97706; }
.gd-svc-card.star { border-color:#fbbf24; box-shadow:0 0 0 1px #fbbf24, 0 4px 20px rgba(251,191,36,.30); position:relative; }
.gd-svc-card.star:hover { transform:translateY(-6px); box-shadow:0 0 0 2px #f59e0b, 0 14px 36px rgba(251,191,36,.40); }
.gd-svc-card.star .gd-svc-accent { background:linear-gradient(90deg,#f59e0b,#e11d48,#f59e0b); background-size:200% 100%; animation:shimmer 2.4s linear infinite; }
@keyframes shimmer { 0%{background-position:100% 0} 100%{background-position:-100% 0} }
.gd-svc-card.star .gd-svc-icon  { background:#fef9c3; }
.gd-svc-card.star .gd-svc-title { color:#92400e; }
.gd-svc-card.star .gd-svc-link  { color:#d97706; }
.gd-svc-star-badge { position:absolute; top:10px; right:10px; background:linear-gradient(135deg,#f59e0b,#e11d48); color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:20px; letter-spacing:.04em; line-height:1.6; box-shadow:0 2px 6px rgba(245,158,11,.4); }
</style>
@endpush

@section('content')
@php
    $approvedAt   = $guestApplication?->contract_approved_at;
    $isNewStudent = $approvedAt && \Carbon\Carbon::parse($approvedAt)->diffInDays(now()) <= 30;
    $fullName     = trim(($guestApplication?->first_name ?? '').' '.($guestApplication?->last_name ?? ''));
    $displayName  = $fullName ?: ($guestApplication?->email ?? 'Öğrenci');
    $initials     = strtoupper(substr($displayName, 0, 2));
    $seniorDisplay = $assignment?->senior_email
        ? (str_contains($assignment->senior_email, '@')
            ? explode('@', $assignment->senior_email)[0]
            : $assignment->senior_email)
        : null;
    $docsApproved = (int)($docSummary['approved'] ?? 0);
    $docsTotal    = (int)($docSummary['total'] ?? 0);
    $docsRejected = (int)($docSummary['rejected'] ?? 0);
    $dmUnread     = (int)($dmUnread ?? 0);
@endphp

@if(!$studentId)
    <section class="panel">
        <strong>Hesabın student kaydı ile eşleşmemiş.</strong>
        <div class="muted">Manager tarafından bu kullanıcıya student_id atanınca portal verileri görünür.</div>
    </section>
@else

    {{-- Yeni öğrenci banner --}}
    @if($isNewStudent)
    <div class="sd-welcome-banner">
        <div class="sd-welcome-icon">🎓</div>
        <div>
            <div class="sd-welcome-title">Danışmanlık ailesine hoş geldiniz!</div>
            <p class="sd-welcome-text">
                Sözleşmeniz onaylandı ve danışmanlık süreciniz başladı.
                Sol menüden belgelerinizi takip edebilir, danışmanınıza mesaj yazabilir ve süreç takibinizi görebilirsiniz.
                <strong>Başarılar dileriz!</strong>
            </p>
        </div>
    </div>
    @endif

    {{-- ── Welcome Hero ── --}}
    <div class="sd-hero">
        <div class="sd-hero-top">
            <div class="sd-hero-text">
                <div class="sd-hero-greeting">{{ $greeting ?? 'Hoş geldiniz' }}</div>
                <div class="sd-hero-name">{{ $displayName }}</div>
                @if($seniorDisplay || $dmUnread > 0)
                <div class="sd-hero-badges">
                    @if($seniorDisplay)
                        <span class="sd-hero-badge">Danışman: {{ $seniorDisplay }}</span>
                    @endif
                    @if($dmUnread > 0)
                        <span class="sd-hero-badge new">{{ $dmUnread }} okunmamış mesaj</span>
                    @endif
                </div>
                @endif
            </div>
            <span class="sd-hero-badge" style="flex-shrink:0;">{{ $studentId }}</span>
        </div>

        <div class="sd-hero-actions">
            <a class="sd-hero-btn primary" href="/student/messages">✉ Danışmana Mesaj</a>
            <a class="sd-hero-btn ghost" href="/student/registration/documents">Belgeler</a>
            <a class="sd-hero-btn ghost" href="/student/process-tracking">Süreç Takibi</a>
            <a class="sd-hero-btn ghost" href="/student/tickets">Destek Ticket</a>
            <a class="sd-hero-btn ghost" href="/logout" style="border-color:rgba(255,255,255,.7);color:#fff;">Çıkış</a>
        </div>

        {{-- Banner carousel --}}
        @if(!empty($banners) && $banners->isNotEmpty())
        <div style="display:flex;gap:12px;overflow-x:auto;padding:0 0 8px;margin-bottom:16px;scrollbar-width:thin;">
            @foreach($banners as $banner)
            <a href="{{ $banner->seo_canonical_url ?: '/student/dashboard' }}"
               data-banner-id="{{ $banner->id }}"
               onclick="studentBannerClick({{ $banner->id }},this)"
               style="flex:0 0 260px;border-radius:10px;overflow:hidden;border:1px solid var(--u-line,#e5e7eb);text-decoration:none;color:inherit;background:#fff;display:block;">
                @if($banner->cover_image_url)
                <img src="{{ $banner->cover_image_url }}" alt="{{ $banner->title_tr ?? '' }}" loading="lazy" style="width:100%;height:120px;object-fit:cover;">
                @endif
                @if($banner->title_tr)
                <div style="padding:8px 10px 10px;font-weight:600;font-size:var(--tx-sm);line-height:1.3;">
                    {{ $banner->title_tr }}
                    @if($banner->summary_tr)
                    <div style="font-weight:400;font-size:var(--tx-xs);color:var(--u-muted,#6b7280);margin-top:3px;">{{ Str::limit($banner->summary_tr,80) }}</div>
                    @endif
                </div>
                @endif
            </a>
            @endforeach
        </div>
        <script>
        function studentBannerClick(id, el) {
            var url = el.getAttribute('href');
            fetch('/student/banner/' + id + '/click', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Content-Type': 'application/json',
                },
            }).catch(function(){});
        }
        </script>
        @endif

        {{-- Step tracker --}}
        <div class="sd-steps">
            @foreach(($progressSteps ?? []) as $i => $step)
                <div class="sd-step {{ !empty($step['done']) ? 'done' : '' }}">
                    <div class="sd-step-dot">
                        @if(!empty($step['done']))✓@else{{ $i + 1 }}@endif
                    </div>
                    <div class="sd-step-label">{{ $step['label'] }}</div>
                    <div class="sd-step-status">{{ !empty($step['done']) ? '✓ Tamamlandı' : 'Bekliyor' }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="sd-kpi-grid">
        <div class="sd-kpi-card">
            <div class="sd-kpi-label">Danışmanlık No</div>
            <div class="sd-kpi-value" style="font-size:var(--tx-base);">{{ $studentId }}</div>
        </div>
        <div class="sd-kpi-card">
            <div class="sd-kpi-label">Atanan Senior</div>
            <div class="sd-kpi-value" style="font-size:var(--tx-base);">{{ $seniorDisplay ?? '-' }}</div>
            @if($assignment?->senior_email && $seniorDisplay !== $assignment->senior_email)
                <div class="sd-kpi-sub">{{ $assignment->senior_email }}</div>
            @endif
        </div>
        <div class="sd-kpi-card">
            <div class="sd-kpi-label">Belgeler (onaylı/toplam)</div>
            <div class="sd-kpi-value">{{ $docsApproved }}/{{ $docsTotal }}</div>
            @if($docsRejected > 0)
                <div class="sd-kpi-sub" style="color:#dc2626;">{{ $docsRejected }} reddedildi</div>
            @else
                <div class="sd-kpi-sub" style="color:#059669;">{{ $docsTotal > 0 ? round($docsApproved/$docsTotal*100) : 0 }}% tamamlandı</div>
            @endif
        </div>
        <div class="sd-kpi-card">
            <div class="sd-kpi-label">Okunmamış Mesaj</div>
            <div class="sd-kpi-value" style="color:{{ $dmUnread > 0 ? '#2563eb' : '#111827' }};">{{ $dmUnread }}</div>
            @if($dmUnread > 0)
                <a class="sd-kpi-sub" href="/student/messages" style="color:#2563eb;font-weight:600;">Göster →</a>
            @else
                <div class="sd-kpi-sub">Tümü okundu</div>
            @endif
        </div>
    </div>

    {{-- ── Hızlı Erişim ── --}}
    <div class="sd-quick-grid">
        <a class="sd-quick-link" href="/student/registration/form">
            <span class="sd-quick-icon" style="background:#2563eb;">K</span>
            Kayıt Formu
        </a>
        <a class="sd-quick-link" href="/student/registration/documents">
            <span class="sd-quick-icon" style="background:#0891b2;">B</span>
            Belgelerim
        </a>
        <a class="sd-quick-link" href="/student/contract">
            <span class="sd-quick-icon" style="background:#7c3aed;">S</span>
            Sözleşme
        </a>
        <a class="sd-quick-link" href="/student/messages">
            <span class="sd-quick-icon" style="background:#059669;">M</span>
            Danışman İletişim
        </a>
        <a class="sd-quick-link" href="/student/services">
            <span class="sd-quick-icon" style="background:#d97706;">H</span>
            Servisler
        </a>
        <a class="sd-quick-link" href="/student/appointments">
            <span class="sd-quick-icon" style="background:#dc2626;">R</span>
            Randevular
        </a>
    </div>

    {{-- ── 2.1 Onboarding Banner (modal trigger) ── --}}
    @if(!empty($onboardingPending))
    <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <span style="font-size:var(--tx-2xl);flex-shrink:0;">🚀</span>
        <div style="flex:1;min-width:200px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:#1e40af;">Portala Hoş Geldiniz!</div>
            <div style="font-size:var(--tx-xs);color:#1e3a8a;margin-top:2px;">Başlamak için birkaç adımı tamamlayın — sadece 2 dakika sürer.</div>
        </div>
        <button onclick="obModalOpen()" class="btn" style="flex-shrink:0;background:#2563eb;color:#fff;border:none;cursor:pointer;">Başlayın →</button>
    </div>
    @endif

    {{-- ── Kritik Uyarı Kartları ── --}}
    @if(isset($alerts) && $alerts->isNotEmpty())
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
        @foreach($alerts as $alert)
        @php $alertCls = match($alert['type']) { 'danger' => '#fef2f2:#fecaca:#991b1b', 'warning' => '#fffbeb:#fde68a:#92400e', 'info' => '#eff6ff:#bfdbfe:#1e40af', default => '#f9fafb:#e5e7eb:#374151' }; @endphp
        @php [$alertBg, $alertBorder, $alertText] = explode(':', $alertCls); @endphp
        <div style="background:{{ $alertBg }};border:1.5px solid {{ $alertBorder }};border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:var(--tx-lg);flex-shrink:0;">{{ $alert['icon'] }}</span>
            <span style="font-size:var(--tx-sm);font-weight:600;color:{{ $alertText }};flex:1;min-width:200px;">{{ $alert['message'] }}</span>
            <a class="btn" href="{{ $alert['action_url'] }}" style="flex-shrink:0;font-size:var(--tx-xs);">{{ $alert['action_text'] }}</a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Countdown Widget'ları ── --}}
    @if(isset($countdowns) && $countdowns->isNotEmpty())
    <div style="margin-bottom:14px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Yaklaşan Tarihler</div>
        <div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin;">
            @foreach($countdowns as $cd)
            @php
                $cdBg     = $cd['urgency'] === 'urgent' ? '#fff5f5' : ($cd['urgency'] === 'warning' ? '#fffbeb' : '#f0f9ff');
                $cdBorder = $cd['urgency'] === 'urgent' ? '#fca5a5' : ($cd['urgency'] === 'warning' ? '#fcd34d' : '#bae6fd');
                $cdColor  = $cd['urgency'] === 'urgent' ? '#991b1b' : ($cd['urgency'] === 'warning' ? '#92400e' : '#0c4a6e');
            @endphp
            <div style="min-width:130px;flex-shrink:0;background:{{ $cdBg }};border:1.5px solid {{ $cdBorder }};border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:var(--tx-2xl);font-weight:800;color:{{ $cdColor }};line-height:1;">{{ $cd['days_left'] }}</div>
                <div style="font-size:var(--tx-xs);color:{{ $cdColor }};margin-bottom:4px;">gün kaldı</div>
                <div style="font-size:var(--tx-xs);font-weight:600;color:#374151;line-height:1.3;">{{ \Illuminate\Support\Str::limit($cd['label'], 30) }}</div>
                <div style="font-size:var(--tx-xs);color:#9ca3af;margin-top:3px;">{{ $cd['deadline'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Hizmet Kartları Şeridi ── --}}
<div class="gd-svc-grid">
    @foreach([
        ['cls'=>'blue',  'icon'=>'🎓','title'=>'Üniversite Rehberi',  'desc'=>'300+ üniversite, program ve şehir karşılaştırması',   'href'=>route('student.info.university-guide'),  'star'=>false],
        ['cls'=>'purple','icon'=>'📋','title'=>'Belge Hazırlama',      'desc'=>'Motivasyon mektubu, CV, resmi çeviri',                'href'=>route('student.info.document-guide'),    'star'=>false],
        ['cls'=>'star',  'icon'=>'⭐','title'=>'Başarı Hikayeleri',   'desc'=>'80+ öğrencinin gerçek Almanya yolculuğu',             'href'=>route('student.info.success-stories'),   'star'=>true],
        ['cls'=>'orange','icon'=>'🏠','title'=>'Almanya\'da Yaşam',    'desc'=>'Konaklama, sigorta, banka, ulaşım rehberi',           'href'=>route('student.info.living-guide'),      'star'=>false],
        ['cls'=>'green', 'icon'=>'🛂','title'=>'Vize & Sperrkonto',    'desc'=>'Vize başvurusu ve bloke hesap danışmanlığı',          'href'=>route('student.info.vize-guide'),        'star'=>false],
    ] as $svc)
    <a href="{{ $svc['href'] }}" class="gd-svc-card {{ $svc['cls'] }}">
        @if($svc['star'])
        <span class="gd-svc-star-badge">⭐ Öne Çıkan</span>
        @endif
        <div class="gd-svc-accent"></div>
        <div class="gd-svc-body">
            <div class="gd-svc-icon">{{ $svc['icon'] }}</div>
            <div class="gd-svc-title">{{ $svc['title'] }}</div>
            <div class="gd-svc-desc">{{ $svc['desc'] }}</div>
            <div class="gd-svc-link">Keşfet</div>
        </div>
    </a>
    @endforeach
</div>

    {{-- ── Keşfet Hızlı Linkler ── --}}
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;padding:14px 18px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:12px;align-items:center;">
    <span style="font-size:.8rem;font-weight:700;color:var(--u-muted,#4f6787);white-space:nowrap;">🧭 İçerik Keşfet:</span>
    @foreach([
        ['href'=>route('student.discover'),                                 'label'=>'🧭 Tüm İçerikler'],
        ['href'=>route('student.discover',['cat'=>'city-content']),         'label'=>'🏙 Şehir Rehberleri'],
        ['href'=>route('student.discover',['cat'=>'tips-tricks']),          'label'=>'💡 Pratik İpuçları'],
        ['href'=>route('student.discover',['cat'=>'careers']),              'label'=>'💼 Kariyer'],
        ['href'=>route('student.discover',['cat'=>'student-life']),         'label'=>'🎓 Öğrenci Hayatı'],
    ] as $lnk)
    <a href="{{ $lnk['href'] }}" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:var(--u-bg,#eaf1fb);color:var(--u-brand,#1f6fd9);font-size:.8rem;font-weight:600;text-decoration:none;border:1px solid var(--u-line,#d6e1ef);transition:background .15s;" onmouseover="this.style.background='var(--u-brand,#1f6fd9)';this.style.color='#fff'" onmouseout="this.style.background='var(--u-bg,#eaf1fb)';this.style.color='var(--u-brand,#1f6fd9)'">{{ $lnk['label'] }}</a>
    @endforeach
</div>

    {{-- ── Haftalık Aktivite + Rozetler (compact, tek satır) ── --}}
    @if(isset($weekActivity) || !empty($achievements) || (isset($checklistSummary) && $checklistSummary['total'] > 0))
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;align-items:stretch;">

        {{-- Bu Hafta: yatay 4 stat --}}
        @if(isset($weekActivity))
        <div style="flex:1;min-width:220px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:10px 14px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Bu Hafta</div>
            <div style="display:flex;gap:0;justify-content:space-between;">
                @foreach([
                    [$weekActivity['documents_uploaded'],'Belge'],
                    [$weekActivity['messages_received'],'Mesaj'],
                    [$weekActivity['outcomes_added'],'Süreç'],
                    [$weekActivity['materials_read'],'Materyal'],
                ] as [$val,$lbl])
                <div style="text-align:center;flex:1;">
                    <div style="font-size:var(--tx-lg);font-weight:700;color:#111827;line-height:1;">{{ $val }}</div>
                    <div style="font-size:var(--tx-xs);color:#9ca3af;margin-top:2px;">{{ $lbl }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Yapılacaklar progress --}}
        @if(isset($checklistSummary) && $checklistSummary['total'] > 0)
        <a href="/student/checklist" style="text-decoration:none;flex:1;min-width:160px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:10px 14px;display:flex;flex-direction:column;justify-content:center;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Yapılacaklar</div>
                <span style="font-size:var(--tx-xs);font-weight:700;color:#7c3aed;">{{ $checklistSummary['done'] }}/{{ $checklistSummary['total'] }}</span>
            </div>
            <div style="height:6px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-bottom:4px;">
                <div style="width:{{ $checklistSummary['percent'] }}%;height:100%;background:#22c55e;border-radius:999px;"></div>
            </div>
            <div style="font-size:var(--tx-xs);color:#6b7280;">%{{ $checklistSummary['percent'] }} tamamlandı@if($checklistSummary['overdue'] > 0) · <span style="color:#ef4444;font-weight:600;">{{ $checklistSummary['overdue'] }} gecikti</span>@endif</div>
        </a>
        @endif

        {{-- Rozetler --}}
        @if(!empty($achievements))
        <div style="flex:1;min-width:180px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:12px;padding:10px 14px;position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.5px;">Rozetler</div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:var(--tx-xs);font-weight:800;color:#7c3aed;">{{ $achievementPoints ?? 0 }} puan</span>
                    <button type="button" id="sdBadgeInfoBtn"
                        style="width:18px;height:18px;border-radius:50%;background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.25);color:#7c3aed;font-size:var(--tx-xs);font-weight:800;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;padding:0;"
                        onclick="sdToggleBadgeInfo()" title="Rozet detayları">ℹ</button>
                </div>
            </div>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
                @foreach($achievements as $bdg)
                @php
                    $bdgColors = [
                        'rocket'   => ['#fef3c7','#d97706'],
                        'star'     => ['#fef3c7','#d97706'],
                        'check'    => ['#dcfce7','#16a34a'],
                        'doc'      => ['#ede9fe','#7c3aed'],
                        'person'   => ['#e0f2fe','#0369a1'],
                        'default'  => ['#f3f4f6','#6b7280'],
                    ];
                    $icon = $bdg['icon'] ?? '🏅';
                    $pts  = $bdg['points'] ?? '';
                @endphp
                <div style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:999px;background:#ede9fe;border:1px solid rgba(124,58,237,.2);">
                    <span style="font-size:var(--tx-sm);line-height:1;">{{ $icon }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:#6d28d9;white-space:nowrap;max-width:80px;overflow:hidden;text-overflow:ellipsis;">{{ $bdg['label'] ?? '' }}</span>
                    @if($pts)<span style="font-size:var(--tx-xs);color:#8b5cf6;font-weight:600;">+{{ $pts }}</span>@endif
                </div>
                @endforeach
            </div>

            {{-- Info popup --}}
            <div id="sdBadgeInfoPanel"
                style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:260px;
                       background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);
                       border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,.13);z-index:500;padding:12px;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--u-line,#e5e7eb);">
                    🏆 Rozet Detayları — {{ $achievementPoints ?? 0 }} puan
                </div>
                @foreach($achievements as $bdg)
                <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--u-line,#f3f4f6);">
                    <span style="font-size:var(--tx-lg);flex-shrink:0;">{{ $bdg['icon'] ?? '🏅' }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);">{{ $bdg['label'] ?? '' }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.4;">{{ $bdg['description'] ?? '' }}</div>
                    </div>
                    @if(!empty($bdg['points']))
                    <span style="font-size:var(--tx-xs);font-weight:800;color:#7c3aed;flex-shrink:0;">+{{ $bdg['points'] }}</span>
                    @endif
                </div>
                @endforeach
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:8px;">Danışmanınız yeni rozetler ekleyebilir.</div>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- Onboarding Tasks --}}
    @if($onboardingTasks->isNotEmpty())
    <div class="sd-card" style="margin-bottom:16px;">
        <div class="sd-card-header">
            <h3 class="sd-card-title">Onboarding Görevleri</h3>
            <span class="badge chip info">{{ $onboardingTasks->count() }} görev</span>
        </div>
        @php
            $tLabels = ['done' => 'Tamamlandı','completed' => 'Tamamlandı','in_progress' => 'Devam Ediyor','pending' => 'Bekliyor'];
        @endphp
        @foreach($onboardingTasks as $t)
            @php
                $tCls = match((string)$t->status) { 'done','completed' => 'ok', 'in_progress' => 'info', default => 'pending' };
                $tLabel = $tLabels[(string)$t->status] ?? ucfirst((string)$t->status);
            @endphp
            <div class="sd-task-item">
                <div>
                    <div class="sd-task-title">{{ $t->title }}</div>
                    @if($t->due_date)
                        <div class="sd-task-due">Son: {{ \Carbon\Carbon::parse($t->due_date)->format('d.m.Y') }}</div>
                    @endif
                </div>
                <span class="badge chip {{ $tCls }}">{{ $tLabel }}</span>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── Content Grid: Belgeler + Bildirimler ── --}}
    <div class="sd-content-grid">

        {{-- Belge Durumu (Accordion) --}}
        <div class="sd-card">
            <div class="sd-card-header">
                <h3 class="sd-card-title">Belge Durumu</h3>
                <a href="/student/registration/documents" class="sd-card-link">Belge Merkezi →</a>
            </div>
            <div style="display:flex;gap:16px;margin-bottom:12px;">
                <div style="font-size:var(--tx-xs);color:#059669;font-weight:600;">✓ {{ $docsApproved }} Onaylı</div>
                <div style="font-size:var(--tx-xs);color:#6b7280;">↑ {{ (int)($docSummary['uploaded'] ?? 0) }} Yüklendi</div>
                @if($docsRejected > 0)
                    <div style="font-size:var(--tx-xs);color:#dc2626;font-weight:600;">✗ {{ $docsRejected }} Reddedildi</div>
                @endif
            </div>
            @php
                $catMeta = [
                    'uni_assist_dokumanlari'       => ['icon' => '📋', 'label' => 'Uni-Assist Belgeleri'],
                    'vize_dokumanlari'             => ['icon' => '✈️', 'label' => 'Vize Belgeleri'],
                    'dil_okulu_dokumanlari'        => ['icon' => '🗣️', 'label' => 'Dil Okulu Belgeleri'],
                    'ikamet_kaydi_dokumanlari'     => ['icon' => '🏠', 'label' => 'İkamet Kaydı Belgeleri'],
                    'almanya_burokrasi_dokumanlari'=> ['icon' => '🏛️', 'label' => 'Almanya Bürokrasi'],
                    'diger_dokumanlar'             => ['icon' => '📁', 'label' => 'Diğer Belgeler'],
                ];
                $grouped = collect($requiredChecklist ?? [])->groupBy('top_category');
            @endphp
            @if($grouped->isEmpty())
                <div style="padding:16px 0;text-align:center;font-size:var(--tx-sm);color:#6b7280;">
                    Zorunlu belge listesi henüz oluşturulmamış.
                </div>
            @else
                <div class="sd-acc">
                    @foreach($catMeta as $catKey => $cat)
                        @php $items = $grouped->get($catKey, collect()); @endphp
                        @if($items->isEmpty()) @continue @endif
                        @php
                            $doneCount = $items->where('done', true)->count();
                            $totalCount = $items->count();
                            $allDone = $doneCount === $totalCount;
                        @endphp
                        <details class="sd-acc-group">
                            <summary>
                                <div class="sd-acc-group-left">
                                    <span class="sd-acc-group-icon">{{ $cat['icon'] }}</span>
                                    <span class="sd-acc-group-title">{{ $cat['label'] }}</span>
                                    <span class="sd-acc-group-count">({{ $doneCount }}/{{ $totalCount }})</span>
                                </div>
                                <div class="sd-acc-group-right">
                                    @if($allDone)
                                        <span class="sd-acc-item-badge ok">Tamamlandı</span>
                                    @else
                                        <span class="sd-acc-item-badge miss">{{ $totalCount - $doneCount }} Eksik</span>
                                    @endif
                                    <span class="sd-acc-group-caret">▼</span>
                                </div>
                            </summary>
                            <div class="sd-acc-body">
                                @foreach($items as $c)
                                    <div class="sd-acc-item">
                                        <div class="sd-acc-item-dot {{ $c['done'] ? 'done' : 'miss' }}">
                                            {{ $c['done'] ? '✓' : '!' }}
                                        </div>
                                        <div class="sd-acc-item-name">{{ $c['name'] }}</div>
                                        <span class="sd-acc-item-badge {{ $c['done'] ? 'ok' : 'miss' }}">
                                            {{ $c['done'] ? 'Tamam' : 'Eksik' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Bildirimler + Süreç --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Son Bildirimler --}}
            <div class="sd-card">
                <div class="sd-card-header">
                    <h3 class="sd-card-title">Son Bildirimler</h3>
                    <div style="display:flex;gap:6px;">
                        @if(($notificationSummary['sent'] ?? 0) > 0)
                            <span class="badge chip ok">{{ $notificationSummary['sent'] }} gönderildi</span>
                        @endif
                        @if(($notificationSummary['failed'] ?? 0) > 0)
                            <span class="badge chip danger">{{ $notificationSummary['failed'] }} başarısız</span>
                        @endif
                    </div>
                </div>
                @php
                    $nLabels = ['sent' => 'Gönderildi','queued' => 'Kuyrukta','failed' => 'Başarısız'];
                @endphp
                @forelse(($notifications ?? collect())->take(4) as $n)
                    @php
                        $nCls = match((string)$n->status) { 'sent' => 'ok','queued' => 'pending','failed' => 'danger', default => 'info' };
                        $nDate = $n->sent_at ?? $n->queued_at ?? null;
                        $nLabel = $nLabels[(string)$n->status] ?? ucfirst((string)$n->status);
                    @endphp
                    <div class="sd-notif-item">
                        <div>
                            <div class="sd-notif-title">{{ $n->subject ?: ($n->category ?: 'Bildirim') }}</div>
                            <div class="sd-notif-ch">{{ strtoupper((string)$n->channel) }}</div>
                        </div>
                        <div class="sd-notif-right">
                            <span class="badge chip {{ $nCls }}" style="font-size:var(--tx-xs);">{{ $nLabel }}</span>
                            @if($nDate)
                                <div class="sd-notif-date">{{ \Carbon\Carbon::parse($nDate)->format('d.m H:i') }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="muted" style="padding:12px 0;text-align:center;font-size:var(--tx-sm);">
                        Henüz bildirim bulunmuyor.
                    </div>
                @endforelse
            </div>

            {{-- Son Süreç Kayıtları --}}
            <div class="sd-card">
                <div class="sd-card-header">
                    <h3 class="sd-card-title">Son Süreç Kayıtları</h3>
                    <a href="/student/process-tracking" class="sd-card-link">Tüm Süreç →</a>
                </div>
                @php
                    $visibleOutcomes = ($outcomes ?? collect())->filter(fn($o) => (bool)$o->is_visible_to_student);
                    $stepLabels = [
                        'application_prep'  => 'Başvuru Hazırlık',
                        'uni_assist'        => 'Uni Assist',
                        'visa_application'  => 'Vize Başvurusu',
                        'language_course'   => 'Dil Kursu',
                        'residence'         => 'İkamet',
                        'official_services' => 'Resmi Hizmetler',
                    ];
                    $outcomeLabels = [
                        'acceptance'             => ['Kabul',         'ok'],
                        'rejection'              => ['Red',           'danger'],
                        'conditional_acceptance' => ['Şartlı Kabul',  'info'],
                        'correction_request'     => ['Düzeltme',      'warn'],
                        'waitlist'               => ['Bekleme Listesi','warn'],
                        'pending'                => ['Beklemede',     'pending'],
                    ];
                @endphp
                @forelse($visibleOutcomes->take(4) as $o)
                    @php
                        [$outLbl, $outBadge] = $outcomeLabels[$o->outcome_type] ?? [ucfirst($o->outcome_type), 'info'];
                        $stepLbl = $stepLabels[$o->process_step] ?? $o->process_step;
                    @endphp
                    <div class="sd-tl-item">
                        <div class="sd-tl-dot"></div>
                        <div style="flex:1;min-width:0;">
                            <div class="sd-tl-step" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span>{{ $stepLbl }}</span>
                                <span class="badge {{ $outBadge }}" style="font-size:10px;">{{ $outLbl }}</span>
                            </div>
                            <div class="sd-tl-detail">{{ \Illuminate\Support\Str::limit((string)($o->details_tr ?? '-'), 100) }}</div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:3px;">
                                <div class="sd-tl-date">{{ $o->created_at?->format('d.m.Y H:i') }}</div>
                                @if($o->document)
                                    <a href="{{ route('student.registration.documents.download', $o->document->id) }}"
                                       style="font-size:10px;color:var(--c-accent);font-weight:600;text-decoration:none;"
                                       title="{{ $o->document->original_file_name }}">
                                        Belgeyi Gör →
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="muted" style="padding:12px 0;text-align:center;font-size:var(--tx-sm);">
                        Henüz süreç kaydı bulunmuyor.<br>
                        <span style="font-size:var(--tx-xs);">Danışmanınız süreç adımlarını işleyince burada görünecek.</span>
                    </div>
                @endforelse
            </div>

        </div>
    </div>


@endif

{{-- ══ ONBOARDING MODAL ══════════════════════════════════════════════════ --}}
@if(!empty($onboardingPending) && !empty($onboardingSteps))
@php
    $obTotal = count($onboardingSteps);
    $obDone  = collect($onboardingSteps)->where('done', true)->count();
    $obPct   = $obTotal > 0 ? (int) round($obDone / $obTotal * 100) : 0;
@endphp

{{-- Overlay --}}
<div id="ob-modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9998;backdrop-filter:blur(2px);"
     onclick="if(event.target===this)obModalClose()">
</div>

{{-- Modal --}}
<div id="ob-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            z-index:9999;width:min(520px,94vw);max-height:88vh;overflow-y:auto;
            background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.22);
            font-family:inherit;">

    {{-- Modal header --}}
    <div style="background:linear-gradient(to right,#1d4ed8,#2563eb);border-radius:18px 18px 0 0;padding:28px 28px 20px;text-align:center;position:relative;">
        <button onclick="obModalClose()"
                style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.15);
                       border:none;color:#fff;width:30px;height:30px;border-radius:50%;
                       font-size:16px;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;">×</button>
        <div style="font-size:48px;margin-bottom:10px;">🚀</div>
        <div style="font-size:var(--tx-xl);font-weight:800;color:#fff;margin-bottom:4px;">Portala Hoş Geldiniz!</div>
        <div style="font-size:var(--tx-sm);color:rgba(255,255,255,.85);">Başlamak için aşağıdaki adımları tamamlayın.</div>

        {{-- Progress --}}
        <div style="margin-top:16px;background:rgba(255,255,255,.2);border-radius:999px;height:8px;overflow:hidden;">
            <div id="ob-modal-bar" style="width:{{ $obPct }}%;height:100%;background:#34d399;border-radius:999px;transition:width .4s;"></div>
        </div>
        <div id="ob-modal-pct" style="font-size:var(--tx-xs);color:rgba(255,255,255,.8);margin-top:6px;">{{ $obDone }} / {{ $obTotal }} adım · %{{ $obPct }}</div>
    </div>

    {{-- Steps --}}
    <div style="padding:20px 24px 24px;display:flex;flex-direction:column;gap:10px;">
        @foreach($onboardingSteps as $step)
        <div id="ob-modal-step-{{ $step['code'] }}"
             style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;
                    border-radius:12px;border:1.5px solid {{ $step['done'] ? '#bbf7d0' : '#e5e7eb' }};
                    background:{{ $step['done'] ? '#f0fdf4' : '#fff' }};
                    transition:all .2s;">
            <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
                        background:{{ $step['done'] ? '#22c55e' : '#e5e7eb' }};
                        display:flex;align-items:center;justify-content:center;
                        font-size:13px;font-weight:700;color:{{ $step['done'] ? '#fff' : '#6b7280' }};">
                {{ $step['done'] ? '✓' : $loop->iteration }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-sm);font-weight:700;color:{{ $step['done'] ? '#15803d' : '#111827' }};
                            {{ $step['done'] ? 'text-decoration:line-through;opacity:.7;' : '' }}">
                    {{ $step['label'] }}
                </div>
                <div style="font-size:var(--tx-xs);color:#6b7280;margin-top:2px;">{{ $step['desc'] }}</div>
                @if(!$step['done'])
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button onclick="obModalComplete('{{ $step['code'] }}')"
                            style="padding:5px 14px;background:#22c55e;color:#fff;border:none;
                                   border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        ✓ Tamamlandı
                    </button>
                    <button onclick="obModalSkip('{{ $step['code'] }}')"
                            style="padding:5px 12px;background:#f3f4f6;color:#6b7280;border:none;
                                   border-radius:8px;font-size:12px;cursor:pointer;">
                        Atla
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Footer --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;padding-top:14px;border-top:1px solid #e5e7eb;">
            <button onclick="obModalDismiss()"
                    style="background:none;border:none;color:#9ca3af;font-size:var(--tx-xs);cursor:pointer;text-decoration:underline;">
                Daha sonra hatırlat
            </button>
            <a href="{{ route('student.onboarding') }}"
               style="font-size:var(--tx-xs);color:#2563eb;font-weight:600;text-decoration:none;">
                Tam sayfada aç →
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    var KEY = 'ob_dismissed_{{ $studentId ?? "x" }}';

    function open() {
        document.getElementById('ob-modal-overlay').style.display = 'block';
        document.getElementById('ob-modal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function close() {
        document.getElementById('ob-modal-overlay').style.display = 'none';
        document.getElementById('ob-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    window.obModalOpen  = open;
    window.obModalClose = close;
    window.obModalDismiss = function () {
        sessionStorage.setItem(KEY, '1');
        close();
    };

    // Auto-open on first visit (session-based, not permanent)
    if (!sessionStorage.getItem(KEY)) {
        setTimeout(open, 600);
    }

    // Remaining counter
    var remaining = {{ collect($onboardingSteps)->where('done', false)->count() }};
    var total     = {{ $obTotal }};

    function refreshBar() {
        var done = total - remaining;
        var pct  = total > 0 ? Math.round(done / total * 100) : 0;
        document.getElementById('ob-modal-bar').style.width = pct + '%';
        document.getElementById('ob-modal-pct').textContent = done + ' / ' + total + ' adım · %' + pct;
    }

    window.obModalComplete = async function (code) {
        var res = await fetch('/student/onboarding/' + code + '/complete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json'
            }
        });
        if (!res.ok) return;
        var el = document.getElementById('ob-modal-step-' + code);
        if (el) {
            el.style.background    = '#f0fdf4';
            el.style.borderColor   = '#bbf7d0';
            var dot = el.querySelector('div');
            dot.style.background   = '#22c55e';
            dot.style.color        = '#fff';
            dot.textContent        = '✓';
            var lbl = dot.nextElementSibling.querySelector('div');
            lbl.style.textDecoration = 'line-through';
            lbl.style.opacity      = '.7';
            lbl.style.color        = '#15803d';
            el.querySelector('[style*="gap:8px"]')?.remove();
        }
        remaining = Math.max(0, remaining - 1);
        refreshBar();
        if (remaining === 0) {
            setTimeout(function () {
                close();
                window.location.reload();
            }, 800);
        }
    };

    window.obModalSkip = async function (code) {
        await fetch('/student/onboarding/' + code + '/skip', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json'
            }
        });
        var el = document.getElementById('ob-modal-step-' + code);
        if (el) {
            el.querySelector('[style*="gap:8px"]')?.remove();
            var badge = document.createElement('span');
            badge.style.cssText = 'font-size:11px;color:#9ca3af;margin-top:4px;display:inline-block;';
            badge.textContent = 'Atlandı';
            el.querySelector('div').nextElementSibling.appendChild(badge);
        }
        remaining = Math.max(0, remaining - 1);
        refreshBar();
        if (remaining === 0) setTimeout(function () { close(); window.location.reload(); }, 800);
    };
}());
</script>
@endif

<script>
function sdToggleBadgeInfo() {
    var panel = document.getElementById('sdBadgeInfoPanel');
    if (!panel) return;
    var isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        var close = function(e) {
            if (!panel.contains(e.target) && e.target.id !== 'sdBadgeInfoBtn') {
                panel.style.display = 'none';
                document.removeEventListener('click', close);
            }
        };
        setTimeout(function() { document.addEventListener('click', close); }, 10);
    }
}
</script>

@endsection
