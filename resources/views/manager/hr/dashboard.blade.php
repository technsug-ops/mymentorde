@extends('manager.layouts.app')

@section('title', 'HR Özet')
@section('page_title', 'HR Özet')

@push('head')
<style>
.hr-quick-link { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:16px 10px;background:var(--u-card);border:1.5px solid var(--u-line);border-radius:12px;text-decoration:none;transition:all .15s;text-align:center; }
.hr-quick-link:hover { border-color:#1e40af;background:#eff6ff;transform:translateY(-1px); }
.hr-quick-link .ql-icon { font-size:22px;line-height:1; }
.hr-quick-link .ql-label { font-size:11px;font-weight:700;color:var(--u-text); }
.dept-bar-row { display:flex;align-items:center;gap:8px;margin-bottom:8px; }
.dept-bar-track { flex:1;height:8px;background:var(--u-line);border-radius:999px;overflow:hidden; }
.dept-bar-fill { height:100%;border-radius:999px;transition:width .4s; }
.att-chip { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700; }
.hire-chip { display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--u-line); }
.hire-chip:last-child { border-bottom:none; }
.ann-chip { display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 12px;border-bottom:1px solid var(--u-line); }
.ann-chip:last-child { border-bottom:none; }
</style>
@endpush

@section('content')

