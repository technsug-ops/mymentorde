@extends('senior.layouts.app')
@section('title','Profil')
@section('page_title','Profil')

@push('head')
<style>
/* ── Eğitim Danışmanı Profile Hero ── */
.sprf-hero {
    background: linear-gradient(to right, #3b1a6e 0%, #6d28d9 60%, #7c3aed 100%);
    border-radius: 16px;
    padding: 32px 28px 28px;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
}
.sprf-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.sprf-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 35%;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.sprf-avatar {
    width: 88px; height: 88px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 3px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 28px;
    flex-shrink: 0; z-index: 1;
}
.sprf-hero-info { flex: 1; min-width: 200px; z-index: 1; }
.sprf-hero-name { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.sprf-hero-email { font-size: 13px; color: rgba(255,255,255,.7); margin-bottom: 10px; }
.sprf-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.sprf-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 3px 12px;
    font-size: 12px; color: #fff; font-weight: 600;
}
.sprf-hero-badge.active { background: rgba(52,211,153,.25); border-color: rgba(52,211,153,.5); }
.sprf-hero-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-left: auto; z-index: 1; }
.sprf-hstat { text-align: center; min-width: 70px; }
.sprf-hstat-val { font-size: 22px; font-weight: 700; color: #fff; line-height: 1; margin-bottom: 4px; }
.sprf-hstat-label { font-size: 11px; color: rgba(255,255,255,.7); font-weight: 500; }
.sprf-hstat-sep { width: 1px; background: rgba(255,255,255,.2); align-self: stretch; }

/* ── Form Sections ── */
.sprf-section {
    background: var(--u-card);
    border: 1px solid var(--u-line);
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 16px;
}
.sprf-section-title {
    font-size: 13px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px; color: #6b7280;
    margin: 0 0 16px; padding-bottom: 10px;
    border-bottom: 1px solid var(--u-line);
}
.sprf-field-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media (max-width: 600px) { .sprf-field-grid { grid-template-columns: 1fr; } }
.sprf-field label {
    display: block; font-size: 12px; font-weight: 600;
    color: #374151; margin-bottom: 6px;
}
.sprf-field input,
.sprf-field textarea {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid #d1d5db; border-radius: 8px;
    font-size: 14px; color: #111827; background: #fff;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box; font-family: inherit;
}
.sprf-field input:focus, .sprf-field textarea:focus {
    outline: none; border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}
.sprf-field textarea { min-height: 100px; resize: vertical; }
.sprf-field.span2 { grid-column: span 2; }
@media (max-width: 600px) { .sprf-field.span2 { grid-column: span 1; } }

/* ── Schedule ── */
.senior-schedule-wrap { display: grid; gap: 8px; }
.senior-schedule-head {
    display: grid;
    grid-template-columns: 150px 84px 1fr 1fr 1.4fr;
    gap: 8px; color: #5a7597;
    font-size: 12px; font-weight: 600; padding: 0 6px;
}
.senior-schedule-row {
    display: grid;
    grid-template-columns: 150px 84px 1fr 1fr 1.4fr;
    gap: 8px; align-items: center;
    background: #faf5ff;
    border: 1px solid rgba(124,58,237,.10);
    border-radius: 12px; padding: 10px;
}
.senior-schedule-day { font-weight: 700; color: #7c3aed; }
.senior-schedule-cell input[type="time"],
.senior-schedule-cell input[type="text"] { width: 100%; }
.senior-schedule-cell.checkbox { display: flex; justify-content: center; }
.senior-schedule-cell.checkbox input[type="checkbox"] { width: 16px; height: 16px; }

/* Schedule preview */
.sprf-schedule-bar {
    height: 20px; background: #f5f3ff; border-radius: 5px;
    position: relative; overflow: hidden;
}
.sprf-schedule-fill {
    position: absolute; height: 100%;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    border-radius: 5px; opacity: .8;
}

@media (max-width: 1100px) {
    .senior-schedule-head, .senior-schedule-row { grid-template-columns: 1fr 84px 1fr 1fr; }
    .senior-schedule-head > :last-child, .senior-schedule-row > :last-child { grid-column: 1 / -1; }
}
@media (max-width: 760px) {
    .senior-schedule-head { display: none; }
    .senior-schedule-row { grid-template-columns: 1fr; gap: 6px; }
    .senior-schedule-cell.checkbox { justify-content: flex-start; }
}
</style>
@endpush

@section('content')
@php
    $user      = auth()->user();
    $initials  = strtoupper(substr($user->name ?? 'SR', 0, 2));
    $isActive  = (bool)($user->is_active ?? true);
    $activeDays = collect($weeklySchedule ?? [])->filter(fn($r) => $r['enabled'] ?? false)->count();
    $dayLabels = [
        'monday' => 'Pazartesi', 'tuesday' => 'Salı', 'wednesday' => 'Çarşamba',
        'thursday' => 'Perşembe', 'friday' => 'Cuma', 'saturday' => 'Cumartesi', 'sunday' => 'Pazar',
    ];
    $barStart = 7; $barEnd = 22; $barRange = ($barEnd - $barStart) * 60;
    $toPct = function(string $t) use ($barStart, $barRange): int {
        $parts = explode(':', $t);
        $mins = (int)($parts[0] ?? 0) * 60 + (int)($parts[1] ?? 0);
        return (int) round(max(0, min(100, ($mins - $barStart * 60) / $barRange * 100)));
    };
@endphp

{{-- ── Hero ── --}}
<div class="sprf-hero">
    <div class="sprf-avatar">{{ $initials }}</div>

    <div class="sprf-hero-info">
        <div class="sprf-hero-name">{{ $user->name ?? '-' }}</div>
        <div class="sprf-hero-email">{{ $user->email ?? '-' }}</div>
        <div class="sprf-hero-badges">
            <span class="sprf-hero-badge">{{ $user->senior_code ?? 'SR' }}</span>
            <span class="sprf-hero-badge {{ $isActive ? 'active' : '' }}">
                {{ $isActive ? 'Aktif' : 'Pasif' }}
            </span>
            @if(data_get($portalPrefs ?? [], 'profile.title'))
                <span class="sprf-hero-badge">{{ data_get($portalPrefs, 'profile.title') }}</span>
            @endif
        </div>
    </div>

    <div class="sprf-hero-stats">
        <div class="sprf-hstat">
            <div class="sprf-hstat-val">{{ $user->max_capacity ?? '-' }}</div>
            <div class="sprf-hstat-label">Kapasite</div>
        </div>
        <div class="sprf-hstat-sep"></div>
        <div class="sprf-hstat">
            <div class="sprf-hstat-val">{{ $activeDays }}</div>
            <div class="sprf-hstat-label">Aktif Gün</div>
        </div>
        <div class="sprf-hstat-sep"></div>
        <div class="sprf-hstat">
            <div class="sprf-hstat-val" style="font-size:var(--tx-sm);text-transform:capitalize;">{{ $user->role ?? 'senior' }}</div>
            <div class="sprf-hstat-label">Rol</div>
        </div>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;color:#166534;font-weight:600;margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;color:#991b1b;margin-bottom:16px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('senior.profile.update') }}">
    @csrf

    {{-- Temel Bilgiler --}}
    <div class="sprf-section">
        <div class="sprf-section-title">Kişisel Bilgiler</div>
        <div class="sprf-field-grid">
            <div class="sprf-field">
                <label>Ad Soyad</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}">
            </div>
            <div class="sprf-field">
                <label>E-posta</label>
                <input type="email" value="{{ $user->email }}" disabled style="background:#f9fafb;color:#6b7280;">
            </div>
            <div class="sprf-field">
                <label>Telefon</label>
                <input type="text" name="phone"
                       value="{{ old('phone', data_get($portalPrefs ?? [], 'profile.phone', '')) }}"
                       placeholder="+90">
            </div>
            <div class="sprf-field">
                <label>Unvan / Rol Tanıtımı</label>
                <input type="text" name="title"
                       value="{{ old('title', data_get($portalPrefs ?? [], 'profile.title', '')) }}"
                       placeholder="Uni-Assist ve Vize Danışmanı">
            </div>
            <div class="sprf-field">
                <label>Uzmanlık Alanları</label>
                <input type="text" name="expertise"
                       value="{{ old('expertise', data_get($portalPrefs ?? [], 'profile.expertise', '')) }}"
                       placeholder="Uni-Assist, Vize, Dil Okulu (virgülle)">
            </div>
            <div class="sprf-field">
                <label>Diller</label>
                <input type="text" name="languages"
                       value="{{ old('languages', data_get($portalPrefs ?? [], 'profile.languages', '')) }}"
                       placeholder="TR, DE, EN">
            </div>
            <div class="sprf-field span2">
                <label>Profil Açıklaması</label>
                <textarea name="bio" rows="4" placeholder="Öğrenciler seni tanısın: hangi süreçlerde destek veriyorsun, çalışma tarzın nedir...">{{ old('bio', data_get($portalPrefs ?? [], 'profile.bio', '')) }}</textarea>
            </div>
            <div class="sprf-field span2">
                <label>Randevu Notu (Öğrenciye Gösterilecek)</label>
                <textarea name="appointment_note" rows="3" placeholder="Randevu almadan önce belge hazırlığı, saat uyumu, süre beklentisi vb.">{{ old('appointment_note', data_get($portalPrefs ?? [], 'profile.appointment_note', '')) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Çalışma Saatleri --}}
    <div class="sprf-section">
        <div class="sprf-section-title">Çalışma Saatleri</div>
        <div class="senior-schedule-wrap">
            <div class="senior-schedule-head">
                <div>Gün</div><div>Aktif</div><div>Başlangıç</div><div>Bitiş</div><div>Not</div>
            </div>
            @foreach(($weeklySchedule ?? []) as $dayKey => $row)
                <div class="senior-schedule-row">
                    <div class="senior-schedule-day">{{ $dayLabels[$dayKey] ?? $dayKey }}</div>
                    <div class="senior-schedule-cell checkbox">
                        <input type="checkbox"
                               name="weekly_schedule[{{ $dayKey }}][enabled]" value="1"
                               @checked((bool) old("weekly_schedule.$dayKey.enabled", $row['enabled'] ?? false))>
                    </div>
                    <div class="senior-schedule-cell">
                        <input type="time" name="weekly_schedule[{{ $dayKey }}][start]"
                               value="{{ old("weekly_schedule.$dayKey.start", $row['start'] ?? '09:00') }}">
                    </div>
                    <div class="senior-schedule-cell">
                        <input type="time" name="weekly_schedule[{{ $dayKey }}][end]"
                               value="{{ old("weekly_schedule.$dayKey.end", $row['end'] ?? '18:00') }}">
                    </div>
                    <div class="senior-schedule-cell">
                        <input type="text" name="weekly_schedule[{{ $dayKey }}][note]"
                               value="{{ old("weekly_schedule.$dayKey.note", $row['note'] ?? '') }}"
                               placeholder="ör. sadece online">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="btn primary" type="submit">Profili Kaydet</button>
    </div>
</form>

{{-- ── Müsaitlik Önizlemesi ── --}}
<div class="sprf-section">
    <div class="sprf-section-title">Haftalık Müsaitlik Önizlemesi</div>
    <div style="display:flex;margin-bottom:8px;padding-left:90px;padding-right:140px;">
        @foreach(range($barStart, $barEnd, 2) as $bh)
            <div style="flex:1;font-size:var(--tx-xs);color:var(--u-muted);text-align:left;">{{ sprintf('%02d', $bh) }}</div>
        @endforeach
    </div>
    @foreach(($weeklySchedule ?? []) as $dayKey => $row)
        @php
            $enabled  = (bool)($row['enabled'] ?? false);
            $start    = $row['start'] ?? '09:00';
            $end      = $row['end']   ?? '18:00';
            $leftPct  = $toPct($start);
            $widthPct = max(0, $toPct($end) - $leftPct);
            $note     = trim((string)($row['note'] ?? ''));
        @endphp
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <div style="width:82px;flex-shrink:0;font-size:var(--tx-sm);font-weight:600;color:#7c3aed;">
                {{ $dayLabels[$dayKey] ?? $dayKey }}
            </div>
            <div class="sprf-schedule-bar" style="flex:1;">
                @if($enabled && $widthPct > 0)
                    <div class="sprf-schedule-fill" style="left:{{ $leftPct }}%;width:{{ $widthPct }}%;"></div>
                @endif
            </div>
            <div style="width:128px;flex-shrink:0;font-size:var(--tx-xs);color:#4a6a8f;text-align:right;">
                @if($enabled)
                    {{ $start }} – {{ $end }}
                    @if($note)<br><span class="muted" style="font-size:var(--tx-xs);">{{ $note }}</span>@endif
                @else
                    <span class="muted">Kapalı</span>
                @endif
            </div>
        </div>
    @endforeach
    <div style="margin-top:10px;font-size:var(--tx-xs);color:var(--u-muted);background:#f7faff;border-radius:8px;padding:10px;">
        Haftada <strong style="color:var(--u-text);">{{ $activeDays }}</strong> gün müsait &nbsp;·&nbsp;
        Unvan: <strong style="color:var(--u-text);">{{ data_get($portalPrefs ?? [], 'profile.title', '-') ?: '-' }}</strong>
    </div>
</div>

{{-- ── İş Sözleşmem ── --}}
<div class="sprf-section" style="margin-top:8px;">
    <div class="sprf-section-title">İş Sözleşmelerim</div>

    @if($contracts->isEmpty())
        <div style="text-align:center;padding:24px;color:var(--u-muted);font-size:13px;">Henüz size gönderilmiş bir sözleşme yok.</div>
    @else
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($contracts as $c)
            <div style="display:flex;align-items:center;gap:14px;background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:12px 16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:600;color:var(--u-text);margin-bottom:3px;">{{ $c->title }}</div>
                    <div style="font-size:12px;color:var(--u-muted);">
                        {{ $c->contract_no }}
                        @if($c->issued_at) &middot; Gönderilme: {{ \Carbon\Carbon::parse($c->issued_at)->format('d.m.Y') }} @endif
                        @if($c->approved_at) &middot; Onaylanma: {{ \Carbon\Carbon::parse($c->approved_at)->format('d.m.Y') }} @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span>
                    @if($c->status === 'issued')
                        <span class="badge warn" style="font-size:11px;">⏳ İmza Bekliyor</span>
                    @endif
                    <a href="{{ route('my-contracts.show', $c) }}" class="btn" style="font-size:12px;padding:5px 12px;">Görüntüle</a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ── İzin Talebi ── --}}
