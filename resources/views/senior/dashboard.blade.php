@extends('senior.layouts.app')

@section('title', 'Eğitim Danışmanı Paneli')
@section('page_title', 'Eğitim Danışmanı Paneli')

@push('head')
<style>
/* ── Eğitim Danışmanı Paneli Hero ── */
.srd-hero {
    background: linear-gradient(to right, #3b1a6e 0%, #6d28d9 60%, #7c3aed 100%);
    border-radius: 0 0 16px 16px;
    padding: 32px 28px 24px;
    position: relative;
    overflow: hidden;
    margin: -20px -20px 20px 0;
}
.srd-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.srd-hero::after {
    content: '';
    position: absolute;
    bottom: -70px; left: 40%;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.srd-hero-top {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    position: relative; z-index: 1;
    margin-bottom: 18px;
}
.srd-avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2.5px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 22px;
    flex-shrink: 0;
}
.srd-hero-info { flex: 1; min-width: 180px; }
.srd-hero-name { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.srd-hero-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.srd-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 2px 10px; font-size: 11px; color: #fff; font-weight: 600;
}
.srd-hero-badge.alert { background: rgba(252,165,165,.2); border-color: rgba(252,165,165,.5); }
.srd-hero-stats {
    display: flex; gap: 20px; flex-wrap: wrap;
    margin-left: auto; flex-shrink: 0;
}
.srd-hstat { text-align: center; }
.srd-hstat-val { font-size: 20px; font-weight: 700; color: #fff; line-height: 1; margin-bottom: 3px; }
.srd-hstat-label { font-size: 11px; color: rgba(255,255,255,.65); font-weight: 500; }
.srd-hstat-sep { width: 1px; background: rgba(255,255,255,.2); align-self: stretch; }
.srd-hero-actions {
    display: flex; gap: 8px; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.srd-hero-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
}
.srd-hero-btn.primary { background: #fff; color: #7c3aed; }
.srd-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
/* KPI row */
.srd-kpis { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 16px; }
@media (max-width: 700px) { .srd-kpis { grid-template-columns: repeat(2,1fr); } }
.srd-kpi-card {
    background: var(--u-card, #fff);
    border: 1px solid var(--u-line, #e5e7eb);
    border-radius: 14px;
    padding: 16px 18px;
    position: relative;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
/* E8: KPI'lar tıklanabilir link → hover affordance */
a.srd-kpi-card { cursor: pointer; text-decoration: none; color: inherit; display: block; }
a.srd-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
    border-color: var(--c-accent, #7c3aed);
}
a.srd-kpi-card::after {
    content: '→';
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 14px;
    color: #d1d5db;
    transition: color .15s, transform .15s;
}
a.srd-kpi-card:hover::after {
    color: var(--c-accent, #7c3aed);
    transform: translateX(3px);
}
.srd-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 6px; }
.srd-kpi-val { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; margin-bottom: 4px; }
.srd-kpi-sub { font-size: 11px; color: #9ca3af; }
.srd-kpi-sub a { color: #7c3aed; }
/* List */
.srd-list { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.srd-list-item {
    border: 1px solid var(--u-line, #e5e7eb);
    border-radius: 10px;
    background: var(--u-card, #fff);
    padding: 10px 12px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}
@media (max-width: 900px) {
    .srd-list-item { flex-direction: column; }
    .srd-list-item > div:last-child { text-align: left; }
}
/* hide default top bar on dashboard */
.top { display: none !important; }
/* ── Hızlı Erişim ── */
.srd-quick-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .srd-quick-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px) { .srd-quick-grid { grid-template-columns: repeat(2, 1fr); } }
.srd-quick-link {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 8px; padding: 14px 8px 12px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; text-decoration: none;
    color: #374151; font-size: 12px; font-weight: 600;
    text-align: center; line-height: 1.3;
    transition: background .15s, border-color .15s, transform .12s;
}
.srd-quick-link:hover {
    background: #f5f3ff; border-color: #c4b5fd;
    color: #6d28d9; transform: translateY(-2px);
    text-decoration: none;
}
.srd-quick-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
/* Accordion list */
.srd-acc-list .srd-list-item:nth-child(n+3) { display: none; }
.srd-acc-list.srd-open .srd-list-item:nth-child(n+3) { display: flex; }
.srd-acc-btn {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    width: 100%; margin-top: 8px; padding: 6px 10px; border-radius: 8px;
    border: 1px dashed var(--u-line, #e5e7eb); background: transparent;
    font-size: 12px; color: #9ca3af; cursor: pointer; transition: all .12s;
    font-weight: 500;
}
.srd-acc-btn:hover { background: var(--u-bg,#f8fafc); color: #374151; border-style: solid; }
</style>
@endpush

@section('content')
@php
    $seniorName = auth()->user()?->name ?? 'Eğitim Danışmanı';
    $initials   = strtoupper(substr($seniorName, 0, 2));
    $unread     = (int) ($dmSummary['unread'] ?? 0);
    $overdue    = (int) ($taskSummary['overdue'] ?? 0);
    $dmOverdue  = (int) ($dmSummary['overdue'] ?? 0);
@endphp

@if(!empty($previewMode))
<div style="background:#fef9c3;border:1.5px solid #fde047;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;margin-bottom:14px;">
    <strong>{{ $previewLabel ?? 'Manager preview' }}</strong>
    <span style="font-size:var(--tx-sm);color:#713f12;">Bu ekran manager tarafından önizleme modunda açıldı.</span>
    <a class="btn" href="/manager/dashboard" style="margin-left:auto;">Manager Dashboard'a Dön</a>
</div>
@endif

{{-- ── Hero ── --}}
<div class="srd-hero">
    <div class="srd-hero-top">
        <div class="srd-avatar">{{ $initials }}</div>
        <div class="srd-hero-info">
            <div class="srd-hero-name">{{ $seniorName }}</div>
            <div class="srd-hero-badges">
                <span class="srd-hero-badge">Eğitim Danışmanı</span>
                <span class="srd-hero-badge">{{ $activeStudentCount }} aktif öğrenci</span>
                @if($unread > 0)<span class="srd-hero-badge alert">{{ $unread }} okunmamış</span>@endif
            </div>
        </div>
        <div class="srd-hero-stats">
            <div class="srd-hstat">
                <div class="srd-hstat-val">{{ $todayAppointments->count() }}</div>
                <div class="srd-hstat-label">Bugün Randevu</div>
            </div>
            <div class="srd-hstat-sep"></div>
            <div class="srd-hstat">
                <div class="srd-hstat-val">{{ $weeklyPerformance['docs_approved'] }}</div>
                <div class="srd-hstat-label">Bu Hafta Belge</div>
            </div>
            <div class="srd-hstat-sep"></div>
            <div class="srd-hstat">
                <div class="srd-hstat-val">{{ $archivedStudentCount }}</div>
                <div class="srd-hstat-label">Arşiv</div>
            </div>
        </div>
    </div>
    {{-- Hero action butonları kaldırıldı — bu linklerin hepsi sidebar'da zaten mevcut. --}}
</div>

{{-- ── Tab Bar ── --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;border-bottom:2px solid var(--u-line,#e2e8f0);padding-bottom:0;">
    <button id="tab-main" onclick="switchSrdTab('main',this)"
        style="padding:10px 20px;font-size:13px;font-weight:700;background:none;border:none;border-bottom:3px solid var(--c-accent,#7c3aed);margin-bottom:-2px;cursor:pointer;color:var(--c-accent,#7c3aed);">
        📊 Genel Bakış
    </button>
    <button id="tab-blt" onclick="switchSrdTab('blt',this)"
        style="padding:10px 20px;font-size:13px;font-weight:700;background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;cursor:pointer;color:var(--u-muted,#64748b);display:flex;align-items:center;gap:6px;">
        📢 Duyurular
        @if(($bulletinUnread ?? 0) > 0)
        <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;line-height:16px;">{{ $bulletinUnread }}</span>
        @endif
    </button>
</div>
<div id="panel-blt" style="display:none;"></div>

<div id="dash-main-content">
{{-- ── Bannerlar ── --}}
@if(!empty($banners) && count($banners))
<div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:4px;margin-bottom:16px;scrollbar-width:thin;">
    @foreach($banners as $banner)
    <div style="min-width:260px;max-width:280px;flex-shrink:0;background:var(--u-card,#fff);border:1px solid var(--u-line,#e5e7eb);border-radius:14px;overflow:hidden;cursor:pointer;"
         onclick="seniorBannerClick({{ $banner->id }}, '{{ $banner->slug ?? '' }}')">
        @if($banner->cover_image_url)
        <div style="height:110px;overflow:hidden;background:#f3f4f6;">
            <img src="{{ $banner->cover_image_url }}" alt="{{ e($banner->title) }}" loading="lazy"
                 style="width:100%;height:100%;object-fit:cover;">
        </div>
        @else
        <div style="height:60px;background:linear-gradient(135deg,#7c3aed,#6d28d9);"></div>
        @endif
        <div style="padding:12px 14px;">
            <div style="font-size:var(--tx-sm);font-weight:700;color:#111827;line-height:1.3;margin-bottom:4px;">{{ e($banner->title) }}</div>
            @if($banner->summary)
            <div style="font-size:var(--tx-xs);color:#6b7280;line-height:1.4;">{{ \Illuminate\Support\Str::limit($banner->summary, 80) }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>
<script>
function seniorBannerClick(id, slug) {
    fetch('/senior/banner/' + id + '/click', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '', 'Accept': 'application/json' }
    }).catch(() => {});
    if (slug) window.location.href = '/senior/knowledge-base#' + slug;
}
</script>
@endif

{{-- ── KPI Cards (E8: senior için anlamlı 4'lü) ──────────────────────────
     Veri kaynağı: $sidebarKpi view composer
     (AppServiceProvider::boot, senior.layouts.app) — sidebar'dan buraya
     taşındı, sidebar artık sadece navigasyon. --}}
@php $kpi = $sidebarKpi ?? ['activeStudents'=>0,'pendingGuests'=>0,'todayTasks'=>0,'todayAppointments'=>0]; @endphp
<div class="srd-kpis">
    <a class="srd-kpi-card" href="/senior/students" style="text-decoration:none;color:inherit;display:block;">
        <div class="srd-kpi-label">Aktif Öğrenci</div>
        <div class="srd-kpi-val">{{ $kpi['activeStudents'] ?? 0 }}</div>
        <div class="srd-kpi-sub">arşiv: {{ $archivedStudentCount }}</div>
    </a>
    <a class="srd-kpi-card" href="/senior/students?pool=guest" style="text-decoration:none;color:inherit;display:block;">
        <div class="srd-kpi-label">Bekleyen Aday Öğrenci</div>
        <div class="srd-kpi-val" style="{{ ($kpi['pendingGuests'] ?? 0) > 0 ? 'color:#dc2626' : '' }}">{{ $kpi['pendingGuests'] ?? 0 }}</div>
        <div class="srd-kpi-sub">başvuru incelemede</div>
    </a>
    <a class="srd-kpi-card" href="/tasks" style="text-decoration:none;color:inherit;display:block;">
        <div class="srd-kpi-label">Bugün Görev</div>
        <div class="srd-kpi-val" style="{{ ($kpi['todayTasks'] ?? 0) > 0 ? 'color:#d97706' : '' }}">{{ $kpi['todayTasks'] ?? 0 }}</div>
        <div class="srd-kpi-sub">bugün bitmesi gereken</div>
    </a>
    <a class="srd-kpi-card" href="/senior/appointments" style="text-decoration:none;color:inherit;display:block;">
        <div class="srd-kpi-label">Bugün Randevu</div>
        <div class="srd-kpi-val">{{ $kpi['todayAppointments'] ?? 0 }}</div>
        <div class="srd-kpi-sub">planlanmış görüşme</div>
    </a>
</div>

{{-- Hızlı Erişim icon grid kaldırıldı — sidebar + KPI tıklanabilir tile'ları yeterli.
     Hepsi sidebar'da zaten var, dashboard'da çift bilgi oluşturuyordu. --}}

{{-- ── Öncelikli Aksiyon Banner (full-width) ── --}}
@php
    $hasUrgency    = $pendingApprovalCount > 0 || $unread > 0 || $overdue > 0 || $dmOverdue > 0;
    $hasTodayInbox = $todayAssignedGuests->isNotEmpty() || $todayDocsForReview->isNotEmpty();
@endphp
@if($hasUrgency)
<div style="background:#fff7ed;border:1.5px solid #fdba74;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
    <div style="font-size:var(--tx-xl);line-height:1;">⚡</div>
    <div style="font-size:var(--tx-sm);font-weight:700;color:#9a3412;">Bugün Aksiyon Gereken İşler</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-left:auto;">
        @if($pendingApprovalCount > 0)
            <a class="btn" href="/senior/process-tracking">Onay Bekleyenler ({{ $pendingApprovalCount }})</a>
        @endif
        @if($unread > 0)
            <a class="btn" href="/im">Okunmamış ({{ $unread }})</a>
        @endif
        @if($overdue > 0)
            <a class="btn" href="/tasks">Geciken Görev ({{ $overdue }})</a>
        @endif
        @if($dmOverdue > 0)
            <a class="btn" href="/im">Geciken SLA ({{ $dmOverdue }})</a>
        @endif
    </div>
</div>
@else
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;margin-bottom:14px;">
    <span class="badge ok" style="font-size:var(--tx-sm);">✓</span>
    <span style="font-size:var(--tx-sm);font-weight:600;color:#166534;">Acil bekleyen iş yok — harika!</span>
    <div class="muted" style="font-size:var(--tx-xs);margin-left:8px;">
        Yapılacak: {{ (int)($taskSummary['todo']??0) }} · Devam eden: {{ (int)($taskSummary['in_progress']??0) }} · DM açık: {{ (int)($dmSummary['open']??0) }}
    </div>
</div>
@endif

{{-- ── 📥 Bugün Sana Gelenler (full-width) ──────────────────────────────── --}}
<article class="panel" style="margin-bottom:14px;border-left:4px solid #7c3aed;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px;">
        <h3 style="margin:0;">📥 Bugün Sana Gelenler</h3>
        <span class="muted" style="font-size:var(--tx-xs);">{{ now()->format('d.m.Y') }}</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        {{-- Yeni atanan guest/student --}}
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <span>👤 Yeni Atanan</span>
                <span style="background:{{ $todayAssignedGuests->count() > 0 ? '#7c3aed' : '#d1d5db' }};color:#fff;border-radius:999px;padding:1px 8px;font-size:10px;font-weight:700;">{{ $todayAssignedGuests->count() }}</span>
            </div>
            @forelse($todayAssignedGuests as $g)
                @php
                    $name = trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? '')) ?: $g->email;
                    $isStudent = (bool) ($g->converted_to_student ?? false);
                    $targetUrl = $isStudent && $g->converted_student_id
                        ? '/senior/students?q=' . urlencode($g->converted_student_id)
                        : '/senior/guest-pipeline?q=' . urlencode($g->email ?? '');
                @endphp
                <a href="{{ $targetUrl }}" class="srd-list-item" style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:6px;text-decoration:none;color:inherit;transition:background .12s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                    <div>
                        <strong style="font-size:var(--tx-sm);">{{ $name }}</strong>
                        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">
                            <span class="badge {{ $isStudent ? 'info' : 'warn' }}">{{ $isStudent ? '🎓 Öğrenci' : '👋 Aday Öğrenci' }}</span>
                            @php
                                $appTypeLabels = [
                                    'yurtdisi'   => 'Yurtdışı',
                                    'bachelor'   => 'Lisans',
                                    'master'     => 'Y. Lisans',
                                    'ausbildung' => 'Ausbildung',
                                    'sprachkurs' => 'Dil Kursu',
                                    'ikamet'     => 'İkamet',
                                ];
                                $appTypeLabel = $appTypeLabels[(string) $g->application_type] ?? $g->application_type;
                            @endphp
                            @if($g->application_type)<span class="muted" style="margin-left:4px;">{{ $appTypeLabel }}</span>@endif
                        </div>
                    </div>
                    <span class="muted" style="font-size:var(--tx-xs);">{{ \Carbon\Carbon::parse($g->assigned_at)->format('H:i') }}</span>
                </a>
            @empty
                <div class="muted" style="font-size:var(--tx-xs);padding:6px 10px;">Bugün yeni atama yok.</div>
            @endforelse
        </div>

        {{-- Onay bekleyen belgeler --}}
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <span>📋 Onay Bekleyen Belge</span>
                <span style="background:{{ $todayDocsForReview->count() > 0 ? '#7c3aed' : '#d1d5db' }};color:#fff;border-radius:999px;padding:1px 8px;font-size:10px;font-weight:700;">{{ $todayDocsForReview->count() }}</span>
            </div>
            @forelse($todayDocsForReview as $doc)
                <a href="/senior/batch-review" class="srd-list-item" style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:6px;text-decoration:none;color:inherit;transition:background .12s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                    <div>
                        <strong style="font-size:var(--tx-sm);">{{ \Illuminate\Support\Str::limit($doc->original_file_name ?? ($doc->document_id ?? 'Belge'), 38) }}</strong>
                        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">
                            {{ $guestMap[$doc->student_id] ?? $doc->student_id }}
                        </div>
                    </div>
                    <span class="muted" style="font-size:var(--tx-xs);">{{ \Carbon\Carbon::parse($doc->created_at)->format('H:i') }}</span>
                </a>
            @empty
                <div class="muted" style="font-size:var(--tx-xs);padding:6px 10px;">Bugün inceleme bekleyen belge yok.</div>
            @endforelse
        </div>
    </div>
</article>

{{-- ── Smart Command Center ── --}}
<div class="grid3" style="margin-bottom:14px;">

    {{-- Today's Agenda (info panel — KPI'daki sayıları detaylandırır) --}}
    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">📅 Bugünün Ajandası</h3>
            <a class="btn" href="/senior/appointments">Randevular</a>
        </div>
        @if($todayAppointments->isEmpty() && $todayTasks->isEmpty())
            <div class="muted" style="font-size:var(--tx-sm);padding:6px 0;">Bugün için planlı randevu veya görev yok.</div>
        @endif
        @foreach($todayAppointments as $apt)
        <div class="srd-list-item" style="margin-bottom:6px;">
            <div>
                <span style="font-size:var(--tx-sm);font-weight:600;">{{ $apt->scheduled_at?->format('H:i') }}</span>
                <span class="badge info" style="margin-left:6px;">{{ $apt->channel ?? 'randevu' }}</span>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">{{ $guestMap[$apt->student_id] ?? $apt->student_id }}</div>
            </div>
            <span class="badge {{ $apt->status === 'confirmed' ? 'ok' : 'warn' }}">{{ $apt->status }}</span>
        </div>
        @endforeach
        @foreach($todayTasks as $task)
        <div class="srd-list-item" style="margin-bottom:6px;">
            <div>
                <span style="font-size:var(--tx-sm);font-weight:600;">📌 {{ \Illuminate\Support\Str::limit($task->title, 40) }}</span>
                <div style="margin-top:2px;">
                    <span class="badge {{ $task->priority === 'high' ? 'danger' : 'warn' }}">{{ $task->priority }}</span>
                    <span class="badge">{{ $task->status }}</span>
                </div>
            </div>
            <a class="btn" href="/tasks">Git →</a>
        </div>
        @endforeach
    </article>

    {{-- Critical Actions --}}
    <article class="panel">
        <div style="margin-bottom:10px;">
            <h3 style="margin:0;">⚡ Kritik Aksiyonlar</h3>
        </div>
        @if($criticalActions->isEmpty())
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;font-size:var(--tx-sm);color:#166534;font-weight:600;">✓ Acil bekleyen aksiyon yok</div>
        @else
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($criticalActions as $action)
            <a href="{{ $action['url'] }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#fff7ed;border:1.5px solid #fdba74;border-radius:8px;text-decoration:none;color:#9a3412;font-size:var(--tx-sm);font-weight:600;transition:background .15s;" onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'">
                <span style="font-size:var(--tx-lg);">{{ $action['icon'] }}</span>
                <span style="flex:1;">{{ $action['label'] }}</span>
                <span style="background:#ea580c;color:#fff;border-radius:20px;padding:2px 8px;font-size:var(--tx-xs);">{{ $action['count'] }}</span>
            </a>
            @endforeach
        </div>
        @endif
    </article>

    {{-- Risk Radar + Weekly Performance --}}
    <article class="panel">
        <div style="margin-bottom:10px;">
            <h3 style="margin:0;">🎯 Risk Radar & Haftalık</h3>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:12px;">
            <div style="flex:1;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:var(--tx-xl);font-weight:700;color:#dc2626;">{{ $riskRadar->count() }}</div>
                <div style="font-size:var(--tx-xs);color:#9ca3af;margin-top:2px;">Risk'li Öğrenci</div>
            </div>
            <div style="flex:1;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:var(--tx-xl);font-weight:700;color:#16a34a;">{{ $weeklyPerformance['docs_approved'] }}</div>
                <div style="font-size:var(--tx-xs);color:#9ca3af;margin-top:2px;">Belge Onaylandı</div>
            </div>
            <div style="flex:1;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:var(--tx-xl);font-weight:700;color:#7c3aed;">{{ $weeklyPerformance['outcomes'] }}</div>
                <div style="font-size:var(--tx-xs);color:#9ca3af;margin-top:2px;">Outcome</div>
            </div>
        </div>
        @if($riskRadar->isNotEmpty())
        <div style="font-size:var(--tx-xs);font-weight:600;color:#6b7280;margin-bottom:6px;">Risk'li Öğrenciler:</div>
        @foreach($riskRadar->take(4) as $r)
        <div style="display:flex;align-items:center;gap:6px;padding:4px 0;border-bottom:1px solid var(--u-line,#e5e7eb);">
            <span class="badge {{ $r->risk_level === 'critical' ? 'danger' : 'warn' }}">{{ $r->risk_level }}</span>
            <span style="font-size:var(--tx-xs);flex:1;">{{ $guestMap[$r->student_id] ?? $r->student_id }}</span>
            <a href="/senior/students/{{ $r->student_id }}" style="font-size:var(--tx-xs);color:#7c3aed;">360° →</a>
        </div>
        @endforeach
        @endif
    </article>
</div>

{{-- ── Öğrenciler + Outcomes ── --}}
<div class="grid2" style="margin-bottom:14px;">
    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Atanan Öğrenciler</h3>
            <a class="btn" href="/senior/students">Tümünü Gör</a>
        </div>
        <div class="srd-list srd-acc-list" id="acc-students">
            @forelse($recentStudents as $s)
                @php $sName = $guestMap[$s->student_id] ?? null; @endphp
                <div class="srd-list-item">
                    <div>
                        <strong>{{ $sName ?: $s->student_id }}</strong>
                        @if($sName)<div class="muted" style="font-size:var(--tx-xs);">{{ $s->student_id }}</div>@endif
                        <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
                            @if($s->branch)<span class="badge info">{{ $s->branch }}</span>@endif
                            @if($s->risk_level)<span class="badge {{ in_array($s->risk_level, ['high','critical']) ? 'danger' : 'warn' }}">risk: {{ $s->risk_level }}</span>@endif
                            @if($s->payment_status)<span class="badge {{ $s->payment_status === 'paid' ? 'ok' : 'pending' }}">{{ $s->payment_status }}</span>@endif
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <span class="badge {{ $s->is_archived ? 'danger' : 'ok' }}">{{ $s->is_archived ? 'arşiv' : 'aktif' }}</span>
                        <div class="muted" style="font-size:var(--tx-xs);margin-top:4px;">{{ $s->updated_at?->format('d.m.Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="srd-list-item muted">Atama kaydı yok.</div>
            @endforelse
        </div>
        @if($recentStudents->count() > 2)
        <button class="srd-acc-btn" onclick="srdAcc(this)">▼ Daha fazla göster ({{ $recentStudents->count() - 2 }})</button>
        @endif
    </article>

    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Son Süreç Çıktıları</h3>
            <a class="btn" href="/senior/process-tracking">Süreçe Git</a>
        </div>
        <div class="srd-list srd-acc-list" id="acc-outcomes">
            @forelse($recentOutcomes as $o)
                <div class="srd-list-item">
                    <div>
                        <strong>{{ $guestMap[$o->student_id] ?? $o->student_id }}</strong>
                        <div style="margin-top:4px;">
                            <span class="badge info">{{ $o->process_step }}</span>
                            <span class="badge">{{ $o->outcome_type }}</span>
                        </div>
                        @if($o->details_tr)
                            <div class="muted" style="margin-top:4px;font-size:var(--tx-xs);">{{ \Illuminate\Support\Str::limit((string) $o->details_tr, 60) }}</div>
                        @endif
                    </div>
                    <div class="muted" style="font-size:var(--tx-xs);flex-shrink:0;">{{ $o->created_at?->format('d.m.Y H:i') }}</div>
                </div>
            @empty
                <div class="srd-list-item muted">Outcome kaydı yok.</div>
            @endforelse
        </div>
        @if($recentOutcomes->count() > 2)
        <button class="srd-acc-btn" onclick="srdAcc(this)">▼ Daha fazla göster ({{ $recentOutcomes->count() - 2 }})</button>
        @endif
    </article>
</div>

{{-- ── Notlar + Bildirimler + Tasklar ── --}}
<div class="grid3" style="margin-bottom:14px;">
    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Son Notlar</h3>
            <a class="btn" href="/senior/notes">Notlar</a>
        </div>
        <div class="srd-list srd-acc-list" id="acc-notes">
            @forelse($recentNotes as $n)
                <div class="srd-list-item">
                    <div>
                        <strong>{{ $guestMap[$n->student_id] ?? $n->student_id }}</strong>
                        <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
                            <span class="badge">{{ $n->category }}</span>
                            @if($n->is_pinned)<span class="badge ok">pinned</span>@endif
                        </div>
                    </div>
                    <div class="muted" style="font-size:var(--tx-xs);flex-shrink:0;">{{ $n->created_at?->format('d.m.Y') }}</div>
                </div>
            @empty
                <div class="srd-list-item muted">Not kaydı yok.</div>
            @endforelse
        </div>
        @if($recentNotes->count() > 2)
        <button class="srd-acc-btn" onclick="srdAcc(this)">▼ Daha fazla göster ({{ $recentNotes->count() - 2 }})</button>
        @endif
    </article>

    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Son Bildirimler</h3>
            <a class="btn" href="/im">Mesajlar</a>
        </div>
        <div class="srd-list srd-acc-list" id="acc-notifications">
            @forelse($recentNotifications as $n)
                @php
                    $nCls = match((string) $n->status) { 'sent' => 'ok', 'failed' => 'danger', default => 'pending' };
                    $nDate = $n->sent_at ?? $n->queued_at ?? $n->failed_at ?? null;
                @endphp
                <div class="srd-list-item">
                    <div>
                        <strong>{{ $guestMap[$n->student_id] ?? $n->student_id }}</strong>
                        <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
                            <span class="badge info">{{ strtoupper((string) $n->channel) }}</span>
                            <span class="badge {{ $nCls }}">{{ $n->status }}</span>
                        </div>
                    </div>
                    <div class="muted" style="font-size:var(--tx-xs);flex-shrink:0;">{{ $nDate ? \Carbon\Carbon::parse($nDate)->format('d.m.Y') : '-' }}</div>
                </div>
            @empty
                <div class="srd-list-item muted">Bildirim kaydı yok.</div>
            @endforelse
        </div>
        @if($recentNotifications->count() > 2)
        <button class="srd-acc-btn" onclick="srdAcc(this)">▼ Daha fazla göster ({{ $recentNotifications->count() - 2 }})</button>
        @endif
    </article>

    <article class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Son Görevler</h3>
            <a class="btn" href="/tasks">Görev Tablosu</a>
        </div>
        <div class="srd-list srd-acc-list" id="acc-tasks">
            @forelse($recentTasks as $t)
                @php
                    $tCls = match((string) $t->status) { 'done','completed' => 'ok', 'in_progress' => 'info', 'blocked' => 'danger', default => 'pending' };
                @endphp
                <div class="srd-list-item">
                    <div>
                        <strong>{{ \Illuminate\Support\Str::limit((string) $t->title, 40) }}</strong>
                        <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
                            <span class="badge {{ $tCls }}">{{ $t->status }}</span>
                            @if($t->priority === 'urgent' || $t->priority === 'high')
                                <span class="badge danger">{{ $t->priority }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="muted" style="font-size:var(--tx-xs);flex-shrink:0;">{{ $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d.m.Y') : '-' }}</div>
                </div>
            @empty
                <div class="srd-list-item muted">Görev kaydı yok.</div>
            @endforelse
        </div>
        @if($recentTasks->count() > 2)
        <button class="srd-acc-btn" onclick="srdAcc(this)">▼ Daha fazla göster ({{ $recentTasks->count() - 2 }})</button>
        @endif
    </article>
</div>

<script>
function srdAcc(btn) {
    var list = btn.previousElementSibling;
    var open = list.classList.toggle('srd-open');
    btn.textContent = open ? '▲ Gizle' : '▼ Daha fazla göster (' + btn.textContent.replace(/\D/g,'') + ')';
}
</script>

{{-- Bekleyen Sözleşme Talepleri --}}
@if(($pendingContracts ?? collect())->isNotEmpty())
<div style="background:var(--u-card,#fff);border:1.5px solid #fdba74;border-radius:14px;padding:18px 20px;margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:#111827;">
            Bekleyen Sözleşme Talepleri
            <span class="badge warn" style="font-size:var(--tx-sm);margin-left:8px;">{{ $pendingContracts->count() }}</span>
        </div>
        <a class="btn" href="/senior/contracts">Tümünü Gör →</a>
    </div>
    <div class="srd-list">
        @foreach($pendingContracts as $row)
        <div class="srd-list-item">
            <div>
                <strong>{{ $row->first_name }} {{ $row->last_name }}</strong>
                <div class="muted">{{ $row->email }}</div>
            </div>
            <div>
                <span class="badge {{ $row->contract_status === 'signed_uploaded' ? 'info' : 'warn' }}">
                    {{ $row->contract_status === 'signed_uploaded' ? 'İmzalı Yüklendi' : 'Talep Geldi' }}
                </span>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:3px;">{{ optional($row->contract_requested_at)->format('d.m.Y H:i') }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
</div>{{-- /dash-main-content --}}

@push('scripts')
<script>
(function(){
    var _loaded = false;
    window.switchSrdTab = function(tab, btn) {
        var main = document.getElementById('dash-main-content');
        var blt  = document.getElementById('panel-blt');
        var tM   = document.getElementById('tab-main');
        var tB   = document.getElementById('tab-blt');
        var acc  = getComputedStyle(document.documentElement).getPropertyValue('--c-accent') || '#7c3aed';
        if (tab === 'blt') {
            if (main) main.style.display = 'none';
            if (blt)  blt.style.display  = 'block';
            tM.style.borderBottomColor = 'transparent'; tM.style.color = 'var(--u-muted,#64748b)';
            tB.style.borderBottomColor = acc;           tB.style.color = acc;
            if (!_loaded) {
                blt.innerHTML = '<div style="padding:32px;text-align:center;color:var(--u-muted,#64748b);">Yükleniyor...</div>';
                fetch('/bulletins/partial', { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text()).then(function(html) { blt.innerHTML = html; _loaded = true; });
            }
        } else {
            if (main) main.style.display = 'block';
            if (blt)  blt.style.display  = 'none';
            tM.style.borderBottomColor = acc;           tM.style.color = acc;
            tB.style.borderBottomColor = 'transparent'; tB.style.color = 'var(--u-muted,#64748b)';
        }
    };
    if (window.location.hash === '#duyurular')
        document.addEventListener('DOMContentLoaded', function(){ switchSrdTab('blt'); });
})();
</script>
@endpush

{{-- ── Performans Analitikleri ── --}}
@if(!empty($seniorAnalytics))
@php $sa = $seniorAnalytics; @endphp
<div style="margin-top:20px;">
    <div style="font-size:14px;font-weight:700;color:var(--text,#111);margin-bottom:14px;">📊 Performans Analitikleri</div>

    {{-- Metrik kartları --}}
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px;">
        <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;text-align:center;border-top:3px solid #3b82f6;">
            <div style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:6px;">Randevu Tamamlama</div>
            <div style="font-size:22px;font-weight:800;color:#3b82f6;">%{{ $sa['appointmentStats']['rate'] }}</div>
            <div style="font-size:10px;color:var(--muted,#94a3b8);">{{ $sa['appointmentStats']['completed'] }}/{{ $sa['appointmentStats']['total'] }} tamamlandı</div>
        </div>
        <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;text-align:center;border-top:3px solid #f59e0b;">
            <div style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:6px;">No-Show / İptal</div>
            <div style="font-size:22px;font-weight:800;color:#f59e0b;">{{ $sa['appointmentStats']['noshow'] }}</div>
            <div style="font-size:10px;color:var(--muted,#94a3b8);">toplam randevu kayıp</div>
        </div>
        <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;text-align:center;border-top:3px solid #16a34a;">
            <div style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:6px;">Ticket Çözüm Süresi</div>
            <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ $sa['ticketResolution'] !== null ? $sa['ticketResolution'] . ' sa' : '-' }}</div>
            <div style="font-size:10px;color:var(--muted,#94a3b8);">ortalama çözüm</div>
        </div>
        <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;text-align:center;border-top:3px solid #8b5cf6;">
            <div style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:6px;">NPS Skoru</div>
            <div style="font-size:22px;font-weight:800;color:#8b5cf6;">{{ $sa['npsAvg'] !== null ? $sa['npsAvg'] : '-' }}</div>
            <div style="font-size:10px;color:var(--muted,#94a3b8);">öğrenci memnuniyeti</div>
        </div>
        <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:14px;text-align:center;border-top:3px solid #ec4899;">
            <div style="font-size:10px;font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;margin-bottom:6px;">Belge Onay Oranı</div>
            <div style="font-size:22px;font-weight:800;color:#ec4899;">%{{ $sa['docApprovalRate'] }}</div>
            <div style="font-size:10px;color:var(--muted,#94a3b8);">onaylanan / toplam</div>
        </div>
    </div>

    {{-- Aylık süreç sonuçları trend --}}
    @if(!empty($sa['monthlyOutcomes']))
    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:16px 18px;">
        <div style="font-size:12px;font-weight:700;color:var(--muted,#64748b);margin-bottom:10px;">Aylık Süreç Sonuçları (son 6 ay)</div>
        @php $maxMo = max(1, max(array_column($sa['monthlyOutcomes'], 'count'))); @endphp
        <div style="display:flex;align-items:flex-end;gap:8px;height:70px;">
            @foreach($sa['monthlyOutcomes'] as $mo)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;">
                    <span style="font-size:11px;font-weight:700;color:var(--text,#111);">{{ $mo['count'] }}</span>
                    <div style="width:100%;background:#8b5cf6;border-radius:3px 3px 0 0;min-height:2px;height:{{ round($mo['count'] / $maxMo * 50) }}px;"></div>
                    <span style="font-size:10px;color:var(--muted,#94a3b8);">{{ $mo['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

@endsection