{{-- ─── SATIR 1: Ana KPI'lar ─── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr) repeat(3,1fr);gap:8px;margin-bottom:12px;">

    {{-- Toplam Çalışan --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #1e40af;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Toplam Çalışan</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $counts['total'] }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">
            <span style="color:#16a34a;font-weight:700;">{{ $counts['active'] }} aktif</span>
            @if($counts['passive'] > 0) · <span style="color:#dc2626;">{{ $counts['passive'] }} pasif</span>@endif
        </div>
    </div>

    {{-- Rol Dağılımı --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #6366f1;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;">Rol Dağılımı</div>
        <div style="font-size:12px;font-weight:700;color:var(--u-text);line-height:2;">
            <span style="color:#1e40af;">{{ $counts['staff'] }}</span> Staff &nbsp;·&nbsp;
            <span style="color:#7c3aed;">{{ $counts['senior'] }}</span> Eğitim Danışmanı &nbsp;·&nbsp;
            <span style="color:#0891b2;">{{ $counts['manager'] }}</span> Manager
        </div>
    </div>

    {{-- Bekleyen İzin --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $pendingLeaves->count() > 0 ? '#f59e0b' : 'var(--u-line)' }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bekleyen İzin</div>
        <div style="font-size:26px;font-weight:800;color:{{ $pendingLeaves->count() > 0 ? '#d97706' : 'var(--u-text)' }};line-height:1;">{{ $pendingLeaves->count() }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">onay bekliyor</div>
    </div>

    {{-- Bugün İzinli --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #16a34a;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bugün İzinli</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $onLeaveToday->count() }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">çalışan</div>
    </div>

    {{-- Bu Ay İzin --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid #0891b2;border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bu Ay İzin</div>
        <div style="font-size:26px;font-weight:800;color:var(--u-text);line-height:1;">{{ $monthLeaveDays }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">gün · {{ $monthLeaveCount }} talep</div>
    </div>

    {{-- Bordro Eksik --}}
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $noSalaryCount > 0 ? '#dc2626' : '#16a34a' }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Bordro Profili Eksik</div>
        <div style="font-size:26px;font-weight:800;color:{{ $noSalaryCount > 0 ? '#dc2626' : '#16a34a' }};line-height:1;">{{ $noSalaryCount }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">
            @if($noSalaryCount > 0)
                <a href="/manager/hr/salary" style="color:#dc2626;font-weight:700;text-decoration:none;">Profil Ekle →</a>
            @else
                tüm staff tanımlı
            @endif
        </div>
    </div>

</div>

{{-- ─── SATIR 2: Hızlı Eylemler ─── --}}
<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-bottom:12px;">
    <a href="/manager/staff"              class="hr-quick-link"><span class="ql-icon">👥</span><span class="ql-label">Çalışanlar</span></a>
    <a href="/manager/hr/leaves"          class="hr-quick-link"><span class="ql-icon">🌴</span><span class="ql-label">İzinler</span></a>
    <a href="/manager/hr/certifications"  class="hr-quick-link"><span class="ql-icon">🎓</span><span class="ql-label">Sertifikalar</span></a>
    <a href="/manager/hr/attendance"      class="hr-quick-link"><span class="ql-icon">⏰</span><span class="ql-label">Devam Raporu</span></a>
    <a href="/manager/staff/performance"  class="hr-quick-link"><span class="ql-icon">📊</span><span class="ql-label">Performans & KPI</span></a>
    <a href="/manager/hr/recruitment"     class="hr-quick-link"><span class="ql-icon">🎯</span><span class="ql-label">İşe Alım</span></a>
    <a href="/manager/hr/salary"          class="hr-quick-link"><span class="ql-icon">💳</span><span class="ql-label">Bordro</span></a>
</div>

{{-- ─── SATIR 3: Departman + Devam ─── --}}
@php $maxDept = max(1, max(array_values($deptDist))); @endphp
<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Departman Dağılımı --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">🏢 Departman Dağılımı</div>
        <div style="padding:14px 16px;">
        @foreach($deptDist as $dept => $cnt)
        @php
            $colors = ['Sistem'=>'#1e40af','Operasyon'=>'#0891b2','Finans'=>'#16a34a','Pazarlama'=>'#7c3aed','Satış'=>'#d97706','Eğitim Danışmanı'=>'#6366f1','Manager'=>'#0891b2','Diğer'=>'#94a3b8'];
            $color  = $colors[$dept] ?? '#94a3b8';
            $pct    = round($cnt / $maxDept * 100);
        @endphp
        <div class="dept-bar-row">
            <div style="width:80px;font-size:11px;font-weight:700;color:var(--u-text);text-align:right;">{{ $dept }}</div>
            <div class="dept-bar-track">
                <div class="dept-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
            </div>
            <div style="width:28px;font-size:12px;font-weight:800;color:{{ $color }};">{{ $cnt }}</div>
        </div>
        @endforeach
        </div>
    </section>

    {{-- Bu Hafta Devam --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:700;font-size:var(--tx-sm);">⏰ Bu Hafta Devam</div>
            <a href="/manager/hr/attendance" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Rapor →</a>
        </div>
        <div style="padding:20px 16px;">
            @if($attStats['total'] === 0)
            <div style="text-align:center;color:var(--u-muted);font-size:13px;padding:16px 0;">Bu hafta devam kaydı yok.</div>
            @else
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <div class="att-chip" style="background:#dcfce7;color:#15803d;">✓ {{ $attStats['present'] }} Zamanında</div>
                <div class="att-chip" style="background:#fef9c3;color:#92400e;">⏱ {{ $attStats['late'] }} Geç</div>
                <div class="att-chip" style="background:#fee2e2;color:#991b1b;">✗ {{ $attStats['absent'] }} Devamsız</div>
            </div>
            @php $total = max(1, $attStats['total']); @endphp
            <div style="margin-bottom:8px;">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--u-muted);margin-bottom:3px;">
                    <span>Zamanında oranı</span>
                    <span>%{{ round($attStats['present'] / $total * 100) }}</span>
                </div>
                <div style="height:6px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:{{ round($attStats['present']/$total*100) }}%;background:#16a34a;border-radius:999px;"></div>
                </div>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--u-muted);margin-bottom:3px;">
                    <span>Geç kalma oranı</span>
                    <span>%{{ round($attStats['late'] / $total * 100) }}</span>
                </div>
                <div style="height:6px;background:var(--u-line);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:{{ round($attStats['late']/$total*100) }}%;background:#f59e0b;border-radius:999px;"></div>
                </div>
            </div>
            @endif
        </div>
    </section>

</div>

{{-- ─── SATIR 4: Bekleyen İzinler + Sertifikalar ─── --}}
<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Bekleyen İzin Talepleri --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--u-line);">
            <div style="font-weight:700;font-size:var(--tx-sm);">🌴 Bekleyen İzin Talepleri</div>
            <a href="/manager/hr/leaves?status=pending" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tümü →</a>
        </div>
        @if($pendingLeaves->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Bekleyen talep yok.</div>
        @else
        @foreach($pendingLeaves as $leave)
        @php $typeLabels = \App\Models\Hr\HrLeaveRequest::$typeLabels; @endphp
        <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <div>
                <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">{{ $leave->user?->name ?: '—' }}</div>
                <div style="font-size:10px;color:var(--u-muted);">
                    {{ $typeLabels[$leave->leave_type] ?? $leave->leave_type }} ·
                    {{ $leave->start_date->format('d.m.Y') }} – {{ $leave->end_date->format('d.m.Y') }} ·
                    {{ $leave->days_count }} gün
                </div>
            </div>
            <div style="display:flex;gap:6px;">
                <form method="POST" action="/manager/hr/leaves/{{ $leave->id }}/approve">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn ok" style="font-size:10px;padding:4px 10px;">✓ Onayla</button>
                </form>
                <a href="/manager/hr/leaves" class="btn alt" style="font-size:10px;padding:4px 10px;">Detay</a>
            </div>
        </div>
        @endforeach
        @endif
    </section>

    {{-- Yakında Sona Erecek Sertifikalar --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--u-line);">
            <div style="font-weight:700;font-size:var(--tx-sm);">🎓 30 Gün İçinde Sona Erecek Sertifikalar</div>
            <a href="/manager/hr/certifications" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Tümü →</a>
        </div>
        @if($expiringSoon->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Yakında sona erecek sertifika yok.</div>
        @else
        @foreach($expiringSoon as $cert)
        <div style="padding:10px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text);">{{ $cert->cert_name }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $cert->user?->name }} · {{ $cert->issuer ?: '—' }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px;font-weight:700;color:#d97706;">{{ $cert->expiry_date->format('d.m.Y') }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $cert->expiry_date->diffForHumans() }}</div>
            </div>
        </div>
        @endforeach
        @endif
    </section>

</div>

{{-- ─── SATIR 5: Son İşe Alımlar + Yıl Dönümleri ─── --}}
<div class="grid2" style="gap:12px;margin-bottom:12px;">

    {{-- Son 90 Gün İşe Başlayanlar --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--u-line);">
            <div style="font-weight:700;font-size:var(--tx-sm);">🆕 Son 90 Gün İşe Başlayanlar</div>
            <a href="/manager/hr/recruitment/onboarding" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">Onboarding →</a>
        </div>
        @if($recentHires->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Son 90 günde yeni işe alım yok.</div>
        @else
        @foreach($recentHires as $hire)
        @php
            $roleLabel = match(true) {
                str_contains($hire->user?->role ?? '', 'system')     => 'Sistem',
                str_contains($hire->user?->role ?? '', 'operations') => 'Operasyon',
                str_contains($hire->user?->role ?? '', 'finance')    => 'Finans',
                str_contains($hire->user?->role ?? '', 'marketing')  => 'Pazarlama',
                str_contains($hire->user?->role ?? '', 'sales')      => 'Satış',
                ($hire->user?->role ?? '') === 'senior'              => 'Eğitim Danışmanı',
                default                                              => $hire->user?->role ?? '—',
            };
            $daysAgo = \Carbon\Carbon::parse($hire->hire_date)->diffInDays(now());
        @endphp
        <div class="hire-chip">
            <div style="width:34px;height:34px;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;flex-shrink:0;">
                {{ strtoupper(substr($hire->user?->name ?? '?', 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:12px;font-weight:700;color:var(--u-text);">{{ $hire->user?->name ?: '—' }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $hire->position_title ?: $roleLabel }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;font-weight:700;color:#1e40af;">{{ $hire->hire_date->format('d.m.Y') }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ $daysAgo }} gün önce</div>
            </div>
        </div>
        @endforeach
        @endif
    </section>

    {{-- Yaklaşan İşe Başlama Yıl Dönümleri --}}
    <section class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);">
            <div style="font-weight:700;font-size:var(--tx-sm);">🎂 30 Gün İçinde Yıl Dönümleri</div>
        </div>
        @if($upcomingAnniversaries->isEmpty())
        <div style="padding:30px;text-align:center;color:var(--u-muted);font-size:13px;">Yaklaşan yıl dönümü yok.</div>
        @else
        @foreach($upcomingAnniversaries as $ann)
        <div class="ann-chip">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;background:#7c3aed;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;flex-shrink:0;">
                    {{ strtoupper(substr($ann->profile->user?->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <div style="font-size:12px;font-weight:700;color:var(--u-text);">{{ $ann->profile->user?->name ?: '—' }}</div>
                    <div style="font-size:10px;color:var(--u-muted);">{{ $ann->years }}. yıl dönümü</div>
                </div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:11px;font-weight:700;color:#7c3aed;">{{ $ann->ann_date->format('d.m.Y') }}</div>
                <div style="font-size:10px;color:var(--u-muted);">
                    @if($ann->days_left === 0) bugün
                    @elseif($ann->days_left === 1) yarın
                    @else {{ $ann->days_left }} gün kaldı
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @endif
    </section>

</div>

{{-- ─── Bugün İzinliler ─── --}}
@if($onLeaveToday->isNotEmpty())
<section class="panel" style="padding:12px 16px;margin-bottom:12px;">
    <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:10px;">📅 Bugün İzinli Çalışanlar</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach($onLeaveToday as $l)
        <a href="/manager/hr/persons/{{ $l->user_id }}"
           style="display:inline-flex;align-items:center;gap:6px;background:var(--u-bg);border:1px solid var(--u-line);border-radius:8px;padding:5px 12px;text-decoration:none;">
            <span style="font-size:12px;">🌴</span>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--u-text);">{{ $l->user?->name }}</div>
                <div style="font-size:10px;color:var(--u-muted);">{{ \App\Models\Hr\HrLeaveRequest::$typeLabels[$l->leave_type] ?? $l->leave_type }}</div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- ─── Bordro Eksik Uyarı Bandı ─── --}}
@if($noSalaryCount > 0)
<div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:13px;font-weight:700;color:#991b1b;">
        ⚠ <strong>{{ $noSalaryCount }}</strong> staff çalışanının aktif bordro profili tanımlanmamış.
    </div>
    <a href="/manager/hr/salary" style="font-size:11px;font-weight:700;color:#dc2626;text-decoration:none;border:1.5px solid #fecaca;padding:5px 14px;border-radius:7px;background:#fff;">Profil Ekle →</a>
</div>
@endif

@endsection