@php
    $leaveTypeLabels = [
        'annual'   => 'Yıllık İzin',
        'sick'     => 'Hastalık İzni',
        'personal' => 'Kişisel İzin',
        'maternity'=> 'Doğum İzni',
        'unpaid'   => 'Ücretsiz İzin',
    ];
    $leaveStatusLabels = [
        'pending'   => ['label' => 'Bekliyor',   'color' => '#d97706'],
        'approved'  => ['label' => 'Onaylandı',  'color' => '#16a34a'],
        'rejected'  => ['label' => 'Reddedildi', 'color' => '#dc2626'],
        'cancelled' => ['label' => 'İptal',      'color' => '#6b7280'],
    ];
@endphp
<div class="sprf-section" style="margin-top:8px;">
    <div class="sprf-section-title">İzin Talepleri</div>

    {{-- Kota özeti --}}
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <div style="flex:1;min-width:120px;background:#f5f3ff;border:1px solid #ede9fe;border-radius:12px;padding:14px 18px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#7c3aed;">{{ $quota }}</div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;margin-top:2px;">Toplam Kota</div>
        </div>
        <div style="flex:1;min-width:120px;background:#fef9ec;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#d97706;">{{ $used }}</div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;margin-top:2px;">Kullanılan ({{ $leaveYear }})</div>
        </div>
        <div style="flex:1;min-width:120px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#16a34a;">{{ $remaining }}</div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;margin-top:2px;">Kalan Gün</div>
        </div>
    </div>

    {{-- Yeni talep formu --}}
    <div style="background:#faf5ff;border:1px solid #ede9fe;border-radius:12px;padding:18px 20px;margin-bottom:20px;">
        <div style="font-size:12px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">Yeni İzin Talebi</div>
        <form method="POST" action="{{ url('/hr/my/leaves') }}" enctype="multipart/form-data">
            @csrf
            <div class="sprf-field-grid">
                <div class="sprf-field">
                    <label>İzin Türü</label>
                    <select name="leave_type">
                        @foreach($leaveTypeLabels as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('leave_type') === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sprf-field" style="display:flex;gap:8px;">
                    <div style="flex:1;">
                        <label>Başlangıç</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div style="flex:1;">
                        <label>Bitiş</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="sprf-field span2">
                    <label>Açıklama (isteğe bağlı)</label>
                    <textarea name="reason" rows="2" placeholder="Ek bilgi veya açıklama...">{{ old('reason') }}</textarea>
                </div>
                <div class="sprf-field">
                    <label>Belge Ekle <span style="font-weight:400;color:#9ca3af;">(PDF, resim, Word — maks. 5MB)</span></label>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="width:100%;padding:7px 10px;border:1.5px dashed #c4b5fd;border-radius:8px;font-size:12px;background:#faf5ff;color:#7c3aed;cursor:pointer;">
                </div>
                <div class="sprf-field">
                    <label>Link Ekle <span style="font-weight:400;color:#9ca3af;">(opsiyonel)</span></label>
                    <input type="url" name="attachment_links[]" placeholder="https://" style="width:100%;padding:9px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;box-sizing:border-box;">
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:9px 22px;font-size:13px;font-weight:600;cursor:pointer;">
                    Talep Gönder
                </button>
            </div>
        </form>
    </div>

    {{-- İzin geçmişi --}}
    @if($leaves->isNotEmpty())
    <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Geçmiş Talepler</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($leaves as $leave)
        @php
            $st = $leaveStatusLabels[$leave->status] ?? ['label' => $leave->status, 'color' => '#6b7280'];
        @endphp
        <div style="display:flex;align-items:center;gap:12px;background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:10px 14px;flex-wrap:wrap;">
            <div style="flex:1;min-width:140px;">
                <div style="font-size:13px;font-weight:600;color:var(--u-text);">{{ $leaveTypeLabels[$leave->leave_type] ?? $leave->leave_type }}</div>
                <div style="font-size:12px;color:var(--u-muted);margin-top:2px;">
                    {{ \Carbon\Carbon::parse($leave->start_date)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($leave->end_date)->format('d.m.Y') }}
                    <span style="margin-left:6px;color:var(--u-text);font-weight:600;">{{ $leave->days_count }} gün</span>
                </div>
            </div>
            <div>
                <span style="background:{{ $st['color'] }}18;color:{{ $st['color'] }};border:1px solid {{ $st['color'] }}40;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;">
                    {{ $st['label'] }}
                </span>
            </div>
            @if($leave->status === 'pending')
            <form method="POST" action="{{ url('/hr/my/leaves/' . $leave->id) }}" onsubmit="return confirm('Talebi iptal et?')">
                @csrf @method('DELETE')
                <button type="submit" style="background:none;border:1px solid #e5e7eb;border-radius:7px;padding:4px 12px;font-size:12px;color:#6b7280;cursor:pointer;">İptal</button>
            </form>
            @endif
            @if($leave->rejection_note)
            <div style="width:100%;font-size:12px;color:#dc2626;background:#fef2f2;border-radius:7px;padding:6px 10px;margin-top:4px;">
                Red notu: {{ $leave->rejection_note }}
            </div>
            @endif
            @if($leave->attachments->isNotEmpty())
            <div style="width:100%;display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                @foreach($leave->attachments as $att)
                    @if($att->type === 'file')
                    <a href="{{ route('hr.my.leave-attachment.download', $att) }}"
                       style="display:inline-flex;align-items:center;gap:4px;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:6px;padding:3px 10px;font-size:11px;color:#7c3aed;text-decoration:none;font-weight:600;">
                        📎 {{ $att->original_name }}
                    </a>
                    @else
                    <a href="{{ $att->url }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:3px 10px;font-size:11px;color:#1d4ed8;text-decoration:none;font-weight:600;">
                        🔗 {{ parse_url($att->url, PHP_URL_HOST) ?: $att->url }}
                    </a>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div style="text-align:center;padding:24px;color:var(--u-muted);font-size:13px;">Henüz izin talebi bulunmuyor.</div>
    @endif
</div>

@endsection
