@extends('manager.layouts.app')

@section('title', 'Belge Talepleri')
@section('page_title', 'Belge Talepleri')

@push('head')
<style>
.pd-table { width:100%; border-collapse:collapse; font-size:12px; }
.pd-table thead tr { background:var(--bg,#f8fafc); }
.pd-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pd-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.pd-table tbody tr:hover { background:rgba(30,64,175,.03); }
.pd-table td { padding:8px 10px; vertical-align:middle; }
.pd-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.pd-ok    { background:rgba(22,163,74,.1); color:#15803d; }
.pd-warn  { background:rgba(217,119,6,.1); color:#b45309; }
.pd-bad   { background:rgba(220,38,38,.1); color:#b91c1c; }
.pd-muted { color:var(--muted,#64748b); }
.pd-empty { padding:28px 14px; text-align:center; color:var(--muted,#64748b); font-size:13px; line-height:1.6; }
.pd-note  { background:rgba(30,64,175,.05); border:1px solid rgba(30,64,175,.2); border-left:3px solid #1e40af; border-radius:8px; padding:10px 14px; font-size:12px; margin-bottom:12px; line-height:1.5; }
.pd-kpi   { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .pd-kpi { grid-template-columns:1fr; } }
.pd-kpi-box { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.pd-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.pd-kpi-val { font-size:22px; font-weight:800; line-height:1; }
</style>
@endpush

@section('content')

@php
    // Talebin durumu: yüklendi mi, süresi doldu mu, hâlâ bekliyor mu.
    $resolve = function ($token) {
        $uploaded = (int) ($token->used_count ?? 0) > 0 || !empty($token->document_id);

        if ($uploaded) {
            return ['Yüklendi', 'pd-ok'];
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return ['Süresi doldu', 'pd-bad'];
        }

        return ['Bekliyor', 'pd-warn'];
    };

    $states  = $rows->map(fn ($t) => $resolve($t)[0]);
    $waiting = $states->filter(fn ($s) => $s === 'Bekliyor')->count();
    $done    = $states->filter(fn ($s) => $s === 'Yüklendi')->count();
    $expired = $states->filter(fn ($s) => $s === 'Süresi doldu')->count();
@endphp

<div class="pd-note">
    Adaylarınızdan ve öğrencilerinizden istenen belgeler ile hangilerinin geldiği.
    Belge talebi oluşturmak için kişinin kendi sayfasındaki <strong>Belge Talebi</strong> bölümünü kullanın.
</div>

<div class="pd-kpi">
    <div class="pd-kpi-box" style="border-top-color:#d97706;">
        <div class="pd-kpi-label">Bekleyen</div>
        <div class="pd-kpi-val" style="color:#b45309;">{{ $waiting }}</div>
    </div>
    <div class="pd-kpi-box" style="border-top-color:#16a34a;">
        <div class="pd-kpi-label">Geldi</div>
        <div class="pd-kpi-val" style="color:#15803d;">{{ $done }}</div>
    </div>
    <div class="pd-kpi-box" style="border-top-color:{{ $expired > 0 ? '#dc2626' : 'var(--border,#e2e8f0)' }};">
        <div class="pd-kpi-label">Süresi Dolan</div>
        <div class="pd-kpi-val" style="{{ $expired > 0 ? 'color:#b91c1c;' : 'color:var(--muted,#64748b);' }}">{{ $expired }}</div>
    </div>
</div>

<section class="panel">
    <div style="overflow-x:auto;">
        <table class="pd-table">
            <thead>
                <tr>
                    <th>Kişi</th>
                    <th>İstenen Belge</th>
                    <th>Durum</th>
                    <th>Talep Tarihi</th>
                    <th>Son Geçerlilik</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $token)
                    @php
                        [$label, $tone] = $resolve($token);

                        $isGuest = $token->target_type === \App\Models\DocumentUploadToken::TARGET_GUEST
                                   || !empty($token->guest_application_id);

                        $personId = $isGuest
                            ? (int) ($token->target_id ?: $token->guest_application_id)
                            : (string) ($token->target_id ?: $token->target_student_id);

                        $person = $isGuest
                            ? ($guests[$personId] ?? null)
                            : ($students[$personId] ?? null);

                        $person = $person ?: ($token->target_display_name ?: '—');
                    @endphp
                    <tr>
                        <td>
                            @if($isGuest && isset($guests[$personId]))
                                <a href="/manager/guests/{{ $personId }}">{{ $person }}</a>
                            @elseif(!$isGuest && isset($students[$personId]))
                                <a href="/manager/students/{{ urlencode((string) $personId) }}">{{ $person }}</a>
                            @else
                                {{ $person }}
                            @endif
                        </td>
                        <td>{{ $token->category_name ?: $token->document_name_de ?: $token->category_code ?: '—' }}</td>
                        <td><span class="pd-badge {{ $tone }}">{{ $label }}</span></td>
                        <td class="pd-muted">{{ $token->created_at?->format('d.m.Y') ?? '—' }}</td>
                        <td class="pd-muted">{{ $token->expires_at?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="pd-empty">
                            Henüz belge talebi yok.<br>
                            Bir adayın sayfasını açıp belge talebi oluşturabilirsiniz.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
